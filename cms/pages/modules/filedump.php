<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2011

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		filedump
 */

/**
 * Module page class.
 */
class Module_filedump
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		$info=array();
		$info['author']='Chris Graham'; 
		$info['organisation']='ocProducts';
		$info['hacked_by']=NULL;
		$info['hack_version']=NULL;
		$info['version']=3;
		$info['update_require_upgrade']=1;
		$info['locked']=false;
		return $info;
	}
	
	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		$GLOBALS['SITE_DB']->drop_if_exists('filedump');
	
		delete_specific_permission('delete_anything_filedump');
		delete_specific_permission('upload_filedump');
		delete_specific_permission('upload_anything_filedump');

		//deldir_contents(get_custom_file_base().'/uploads/filedump',true);

		delete_menu_item_simple('_SEARCH:filedump:type=misc');
		
		delete_config_option('filedump_show_stats_count_total_files');
		delete_config_option('filedump_show_stats_count_total_space');
	}

	/**
	 * Standard modular install function.
	 *
	 * @param  ?integer	What version we're upgrading from (NULL: new install)
	 * @param  ?integer	What hack version we're upgrading from (NULL: new-install/not-upgrading-from-a-hacked-version)
	 */
	function install($upgrade_from=NULL,$upgrade_from_hack=NULL)
	{
		if (is_null($upgrade_from))
		{
			$GLOBALS['SITE_DB']->create_table('filedump',array(
				'id'=>'*AUTO',
				'name'=>'ID_TEXT',
				'path'=>'URLPATH',
				'description'=>'SHORT_TRANS',
				'the_member'=>'USER'
			));
	
			add_specific_permission('FILE_DUMP','upload_anything_filedump',false);
			add_specific_permission('FILE_DUMP','upload_filedump',true);
			add_specific_permission('FILE_DUMP','delete_anything_filedump',false);

			require_lang('filedump');
			if (addon_installed('collaboration_zone'))
			{
				add_menu_item_simple('collab_features',NULL,'FILE_DUMP','_SEARCH:filedump:type=misc');
			}
		}

		if ((is_null($upgrade_from)) || ($upgrade_from<3))
		{
			add_config_option('FILEDUMP_COUNT_FILES','filedump_show_stats_count_total_files','tick','return addon_installed(\'stats_block\')?\'0\':NULL;','BLOCKS','STATISTICS');
			add_config_option('FILEDUMP_DISK_USAGE','filedump_show_stats_count_total_space','tick','return addon_installed(\'stats_block\')?\'0\':NULL;','BLOCKS','STATISTICS');
		}

		if (addon_installed('redirects_editor'))
		{
			$GLOBALS['SITE_DB']->query_delete('redirects',array('r_from_page'=>'filedump','r_from_zone'=>'collaboration','r_to_page'=>'filedump','r_to_zone'=>'cms','r_is_transparent'=>1));
		}
	}
	
	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return array('misc'=>'FILE_DUMP');
	}
	
	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		require_lang('filedump');
		require_code('files2');
	
		$type=get_param('type','misc');
	
		if ($type=='ac') return $this->module_do_add_folder();
		if ($type=='ad') return $this->module_do_upload();
		if ($type=='ec') return $this->module_do_delete_folder();
		if ($type=='ed') return $this->module_do_delete_file();
		if ($type=='misc') return $this->module_do_gui();
	
		return new ocp_tempcode();
	}
	
	/**
	 * The main user interface for the file dump.
	 *
	 * @return tempcode	The UI.
	 */
	function module_do_gui()
	{
		$title=get_page_title('FILE_DUMP');
	
		$place=filter_naughty(get_param('place','/'));
		if (substr($place,-1,1)!='/') $place.='/';
	
		$GLOBALS['FEED_URL']=find_script('backend').'?mode=filedump&filter='.$place;

		// Show tree
		$dirs=explode('/',substr($place,0,strlen($place)-1));
		$i=0;
		$pre='';
		$file_tree=new ocp_tempcode();
		while (array_key_exists($i,$dirs))
		{
			if ($i>0) $d=$dirs[$i]; else $d=do_lang('FILE_DUMP');
			
			if (array_key_exists($i+1,$dirs))
			{
				$tree_url=build_url(array('page'=>'_SELF','place'=>$pre.$dirs[$i].'/'),'_SELF');
				if (!$file_tree->is_empty()) $file_tree->attach(do_template('BREADCRUMB',array('_GUID'=>'7ee62e230d53344a7d9667dc59be21c6')));
				$file_tree->attach(hyperlink($tree_url,$d));
			}
			$pre.=$dirs[$i].'/';
			$i++;
		}
		if (!$file_tree->is_empty()) breadcrumb_add_segment($file_tree,$d); else breadcrumb_set_self(make_string_tempcode(escape_html($d)));

		// Check directory exists
		$fullpath=get_custom_file_base().'/uploads/filedump'.$place;
		if (!file_exists(get_custom_file_base().'/uploads/filedump'.$place))
		{
			if (has_specific_permission(get_member(),'upload_filedump'))
			{
				@mkdir($fullpath,0777) OR warn_exit(do_lang_tempcode('WRITE_ERROR_DIRECTORY',escape_html($fullpath),escape_html(dirname($fullpath))));
				fix_permissions($fullpath,0777);
				sync_file($fullpath);
			}
		}

		// Find all files in the incoming directory
		$handle=opendir(get_custom_file_base().'/uploads/filedump'.$place);
		$i=0;
		$filename=array();
		$description=array();
		$filesize=array();
		$filetime=array();
		$directory=array();
		$deletable=array();
		while (false!==($file=readdir($handle)))
		{
			if (!is_special_file($file))
			{
				$directory[$i]=!is_file(get_custom_file_base().'/uploads/filedump'.$place.$file);
				$filename[$i]=$directory[$i]?($file.'/'):$file;
				if ($directory[$i])
				{
					$filesize[$i]=do_lang_tempcode('NA_EM');
				}
				$dbrows=$GLOBALS['SITE_DB']->query_select('filedump',array('description','the_member'),array('name'=>$file,'path'=>$place));
				if (!array_key_exists(0,$dbrows)) $description[$i]=($directory[$i])?do_lang_tempcode('NA_EM'):do_lang_tempcode('NONE_EM'); else $description[$i]=make_string_tempcode(get_translated_text($dbrows[0]['description']));
				if ($description[$i]->is_empty()) $description[$i]=do_lang_tempcode('NONE_EM');
				$deletable[$i]=(array_key_exists(0,$dbrows) && ($dbrows[0]['the_member']==get_member())) || (has_specific_permission(get_member(),'delete_anything_filedump'));
				if ($directory[$i])
				{
					$size=get_directory_size(get_custom_file_base().'/uploads/filedump'.$place.$file);
					$timestamp=NULL;
				} else
				{
					$size=filesize(get_custom_file_base().'/uploads/filedump'.$place.$file);
					$timestamp=filemtime(get_custom_file_base().'/uploads/filedump'.$place.$file);
				}
				$filesize[$i]=clean_file_size($size);
				$filetime[$i]=is_null($timestamp)?NULL:get_timezoned_date($timestamp);

				$i++;
			}
		}
		closedir($handle);

		if ($i!=0) // If there are some files
		{
			require_code('templates_table_table');
			$header_row=table_table_header_row(array(do_lang_tempcode('FILENAME'),do_lang_tempcode('DESCRIPTION'),do_lang_tempcode('SIZE'),do_lang_tempcode('DATE_TIME'),do_lang_tempcode('ACTIONS')));

			$rows=new ocp_tempcode();
			for ($a=0;$a<$i;$a++)
			{
				if ($directory[$a])
				{
					$link=build_url(array('page'=>'_SELF','place'=>$place.$filename[$a]),'_SELF');
				} else $link=make_string_tempcode(get_custom_base_url().'/uploads/filedump'.str_replace('%2F','/',rawurlencode($place.$filename[$a])));

				if (!$directory[$a])
				{
					if ($deletable[$a])
					{
						$delete_url=build_url(array('page'=>'_SELF','type'=>'ed','file'=>$filename[$a],'place'=>$place),'_SELF');
						$actions=do_template('TABLE_TABLE_ACTION_DELETE_ENTRY',array('_GUID'=>'9b91e485d80417b1664145f9bca5a2f5','NAME'=>$filename[$a],'URL'=>$delete_url));
					} else $actions=new ocp_tempcode();
				}
				else
				{
					$delete_url=build_url(array('page'=>'_SELF','type'=>'ec','file'=>$filename[$a],'place'=>$place),'_SELF');
					$actions=do_template('TABLE_TABLE_ACTION_DELETE_CATEGORY',array('_GUID'=>'0fa7d4090c6195328191399a14799169','NAME'=>$filename[$a],'URL'=>$delete_url));
				}

				$rows->attach(table_table_row(array(
					hyperlink($link,escape_html($filename[$a]),!$directory[$a]),
					escape_html($description[$a]),
					escape_html($filesize[$a]),
					is_null($filetime[$a])?do_lang_tempcode('NA'):make_string_tempcode(escape_html($filetime[$a])),
					$actions
				)));
			}

			$files=do_template('TABLE_TABLE',array('_GUID'=>'1c0a91d47c5fc8a7c2b35c7d9b36132f','HEADER_ROW'=>$header_row,'ROWS'=>$rows));

		}
		else
		{
			$files=new ocp_tempcode();
		}

		// Do a form so people can upload their own stuff
		if (has_specific_permission(get_member(),'upload_filedump'))
		{
			$post_url=build_url(array('page'=>'_SELF','type'=>'ad','uploading'=>1),'_SELF');
			$submit_name=do_lang_tempcode('FILEDUMP_UPLOAD');
			$max=floatval(get_max_file_size());
			$text=new ocp_tempcode();
			if ($max<30.0)
			{
				$config_url=get_upload_limit_config_url();
				$text->attach(do_lang_tempcode(is_null($config_url)?'MAXIMUM_UPLOAD':'MAXIMUM_UPLOAD_STAFF',escape_html(($max>10.0)?integer_format(intval($max)):float_format($max/1024.0/1024.0)),escape_html($config_url)));
			}
			require_code('form_templates');
			$fields=form_input_upload(do_lang_tempcode('UPLOAD'),do_lang_tempcode('_DESCRIPTION_UPLOAD'),'file',true);
			$fields->attach(form_input_line(do_lang_tempcode('DESCRIPTION'),do_lang_tempcode('DESCRIPTION_DESCRIPTION'),'description','',false));
			$hidden=new ocp_tempcode();
			$hidden->attach(form_input_hidden('place',$place));
			handle_max_file_size($hidden);
			$upload_form=do_template('FORM',array('TABINDEX'=>strval(get_form_field_tabindex()),'SKIP_REQUIRED'=>true,'HIDDEN'=>$hidden,'TEXT'=>$text,'FIELDS'=>$fields,'SUBMIT_NAME'=>$submit_name,'URL'=>$post_url));
		} else $upload_form=new ocp_tempcode();

		// Do a form so people can make folders
		if (get_option('is_on_folder_create')=='1')
		{
			$post_url=build_url(array('page'=>'_SELF','type'=>'ac'),'_SELF');
			require_code('form_templates');
			$fields=form_input_line(do_lang_tempcode('NAME'),do_lang_tempcode('DESCRIPTION_NAME'),'name','',true);
			$hidden=form_input_hidden('place',$place);
			$submit_name=do_lang_tempcode('FILEDUMP_CREATE_FOLDER');
			$create_folder_form=do_template('FORM',array('_GUID'=>'043f9b595d3699b7d8cd7f2284cdaf98','TABINDEX'=>strval(get_form_field_tabindex()),'SKIP_REQUIRED'=>true,'SECONDARY_FORM'=>true,'HIDDEN'=>$hidden,'TEXT'=>'','FIELDS'=>$fields,'SUBMIT_NAME'=>$submit_name,'URL'=>$post_url));
		} else
		{
			$create_folder_form=new ocp_tempcode();
		}

		return do_template('FILE_DUMP_SCREEN',array('_GUID'=>'3f49a8277a11f543eff6488622949c84','TITLE'=>$title,'FILES'=>$files,'UPLOAD_FORM'=>$upload_form,'CREATE_FOLDER_FORM'=>$create_folder_form));
	}

	/**
	 * The actualiser for deleting a file.
	 *
	 * @return tempcode	The UI.
	 */
	function module_do_delete_file()
	{
		$title=get_page_title('FILEDUMP_DELETE_FILE');

		$file=filter_naughty(get_param('file'));
		$place=filter_naughty(get_param('place'));

		breadcrumb_set_parents(array(array('_SELF:_SELF',do_lang_tempcode('FILE_DUMP'))));

		if (post_param_integer('confirmed',0)!=1)
		{
			$url=get_self_url();
			$text=do_lang_tempcode('CONFIRM_DELETE',$file);

			breadcrumb_set_self(do_lang_tempcode('CONFIRM'));

			$hidden=build_keep_post_fields();
			$hidden->attach(form_input_hidden('confirmed','1'));

			return do_template('CONFIRM_SCREEN',array('_GUID'=>'19503cf5dc795b9c85d26702b79e3202','TITLE'=>$title,'FIELDS'=>$hidden,'PREVIEW'=>$text,'URL'=>$url));
		}

		$owner=$GLOBALS['SITE_DB']->query_value_null_ok('filedump','the_member',array('name'=>$file,'path'=>$place));
		if (((!is_null($owner)) && ($owner==get_member())) || (has_specific_permission(get_member(),'delete_anything_filedump')))
		{
			$test=$GLOBALS['SITE_DB']->query_value_null_ok('filedump','description',array('name'=>$file,'path'=>$place));
			if (!is_null($test)) delete_lang($test);

			$path=get_custom_file_base().'/uploads/filedump'.$place.$file;
			@unlink($path) OR intelligent_write_error($path);
			sync_file('uploads/filedump/'.$file);
		}
		else access_denied('I_ERROR');

		$return_url=build_url(array('page'=>'_SELF','type'=>'misc','place'=>$place),'_SELF');

		log_it('FILEDUMP_DELETE_FILE',$file,$place);
	
		return redirect_screen($title,$return_url,do_lang_tempcode('SUCCESS'));
	}
	
	/**
	 * The actualiser for deleting a folder.
	 *
	 * @return tempcode	The UI.
	 */
	function module_do_delete_folder()
	{
		$title=get_page_title('FILEDUMP_DELETE_FOLDER');

		$file=filter_naughty(get_param('file'));
		$place=filter_naughty(get_param('place'));
	
		breadcrumb_set_parents(array(array('_SELF:_SELF',do_lang_tempcode('FILE_DUMP'))));

		if (post_param_integer('confirmed',0)!=1)
		{
			$url=get_self_url();
			$text=do_lang_tempcode('CONFIRM_DELETE',$file);

			breadcrumb_set_self(do_lang_tempcode('CONFIRM'));

			$hidden=build_keep_post_fields();
			$hidden->attach(form_input_hidden('confirmed','1'));

			return do_template('CONFIRM_SCREEN',array('_GUID'=>'55cd4cafa3bf8285028da9862508d811','TITLE'=>$title,'FIELDS'=>$hidden,'PREVIEW'=>$text,'URL'=>$url));
		}

		$ret=@rmdir(get_custom_file_base().'/uploads/filedump'.$place.$file);
		sync_file('uploads/filedump/'.$place.$file);

		if ($ret)
		{
			$return_url=build_url(array('page'=>'_SELF','type'=>'misc','place'=>$place),'_SELF');

			log_it('FILEDUMP_DELETE_FOLDER',$file,$place);

			return redirect_screen($title,$return_url,do_lang_tempcode('SUCCESS'));

		} else warn_exit(do_lang_tempcode('FOLDER_DELETE_ERROR'));
	
		return new ocp_tempcode();
	}

	/**
	 * The actualiser for adding a folder.
	 *
	 * @return tempcode	The UI.
	 */
	function module_do_add_folder()
	{
		$title=get_page_title('FILEDUMP_CREATE_FOLDER');
	
		$name=filter_naughty(post_param('name'));
		$place=filter_naughty(post_param('place'));
	
		if (!file_exists(get_custom_file_base().'/uploads/filedump'.$place.$name))
		{
			$path=get_custom_file_base().'/uploads/filedump'.$place.$name;
			@mkdir($path,0777) OR warn_exit(do_lang_tempcode('WRITE_ERROR_DIRECTORY',escape_html($place),escape_html(dirname($place))));
			fix_permissions($path,0777);
			sync_file($path);

			$return_url=build_url(array('page'=>'_SELF','type'=>'misc','place'=>$place),'_SELF');
	
			log_it('FILEDUMP_CREATE_FOLDER',$name,$place);
	
			return redirect_screen($title,$return_url,do_lang_tempcode('SUCCESS'));
		} else
		{
			warn_exit(do_lang_tempcode('FOLDER_OVERWRITE_ERROR'));
		}
	
		return new ocp_tempcode();
	}
	
	/**
	 * The actualiser for uploading a file.
	 *
	 * @return tempcode	The UI.
	 */
	function module_do_upload()
	{
		if (!has_specific_permission(get_member(),'upload_filedump')) access_denied('I_ERROR');
	
		$title=get_page_title('FILEDUMP_UPLOAD');

		if (function_exists('set_time_limit')) @set_time_limit(0); // Slowly uploading a file can trigger time limit, on some servers
	
		$place=filter_naughty(post_param('place'));

		require_code('uploads');
		if ((!is_swf_upload(true)) && ((!array_key_exists('file',$_FILES)) || (!is_uploaded_file($_FILES['file']['tmp_name'])))) // Error
		{
			$attach_name='file';
			$max_size=get_max_file_size();
			if (($_FILES[$attach_name]['error']==1) || ($_FILES[$attach_name]['error']==2))
				warn_exit(do_lang_tempcode('FILE_TOO_BIG',integer_format($max_size)));
			elseif (($_FILES[$attach_name]['error']==3) || ($_FILES[$attach_name]['error']==6) || ($_FILES[$attach_name]['error']==7))
				warn_exit(do_lang_tempcode('ERROR_UPLOADING_'.strval($_FILES[$attach_name]['error'])));
			else warn_exit(do_lang_tempcode('ERROR_UPLOADING'));
		}

		if (get_magic_quotes_gpc()) $_FILES['file']['name']=stripslashes($_FILES['file']['name']);

		if (!has_specific_permission(get_member(),'upload_anything_filedump')) check_extension($_FILES['file']['name']);
		$_FILES['file']['name']=str_replace('.','-',basename($_FILES['file']['name'],'.'.get_file_extension($_FILES['file']['name']))).'.'.get_file_extension($_FILES['file']['name']);

		if (!file_exists(get_custom_file_base().'/uploads/filedump'.$place.$_FILES['file']['name']))
		{
			$max_size=get_max_file_size();
			if ($_FILES['file']['size']>$max_size) warn_exit(do_lang_tempcode('FILE_TOO_BIG',integer_format(intval($max_size))));

			$file=$_FILES['file']['name'];
			$full=get_custom_file_base().'/uploads/filedump'.$place.$_FILES['file']['name'];
			if (is_swf_upload(true))
			{
				@rename($_FILES['file']['tmp_name'],$full) OR warn_exit(do_lang_tempcode('FILE_MOVE_ERROR',escape_html($file),escape_html('uploads/filedump'.$place)));
			} else
			{
				@move_uploaded_file($_FILES['file']['tmp_name'],$full) OR warn_exit(do_lang_tempcode('FILE_MOVE_ERROR',escape_html($file),escape_html('uploads/filedump'.$place)));
			}
			fix_permissions($full);
			sync_file($full);

			$return_url=build_url(array('page'=>'_SELF','place'=>$place),'_SELF');

			$test=$GLOBALS['SITE_DB']->query_value_null_ok('filedump','description',array('name'=>$file,'path'=>$place));
			if (!is_null($test)) delete_lang($test);
			$GLOBALS['SITE_DB']->query_delete('filedump',array('name'=>$file,'path'=>$place),'',1);
			$GLOBALS['SITE_DB']->query_insert('filedump',array('name'=>$file,'path'=>$place,'the_member'=>get_member(),'description'=>insert_lang_comcode(post_param('description'),3)));
	
			log_it('FILEDUMP_UPLOAD',$_FILES['file']['name'],$place);
			if (has_actual_page_access($GLOBALS['FORUM_DRIVER']->get_guest_id(),get_page_name(),get_zone_name()))
				syndicate_described_activity('filedump:FILEDUMP_UPLOAD',$place.'/'.$file,'','','','','','filedump');

			return redirect_screen($title,$return_url,do_lang_tempcode('SUCCESS'));
		} else warn_exit(do_lang_tempcode('OVERWRITE_ERROR'));

		return new ocp_tempcode();
	}

}

