<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		ocportal_release_build
 */

function init__make_release()
{
	require_code('files2');
}

function make_files_manifest()
{
	$_files=get_directory_contents(get_file_base());
	$files=array();
	foreach ($_files as $file)
	{
		$contents=file_get_contents(get_file_base().'/'.$file,FILE_BINARY);
		if (basename($file)=='version.php') $contents=preg_replace('/\d{10}/','',$contents);
		$files[$file]=array(sprintf('%u',crc32(preg_replace('#[\r\n\t ]#','',$contents))));
	}
	$myfile=fopen(get_file_base().'/data/files.dat','wb');
	fwrite($myfile,serialize($files));
	fclose($myfile);
	fix_permissions(get_file_base().'/data/files.dat','wb');
}

function make_installers($skip_file_grab=false)
{
	// Tracking
	global $FILE_ARRAY,$DIR_ARRAY,$TOTAL_DIRS,$TOTAL_FILES;
	$FILE_ARRAY=array();
	$DIR_ARRAY=array();
	$TOTAL_DIRS=0;
	$TOTAL_FILES=0;

	// Start output
	$out='';
	$out.='<p>An ocPortal build is being compiled and packed up into installation packages.</p>';

	// Build manifest
	if (!$skip_file_grab)
		make_files_manifest();

	require_code('version2');
	$version_dotted=get_version_dotted();
	$version_branch=get_version_branch();

	// Make requisite directories
	$builds_path=get_builds_path();
	if (!file_exists($builds_path.'/builds/build/'))
	{
		@mkdir($builds_path.'/builds/build/',0777) OR warn_exit('Could not make temporary build folder');
		fix_permissions($builds_path.'/builds/build/',0777);
	}
	if (!$skip_file_grab)
		deldir_contents($builds_path.'/builds/build/'.$version_branch.'/');
	if (!file_exists($builds_path.'/builds/build/'.$version_branch.'/'))
	{
		mkdir($builds_path.'/builds/build/'.$version_branch.'/',0777) OR warn_exit('Could not make branch build folder');
		fix_permissions($builds_path.'/builds/build/'.$version_branch.'/',0777);
	}
	if (!file_exists($builds_path.'/builds/'.$version_dotted.'/'))
	{
		mkdir($builds_path.'/builds/'.$version_dotted.'/',0777) OR warn_exit('Could not make version build folder');
		fix_permissions($builds_path.'/builds/'.$version_dotted.'/',0777);
	}

	if (!$skip_file_grab)
	{
		@copy(get_file_base().'/install.php',$builds_path.'/builds/build/'.$version_branch.'/install.php');
		fix_permissions($builds_path.'/builds/build/'.$version_branch.'/install.php');

		// Get file data array
		$out.='<ul>';
		$out.=populate_build_files_array();
		$out.='</ul>';
	}

	// What we'll be building
	$bundled=$builds_path.'/builds/'.$version_dotted.'/ocportal-'.$version_dotted.'.tar';
	$quick_zip=$builds_path.'/builds/'.$version_dotted.'/ocportal_quick_installer-'.$version_dotted.'.zip';
	$manual_zip=$builds_path.'/builds/'.$version_dotted.'/ocportal_manualextraction_installer-'.$version_dotted.'.zip';
	$debian=$builds_path.'/builds/'.$version_dotted.'/debian-'.$version_dotted.'.tar';
	$mszip=$builds_path.'/builds/'.$version_dotted.'/ocportal-'.$version_dotted.'-webpi.zip'; // Aka msappgallery, related to webmatrix

	// Flags
	$make_quick=!isset($_GET['skip_quick']);
	$make_manual=!isset($_GET['skip_manual']);
	$make_bundled=!isset($_GET['skip_bundled']);
	$make_mszip=!isset($_GET['skip_mszip']);
	$make_debian=false;//!isset($_GET['skip_debian']);

	disable_php_memory_limit();

	// Build quick installer
	if ($make_quick)
	{
		// Write out our installer data file
		$data_file=fopen($builds_path.'/builds/'.$version_dotted.'/data.ocp','wb');
		require_code('zip');
		$zip_file_array=array();
		foreach ($FILE_ARRAY as $filename=>$data)
		{
			$zip_file_array[]=array('time'=>filemtime(get_file_base().'/'.$filename),'data'=>$data,'name'=>$filename);
		}
		list($data,$offsets,$sizes)=create_zip_file($zip_file_array,false,true);
		fwrite($data_file,$data);
		fclose($data_file);
		fix_permissions($builds_path.'/builds/'.$version_dotted.'/data.ocp');
		$archive_size=filesize($builds_path.'/builds/'.$version_dotted.'/data.ocp');
		// The installer does an md5 check to check validity - prepare for it
		$md5_test_path='data/images/advertise_here.png';
		$md5=md5(file_get_contents($builds_path.'/builds/build/'.$version_branch.'/'.$md5_test_path));

		// Write out our PHP installer file
		$file_count=count($FILE_ARRAY);
		$size_list='';
		$offset_list='';
		$file_list='';
		foreach (array_keys($FILE_ARRAY) as $path) // $FILE_ARRAY is Current path->contents. We need number->path, so we can count through them without having to have the array with us. We end up with this in string form, as it goes in our file
		{
			$out.=do_build_file_output($path);
			$size_list.='\''.$path.'\'=>'.$sizes[$path].','."\n";
			$offset_list.='\''.$path.'\'=>'.$offsets[$path].','."\n";
			$file_list.='\''.$path.'\',';
		}

		// Build install.php, which has to have all our data.ocp file offsets put into it (data.ocp is an uncompressed zip, but the quick installer cheats - it can't truly read arbitrary zips)
		$code=file_get_contents(get_file_base().'/install.php');
		$auto_installer=fopen($builds_path.'/builds/'.$version_dotted.'/install.php','wb');
		$installer_start="<?php
			/* QUICK INSTALLER CODE starts */

			global \$FILE_ARRAY,\$SIZE_ARRAY,\$OFFSET_ARRAY,\$DIR_ARRAY,\$myfile;
			\$OFFSET_ARRAY=array({$offset_list});
			\$SIZE_ARRAY=array({$size_list});
			\$FILE_ARRAY=array({$file_list});
			\$myfile=fopen('data.ocp','rb');
			if (\$myfile===false) warn_exit('data.ocp missing / inaccessible');
			if (filesize('data.ocp')!={$archive_size}) warn_exit('data.ocp not fully uploaded, or wrong version for this installer');
			if (md5(file_array_get('{$md5_test_path}'))!='{$md5}') warn_exit('data.ocp corrupt. Must not be uploaded in text mode');

			function file_array_get(\$path)
			{
				global \$OFFSET_ARRAY,\$SIZE_ARRAY,\$myfile,\$FILE_BASE;

				if (substr(\$path,0,strlen(\$FILE_BASE.'/'))==\$FILE_BASE.'/')
					\$path=substr(\$path,strlen(\$FILE_BASE.'/'));

				if (!isset(\$OFFSET_ARRAY[\$path])) return;
				\$offset=\$OFFSET_ARRAY[\$path];
				\$size=\$SIZE_ARRAY[\$path];
				if (\$size==0) return '';
				fseek(\$myfile,\$offset,SEEK_SET);
				if (\$size>1024*1024)
				{
					return array(\$size,\$myfile,\$offset);
				}
				\$data=fread(\$myfile,\$size);
				return \$data;
			}

			function file_array_exists(\$path)
			{
				global \$OFFSET_ARRAY;
				return (isset(\$OFFSET_ARRAY[\$path]));
			}

			function file_array_get_at(\$i)
			{
				global \$FILE_ARRAY;
				\$name=\$FILE_ARRAY[\$i];
				return array(\$name,file_array_get(\$name));
			}

			function file_array_count()
			{
				return {$file_count};
			}";
		$installer_start=preg_replace('#^\t{3}#m','',$installer_start); // Format it correctly
		fputs($auto_installer,$installer_start);
		global $DIR_ARRAY;
		foreach ($DIR_ARRAY as $dir)
		{
			fputs($auto_installer,'$DIR_ARRAY[]=\''.$dir.'\';'."\n");
		}
		fputs($auto_installer,'/* QUICK INSTALLER CODE ends */ ?'.'>');
		fputs($auto_installer,$code);
		fclose($auto_installer);
		fix_permissions($builds_path.'/builds/'.$version_dotted.'/install.php');

		@unlink($quick_zip);

		chdir($builds_path.'/builds/'.$version_dotted);
		$cmd='zip -r -9 '.escapeshellarg($quick_zip).' '.escapeshellarg('data.ocp').' '.escapeshellarg('install.php');
		$output2=$cmd.':'."\n".shell_exec($cmd);

		chdir(get_file_base().'/data_custom/builds');
		$cmd='zip -r -9 '.escapeshellarg($quick_zip).' '.escapeshellarg('readme.txt');
		$output2.=$cmd.':'."\n".shell_exec($cmd);
		$out.=do_build_zip_output($quick_zip,$output2);

		chdir(get_file_base());
	}

	/*
	The other installers are built up file-by-file...
	*/

	// Build manual
	if ($make_manual)
	{
		@unlink($manual_zip);

		// Do the main work
		chdir($builds_path.'/builds/build/'.$version_branch);
		$cmd='zip -r -9 '.escapeshellarg($manual_zip).' *';
		$output2=shell_exec($cmd);
		$out.=do_build_zip_output($manual_zip,$output2);

		chdir(get_file_base());
	}

	// Build bundled version (Installatron, Bitnami, ...)
	if ($make_bundled || $make_debian)
	{
		@unlink($bundled);
		@unlink($bundled.'.gz');

		// Copy some files we need
		copy(get_file_base().'/install.sql',$builds_path.'/builds/build/'.$version_branch.'/install.sql');
		fix_permissions($builds_path.'/builds/build/'.$version_branch.'/install.sql');
		copy(get_file_base().'/info.php.template',$builds_path.'/builds/build/'.$version_branch.'/info.php.template');
		fix_permissions($builds_path.'/builds/build/'.$version_branch.'/info.php.template');

		// Do the main work
		chdir($builds_path.'/builds/build/'.$version_branch);
		$cmd='tar -cvf '.escapeshellarg($bundled).' * --mode=a+X';
		$output2=shell_exec($cmd);
		chdir(get_file_base().'/data_custom/builds');
		$cmd='tar -rvf '.escapeshellarg($bundled).' readme.txt --mode=a+X';
		$output2.=shell_exec($cmd);
		//$out.=do_build_zip_output($v,$output2);	Don't mention, as will get auto-deleted after gzipping anyway
		chdir($builds_path.'/builds/build/'.$version_branch);
		$cmd='gzip -n '.escapeshellarg($bundled);
		shell_exec($cmd);
		@unlink($bundled);
		$out.=do_build_zip_output($bundled.'.gz',$output2);

		// Remove those files we copied
		unlink($builds_path.'/builds/build/'.$version_branch.'/install.sql');
		unlink($builds_path.'/builds/build/'.$version_branch.'/info.php.template');

		chdir(get_file_base());
	}

	// Build debian version (built on top of bundled version)
	if ($make_debian)
	{
		// To our correct versioned builds directory
		if (file_exists($builds_path.'/builds/debian-build')) deldir_contents($builds_path.'/builds/debian-build');
		@mkdir($builds_path.'/builds/debian-build',0777);
		fix_permissions($builds_path.'/builds/debian-build',0777);

		// Take existing .tar.gz package, gunzip the tar
		chdir($builds_path.'/builds/debian-build');
		@mkdir($builds_path.'/builds/debian-build/ocportal-'.$version_dotted,0777);
		fix_permissions($builds_path.'/builds/debian-build/ocportal-'.$version_dotted,0777);
		copy($bundled'.gz',$builds_path.'/builds/debian-build/ocportal-'.$version_dotted.'.tar.gz');
		fix_permissions($builds_path.'/builds/debian-build/ocportal-'.$version_dotted.'.tar.gz');
		$cmd='gunzip '.escapeshellarg($builds_path.'/builds/debian-build/ocportal-'.$version_dotted.'.tar.gz');
		shell_exec($cmd);

		// Extract the tar to to "ocportal-<version>"
		chdir($builds_path.'/builds/debian-build/ocportal-'.$version_dotted);
		@mkdir($builds_path.'/builds/debian-build/ocportal-'.$version_dotted,0777);
		fix_permissions($builds_path.'/builds/debian-build/ocportal-'.$version_dotted,0777);
		$cmd='tar xvf '.escapeshellarg($builds_path.'/builds/debian-build/ocportal-'.$version_dotted.'.tar');
		echo shell_exec($cmd);

		// Filter out non-free stuff from "ocportal-<version>"
		$prefix=$builds_path.'/builds/debian-build/ocportal-'.$version_dotted;
		unlink($prefix.'/sources/hooks/systems/addon_registry/jwplayer.php');
		unlink($prefix.'/data/flvplayer.swf');
		unlink($prefix.'/themes/default/templates/ATTACHMENT_FLV.tpl');
		unlink($prefix.'/themes/default/templates/COMCODE_FLV.tpl');
		unlink($prefix.'/themes/default/templates/GALLERY_VIDEO_FLV.tpl');
		unlink($prefix.'/themes/default/templates/JAVASCRIPT_JWPLAYER.tpl');
		unlink($prefix.'/sources/jsmin.php');
		unlink($prefix.'/themes/default/images/cedi_link.png');
		unlink($prefix.'/themes/default/images/calendar/birthday.png');
		unlink($prefix.'/themes/default/images/calendar/anniversary.png');
		unlink($prefix.'/themes/default/images/calendar/appointment.png');
		unlink($prefix.'/themes/default/images/calendar/general.png');
		unlink($prefix.'/themes/default/images/calendar/activity.png');
		unlink($prefix.'/themes/default/images/calendar/system_command.png');
		unlink($prefix.'/themes/default/images/calendar/duty.png');
		unlink($prefix.'/themes/default/images/calendar/festival.png');
		unlink($prefix.'/themes/default/images/calendar/commitment.png');
		unlink($prefix.'/themes/default/images/bigicons/move.png');
		unlink($prefix.'/themes/default/images/bigicons/of_catalogues.png');
		unlink($prefix.'/themes/default/images/bigicons/language.png');
		unlink($prefix.'/themes/default/images/bigicons/forums.png');
		unlink($prefix.'/themes/default/images/bigicons/chatrooms.png');
		unlink($prefix.'/themes/default/images/bigicons/view_this.png');
		unlink($prefix.'/themes/default/images/bigicons/polls.png');
		unlink($prefix.'/themes/default/images/bigicons/awards.png');
		unlink($prefix.'/themes/default/images/bigicons/set-own-profile.png');
		unlink($prefix.'/themes/default/images/bigicons/phpinfo.png');
		unlink($prefix.'/themes/default/images/bigicons/download_csv.png');
		unlink($prefix.'/themes/default/images/bigicons/ocp-logo.png');
		unlink($prefix.'/themes/default/images/bigicons/custom-comcode.png');
		unlink($prefix.'/themes/default/images/bigicons/permissionstree.png');
		unlink($prefix.'/themes/default/images/bigicons/redirect.png');
		unlink($prefix.'/themes/default/images/bigicons/orders.png');
		unlink($prefix.'/themes/default/images/bigicons/edit_one_catalogue.png');
		unlink($prefix.'/themes/default/images/bigicons/manage_images.png');
		unlink($prefix.'/themes/default/images/bigicons/top_keywords.png');
		unlink($prefix.'/themes/default/images/bigicons/ldap.png');
		unlink($prefix.'/themes/default/images/bigicons/edit_one.png');
		unlink($prefix.'/themes/default/images/bigicons/show_orders.png');
		unlink($prefix.'/themes/default/images/bigicons/xml.png');
		unlink($prefix.'/themes/default/images/bigicons/actionlog.png');
		unlink($prefix.'/themes/default/images/bigicons/multimods.png');
		unlink($prefix.'/themes/default/images/bigicons/edit_css.png');
		unlink($prefix.'/themes/default/images/bigicons/geolocate.png');
		unlink($prefix.'/themes/default/images/bigicons/bulkupload.png');
		unlink($prefix.'/themes/default/images/bigicons/ssl.png');
		unlink($prefix.'/themes/default/images/bigicons/page_views.png');
		unlink($prefix.'/themes/default/images/bigicons/newsletters.png');
		unlink($prefix.'/themes/default/images/bigicons/statistics_search.png');
		unlink($prefix.'/themes/default/images/bigicons/messaging.png');
		unlink($prefix.'/themes/default/images/bigicons/transactions.png');
		unlink($prefix.'/themes/default/images/bigicons/statistics_posting_rates.png');
		unlink($prefix.'/themes/default/images/bigicons/import_subscribers.png');
		unlink($prefix.'/themes/default/images/bigicons/security.png');
		unlink($prefix.'/themes/default/images/bigicons/cash_flow.png');
		unlink($prefix.'/themes/default/images/bigicons/usergroups.png');
		unlink($prefix.'/themes/default/images/bigicons/usergroups_temp.png');
		unlink($prefix.'/themes/default/images/bigicons/zones.png');
		unlink($prefix.'/themes/default/images/bigicons/occle.png');
		unlink($prefix.'/themes/default/images/bigicons/view_archive.png');
		unlink($prefix.'/themes/default/images/bigicons/pagewizard.png');
		unlink($prefix.'/themes/default/images/bigicons/deletelurkers.png');
		unlink($prefix.'/themes/default/images/bigicons/cms_home.png');
		unlink($prefix.'/themes/default/images/bigicons/import.png');
		unlink($prefix.'/themes/default/images/bigicons/edit_one_image.png');
		unlink($prefix.'/themes/default/images/bigicons/ipban.png');
		unlink($prefix.'/themes/default/images/bigicons/multisitenetwork.png');
		unlink($prefix.'/themes/default/images/bigicons/tickets.png');
		unlink($prefix.'/themes/default/images/bigicons/export.png');
		unlink($prefix.'/themes/default/images/bigicons/themewizard.png');
		unlink($prefix.'/themes/default/images/bigicons/findwinners.png');
		unlink($prefix.'/themes/default/images/bigicons/calendar.png');
		unlink($prefix.'/themes/default/images/bigicons/main_home.png');
		unlink($prefix.'/themes/default/images/bigicons/add_one.png');
		unlink($prefix.'/themes/default/images/bigicons/catalogues.png');
		unlink($prefix.'/themes/default/images/bigicons/addmember.png');
		unlink($prefix.'/themes/default/images/bigicons/authors.png');
		unlink($prefix.'/themes/default/images/bigicons/wordfilter.png');
		unlink($prefix.'/themes/default/images/bigicons/delete.png');
		unlink($prefix.'/themes/default/images/bigicons/cleanup.png');
		unlink($prefix.'/themes/default/images/bigicons/add_one_catalogue.png');
		unlink($prefix.'/themes/default/images/bigicons/errorlog.png');
		unlink($prefix.'/themes/default/images/bigicons/newsletter_from_changes.png');
		unlink($prefix.'/themes/default/images/bigicons/top_referrers.png');
		unlink($prefix.'/themes/default/images/bigicons/posttemplates.png');
		unlink($prefix.'/themes/default/images/bigicons/users_online.png');
		unlink($prefix.'/themes/default/images/bigicons/downloads.png');
		unlink($prefix.'/themes/default/images/bigicons/survey_results.png');
		unlink($prefix.'/themes/default/images/bigicons/import_csv.png');
		unlink($prefix.'/themes/default/images/bigicons/matchkeysecurity.png');
		unlink($prefix.'/themes/default/images/bigicons/subscribers.png');
		unlink($prefix.'/themes/default/images/bigicons/baseconfig.png');
		unlink($prefix.'/themes/default/images/bigicons/add_one_category.png');
		unlink($prefix.'/themes/default/images/bigicons/securitylog.png');
		unlink($prefix.'/themes/default/images/bigicons/backups.png');
		unlink($prefix.'/themes/default/images/bigicons/statistics.png');
		unlink($prefix.'/themes/default/images/bigicons/clear_stats.png');
		unlink($prefix.'/themes/default/images/bigicons/sitetree.png');
		unlink($prefix.'/themes/default/images/bigicons/invoices.png');
		unlink($prefix.'/themes/default/images/bigicons/news.png');
		unlink($prefix.'/themes/default/images/bigicons/admin_home.png');
		unlink($prefix.'/themes/default/images/bigicons/edit_this_catalogue.png');
		unlink($prefix.'/themes/default/images/bigicons/edit_one_video.png');
		unlink($prefix.'/themes/default/images/bigicons/comcode_page_edit.png');
		unlink($prefix.'/themes/default/images/bigicons/view_this_category.png');
		unlink($prefix.'/themes/default/images/bigicons/config.png');
		unlink($prefix.'/themes/default/images/bigicons/back.png');
		unlink($prefix.'/themes/default/images/bigicons/trackbacks.png');
		unlink($prefix.'/themes/default/images/bigicons/criticise_language.png');
		unlink($prefix.'/themes/default/images/bigicons/add_one_image.png');
		unlink($prefix.'/themes/default/images/bigicons/add_video_to_this.png');
		unlink($prefix.'/themes/default/images/bigicons/merge.png');
		unlink($prefix.'/themes/default/images/bigicons/edit_templates.png');
		unlink($prefix.'/themes/default/images/bigicons/debrand.png');
		unlink($prefix.'/themes/default/images/bigicons/edit_one_category.png');
		unlink($prefix.'/themes/default/images/bigicons/emoticons.png');
		unlink($prefix.'/themes/default/images/bigicons/statistics_demographics.png');
		unlink($prefix.'/themes/default/images/bigicons/pointslog.png');
		unlink($prefix.'/themes/default/images/bigicons/edit_this_category.png');
		unlink($prefix.'/themes/default/images/bigicons/add_image_to_this.png');
		unlink($prefix.'/themes/default/images/bigicons/zone_editor.png');
		unlink($prefix.'/themes/default/images/bigicons/profit_loss.png');
		unlink($prefix.'/themes/default/images/bigicons/add_to_catalogue.png');
		unlink($prefix.'/themes/default/images/bigicons/ecommerce.png');
		unlink($prefix.'/themes/default/images/bigicons/manage_themes.png');
		unlink($prefix.'/themes/default/images/bigicons/staff.png');
		unlink($prefix.'/themes/default/images/bigicons/pointstorelog.png');
		unlink($prefix.'/themes/default/images/bigicons/iotds.png');
		unlink($prefix.'/themes/default/images/bigicons/galleries.png');
		unlink($prefix.'/themes/default/images/bigicons/setupwizard.png');
		unlink($prefix.'/themes/default/images/bigicons/add_one_licence.png');
		unlink($prefix.'/themes/default/images/bigicons/add_one_video.png');
		unlink($prefix.'/themes/default/images/bigicons/pointstore.png');
		unlink($prefix.'/themes/default/images/bigicons/make_logo.png');
		unlink($prefix.'/themes/default/images/bigicons/addons.png');
		unlink($prefix.'/themes/default/images/bigicons/submits.png');
		unlink($prefix.'/themes/default/images/bigicons/load_times.png');
		unlink($prefix.'/themes/default/images/bigicons/undispatched.png');
		unlink($prefix.'/themes/default/images/bigicons/searchstats.png');
		unlink($prefix.'/themes/default/images/bigicons/investigateuser.png');
		unlink($prefix.'/themes/default/images/bigicons/quotes.png');
		unlink($prefix.'/themes/default/images/bigicons/banners.png');
		unlink($prefix.'/themes/default/images/bigicons/privileges.png');
		unlink($prefix.'/themes/default/images/bigicons/welcome_emails.png');
		unlink($prefix.'/themes/default/images/bigicons/realtime_rain.png');
		unlink($prefix.'/themes/default/images/bigicons/edit_one_licence.png');
		unlink($prefix.'/themes/default/images/bigicons/add_to_category.png');
		unlink($prefix.'/themes/default/images/bigicons/cedi.png');
		unlink($prefix.'/themes/default/images/bigicons/menus.png');
		unlink($prefix.'/themes/default/images/bigicons/customprofilefields.png');
		unlink($prefix.'/themes/default/images/bigicons/quiz.png');
		unlink($prefix.'/themes/default/images/bigicons/clubs.png');
		unlink($prefix.'/themes/default/images/bigicons/merge_members.png');
		unlink($prefix.'/themes/default/images/bigicons/edit_this.png');
		unlink($prefix.'/themes/default/images/bigicons/editmember.png');
		unlink($prefix.'/themes/default/images/newscats/entertainment.jpg');
		unlink($prefix.'/themes/default/images/newscats/art.jpg');
		unlink($prefix.'/themes/default/images/newscats/business.jpg');
		unlink($prefix.'/themes/default/images/newscats/general.jpg');
		unlink($prefix.'/themes/default/images/newscats/difficulties.jpg');
		unlink($prefix.'/themes/default/images/newscats/community.jpg');
		unlink($prefix.'/themes/default/images/newscats/technology.jpg');
		unlink($prefix.'/themes/default/images/cedi_link_hover.png');
		unlink($prefix.'/themes/default/images/recommend/twitter.gif');
		unlink($prefix.'/themes/default/images/recommend/stumbleupon.gif');
		unlink($prefix.'/themes/default/images/recommend/print.gif');
		unlink($prefix.'/themes/default/images/recommend/recommend.gif');
		unlink($prefix.'/themes/default/images/recommend/favorites.gif');
		unlink($prefix.'/themes/default/images/recommend/digg.gif');
		unlink($prefix.'/themes/default/images/recommend/facebook.gif');
		unlink($prefix.'/themes/default/images/results/dispatch.png');
		unlink($prefix.'/themes/default/images/results/hold.png');
		unlink($prefix.'/themes/default/images/results/add_note.png');
		unlink($prefix.'/themes/default/images/results/return.gif');
		unlink($prefix.'/themes/default/images/results/view.gif');
		unlink($prefix.'/themes/default/images/pagepics/move.png');
		unlink($prefix.'/themes/default/images/pagepics/language.png');
		unlink($prefix.'/themes/default/images/pagepics/forums.png');
		unlink($prefix.'/themes/default/images/pagepics/loadtimes.png');
		unlink($prefix.'/themes/default/images/pagepics/chatrooms.png');
		unlink($prefix.'/themes/default/images/pagepics/polls.png');
		unlink($prefix.'/themes/default/images/pagepics/awards.png');
		unlink($prefix.'/themes/default/images/pagepics/phpinfo.png');
		unlink($prefix.'/themes/default/images/pagepics/installgeolocationdata.png');
		unlink($prefix.'/themes/default/images/pagepics/unvalidated.png');
		unlink($prefix.'/themes/default/images/pagepics/ocp-logo.png');
		unlink($prefix.'/themes/default/images/pagepics/statistics_clear.png');
		unlink($prefix.'/themes/default/images/pagepics/redirect.png');
		unlink($prefix.'/themes/default/images/pagepics/ldap.png');
		unlink($prefix.'/themes/default/images/pagepics/xml.png');
		unlink($prefix.'/themes/default/images/pagepics/actionlog.png');
		unlink($prefix.'/themes/default/images/pagepics/configwizard.png');
		unlink($prefix.'/themes/default/images/pagepics/importdata.png');
		unlink($prefix.'/themes/default/images/pagepics/statistics_google.png');
		unlink($prefix.'/themes/default/images/pagepics/ssl.png');
		unlink($prefix.'/themes/default/images/pagepics/statistics_search.png');
		unlink($prefix.'/themes/default/images/pagepics/statistics_usersonline.png');
		unlink($prefix.'/themes/default/images/pagepics/messaging.png');
		unlink($prefix.'/themes/default/images/pagepics/transactions.png');
		unlink($prefix.'/themes/default/images/pagepics/cash_flow.png');
		unlink($prefix.'/themes/default/images/pagepics/usergroups.png');
		unlink($prefix.'/themes/default/images/pagepics/usergroups_temp.png');
		unlink($prefix.'/themes/default/images/pagepics/zones.png');
		unlink($prefix.'/themes/default/images/pagepics/occle.png');
		unlink($prefix.'/themes/default/images/pagepics/deletelurkers.png');
		unlink($prefix.'/themes/default/images/pagepics/ipban.png');
		unlink($prefix.'/themes/default/images/pagepics/tickets.png');
		unlink($prefix.'/themes/default/images/pagepics/export.png');
		unlink($prefix.'/themes/default/images/pagepics/themewizard.png');
		unlink($prefix.'/themes/default/images/pagepics/findwinners.png');
		unlink($prefix.'/themes/default/images/pagepics/calendar.png');
		unlink($prefix.'/themes/default/images/pagepics/catalogues.png');
		unlink($prefix.'/themes/default/images/pagepics/addmember.png');
		unlink($prefix.'/themes/default/images/pagepics/authors.png');
		unlink($prefix.'/themes/default/images/pagepics/wordfilter.png');
		unlink($prefix.'/themes/default/images/pagepics/cleanup.png');
		unlink($prefix.'/themes/default/images/pagepics/sitetreeeditor.png');
		unlink($prefix.'/themes/default/images/pagepics/errorlog.png');
		unlink($prefix.'/themes/default/images/pagepics/newsletter_from_changes.png');
		unlink($prefix.'/themes/default/images/pagepics/addpagewizard.png');
		unlink($prefix.'/themes/default/images/pagepics/posttemplates.png');
		unlink($prefix.'/themes/default/images/pagepics/ocpmainpage.png');
		unlink($prefix.'/themes/default/images/pagepics/customcomcode.png');
		unlink($prefix.'/themes/default/images/pagepics/downloads.png');
		unlink($prefix.'/themes/default/images/pagepics/survey_results.png');
		unlink($prefix.'/themes/default/images/pagepics/import_csv.png');
		unlink($prefix.'/themes/default/images/pagepics/matchkeysecurity.png');
		unlink($prefix.'/themes/default/images/pagepics/securitylog.png');
		unlink($prefix.'/themes/default/images/pagepics/backups.png');
		unlink($prefix.'/themes/default/images/pagepics/statistics.png');
		unlink($prefix.'/themes/default/images/pagepics/multimoderations.png');
		unlink($prefix.'/themes/default/images/pagepics/statistics_referrers.png');
		unlink($prefix.'/themes/default/images/pagepics/invoices.png');
		unlink($prefix.'/themes/default/images/pagepics/news.png');
		unlink($prefix.'/themes/default/images/pagepics/newsletter.png');
		unlink($prefix.'/themes/default/images/pagepics/comcode_page_edit.png');
		unlink($prefix.'/themes/default/images/pagepics/bulkuploadassistant.png');
		unlink($prefix.'/themes/default/images/pagepics/config.png');
		unlink($prefix.'/themes/default/images/pagepics/deletepage.png');
		unlink($prefix.'/themes/default/images/pagepics/trackbacks.png');
		unlink($prefix.'/themes/default/images/pagepics/criticise_language.png');
		unlink($prefix.'/themes/default/images/pagepics/setauthorprofile.png');
		unlink($prefix.'/themes/default/images/pagepics/mergemembers.png');
		unlink($prefix.'/themes/default/images/pagepics/debrand.png');
		unlink($prefix.'/themes/default/images/pagepics/emoticons.png');
		unlink($prefix.'/themes/default/images/pagepics/profit_loss.png');
		unlink($prefix.'/themes/default/images/pagepics/ecommerce.png');
		unlink($prefix.'/themes/default/images/pagepics/themes.png');
		unlink($prefix.'/themes/default/images/pagepics/staff.png');
		unlink($prefix.'/themes/default/images/pagepics/iotds.png');
		unlink($prefix.'/themes/default/images/pagepics/statistics_pageviews.png');
		unlink($prefix.'/themes/default/images/pagepics/permissiontree.png');
		unlink($prefix.'/themes/default/images/pagepics/pointstore.png');
		unlink($prefix.'/themes/default/images/pagepics/addons.png');
		unlink($prefix.'/themes/default/images/pagepics/submits.png');
		unlink($prefix.'/themes/default/images/pagepics/investigateuser.png');
		unlink($prefix.'/themes/default/images/pagepics/quotes.png');
		unlink($prefix.'/themes/default/images/pagepics/multisitenetworking.png');
		unlink($prefix.'/themes/default/images/pagepics/banners.png');
		unlink($prefix.'/themes/default/images/pagepics/privileges.png');
		unlink($prefix.'/themes/default/images/pagepics/welcome_emails.png');
		unlink($prefix.'/themes/default/images/pagepics/logowizard.png');
		unlink($prefix.'/themes/default/images/pagepics/points.png');
		unlink($prefix.'/themes/default/images/pagepics/cedi.png');
		unlink($prefix.'/themes/default/images/pagepics/menus.png');
		unlink($prefix.'/themes/default/images/pagepics/customprofilefields.png');
		unlink($prefix.'/themes/default/images/pagepics/quiz.png');
		unlink($prefix.'/themes/default/images/pagepics/flagrant.png');
		unlink($prefix.'/themes/default/images/pagepics/clubs.png');
		unlink($prefix.'/themes/default/images/pagepics/images.png');
		unlink($prefix.'/themes/default/images/pagepics/editmember.png');
		unlink($prefix.'/themes/default/images/under_construction_animated.gif');
		unlink($prefix.'/themes/default/images/EN/page/amend.png');
		unlink($prefix.'/themes/default/images/EN/page/edit.png');
		unlink($prefix.'/themes/default/images/EN/page/redirect.png');
		unlink($prefix.'/themes/default/images/EN/page/cart_empty.png');
		unlink($prefix.'/themes/default/images/EN/page/all2.png');
		unlink($prefix.'/themes/default/images/EN/page/new.png');
		unlink($prefix.'/themes/default/images/EN/page/no_next.png');
		unlink($prefix.'/themes/default/images/EN/page/search.png');
		unlink($prefix.'/themes/default/images/EN/page/slideshow.png');
		unlink($prefix.'/themes/default/images/EN/page/cart_update.png');
		unlink($prefix.'/themes/default/images/EN/page/cart_checkout.png');
		unlink($prefix.'/themes/default/images/EN/page/next.png');
		unlink($prefix.'/themes/default/images/EN/page/convert.png');
		unlink($prefix.'/themes/default/images/EN/page/cart_view.png');
		unlink($prefix.'/themes/default/images/EN/page/discard.png');
		unlink($prefix.'/themes/default/images/EN/page/shopping_buy_now.png');
		unlink($prefix.'/themes/default/images/EN/page/quick_reply.png');
		unlink($prefix.'/themes/default/images/EN/page/delete.png');
		unlink($prefix.'/themes/default/images/EN/page/closed.png');
		unlink($prefix.'/themes/default/images/EN/page/changes.png');
		unlink($prefix.'/themes/default/images/EN/page/new_post.png');
		unlink($prefix.'/themes/default/images/EN/page/add_event.png');
		unlink($prefix.'/themes/default/images/EN/page/simple.png');
		unlink($prefix.'/themes/default/images/EN/page/cart_add.png');
		unlink($prefix.'/themes/default/images/EN/page/no.png');
		unlink($prefix.'/themes/default/images/EN/page/edit_tree.png');
		unlink($prefix.'/themes/default/images/EN/page/close.png');
		unlink($prefix.'/themes/default/images/EN/page/join.png');
		unlink($prefix.'/themes/default/images/EN/page/mark_unread.png');
		unlink($prefix.'/themes/default/images/EN/page/rename.png');
		unlink($prefix.'/themes/default/images/EN/page/cancel.png');
		unlink($prefix.'/themes/default/images/EN/page/ok.png');
		unlink($prefix.'/themes/default/images/EN/page/reply.png');
		unlink($prefix.'/themes/default/images/EN/page/staff_only_reply.png');
		unlink($prefix.'/themes/default/images/EN/page/login.png');
		unlink($prefix.'/themes/default/images/EN/page/no_previous.png');
		unlink($prefix.'/themes/default/images/EN/page/invite_member.png');
		unlink($prefix.'/themes/default/images/EN/page/mark_read.png');
		unlink($prefix.'/themes/default/images/EN/page/new_topic.png');
		unlink($prefix.'/themes/default/images/EN/page/shopping_continue.png');
		unlink($prefix.'/themes/default/images/EN/page/advanced.png');
		unlink($prefix.'/themes/default/images/EN/page/all.png');
		unlink($prefix.'/themes/default/images/EN/page/previous.png');
		unlink($prefix.'/themes/default/images/EN/page/ignore.png');
		unlink($prefix.'/themes/default/images/background_image.png');
		unlink($prefix.'/themes/default/images/filetypes/page_ods.png');
		unlink($prefix.'/themes/default/images/filetypes/page_media.png');
		unlink($prefix.'/themes/default/images/filetypes/page_archive.png');
		unlink($prefix.'/themes/default/images/filetypes/page_odp.png');
		unlink($prefix.'/themes/default/images/filetypes/page_torrent.png');
		unlink($prefix.'/themes/default/images/filetypes/feed.png');
		unlink($prefix.'/themes/default/images/filetypes/page_doc.png');
		unlink($prefix.'/themes/default/images/filetypes/page_txt.png');
		unlink($prefix.'/themes/default/images/filetypes/page_odt.png');
		unlink($prefix.'/themes/default/images/filetypes/page_xls.png');
		unlink($prefix.'/themes/default/images/filetypes/page_ppt.png');
		unlink($prefix.'/themes/default/images/filetypes/external_link.png');
		unlink($prefix.'/themes/default/images/filetypes/email_link.png');
		unlink($prefix.'/themes/default/images/filetypes/page_pdf.png');
		unlink($prefix.'/themes/default/images/awarded.png');

		// Create "ocportal-<version>.tar.gz" package from "ocportal-<version>"
		chdir($builds_path.'/builds/debian-build');
		$cmd='tar -cvf '.escapeshellarg($builds_path.'/builds/debian-build/ocportal-'.$version_dotted.'.tar').' ocportal-'.$version_dotted.'/* --mode=a+X';
		$output2=shell_exec($cmd);
		$cmd='gzip -n '.escapeshellarg($builds_path.'/builds/debian-build/ocportal-'.$version_dotted.'.tar');
		shell_exec($cmd);

		// Copy "debian" directory into "ocportal-<version>"
		copy_r(get_file_base().'data_custom/builds/debian',$prefix.'/debian');

		// Tar up "ocportal-<version>" and "ocportal-<version>.tar.gz" together into "debian-<version>.tar"
		chdir($builds_path.'/builds/debian-build');
		$cmd='tar -cvf '.escapeshellarg($debian).' ocportal-'.$version_dotted.'/* ocportal-'.$version_dotted.'.tar.gz --mode=a+X';
		$output2.=shell_exec($cmd);

		$out.=do_build_zip_output($debian,$output2);

		chdir(get_file_base());
	}

	// Build Microsoft version
	if ($make_mszip)
	{
		@unlink($mszip);
		if (file_exists($builds_path.'/builds/build/ocportal/')) deldir_contents($builds_path.'/builds/build/ocportal/');

		// Move files out temporarily
		rename($builds_path.'/builds/build/'.$version_branch.'/info.php',$builds_path.'/builds/build/info.php');
		rename($builds_path.'/builds/build/'.$version_branch.'/install.sql',$builds_path.'/builds/build/install.sql');
		rename($builds_path.'/builds/build/'.$version_branch.'/install.php',$builds_path.'/builds/build/install.php');

		// Copy some stuff we need
		copy(get_file_base().'/info.php.template',$builds_path.'/builds/build/info.php.template');
		fix_permissions($builds_path.'/builds/build/info.php.template');
		for ($i=1;$i<=4;$i++)
		{
			copy(get_file_base().'/install'.strval($i).'.sql',$builds_path.'/builds/build/install'.strval($i).'.sql');
			fix_permissions($builds_path.'/builds/build/install'.strval($i).'.sql');
		}
		copy(get_file_base().'/user.sql',$builds_path.'/builds/build/user.sql');
		fix_permissions($builds_path.'/builds/build/user.sql');
		copy(get_file_base().'/postinstall.sql',$builds_path.'/builds/build/postinstall.sql');
		fix_permissions($builds_path.'/builds/build/postinstall.sql');
		copy(get_file_base().'/manifest.xml',$builds_path.'/builds/build/manifest.xml');
		fix_permissions($builds_path.'/builds/build/manifest.xml');
		copy(get_file_base().'/parameters.xml',$builds_path.'/builds/build/parameters.xml');
		fix_permissions($builds_path.'/builds/build/parameters.xml');

		// Temporary renaming
		rename($builds_path.'/builds/build/'.$version_branch,$builds_path.'/builds/build/ocportal');

		// Do the main work
		chdir($builds_path.'/builds/build');
		$cmd='zip -r -9 -v '.escapeshellarg($mszip).' ocportal manifest.xml parameters.xml install1.sql install2.sql install3.sql install4.sql user.sql postinstall.sql';
		$output2=shell_exec($cmd);
		$out.=do_build_zip_output($mszip,$output2);

		// Undo temporary renaming
		rename($builds_path.'/builds/build/ocportal',$builds_path.'/builds/build/'.$version_branch);

		// Move files moved out temporarily back
		rename($builds_path.'/builds/build/info.php',$builds_path.'/builds/build/'.$version_branch.'/info.php');
		rename($builds_path.'/builds/build/install.sql',$builds_path.'/builds/build/'.$version_branch.'/install.sql');
		rename($builds_path.'/builds/build/install.php',$builds_path.'/builds/build/'.$version_branch.'/install.php');

		chdir(get_file_base());
	}

	// We're done, show the result

	$details='';
	if ($make_quick) $details.='<li>'.$quick_zip.' file size: '.clean_file_size(filesize($quick_zip)).'</li>';
	if ($make_manual) $details.='<li>'.$manual_zip.' file size: '.clean_file_size(filesize($manual_zip)).'</li>';
	if ($make_debian) $details.='<li>'.$debian.' file size: '.clean_file_size(filesize($debian)).'</li>';
	if ($make_mszip) $details.='<li>'.$mszip.' file size: '.clean_file_size(filesize($mszip)).'</li>';
	if ($make_bundled) $details.='<li>'.$bundled.'.gz file size: '.clean_file_size(filesize($bundled.'.gz')).'</li>';

	$out.='
		<h2>Statistics</h2>
		<ul>
			<li>Total files compiled: '.integer_format($TOTAL_FILES).'</li>
			<li>Total directories traversed: '.integer_format($TOTAL_DIRS).'</li>
			'.$details.'
		</ul>';

	return $out;
}

function get_builds_path()
{
	$builds_path=dirname(get_file_base()).'/exports';
	if (!file_exists($builds_path.'/builds'))
	{
		mkdir($builds_path.'/builds',0777) OR warn_exit('Could not make master build folder');
		fix_permissions($builds_path.'/builds',0777);
	}
	return $builds_path;
}

function copy_r($path,$dest)
{
	if (is_dir($path))
	{
		@mkdir($dest,0777);
		fix_permissions($dest,0777);
		$objects=scandir($path);
		if (count($objects)>0)
		{
			foreach ($objects as $file)
			{
				if (($file=='.') || ($file=='..'))
					continue;

				if (is_dir($path.'/'.$file))
				{
					copy_r($path.'/'.$file,$dest.'/'.$file);
				}
				else
				{
					copy($path.'/'.$file,$dest.'/'.$file);
					fix_permissions($dest.'/'.$file);
				}
			}
		}
		return true;
	}
	elseif (is_file($path))
	{
		return copy($path,$dest);
	} else
	{
		return false;
	}
}

function do_build_file_output($path)
{
	global $TOTAL_FILES;
	$TOTAL_FILES++;
	return '<li>File "'.escape_html($path).'" compiled.</li>';
}

function do_build_directory_output($path)
{
	global $TOTAL_DIRS;
	$TOTAL_DIRS++;
	return '<li>Directory "'.escape_html($path).'" traversed.</li>';
}

function do_build_zip_output($file,$new_output)
{
	$version_dotted=get_version_dotted();

	$builds_path=get_builds_path();
	do_output('
		<div class="zip_surround">
		<h2>Compiling ZIP file "<a href="'.escape_html($file).'" title="Download the file.">'.escape_html(builds_path().$version_dotted.'/'.$file).'</a>"</h2>
		<p>'.trim(escape_html($new_output)).'</p>
		</div>'
	);
}

function populate_build_files_array($dir='',$pretend_dir='')
{
	global $FILE_ARRAY,$DIR_ARRAY;

	$out='';

	$version_branch=get_version_branch();

	$full_dir=get_file_base().'/'.$dir;
	$dh=opendir($full_dir);
	while (($file=readdir($dh))!==false)
	{
		$is_dir=is_dir(get_file_base().'/'.$dir.$file);

		if (should_ignore_file($pretend_dir.$file,IGNORE_NONBUNDLED_SCATTERED | IGNORE_CUSTOM_DIR_CONTENTS | IGNORE_CUSTOM_ZONES | IGNORE_CUSTOM_THEMES | IGNORE_NON_EN_SCATTERED_LANGS,0)) continue;

		if ($is_dir)
		{
			$num_files=count($FILE_ARRAY);
			$DIR_ARRAY[]=$pretend_dir.$file;
			@mkdir($builds_path.'/builds/build/'.$version_branch.'/'.$pretend_dir.$file,0777);
			fix_permissions($builds_path.'/builds/build/'.$version_branch.'/'.$pretend_dir.$file,0777);
			$_out=populate_build_files_array($dir.$file.'/',$pretend_dir.$file.'/');
			if ($num_files==count($FILE_ARRAY)) // Empty, effectively (maybe was from a non-bundled addon) - don't use it
			{
				array_pop($DIR_ARRAY);
				rmdir($builds_path.'/builds/build/'.$version_branch.'/'.$pretend_dir.$file);
			} else
			{
				$out.=$_out;
			}

			// Imply some extra dirs into sources_custom that we would have skipped
			if ($pretend_dir=='sources_custom')
			{
				$extra_dirs=array(
					'sources_custom/blocks',
					'sources_custom/database',
					'sources_custom/hooks',
					'sources_custom/hooks/blocks',
					'sources_custom/hooks/modules',
					'sources_custom/hooks/systems',
					'sources_custom/miniblocks',
				);
				foreach ($extra_dirs as $extra_dir)
				{
					$DIR_ARRAY[]=$extra_dir;
					@mkdir($builds_path.'/builds/build/'.$version_branch.'/'.$extra_dir,0777);
					fix_permissions($builds_path.'/builds/build/'.$version_branch.'/'.$extra_dir,0777);
				}
			}
		}
		else
		{
			// Reset volatile files to how they should be by default
			if (($pretend_dir.$file)=='info.php') $FILE_ARRAY[$pretend_dir.$file]='';
			elseif (($pretend_dir.$file)=='themes/map.ini') $FILE_ARRAY[$pretend_dir.$file]='default=default'.chr(10);
			elseif ($pretend_dir.$file=='data_custom/functions.dat') $FILE_ARRAY[$pretend_dir.$file]='';
			elseif ($pretend_dir.$file=='pages/html_custom/EN/download_tree_made.htm') $FILE_ARRAY[$pretend_dir.$file]='';
			elseif ($pretend_dir.$file=='site/pages/html_custom/EN/cedi_tree_made.htm') $FILE_ARRAY[$pretend_dir.$file]='';
			elseif ($pretend_dir.$file=='data_custom/spelling/output.log') $FILE_ARRAY[$pretend_dir.$file]='';
			elseif ($pretend_dir.$file=='data_custom/spelling/write.log') $FILE_ARRAY[$pretend_dir.$file]='';
			elseif ($pretend_dir.$file=='ocp_sitemap.xml') $FILE_ARRAY[$pretend_dir.$file]='';
			elseif ($pretend_dir.$file=='data_custom/errorlog.php') $FILE_ARRAY[$pretend_dir.$file]="<?php return; ?".">\n"; // So that code can't be executed
			elseif ($pretend_dir.$file=='data_custom/execute_temp.php') $FILE_ARRAY[$pretend_dir.$file]=preg_replace('#function execute_temp\(\)\n\n\{\n.*\}\n\n#s',"function execute_temp()\n\n{\n}\n\n#",file_get_contents(get_file_base().'/'.$dir.$file));
			// NB: 'data_custom/breadcrumbs.xml' and 'data_custom/fields.xml' are also volatile for users, but in git we're not allowed to mess with these without commit/release intent.

			// Update time of version in version.php
			elseif ($pretend_dir.$file=='sources/version.php') $FILE_ARRAY[$pretend_dir.$file]=preg_replace('/\d{10}/',strval(time()),file_get_contents(get_file_base().'/'.$dir.$file),1);

			// Copy file as-is
			else $FILE_ARRAY[$pretend_dir.$file]=file_get_contents(get_file_base().'/'.$dir.$file);

			// Write the file out
			$tmp=fopen($builds_path.'/builds/build/'.$version_branch.'/'.$pretend_dir.$file,'wb');
	 		fwrite($tmp,$FILE_ARRAY[$pretend_dir.$file]);
			fclose($tmp);
			fix_permissions($builds_path.'/builds/build/'.$version_branch.'/'.$pretend_dir.$file);
		}
	}

	$out.=do_build_directory_output($pretend_dir);
	return $out;
}