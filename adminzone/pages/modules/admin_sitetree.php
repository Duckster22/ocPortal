<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		page_management
 */

/**
 * Module page class.
 */
class Module_admin_sitetree
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
		$info['version']=4;
		$info['update_require_upgrade']=1;
		$info['locked']=false;
		return $info;
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return array('misc'=>'ZONES','pagewizard'=>'PAGE_WIZARD','site_tree'=>'SITE_TREE_EDITOR','move'=>'MOVE');
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		require_code('zones2');
		require_code('zones3');
		require_lang('zones');

		$type=get_param('type','misc');

		if ($type=='misc') return $this->misc();
		if ($type=='pagewizard') return $this->page_wizard();
		if ($type=='_pagewizard') return $this->_page_wizard();
		if ($type=='site_tree') return $this->site_tree();
		if ($type=='delete') return $this->delete();
		if ($type=='_delete') return $this->_delete();
		if ($type=='__delete') return $this->__delete();
		if ($type=='move') return $this->move();
		if ($type=='_move') return $this->_move();
		//if ($type=='mass_validate') return $this->mass_validate();

		return new ocp_tempcode();
	}

	/**
	 * The do-next manager for before content management. This is intended for exceptional users who cannot use the site-tree editor
	 *
	 * @return tempcode		The UI
	 */
	function misc()
	{
		$GLOBALS['HELPER_PANEL_PIC']='pagepics/sitetreeeditor';
		$GLOBALS['HELPER_PANEL_TUTORIAL']='tut_structure';

		require_code('templates_donext');
		return do_next_manager(get_page_title('PAGES'),comcode_lang_string('DOC_PAGES'),
					array(
						/*	 type							  page	 params													 zone	  */
						array('comcode_page_edit',array('_SELF',array('type'=>'ed'),'_SELF'),do_lang('COMCODE_PAGE_EDIT')),
						array('delete',array('_SELF',array('type'=>'delete'),'_SELF'),do_lang('DELETE_PAGES')),
						array('move',array('_SELF',array('type'=>'move'),'_SELF'),do_lang('MOVE_PAGES')),
					),
					do_lang('PAGES')
		);
	}

	/**
	 * The do-next manager for after content management.
	 *
	 * @param  tempcode		The title (output of get_page_title)
	 * @param  ?ID_TEXT		The name of the page just handled (NULL: none)
	 * @param  ID_TEXT		The name of the zone just handled (blank: none/welcome-zone)
	 * @param  tempcode		The text to show (blank: default)
	 * @return tempcode		The UI
	 */
	function do_next_manager($title,$page,$zone,$completion_text)
	{
		breadcrumb_set_self(do_lang_tempcode('DONE'));

		require_code('zones2');
		require_code('zones3');
		return site_tree_do_next_manager($title,$page,$zone,$completion_text);
	}

	/**
	 * The UI for the site-tree editor.
	 *
	 * @return tempcode		The UI
	 */
	function site_tree()
	{
		$title=get_page_title('SITE_TREE_EDITOR');

		if (!has_js())
		{
			// Send them to the page permissions screen
			$url=build_url(array('page'=>'_SELF','type'=>'page'),'_SELF');
			require_code('site2');
			assign_refresh($url,5.0);
			return do_template('REDIRECT_SCREEN',array('_GUID'=>'a801d4cb71aa0c680eff4469e29cf4c7','URL'=>$url,'TITLE'=>$title,'TEXT'=>do_lang_tempcode('NO_JS_ADVANCED_SCREEN_SITE_TREE')));
		}

		if (count($GLOBALS['SITE_DB']->query_value('zones','COUNT(*)'))>=300) attach_message(do_lang_tempcode('TOO_MUCH_CHOOSE__ALPHABETICAL',escape_html(integer_format(50))),'warn');

		require_javascript('javascript_ajax');
		require_javascript('javascript_more');
		require_javascript('javascript_tree_list');
		require_javascript('javascript_dragdrop');
		require_javascript('javascript_site_tree_editor');

		breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('PAGES'))));

		return do_template('SITE_TREE_EDITOR_SCREEN',array('_GUID'=>'2d42cb71e03d31c855a6b6467d2082d2','TITLE'=>$title));
	}

	/**
	 * The UI for the add-new-page wizard (choose zone, page name).
	 *
	 * @return tempcode		The UI
	 */
	function page_wizard()
	{
		$zone=get_param('zone','site');

		$GLOBALS['HELPER_PANEL_PIC']='pagepics/addpagewizard';
		$GLOBALS['HELPER_PANEL_TUTORIAL']='tut_comcode_pages';

		$title=get_page_title('PAGE_WIZARD_STEP',true,array(integer_format(1),integer_format(3)));

		require_code('form_templates');
		require_code('zones2');
		require_code('zones3');
		$fields=new ocp_tempcode();
		$fields->attach(form_input_list(do_lang_tempcode('ZONE'),do_lang_tempcode('MENU_ZONE'),'zone',nice_get_zones($zone),NULL,true));
		$fields->attach(form_input_codename(do_lang_tempcode('CODENAME'),do_lang_tempcode('DESCRIPTION_PAGE_NAME'),'name','',true));
		$post_url=build_url(array('page'=>'_SELF','type'=>'_pagewizard'),'_SELF',NULL,false,true);
		$submit_name=do_lang_tempcode('PROCEED');

//		breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('PAGES'))));
		breadcrumb_set_self(do_lang_tempcode('PAGE_WIZARD'));

		return do_template('FORM_SCREEN',array('_GUID'=>'4c982255f035472282b5a3740d8df82d','SKIP_VALIDATION'=>true,'TITLE'=>$title,'HIDDEN'=>'','TEXT'=>'','FIELDS'=>$fields,'URL'=>$post_url,'SUBMIT_NAME'=>$submit_name));
	}

	/**
	 * The UI for the add-new-page wizard (choose which menu to add it to, and what title to give it - or choose not to add to a menu).
	 *
	 * @return tempcode		The UI
	 */
	function _page_wizard()
	{
		$GLOBALS['HELPER_PANEL_PIC']='pagepics/addpagewizard';
		$GLOBALS['HELPER_PANEL_TUTORIAL']='tut_comcode_pages';

		$title=get_page_title('PAGE_WIZARD_STEP',true,array(integer_format(2),integer_format(3)));

		$zone=post_param('zone','');

		breadcrumb_set_parents(array(/*array('_SELF:_SELF:misc',do_lang_tempcode('PAGES')),*/array('_SELF:_SELF:pagewizard',do_lang_tempcode('PAGE_WIZARD'))));
		breadcrumb_set_self(do_lang_tempcode('DETAILS'));

		require_code('type_validation');
		if (!is_alphanumeric(str_replace(':','',post_param('name')))) warn_exit(do_lang('BAD_CODENAME'));

		$zones=find_all_zones(false,true);
		$pages=array();
		foreach ($zones as $_zone)
		{
			$pages[$_zone[0]]=find_all_pages_wrap($_zone[0],true);
		}

		require_code('form_templates');
		$rows=$GLOBALS['SITE_DB']->query_select('menu_items',array('DISTINCT i_menu'),NULL,'ORDER BY i_menu');
		$list=new ocp_tempcode();
		$list2=new ocp_tempcode();
		$list->attach(form_input_list_entry(STRING_MAGIC_NULL,false,do_lang_tempcode('NA_EM')));
		$list->attach(form_input_list_entry('',false,'',false,true));

		// See if we can discern nice names for the menus, to help relate them
		foreach ($rows as $row)
		{
			$menu_name=make_string_tempcode(escape_html($row['i_menu']));
			$found=false;
			foreach ($pages as $zone_under=>$under)
			{
				foreach ($under as $filename=>$type)
				{
					if (substr(strtolower($filename),-4)=='.txt')
					{
						$matches=array();
						$path=zone_black_magic_filterer(((substr($type,0,15)=='comcode_custom/')?get_custom_file_base():get_file_base()).'/'.(($zone_under=='')?'':($zone_under.'/')).'pages/'.$type.'/'.$filename);
						if (!file_exists($path))
							$path=zone_black_magic_filterer(get_file_base().'/'.(($zone_under=='')?'':($zone_under.'/')).'pages/'.$type.'/'.$filename);
						$contents='';
						if (file_exists($path))
						{
							$contents.=file_get_contents($path);
						} else
						{
							$fallback=zone_black_magic_filterer(get_file_base().'/'.(($zone_under=='')?'':($zone_under.'/')).'pages/comcode/'.fallback_lang().'/'.$filename);
							if (file_exists($fallback)) $contents.=file_get_contents($fallback);
						}
						if (preg_match('#\[block="'.str_replace('#','\#',preg_quote($row['i_menu'])).'"[^\]]* caption="([^"]*)"[^\]]*\]side_stored_menu\[/block\]#',$contents,$matches)!=0)
						{
							$zone_title=preg_replace('# '.str_replace('#','\#',preg_quote(do_lang('ZONE'))).'$#','',$zones[$zone_under][1]);
							$menu_name=do_lang_tempcode('MENU_FULL_DETAILS',$menu_name,make_string_tempcode(escape_html($matches[1])),make_string_tempcode(escape_html($zone_title)));
							$found=true;
							break 2;
						}
					}
				}
			}

			$selected=(($zone=='forum') && ($row['i_menu']=='forum_features')) || (($zone=='collaboration') && ($row['i_menu']=='collab_website')) || ((($zone=='site') || (($zone=='') && (get_option('collapse_user_zones')=='1'))) && (($row['i_menu']=='site') || ($row['i_menu']=='main_website'))) || (($zone=='') && ($row['i_menu']=='root_website'));

			if ($found)
			{
				$list->attach(form_input_list_entry($row['i_menu'],$selected,$menu_name));
			} else
			{
				$list2->attach(form_input_list_entry($row['i_menu'],false,($row['i_menu']=='zone_menu')?$menu_name:do_lang('MENU_UNUSED',$menu_name)));
			}
		}
		if (!$list2->is_empty())
		{
			$list->attach(form_input_list_entry('',false,'',false,true));
			$list->attach($list2);
		}

		// Now see if there are any menus pending creation
		foreach ($pages as $zone_under=>$under)
		{
			foreach ($under as $filename=>$type)
			{
				if (substr(strtolower($filename),-4)=='.txt')
				{
					$matches=array();
					$path=zone_black_magic_filterer(((substr($type,0,15)=='comcode_custom/')?get_custom_file_base():get_file_base()).'/'.(($zone_under=='')?'':($zone_under.'/')).'pages/'.$type.'/'.$filename);
					if (!file_exists($path))
						$path=zone_black_magic_filterer(get_file_base().'/'.(($zone_under=='')?'':($zone_under.'/')).'pages/'.$type.'/'.$filename);
					$contents='';
					if (file_exists($path))
					{
						$contents.=file_get_contents($path);
					} else
					{
						$fallback=zone_black_magic_filterer(get_file_base().'/'.(($zone_under=='')?'':($zone_under.'/')).'pages/comcode/'.fallback_lang().'/'.$filename);
						if (file_exists($fallback)) $contents.=file_get_contents($fallback);
					}
					$num_matches=preg_match_all('#\[block="([^"]*)"[^\]]* caption="([^"]*)"[^\]]*\]side_stored_menu\[/block\]#',$contents,$matches);
					for ($i=0;$i<$num_matches;$i++)
					{
						$menu_name=$matches[1][$i];

						foreach ($rows as $row)
						{
							if ($row['i_menu']==$menu_name)
								continue 2;
						}

						$zone_title=$zones[$zone_under][1];
						$menu_name=do_lang_tempcode('MENU_FULL_DETAILS',$menu_name,make_string_tempcode(escape_html($matches[2][$i])),make_string_tempcode(escape_html($zone_title)));
						$list->attach(form_input_list_entry($matches[1][$i],$selected,$menu_name));
					}
				}
			}
		}

		$fields=new ocp_tempcode();
		$fields->attach(form_input_list(do_lang_tempcode('MENU'),do_lang_tempcode('MENU_TO_ADD_TO'),'menu',$list,NULL,true));
		$fields->attach(form_input_line(do_lang_tempcode('TITLE'),do_lang_tempcode('DESCRIPTION_MENU_TITLE'),'title',ucwords(str_replace('_',' ',post_param('name'))),true));
		$post_url=build_url(array('page'=>'cms_comcode_pages','type'=>'_ed','simple_add'=>1),get_module_zone('cms_comcode_pages'));
		$submit_name=do_lang_tempcode('PROCEED');
		$hidden=new ocp_tempcode();
		$hidden->attach(form_input_hidden('page_link',$zone.':'.post_param('name')));

		return do_template('FORM_SCREEN',array('_GUID'=>'3281970772c410cf071c422792d1571d','GET'=>true,'SKIP_VALIDATION'=>true,'TITLE'=>$title,'HIDDEN'=>$hidden,'TEXT'=>'','FIELDS'=>$fields,'URL'=>$post_url,'SUBMIT_NAME'=>$submit_name));
	}

	/**
	 * The UI to do mass-XHTML validation.
	 *
	 * @return tempcode		The UI
	 */
	/*function mass_validate()
	{
		require_lang('validation');
		require_code('obfuscate');
		require_code('validation');

		$title=g et_page_title('XHTML_CHECK');

		$zone=post_param('zone','!');
		if ($zone=='!') return $this->choose_zone($title);

		// Find entry points
		$found=array();
		$pages=find_all_pages_wrap($zone);
		foreach ($pages as $page=>$type)
		{
			if (strpos($page,'_tree_made')!==false) continue;

			$entry_points=NULL;
			$entry_point=$zone.':'.$page;
			if (($type=='modules') || ($type=='modules_custom'))
			{
				require_once(zone_black_magic_filterer(get_file_base().'/'.filter_naughty_harsh($zone).'/pages/'.filter_naughty_harsh($type).'/'.filter_naughty_harsh($page).'.php'));

				if (class_exists('Mx_'.filter_naughty_harsh($page)))
				{
					$object=object_factory('Mx_'.filter_naughty_harsh($page));
				} else
				{
					$object=object_factory('Module_'.filter_naughty_harsh($page));
				}
				if (method_exists($object,'get_entry_points'))
				{
					$entry_points=$object->get_entry_points();
					foreach (array_keys($entry_points) as $code)
					{
						if (($code=='logout') || ($code=='concede') || ($code=='admin_phpinfo')) continue;

						$new_entry_point=($code=='!')?$entry_point:($entry_point.':type='.$code);
						$map=array('page'=>$page,'keep_novalidate'=>1,'keep_session'=>get_session_id());
						if ($code!='!') $map['type']=$code;
						$url=build_url($map,$zone);
						$found[$new_entry_point]=$url;
					}
				}
			} elseif (substr($type,0,7)=='comcode')
			{
				$found[$entry_point]=build_url(array('page'=>$page,'keep_novalidate'=>1,'keep_session'=>get_session_id()),$zone);
			}
			if (is_null($entry_points))
			{
				$url=build_url(array('page'=>$page,'keep_novalidate'=>1,'keep_session'=>get_session_id()),$zone);
				$found[$entry_point]=build_url(array('page'=>$page),$zone);
			}
		}

		if (function_exists('set_time_limit')) @set_time_limit(0);

		// Make tempcode
		$contents=new ocp_tempcode();
		foreach ($found as $code=>$url)
		{
			$error=check_xhtml(http_download_file($url->evaluate(),NULL,false,false));
			if (count($error['errors'])!=0)
				$contents->attach(do_template('VALIDATE_CHECK_ERROR',array('_GUID'=>'a82ea6421827305c8c9579d79597fc30','URL'=>$url,'POINT'=>$code)));

			echo ((count($error['errors'])!=0)?'! ':' ').$code.'<br />';
			if (count($error['errors'])!=0) print_r($error['errors']);
		}
		if ($contents->is_empty()) return inform_screen($title,do_lang_tempcode('NO_ENTRIES'));

		return do_template('VALIDATE_CHECK',array('_GUID'=>'aca278de6738c2b5f840631234e44c7b','TITLE'=>$title,'CONTENTS'=>$contents));
	}*/

	/**
	 * The UI to choose a zone.
	 *
	 * @param  tempcode		The title for the "choose a zone" page
	 * @param  ?string		Zone to not allow the selection of (NULL: none to filter out)
	 * @return tempcode		The UI
	 */
	function choose_zone($title,$no_go=NULL)
	{
		$fields=new ocp_tempcode();
		require_code('form_templates');

		require_code('zones2');
		require_code('zones3');
		$zones=nice_get_zones(NULL,is_null($no_go)?NULL:array($no_go));
		$fields->attach(form_input_list(do_lang_tempcode('ZONE'),'','zone',$zones,NULL,true));

		$post_url=get_self_url(false,false,NULL,false,true);

		breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('PAGES'))));

		return do_template('FORM_SCREEN',array('_GUID'=>'df58e16290a783d24f9f81fc9227e6ff','GET'=>true,'SKIP_VALIDATION'=>true,'HIDDEN'=>'','SUBMIT_NAME'=>do_lang_tempcode('CHOOSE'),'TITLE'=>$title,'FIELDS'=>$fields,'URL'=>$post_url,'TEXT'=>''));
	}

	/**
	 * The UI to delete a page.
	 *
	 * @return tempcode		The UI
	 */
	function delete()
	{
		if (get_file_base()!=get_custom_file_base()) warn_exit(do_lang_tempcode('SHARED_INSTALL_PROHIBIT'));

		$GLOBALS['HELPER_PANEL_PIC']='pagepics/deletepage';

		$title=get_page_title('DELETE_PAGES');

		$zone=get_param('zone',NULL);
		if (is_null($zone)) return $this->choose_zone($title);

		require_code('form_templates');
		require_code('zones2');

		$post_url=build_url(array('page'=>'_SELF','type'=>'_delete'),'_SELF');
		$submit_name=do_lang_tempcode('DELETE_PAGES');

		$fields=new ocp_tempcode();
		$pages=find_all_pages_wrap($zone);
		foreach ($pages as $page=>$type)
		{
			if (substr($type,0,7)=='modules')
			{
				$info=extract_module_info(zone_black_magic_filterer(get_file_base().'/'.$zone.(($zone=='')?'':'/').'pages/'.$type.'/'.$page.'.php'));
				if ((!is_null($info)) && (array_key_exists('locked',$info)) && ($info['locked']==1)) continue;
			}
			$fields->attach(form_input_tick($zone.':'.$page,do_lang_tempcode('_TYPE',escape_html($type)),'page__'.$page,false));
		}

		$hidden=form_input_hidden('zone',$zone);

		breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('PAGES'))));

		return do_template('FORM_SCREEN',array('_GUID'=>'a7310327788808856f1da4351f116b92','SKIP_VALIDATION'=>true,'FIELDS'=>$fields,'TITLE'=>$title,'SUBMIT_NAME'=>$submit_name,'TEXT'=>paragraph(do_lang_tempcode('SELECT_PAGES_DELETE')),'URL'=>$post_url,'HIDDEN'=>$hidden));
	}

	/**
	 * The UI to confirm deletion of a page.
	 *
	 * @return tempcode		The UI
	 */
	function _delete()
	{
		$GLOBALS['HELPER_PANEL_PIC']='pagepics/deletepage';

		$hidden=new ocp_tempcode();

		$file=new ocp_tempcode();
		$zone=either_param('zone');
		$pages=array();
		require_code('site');
		foreach ($_REQUEST as $key=>$val)
		{
			if ((substr($key,0,6)=='page__') && ($val==='1'))
			{
				$page=substr($key,6);
				$page_details=_request_page($page,$zone,NULL,NULL,true);
				$pages[$page]=strtolower($page_details[0]);
			}
		}
		foreach ($pages as $page=>$type)
		{
			if (is_integer($page)) $page=strval($page);

			if (either_param_integer('page__'.$page,0)==1)
			{
				$hidden->attach(form_input_hidden('page__'.$page,'1'));

				if (!$file->is_empty()) $file->attach(do_lang_tempcode('LIST_SEP'));
				$file->attach(do_lang_tempcode('ZONE_WRITE',escape_html($zone),escape_html($page)));

				if ((get_file_base()!=get_custom_file_base()) && ($type!='comcode_custom'))
				{
					warn_exit(do_lang_tempcode('SHARED_INSTALL_PROHIBIT'));
				}
			}
		}

		$title=get_page_title('DELETE_PAGES');
		$url=build_url(array('page'=>'_SELF','type'=>'__delete'),'_SELF');
		$text=do_lang_tempcode('CONFIRM_DELETE',escape_html($file));

		breadcrumb_set_self(do_lang_tempcode('CONFIRM'));
		breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('PAGES')),array('_SELF:_SELF:delete',do_lang_tempcode('DELETE_PAGES'))));

		$hidden->attach(form_input_hidden('zone',$zone));

		return do_template('YESNO_SCREEN',array('_GUID'=>'f732bb10942759c6ca5771d2d446c333','TITLE'=>$title,'HIDDEN'=>$hidden,'TEXT'=>$text,'URL'=>$url));
	}

	/**
	 * The actualiser to delete a page.
	 *
	 * @return tempcode		The UI
	 */
	function __delete()
	{
		$GLOBALS['HELPER_PANEL_PIC']='pagepics/deletepage';

		$zone=post_param('zone',NULL);

		$afm_needed=false;
		$pages=find_all_pages_wrap($zone);
		foreach ($pages as $page=>$type)
		{
			if (is_integer($page)) $page=strval($page);

			if (post_param_integer('page__'.$page,0)==1)
			{
				if ((get_file_base()!=get_custom_file_base()) && (strpos($type,'comcode_custom')!==false))
				{
					warn_exit(do_lang_tempcode('SHARED_INSTALL_PROHIBIT'));
				}

				if ($type!='comcode_custom') $afm_needed=true;
			}
		}	

		if ($afm_needed)
		{
			require_code('abstract_file_manager');
			force_have_afm_details();
		}

		foreach ($pages as $page=>$type)
		{
			if (is_integer($page)) $page=strval($page);

			if (post_param_integer('page__'.$page,0)==1)
			{
				if (substr($type,0,7)=='modules') $_page=$page.'.php';
				elseif (substr($type,0,7)=='comcode') $_page=$page.'.txt';
				elseif (substr($type,0,4)=='html') $_page=$page.'.htm';

				$GLOBALS['SITE_DB']->query_delete('menu_items',array('i_url'=>$zone.':'.$page));

				if ((substr($type,0,7)=='comcode') || (substr($type,0,4)=='html'))
				{
					$type_shortened=preg_replace('#/.+#','',$type);

					if ((substr($type,0,7)=='comcode') && (get_option('store_revisions')=='1'))
					{
						$time=time();
						$fullpath=zone_black_magic_filterer(((strpos($type,'comcode/')!==false)?get_file_base():get_custom_file_base()).'/'.filter_naughty($zone).(($zone!='')?'/':'').'pages/'.filter_naughty($type).'/'.$_page);
						$bs_path=zone_black_magic_filterer(str_replace('/comcode/','/comcode_custom/',$fullpath).'.'.strval($time));
						@copy($fullpath,$bs_path) OR intelligent_write_error($fullpath);
						sync_file($bs_path);
						fix_permissions($bs_path);
					}

					$langs=find_all_langs(true);
					foreach (array_keys($langs) as $lang)
					{
						$_path=zone_black_magic_filterer(filter_naughty($zone).(($zone!='')?'/':'').'pages/'.filter_naughty($type_shortened).'/'.$lang.'/'.$_page,true);
						$path=((strpos($type,'comcode/')!==false)?get_file_base():get_custom_file_base()).'/'.$_path;
						if (file_exists($path))
						{
							if ($afm_needed)
							{
								afm_delete_file($_path);
							} else
							{
								unlink(get_custom_file_base().'/'.$_path);
							}
						}
					}

					if (substr($type,0,7)=='comcode')
					{
						require_code('attachments2');
						require_code('attachments3');
						delete_comcode_attachments('comcode_page',$zone.':'.$page);
						$GLOBALS['SITE_DB']->query_delete('cached_comcode_pages',array('the_page'=>$page,'the_zone'=>$zone));
						$GLOBALS['SITE_DB']->query_delete('comcode_pages',array('the_page'=>$page,'the_zone'=>$zone));
						persistant_cache_empty();
						decache('main_comcode_page_children');

						require_code('seo2');
						seo_meta_erase_storage('comcode_page',$zone.':'.$page);
					}
				} else
				{
					$_path=zone_black_magic_filterer(filter_naughty($zone).(($zone!='')?'/':'').'pages/'.filter_naughty($type).'/'.$_page,true);
					$path=((strpos($type,'_custom')===false)?get_file_base():get_custom_file_base()).'/'.$_path;
					if (file_exists($path))
					{
						if ($afm_needed)
						{
							afm_delete_file($_path);
						} else
						{
							unlink(get_custom_file_base().'/'.$_path);
						}
					}
				}

				$GLOBALS['SITE_DB']->query_delete('https_pages',array('https_page_name'=>$page),'',1);

				log_it('DELETE_PAGES',$page);
			}
		}

		persistant_cache_empty();

		decache('main_sitemap');

		$title=get_page_title('DELETE_PAGES');

		breadcrumb_set_self(do_lang_tempcode('DONE'));
		breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('PAGES')),array('_SELF:_SELF:delete',do_lang_tempcode('DELETE_PAGES'))));

		return $this->do_next_manager($title,NULL,$zone,new ocp_tempcode());
	}

	/**
	 * The UI to move a page.
	 *
	 * @return tempcode		The UI
	 */
	function move()
	{
		if (get_file_base()!=get_custom_file_base()) warn_exit(do_lang_tempcode('SHARED_INSTALL_PROHIBIT'));

		$GLOBALS['HELPER_PANEL_PIC']='pagepics/move';
		$GLOBALS['HELPER_PANEL_TUTORIAL']='tut_structure';

		$title=get_page_title('MOVE_PAGES');

		$zone=get_param('zone',NULL);
		if (is_null($zone)) return $this->choose_zone($title);

		require_code('form_templates');

		$post_url=build_url(array('page'=>'_SELF','type'=>'_move'),'_SELF');
		$submit_name=do_lang_tempcode('MOVE_PAGES');

		$fields=new ocp_tempcode();
		$pages=find_all_pages_wrap($zone);
		foreach ($pages as $page=>$type)
		{
			// We can't move admin modules
			if (($zone=='adminzone') && (substr($page,0,6)=='admin_') && (substr($type,0,6)=='module')) continue;

			// We can't move modules we've hard-optimised to be in a certain place
			global $MODULES_ZONES_DEFAULT;
			if (array_key_exists($page,$MODULES_ZONES_DEFAULT)) continue;

			$fields->attach(form_input_tick($page,do_lang_tempcode('_TYPE',escape_html($type)),'page__'.$page,false));
		}
		require_code('zones2');
		require_code('zones3');
		$zones=nice_get_zones();
		$fields->attach(form_input_list(do_lang_tempcode('DESTINATION'),do_lang_tempcode('DESCRIPTION_DESTINATION_ZONE'),'destination_zone',$zones,NULL,true));

		$hidden=form_input_hidden('zone',$zone);

		breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('PAGES'))));

		return do_template('FORM_SCREEN',array('_GUID'=>'79869440ede2482fe51839df04b9d880','SKIP_VALIDATION'=>true,'FIELDS'=>$fields,'TITLE'=>$title,'SUBMIT_NAME'=>$submit_name,'TEXT'=>paragraph(do_lang_tempcode('SELECT_PAGES_MOVE')),'URL'=>$post_url,'HIDDEN'=>$hidden));
	}

	/**
	 * The actualiser to move a page.
	 *
	 * @return tempcode		The UI
	 */
	function _move()
	{
		$title=get_page_title('MOVE_PAGES');

		if (get_file_base()!=get_custom_file_base()) warn_exit(do_lang_tempcode('SHARED_INSTALL_PROHIBIT'));

		$GLOBALS['HELPER_PANEL_PIC']='pagepics/move';
		$GLOBALS['HELPER_PANEL_TUTORIAL']='tut_structure';

		$zone=post_param('zone',NULL);

		if (is_null($zone))
		{
			$post_url=build_url(array('page'=>'_SELF','type'=>get_param('type')),'_SELF',NULL,true);
			$hidden=build_keep_form_fields('',true);

			return do_template('YESNO_SCREEN',array('_GUID'=>'c6e872cc62bdc7cf1c5157fbfdb2dfd6','TITLE'=>$title,'TEXT'=>do_lang_tempcode('Q_SURE'),'URL'=>$post_url,'HIDDEN'=>$hidden));
		}

		$new_zone=post_param('destination_zone');
		if (substr($new_zone,-1)==':') $new_zone=substr($new_zone,0,strlen($new_zone)-1);

		//$pages=find_all_pages_wrap($zone);
		$pages=array();
		require_code('site');
		foreach ($_POST as $key=>$val)
		{
			if ((substr($key,0,6)=='page__') && ($val==='1'))
			{
				$page=substr($key,6);
				$page_details=_request_page($page,$zone,NULL,NULL,true);
				$pages[$page]=strtolower($page_details[0]);
				if (array_key_exists(3,$page_details)) $pages[$page].='/'.$page_details[3];
			}
		}

		$afm_needed=false;
		foreach ($pages as $page=>$type)
		{
			if (post_param_integer('page__'.$page,0)==1)
			{
				if ($type!='comcode_custom') $afm_needed=true;
			}
		}

		if ($afm_needed)
		{
			require_code('abstract_file_manager');
			force_have_afm_details();
		}
		$cannot_move=new ocp_tempcode();
		foreach ($pages as $page=>$type)
		{
			if (!is_string($page)) $page=strval($page);

			if (post_param_integer('page__'.$page,0)==1)
			{
				if (substr($type,0,7)=='modules') $_page=$page.'.php';
				elseif (substr($type,0,7)=='comcode') $_page=$page.'.txt';
				elseif (substr($type,0,4)=='html') $_page=$page.'.htm';
				if (file_exists(zone_black_magic_filterer(get_custom_file_base().'/'.filter_naughty($new_zone).(($new_zone!='')?'/':'').'pages/'.filter_naughty($type).'/'.$_page)))
				{
					if (!$cannot_move->is_empty()) $cannot_move->attach(do_lang_tempcode('LIST_SEP'));
					$cannot_move->attach(do_lang_tempcode('PAGE_WRITE',escape_html($page)));
					continue;
				}
			}
		}

		$moved_something=NULL;
		foreach ($pages as $page=>$type)
		{
			if (!is_string($page)) $page=strval($page);

			if (post_param_integer('page__'.$page,0)==1)
			{
				$moved_something=$page;

				if (substr($type,0,7)=='modules') $_page=$page.'.php';
				elseif (substr($type,0,7)=='comcode') $_page=$page.'.txt';
				elseif (substr($type,0,4)=='html') $_page=$page.'.htm';
				if (file_exists(zone_black_magic_filterer(get_custom_file_base().'/'.filter_naughty($new_zone).(($new_zone!='')?'/':'').'pages/'.filter_naughty($type).'/'.$_page)))
				{
					continue;
				}

				if (file_exists(zone_black_magic_filterer(get_custom_file_base().'/'.filter_naughty($zone).(($zone!='')?'/':'').'pages/'.filter_naughty($type).'/'.$_page)))
				{
					if ($afm_needed)
					{
						afm_move(zone_black_magic_filterer(filter_naughty($zone).(($zone!='')?'/':'').'pages/'.filter_naughty($type).'/'.$_page,true),
									zone_black_magic_filterer(filter_naughty($new_zone).(($new_zone!='')?'/':'').'pages/'.filter_naughty($type).'/'.$_page,true));
					} else
					{
						rename(zone_black_magic_filterer(get_custom_file_base().'/'.filter_naughty($zone).(($zone!='')?'/':'').'pages/'.filter_naughty($type).'/'.$_page),
									zone_black_magic_filterer(get_custom_file_base().'/'.filter_naughty($new_zone).(($new_zone!='')?'/':'').'pages/'.filter_naughty($type).'/'.$_page));
					}
				}

				// If a non-overridden one is there too, need to move that too
				if ((strpos($type,'_custom')!==false) && (file_exists(zone_black_magic_filterer(get_custom_file_base().'/'.filter_naughty($zone).(($zone!='')?'/':'').'pages/'.filter_naughty(str_replace('_custom','',$type)).'/'.$_page))) && (!file_exists(zone_black_magic_filterer(get_custom_file_base().'/'.filter_naughty($new_zone).(($new_zone!='')?'/':'').'pages/'.filter_naughty(str_replace('_custom','',$type)).'/'.$_page))))
				{
					if ($afm_needed)
					{
						afm_move(zone_black_magic_filterer(filter_naughty($zone).(($zone!='')?'/':'').'pages/'.filter_naughty(str_replace('_custom','',$type)).'/'.$_page,true),
									zone_black_magic_filterer(filter_naughty($new_zone).(($new_zone!='')?'/':'').'pages/'.filter_naughty(str_replace('_custom','',$type)).'/'.$_page,true));
					} else
					{
						rename(zone_black_magic_filterer(get_custom_file_base().'/'.filter_naughty($zone).(($zone!='')?'/':'').'pages/'.filter_naughty(str_replace('_custom','',$type)).'/'.$_page),
									zone_black_magic_filterer(get_custom_file_base().'/'.filter_naughty($new_zone).(($new_zone!='')?'/':'').'pages/'.filter_naughty(str_replace('_custom','',$type)).'/'.$_page));
					}
				}

				log_it('MOVE_PAGES',$page);
			}
		}
		if (is_null($moved_something)) warn_exit(do_lang_tempcode('NOTHING_SELECTED'));

		persistant_cache_empty();

		require_lang('addons');
		if ($cannot_move->is_empty()) $message=do_lang_tempcode('SUCCESS'); else $message=do_lang_tempcode('WOULD_NOT_OVERWRITE_BUT_SUCCESS',$cannot_move);

		breadcrumb_set_self(do_lang_tempcode('DONE'));
		breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('PAGES')),array('_SELF:_SELF:move',do_lang_tempcode('MOVE_PAGES'))));

		decache('main_sitemap');

		if (has_js())
		{
			return inform_screen($title,$message); // Came from site-tree editor, so want to just close this window when done
		}
		return $this->do_next_manager($title,$moved_something,$new_zone,new ocp_tempcode());
	}

}


