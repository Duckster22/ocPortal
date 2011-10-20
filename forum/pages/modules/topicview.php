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
 * @package		ocf_forum
 */

/**
 * Module page class.
 */
class Module_topicview
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
		$info['version']=2;
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
		return array();
	}
	
	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		if (get_forum_type()!='ocf') warn_exit(do_lang_tempcode('NO_OCF')); else ocf_require_all_forum_stuff();
		require_code('ocf_topicview');
		require_css('ocf');
	
		global $NON_CANONICAL_PARAMS;
		$NON_CANONICAL_PARAMS[]='max';
		$NON_CANONICAL_PARAMS[]='start';

		$start=get_param_integer('start',0);
		$default_max=intval(get_option('forum_posts_per_page'));
		$max=get_param_integer('max',$default_max);
		if ($max==0) $max=$default_max;
		$first_unread_id=-1;

		global $NON_CANONICAL_PARAMS;
		foreach (array_keys($_GET) as $key)
			if (substr($key,0,3)=='kfs') $NON_CANONICAL_PARAMS[]=$key;

		$type=get_param('type','misc');

		$id=get_param_integer('id',NULL);
		if ((is_guest()) && (is_null($id))) access_denied('NOT_AS_GUEST');

		if ($type=='findpost')
		{
			$post_id=get_param_integer('id');
			$redirect=find_post_id_url($post_id);
			require_code('site2');
			assign_refresh($redirect,0.0);
			return do_template('REDIRECT_SCREEN',array('_GUID'=>'76e6d34c20a4f5284119827e41c7752f','URL'=>$redirect,'TITLE'=>get_page_title('VIEW_TOPIC'),'TEXT'=>do_lang_tempcode('REDIRECTING')));
		} else
		{
			if ($type=='first_unread')
			{
				$redirect=find_first_unread_url($id);
				require_code('site2');
				assign_refresh($redirect,0.0);
				return do_template('REDIRECT_SCREEN',array('_GUID'=>'12c5d16f60e8c4df03536d9a7a932528','URL'=>$redirect,'TITLE'=>get_page_title('VIEW_TOPIC'),'TEXT'=>do_lang_tempcode('REDIRECTING')));
			}
		}

		if (!is_null($id))
			$GLOBALS['FEED_URL']=find_script('backend').'?mode=ocf_topicview&filter='.strval($id);

		$view_poll_results=get_param_integer('view_poll_results',0);

		// Mark as read
		if (!is_null($id))
		{
			if (!is_guest())
			{
				$GLOBALS['FORUM_DB']->query_delete('f_read_logs',array('l_member_id'=>get_member(),'l_topic_id'=>$id),'',1);
				$GLOBALS['FORUM_DB']->query_insert('f_read_logs',array('l_member_id'=>get_member(),'l_topic_id'=>$id,'l_time'=>time()),false,true); // race condition
			}
			$GLOBALS['FORUM_DB']->query('UPDATE '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_topics SET t_num_views=(t_num_views+1) WHERE id='.strval((integer)$id));
		}

		// Poster detail hooks
		$hooks=find_all_hooks('modules','topicview');
		$hook_objects=array();
		foreach (array_keys($hooks) as $hook)
		{
			require_code('hooks/modules/topicview/'.filter_naughty_harsh($hook));
			$object=object_factory('Hook_'.filter_naughty_harsh($hook),true);
			if (is_null($object)) continue;
			$hook_objects[$hook]=$object;
		}

		// Read in
		$topic_info=ocf_read_in_topic($id,$start,$max,$view_poll_results==1);
		global $SEO_TITLE;
		$SEO_TITLE=do_lang('_VIEW_TOPIC',$topic_info['title']);
		$posts=new ocp_tempcode();
		$replied=false;
		$forum_id=$topic_info['forum_id'];
		if (is_null($forum_id))
		{
			decache('side_ocf_personal_topics',array(get_member()));
			decache('_new_pp',array(get_member()));
		}
		$second_poster=$topic_info['first_poster'];
		$may_reply=(array_key_exists('may_reply',$topic_info)) && (($topic_info['is_open']) || (array_key_exists('may_post_closed',$topic_info)));
		foreach ($topic_info['posts'] as $array_id=>$_postdetails)
		{
			if ($array_id==0)
			{
				$description=$topic_info['description'];
			} else $description=NULL;

			if ($_postdetails['poster']==get_member()) $replied=true;

			if (($array_id==1 && $start==0) || ($array_id==0 && $start!=0)) $second_poster=$_postdetails['poster'];

			if (array_key_exists('last_edit_time',$_postdetails))
			{
				$last_edited=do_template('OCF_TOPIC_POST_LAST_EDITED',array('_GUID'=>'77a28e8bc3cf2ec2211aafdb5ba192bf','LAST_EDIT_DATE_RAW'=>is_null($_postdetails['last_edit_time'])?'':strval($_postdetails['last_edit_time']),'LAST_EDIT_DATE'=>$_postdetails['last_edit_time_string'],'LAST_EDIT_PROFILE_URL'=>$GLOBALS['FORUM_DRIVER']->member_profile_link($_postdetails['last_edit_by'],false,true),'LAST_EDIT_USERNAME'=>$_postdetails['last_edit_by_username']));
			} else $last_edited=new ocp_tempcode();
			$last_edited_raw=(array_key_exists('last_edit_time',$_postdetails))?(is_null($_postdetails['last_edit_time'])?'':strval($_postdetails['last_edit_time'])):'0';

			// Post buttons
			$buttons=new ocp_tempcode();
			if (array_key_exists('may_delete',$_postdetails))
			{
				$map=array('page'=>'topics','type'=>'delete_post','id'=>$_postdetails['id']);
				$test=get_param_integer('kfs'.(is_null($forum_id)?'':strval($forum_id)),-1);
				if (($test!=-1) && ($test!=0)) $map['kfs'.(is_null($forum_id)?'':strval($forum_id))]=$test;
				$delete_url=build_url($map,get_module_zone('topics'));
				$_title=do_lang_tempcode('DELETE_POST');
				$_title->attach(do_lang_tempcode('ID_NUM',strval($_postdetails['id'])));
				$buttons->attach(do_template('SCREEN_ITEM_BUTTON',array('_GUID'=>'8bf6d098ddc217eef75718464dc03d41','REL'=>'delete','IMMEDIATE'=>false,'IMG'=>'delete','TITLE'=>$_title,'URL'=>$delete_url)));
			}
			if (array_key_exists('may_edit',$_postdetails))
			{
				$map=array('page'=>'topics','type'=>'edit_post','id'=>$_postdetails['id']);
				$test=get_param_integer('kfs'.(is_null($forum_id)?'':strval($forum_id)),-1);
				if (($test!=-1) && ($test!=0)) $map['kfs'.(is_null($forum_id)?'':strval($forum_id))]=$test;
				$edit_url=build_url($map,get_module_zone('topics'));
				$_title=do_lang_tempcode('EDIT_POST');
				$_title->attach(do_lang_tempcode('ID_NUM',strval($_postdetails['id'])));
				$buttons->attach(do_template('SCREEN_ITEM_BUTTON',array('_GUID'=>'f341cfc94b3d705437d43e89f572bff6','REL'=>'edit','IMMEDIATE'=>false,'IMG'=>'edit','TITLE'=>$_title,'URL'=>$edit_url)));
			}
			if ((array_key_exists('may_report_posts',$topic_info)) && (addon_installed('ocf_reported_posts')) && (is_null(get_bot_type())))
			{
				$action_url=build_url(array('page'=>'topics','type'=>'report_post','id'=>$_postdetails['id']),get_module_zone('topics'));
				$_title=do_lang_tempcode('REPORT_POST');
				$_title->attach(do_lang_tempcode('ID_NUM',strval($_postdetails['id'])));
				$buttons->attach(do_template('SCREEN_ITEM_BUTTON',array('_GUID'=>'f81cbe84f524b4ed9e089c6e89a7c717','REL'=>'report','IMMEDIATE'=>false,'IMG'=>'report_post','TITLE'=>$_title,'URL'=>$action_url)));
			}
			if ((array_key_exists('may_warn_members',$topic_info)) && ($_postdetails['poster']!=$GLOBALS['OCF_DRIVER']->get_guest_id()) && (addon_installed('ocf_warnings')))
			{
				$redir_url=get_self_url(true);
				$redir_url.='#post_'.strval($_postdetails['id']);
				$action_url=build_url(array('page'=>'warnings','type'=>'ad','id'=>$_postdetails['poster'],'post_id'=>$_postdetails['id'],'redirect'=>$redir_url),get_module_zone('warnings'));
				$_title=do_lang_tempcode('WARN_MEMBER');
				$_title->attach(do_lang_tempcode('ID_NUM',strval($_postdetails['id'])));
				$buttons->attach(do_template('SCREEN_ITEM_BUTTON',array('_GUID'=>'2698c51b06a72773ac7135bbfe791318','IMMEDIATE'=>false,'IMG'=>'punish','TITLE'=>$_title,'URL'=>$action_url)));
			}
			if ((array_key_exists('may_validate_posts',$topic_info)) && ((($topic_info['validated']==0) && ($array_id==0)) || ($_postdetails['validated']==0)))
			{
				$map=array('page'=>'topics','type'=>'validate_post','id'=>$_postdetails['id']);
				$test=get_param_integer('kfs'.(is_null($forum_id)?'':strval($forum_id)),-1);
				if (($test!=-1) && ($test!=0)) $map['kfs'.(is_null($forum_id)?'':strval($forum_id))]=$test;
				$action_url=build_url($map,get_module_zone('topics'));
				$_title=do_lang_tempcode('VALIDATE_POST');
				$_title->attach(do_lang_tempcode('ID_NUM',strval($_postdetails['id'])));
				$buttons->attach(do_template('SCREEN_ITEM_BUTTON',array('_GUID'=>'712fdaee35f378e37b007f3a73246690','REL'=>'validate','IMMEDIATE'=>true,'IMG'=>'validate','TITLE'=>$_title,'URL'=>$action_url)));
			}
			if (($may_reply) && (is_null(get_bot_type())))
			{
				$map=array('page'=>'topics','type'=>'new_post','id'=>$_postdetails['topic_id'],'quote'=>$_postdetails['id']);
				if (array_key_exists('intended_solely_for',$_postdetails))
				{
					$map['intended_solely_for']=$_postdetails['poster'];
				}
				$test=get_param_integer('kfs'.(is_null($forum_id)?'':strval($forum_id)),-1);
				if (($test!=-1) && ($test!=0)) $map['kfs'.(is_null($forum_id)?'':strval($forum_id))]=$test;
				$action_url=build_url($map,get_module_zone('topics'));
				$_title=do_lang_tempcode('QUOTE_POST');
				$_title->attach(do_lang_tempcode('ID_NUM',strval($_postdetails['id'])));
				$javascript=NULL;

				if ((array_key_exists('post_original',$_postdetails)) && (!is_null($_postdetails['post_original'])) && (!array_key_exists('intended_solely_for',$map)))
				{
					$javascript='var post=document.getElementById(\'post\'); if (!post) return true; var y=findPosY(post); if (y==0) return true; post.value+=((post.value==\'\')?\'\':\'\n\n\')+\'[quote="'.addslashes($_postdetails['poster_username']).'"]'.str_replace(chr(10),'\n',addslashes($_postdetails['post_original'])).'[/quote]\n\'; window.scrollTo(0,y); return false;';
				}
				$buttons->attach(do_template('SCREEN_ITEM_BUTTON',array('_GUID'=>'fc13d12cfe58324d78befec29a663b4f','REL'=>'add reply','IMMEDIATE'=>false,'IMG'=>'quote','TITLE'=>$_title,'URL'=>$action_url,'JAVASCRIPT'=>$javascript)));
			}
			require_code('ocf_members2');
			if ((array_key_exists('may_pt_members',$topic_info)) && ($may_reply) && ($_postdetails['poster']!=$GLOBALS['OCF_DRIVER']->get_guest_id()) && (ocf_may_whisper($_postdetails['poster'])) && (get_option('overt_whisper_suggestion')=='1'))
			{
				$whisper_type=(get_value('no_inline_pp_advertise')=='1')?'new_pt':'whisper';
				$action_url=build_url(array('page'=>'topics','type'=>$whisper_type,'id'=>$_postdetails['topic_id'],'quote'=>$_postdetails['id'],'intended_solely_for'=>$_postdetails['poster']),get_module_zone('topics'));
				$_title=do_lang_tempcode('WHISPER');
				$_title->attach(do_lang_tempcode('ID_NUM',strval($_postdetails['id'])));
				$buttons->attach(do_template('SCREEN_ITEM_BUTTON',array('_GUID'=>'fb1c74bae9c553dc160ade85adf289b5','REL'=>'add reply','IMMEDIATE'=>false,'IMG'=>'whisper','TITLE'=>$_title,'URL'=>$action_url)));
			}
			if ((has_specific_permission(get_member(),'view_content_history')) && ($_postdetails['has_history']))
			{
				$action_url=build_url(array('page'=>'admin_ocf_history','type'=>'misc','post_id'=>$_postdetails['id']),'adminzone');
				$_title=do_lang_tempcode('POST_HISTORY');
				$_title->attach(do_lang_tempcode('ID_NUM',strval($_postdetails['id'])));
				$buttons->attach(do_template('SCREEN_ITEM_BUTTON',array('_GUID'=>'a66f98cb4d56bd0d64e9ecc44d357141','REL'=>'history','IMMEDIATE'=>false,'IMG'=>'history','TITLE'=>$_title,'URL'=>$action_url)));
			}
			if ((addon_installed('points')) && (!is_guest()) && (!is_guest($_postdetails['poster'])))
			{
				$action_url=build_url(array('page'=>'points','type'=>'member','id'=>$_postdetails['poster']),get_module_zone('points'));
				$_title=do_lang_tempcode('POINTS_THANKS');
				$buttons->attach(do_template('SCREEN_ITEM_BUTTON',array('_GUID'=>'a66f98cb4d56bd0d64e9ecc44d357141','IMMEDIATE'=>false,'IMG'=>'points','TITLE'=>$_title,'URL'=>$action_url)));
			}

			// Avatar
			if ((array_key_exists('poster_avatar',$_postdetails)) && ($_postdetails['poster_avatar']!=''))
			{
				$post_avatar=do_template('OCF_TOPIC_POST_AVATAR',array('_GUID'=>'d647ada9c11d56eedc0ff7894d33e83c','AVATAR'=>$_postdetails['poster_avatar']));
			} else $post_avatar=new ocp_tempcode();
	
			// Rank images
			$rank_images=new ocp_tempcode();
			$posters_groups=$GLOBALS['FORUM_DRIVER']->get_members_groups($_postdetails['poster'],true);
			foreach ($posters_groups as $group)
			{
				$rank_image=ocf_get_group_property($group,'rank_image');
				$group_leader=ocf_get_group_property($group,'group_leader');
				$group_name=ocf_get_group_name($group);
				$rank_image_pri_only=ocf_get_group_property($group,'rank_image_pri_only');
				if (($rank_image!='') && (($rank_image_pri_only==0) || ($group==$GLOBALS['FORUM_DRIVER']->get_member_row_field($_postdetails['poster'],'m_primary_group'))))
				{
					$rank_images->attach(do_template('OCF_RANK_IMAGE',array('_GUID'=>'0ff7855482b901be95591964d4212c44','GROUP_NAME'=>$group_name,'USERNAME'=>$GLOBALS['FORUM_DRIVER']->get_username($_postdetails['poster']),'IMG'=>$rank_image,'IS_LEADER'=>$group_leader==$_postdetails['poster'])));
				}
			}

			// Poster details
			if (!is_guest($_postdetails['poster']))
			{
				require_code('ocf_members2');
				$poster_details=ocf_show_member_box($_postdetails,false,$hooks,$hook_objects,false);
			} else
			{
				$custom_fields=new ocp_tempcode();
				if (array_key_exists('ip_address',$_postdetails))
				{
					$custom_fields->attach(do_template('OCF_TOPIC_POST_CUSTOM_FIELD',array('_GUID'=>'d85be094dff0d039a64120d6f8f381bb','NAME'=>do_lang_tempcode('IP_ADDRESS'),'VALUE'=>($_postdetails['ip_address']))));
					$poster_details=do_template('OCF_GUEST_DETAILS',array('_GUID'=>'e43534acaf598008602e8da8f9725f38','CUSTOM_FIELDS'=>$custom_fields));
				} else
				{
					$poster_details=new ocp_tempcode();
				}
			}
	
			if (!is_guest($_postdetails['poster']))
			{
				$poster=do_template('OCF_POSTER_MEMBER',array('_GUID'=>'dbbed1850b6c01a6c9601d85c6aee43f','ONLINE'=>member_is_online($_postdetails['poster']),'ID'=>strval($_postdetails['poster']),'POSTER_DETAILS'=>$poster_details,'PROFILE_URL'=>$GLOBALS['FORUM_DRIVER']->member_profile_link($_postdetails['poster'],false,true),'POSTER_USERNAME'=>$_postdetails['poster_username'],'HIGHLIGHT_NAME'=>array_key_exists('poster_highlighted_name',$_postdetails)?strval($_postdetails['poster_highlighted_name']):NULL));
			} else
			{
				$ip_link=((array_key_exists('ip_address',$_postdetails)) && (has_actual_page_access(get_member(),'admin_lookup')))?build_url(array('page'=>'admin_lookup','param'=>$_postdetails['ip_address']),get_module_zone('admin_lookup')):new ocp_tempcode();
				$poster=do_template('OCF_POSTER_GUEST',array('_GUID'=>'36a8e550222cdac5165ef8f722be3def','IP_LINK'=>$ip_link,'POSTER_DETAILS'=>$poster_details,'POSTER_USERNAME'=>$_postdetails['poster_username']));
			}

			// Signature
			$signature=new ocp_tempcode();
			if ((array_key_exists('signature',$_postdetails)) && (!$_postdetails['signature']->is_empty()))
			{
				$signature=$_postdetails['signature'];
			}

			$post_title=$_postdetails['title'];

			$first_unread=(($_postdetails['id']==$first_unread_id) || (($first_unread_id<0) && ($array_id==count($topic_info['posts'])-1)))?do_template('OCF_TOPIC_FIRST_UNREAD'):new ocp_tempcode();

			$unvalidated=($_postdetails['validated']==0)?do_lang_tempcode('UNVALIDATED'):new ocp_tempcode();

			$post_url=$GLOBALS['FORUM_DRIVER']->post_link($_postdetails['id'],is_null($forum_id)?'':strval($forum_id),true);
	
			if (array_key_exists('intended_solely_for',$_postdetails))
			{
				decache('side_ocf_personal_topics',array(get_member()));
				decache('_new_pp',array(get_member()));
			}

			$emphasis=new ocp_tempcode();
			if ($_postdetails['is_emphasised'])
			{
				$emphasis=do_lang_tempcode('IMPORTANT');
			}
			elseif (array_key_exists('intended_solely_for',$_postdetails))
			{
				$pp_to_username=$GLOBALS['FORUM_DRIVER']->get_username($_postdetails['intended_solely_for']);
				if (is_null($pp_to_username)) $pp_to_username=do_lang('UNKNOWN');
				$emphasis=do_lang('PP_TO',$pp_to_username);
			}

			$posts->attach(do_template('OCF_TOPIC_POST',array(
						'_GUID'=>'sacd09wekfofpw2f',
						'ID'=>strval($_postdetails['id']),
						'TOPIC_FIRST_POST_ID'=>is_null($topic_info['first_post_id'])?'':strval($topic_info['first_post_id']),
						'TOPIC_FIRST_POSTER'=>is_null($topic_info['first_poster'])?'':strval($topic_info['first_poster']),
						'POST_ID'=>strval($_postdetails['id']),
						'URL'=>$post_url,
						'CLASS'=>$_postdetails['is_emphasised']?'ocf_post_emphasis':(array_key_exists('intended_solely_for',$_postdetails)?'ocf_post_personal':''),
						'EMPHASIS'=>$emphasis,
						'FIRST_UNREAD'=>$first_unread,
						'POSTER_TITLE'=>$_postdetails['poster_title'],
						'POST_TITLE'=>$post_title,
						'POST_DATE_RAW'=>strval($_postdetails['time']),
						'POST_DATE'=>$_postdetails['time_string'],
						'POST'=>$_postdetails['post'],
						'TOPIC_ID'=>is_null($id)?'':strval($id),
						'LAST_EDITED_RAW'=>$last_edited_raw,
						'LAST_EDITED'=>$last_edited,
						'POSTER_ID'=>strval($_postdetails['poster']),
						'POSTER'=>$poster,
						'POSTER_DETAILS'=>$poster_details,
						'POST_AVATAR'=>$post_avatar,
						'RANK_IMAGES'=>$rank_images,
						'BUTTONS'=>$buttons,
						'SIGNATURE'=>$signature,
						'UNVALIDATED'=>$unvalidated,
						'DESCRIPTION'=>$description,
			)));
		}

		// Buttons
		$button_array=array();
		if ((!is_guest()) && (!is_null($id)))
		{
			if (!$topic_info['is_being_tracked'])
			{
				$map=array('page'=>'topics','type'=>'track_topic','id'=>$id);
				$test=get_param_integer('kfs'.(is_null($forum_id)?'':strval($forum_id)),-1);
				if (($test!=-1) && ($test!=0)) $map['kfs'.(is_null($forum_id)?'':strval($forum_id))]=$test;
				$track_topic_url=build_url($map,get_module_zone('topics'));
				$button_array[]=array('immediate'=>true,'rel'=>'track','title'=>do_lang_tempcode('TRACK_TOPIC'),'url'=>$track_topic_url,'img'=>'track_topic');
			}
			else
			{
				$map=array('page'=>'topics','type'=>'untrack_topic','id'=>$id);
				$test=get_param_integer('kfs'.strval($forum_id),-1);
				if (($test!=-1) && ($test!=0)) $map['kfs'.strval($forum_id)]=$test;
				$track_topic_url=build_url($map,get_module_zone('topics'));
				$button_array[]=array('immediate'=>true,'rel'=>'untrack','title'=>do_lang_tempcode('UNTRACK_TOPIC'),'url'=>$track_topic_url,'img'=>'untrack_topic');
			}
		}
		if (!is_null($id))
		{
			$map=array('page'=>'topics','type'=>'mark_unread_topic','id'=>$id);
			$test=get_param_integer('kfs'.(is_null($forum_id)?'':strval($forum_id)),-1);
			if (($test!=-1) && ($test!=0)) $map['kfs'.(is_null($forum_id)?'':strval($forum_id))]=$test;
			$mark_unread_url=build_url($map,get_module_zone('topics'));
			$button_array[]=array('immediate'=>true,'title'=>do_lang_tempcode('MARK_UNREAD'),'url'=>$mark_unread_url,'img'=>'mark_unread');

			if (($may_reply) && (is_null(get_bot_type())))
			{
				$reply_prevented=false;

				// "Staff-only" reply for support tickets
				if (($GLOBALS['FORUM_DRIVER']->is_staff(get_member())) && (addon_installed('tickets')))
				{
					require_code('tickets');
					if (is_ticket_forum($forum_id))
					{
						if (is_guest($second_poster))
							$reply_prevented=true;

						require_lang('tickets');
						$map=array('page'=>'topics','type'=>'new_post','id'=>$id,'intended_solely_for'=>$GLOBALS['FORUM_DRIVER']->get_guest_id());
						$test=get_param_integer('kfs'.(is_null($forum_id)?'':strval($forum_id)),-1);
						if (($test!=-1) && ($test!=0)) $map['kfs'.(is_null($forum_id)?'':strval($forum_id))]=$test;
						$new_post_url=build_url($map,get_module_zone('topics'));
						$button_array[]=array('immediate'=>false,'rel'=>'add','title'=>do_lang_tempcode('TICKET_STAFF_ONLY_REPLY'),'url'=>$new_post_url,'img'=>'staff_only_reply');
					}
				}

				if (!$reply_prevented)
				{
					$map=array('page'=>'topics','type'=>'new_post','id'=>$id);
					$test=get_param_integer('kfs'.(is_null($forum_id)?'':strval($forum_id)),-1);
					if (($test!=-1) && ($test!=0)) $map['kfs'.(is_null($forum_id)?'':strval($forum_id))]=$test;
					$new_post_url=build_url($map,get_module_zone('topics'));
					$button_array[]=array('immediate'=>false,'rel'=>'add','title'=>do_lang_tempcode($topic_info['is_open']?'REPLY':'CLOSED'),'url'=>$new_post_url,'img'=>$topic_info['is_open']?'reply':'closed');
				} else
				{
					unset($topic_info['may_use_quick_reply']);
				}
			}
			elseif (((is_null($forum_id)) || (has_specific_permission(get_member(),'submit_lowrange_content','topics',array('forums',$forum_id)))) && ($topic_info['last_poster']==get_member()) && (!is_guest()) && (ocf_may_edit_post_by(get_member(),$forum_id)))
			{
				$map=array('page'=>'topics','type'=>'edit_post','id'=>$topic_info['last_post_id']);
				$test=get_param_integer('kfs'.strval($forum_id),-1);
				if (($test!=-1) && ($test!=0)) $map['kfs'.strval($forum_id)]=$test;
				$new_post_url=build_url($map,get_module_zone('topics'));
				$button_array[]=array('immediate'=>false,'rel'=>'edit','title'=>do_lang_tempcode('LAST_POST'),'url'=>$new_post_url,'img'=>'amend');
			}

			if (!is_null($topic_info['forum_id']))
			{
				if (ocf_may_post_topic($topic_info['forum_id'],get_member()))
				{
					$new_topic_url=build_url(array('page'=>'topics','type'=>'new_topic','id'=>$topic_info['forum_id']),get_module_zone('topics'));
					$button_array[]=array('immediate'=>false,'rel'=>'add','title'=>do_lang_tempcode('ADD_TOPIC'),'url'=>$new_topic_url,'img'=>'new_topic');
				}
			} else
			{
				$invite_url=build_url(array('page'=>'topics','type'=>'invite_member','id'=>$id),get_module_zone('topics'));
				$button_array[]=array('immediate'=>false,'title'=>do_lang_tempcode('INVITE_MEMBER_TO_PT'),'url'=>$invite_url,'img'=>'invite_member');
			}
		}
		$buttons=ocf_screen_button_wrap($button_array);
	
		// Poll
		if (array_key_exists('poll',$topic_info))
		{
			$_poll=$topic_info['poll'];
			$voted_already=$_poll['voted_already'];
			$poll_results=(array_key_exists(0,$_poll['answers'])) && (array_key_exists('num_votes',$_poll['answers'][0]));
			$answers=new ocp_tempcode();
			$real_button=false;
			if ($_poll['is_open'])
			{
				if ($poll_results)
				{
					$button=new ocp_tempcode();
				}
				elseif (($_poll['requires_reply']) && (!$replied))
				{
					$button=do_lang_tempcode('POLL_REQUIRES_REPLY');
				} else
				{
					if ((!has_specific_permission(get_member(),'vote_in_polls')) || (is_guest()))
					{
						$button=do_lang_tempcode('VOTE_DENIED');
					} else
					{
						if (!is_null($voted_already))
						{
							$button=do_lang_tempcode('NOVOTE');
						} else
						{
							require_lang('polls');
							$map=array('page'=>'topicview','id'=>$id,'view_poll_results'=>1,'start'=>($start==0)?NULL:$start,'max'=>($max==$default_max)?NULL:$max);
							$test=get_param_integer('kfs'.(is_null($forum_id)?'':strval($forum_id)),-1);
							if (($test!=-1) && ($test!=0)) $map['kfs'.(is_null($forum_id)?'':strval($forum_id))]=$test;
							$results_url=build_url($map,get_module_zone('topics'));
							$button=do_template('OCF_TOPIC_POLL_BUTTON',array('_GUID'=>'94b932fd01028df8f67bb5864d9235f9','RESULTS_URL'=>$results_url));
							$real_button=true;
						}
					}
				}
			} else $button=do_lang_tempcode('TOPIC_POLL_CLOSED');
			foreach ($_poll['answers'] as $answer)
			{
				if (($poll_results) && (($_poll['requires_reply']==0) || ($replied)))
				{
					$num_votes=$answer['num_votes'];
					$total_votes=$_poll['total_votes'];
					if ($total_votes!=0)
						$width=intval(round(70.0*floatval($num_votes)/floatval($total_votes)));
					else $width=0;
					$answer_tpl=do_template('OCF_TOPIC_POLL_ANSWER_RESULTS',array('_GUID'=>'b32f4c526e147abf20ca0d668e40d515','ID'=>strval($_poll['id']),'NUM_VOTES'=>integer_format($num_votes),'WIDTH'=>strval($width),'ANSWER'=>$answer['answer'],'I'=>strval($answer['id'])));
				} else
				{
					$answer_tpl=do_template('OCF_TOPIC_POLL_ANSWER'.($_poll['maximum_selections']==1?'_RADIO':''),array('REAL_BUTTON'=>$real_button,'ID'=>strval($_poll['id']),'ANSWER'=>$answer['answer'],'I'=>strval($answer['id'])));
				}
				$answers->attach($answer_tpl);
			}
			$map=array('page'=>'topics','type'=>'vote_poll','id'=>$id,'start'=>($start==0)?NULL:$start,'max'=>($max==$default_max)?NULL:$max);
			$test=get_param_integer('kfs'.(is_null($forum_id)?'':strval($forum_id)),-1);
			if (($test!=-1) && ($test!=0)) $map['kfs'.(is_null($forum_id)?'':strval($forum_id))]=$test;
			$vote_url=build_url($map,get_module_zone('topics'));
			if ($_poll['is_private']) $private=paragraph(do_lang_tempcode('TOPIC_POLL_IS_PRIVATE'),'dfgsdgdsgs'); else $private=new ocp_tempcode();
			if ($_poll['maximum_selections']>1) $num_choices=paragraph(($_poll['minimum_selections']==$_poll['maximum_selections'])?do_lang_tempcode('POLL_NOT_ENOUGH_ERROR_2',integer_format($_poll['minimum_selections'])):do_lang_tempcode('POLL_NOT_ENOUGH_ERROR',integer_format($_poll['minimum_selections']),integer_format($_poll['maximum_selections'])),'dsfsdfsdfs'); else $num_choices=new ocp_tempcode();

			$poll=do_template('OCF_TOPIC_POLL'.($poll_results?'_VIEW_RESULTS':''),array('ID'=>strval($_poll['id']),'NUM_CHOICES'=>$num_choices,'PRIVATE'=>$private,'QUESTION'=>$_poll['question'],'ANSWERS'=>$answers,'REAL_BUTTON'=>$real_button,'BUTTON'=>$button,'VOTE_URL'=>$vote_url,'MINIMUM_SELECTIONS'=>integer_format($_poll['minimum_selections']),'MAXIMUM_SELECTIONS'=>integer_format($_poll['maximum_selections'])));
		} else $poll=new ocp_tempcode();

		// Forum nav tree
		if (!is_null($topic_info['forum_id']))
		{
			$tree=ocf_forum_breadcrumbs($topic_info['forum_id'],NULL,NULL,false);
		} else
		{
			$root_forum_name=$GLOBALS['FORUM_DB']->query_value('f_forums','f_name',array('id'=>get_param_integer('keep_forum_root',db_get_first_id())));
			$tree=hyperlink(build_url(array('page'=>'forumview','id'=>get_param_integer('keep_forum_root',NULL)),get_module_zone('forumview')),escape_html($root_forum_name),false,false,do_lang_tempcode('GO_BACKWARDS_TO',$root_forum_name),NULL,NULL,'up');
			$tree->attach(do_template('BREADCRUMB_ESCAPED'));
			if (has_specific_permission(get_member(),'view_other_pt'))
			{
				$of_member=($topic_info['pt_from']==get_member())?$topic_info['pt_from']:$topic_info['pt_to'];
			} else $of_member=get_member();
			$of_username=$GLOBALS['FORUM_DRIVER']->get_username($of_member);
			if (is_null($of_username)) $of_username=do_lang('UNKNOWN');
			$forum_of_name=do_lang('PERSONAL_TOPICS_OF',$of_username);
			$tree->attach(hyperlink(build_url(array('page'=>'forumview','type'=>'pt','id'=>$of_member),get_module_zone('forumview')),escape_html($forum_of_name),false,false,do_lang_tempcode('GO_BACKWARDS_TO',$forum_of_name),NULL,NULL,'up'));
		}

		// Quick reply
		if ((array_key_exists('may_use_quick_reply',$topic_info)) && ($may_reply) && (!is_null($id)))
		{
			$map=array('page'=>'topics','type'=>'_add_reply','topic_id'=>$id);
			$test=get_param_integer('kfs'.(is_null($forum_id)?'':strval($forum_id)),-1);
			if (($test!=-1) && ($test!=0)) $map['kfs'.(is_null($forum_id)?'':strval($forum_id))]=$test;
			$_post_url=build_url($map,get_module_zone('topics'));
			$post_url=$_post_url->evaluate();
			$map=array('page'=>'topics','type'=>'new_post','id'=>$id);
			if (($test!=-1) && ($test!=0)) $map['kfs'.(is_null($forum_id)?'':strval($forum_id))]=$test;
			$more_url=build_url($map,get_module_zone('topics'));
			$_postdetails=array_key_exists('first_post',$topic_info)?get_translated_tempcode($topic_info['first_post'],$GLOBALS['FORUM_DB']):new ocp_tempcode();
			$first_post=$_postdetails;
			$first_post_url=$GLOBALS['FORUM_DRIVER']->post_link($topic_info['first_post_id'],is_null($forum_id)?'':strval($forum_id),true);
			$display='block';
			$expand_type='contract';
			if ($topic_info['max_rows']>$start+$max)
			{
				$display='none';
				$expand_type='expand';
			}
			$em=$GLOBALS['FORUM_DRIVER']->get_emoticon_chooser();
			require_javascript('javascript_editing');
			require_javascript('javascript_validation');
			if (addon_installed('captcha'))
			{
				require_code('captcha');
				$use_captcha=use_captcha();
				if ($use_captcha)
				{
					generate_captcha();
				}
			} else $use_captcha=false;
			$quick_reply=do_template('COMMENTS',array('_GUID'=>'4c532620f3eb68d9cc820b18265792d7','JOIN_BITS'=>'','USE_CAPTCHA'=>$use_captcha,'GET_EMAIL'=>false,'EMAIL_OPTIONAL'=>true,'GET_TITLE'=>false,'POST_WARNING'=>'','COMMENT_TEXT'=>'','EM'=>$em,'EXPAND_TYPE'=>$expand_type,'DISPLAY'=>$display,'FIRST_POST_URL'=>$first_post_url,'FIRST_POST'=>$first_post,'MORE_URL'=>$more_url,'COMMENT_URL'=>$post_url,'TITLE'=>do_lang_tempcode('QUICK_REPLY')));
		} else $quick_reply=new ocp_tempcode();

		$action_url=build_url(array('page'=>'topics','id'=>$id),get_module_zone('topics'));
		if (!is_null($id))
		{
			// Moderation options
			$moderator_actions='';
			if (is_null($forum_id))
			{
				$moderator_actions.='<option value="categorise_pts">'.do_lang('_CATEGORISE_PTS').'</option>';
			}
			if ((array_key_exists('may_multi_moderate',$topic_info)) && (array_key_exists('forum_id',$topic_info)))
			{
				$multi_moderations=ocf_list_multi_moderations($topic_info['forum_id']);
				if (count($multi_moderations)!=0)
				{
					$moderator_actions.='<optgroup label="'.do_lang('MULTI_MODERATIONS').'">';
					foreach ($multi_moderations as $mm_id=>$mm_name)
						$moderator_actions.='<option value="mm_'.strval($mm_id).'">'.$mm_name.'</option>';
					$moderator_actions.='</optgroup>';
				}
			}
			if (array_key_exists('may_move_topic',$topic_info))
				$moderator_actions.='<option value="move_topic">'.do_lang('MOVE_TOPIC').'</option>';
			if (array_key_exists('may_edit_topic',$topic_info))
				$moderator_actions.='<option value="edit_topic">'.do_lang('EDIT_TOPIC').'</option>';
			if (array_key_exists('may_delete_topic',$topic_info))
				$moderator_actions.='<option value="delete_topic">'.do_lang('DELETE_TOPIC').'</option>';
			if (array_key_exists('may_pin_topic',$topic_info))
				$moderator_actions.='<option value="pin_topic">'.do_lang('PIN_TOPIC').'</option>';
			if (array_key_exists('may_unpin_topic',$topic_info))
				$moderator_actions.='<option value="unpin_topic">'.do_lang('UNPIN_TOPIC').'</option>';
			if (array_key_exists('may_sink_topic',$topic_info))
				$moderator_actions.='<option value="sink_topic">'.do_lang('SINK_TOPIC').'</option>';
			if (array_key_exists('may_unsink_topic',$topic_info))
				$moderator_actions.='<option value="unsink_topic">'.do_lang('UNSINK_TOPIC').'</option>';
			if (array_key_exists('may_cascade_topic',$topic_info))
				$moderator_actions.='<option value="cascade_topic">'.do_lang('CASCADE_TOPIC').'</option>';
			if (array_key_exists('may_uncascade_topic',$topic_info))
				$moderator_actions.='<option value="uncascade_topic">'.do_lang('UNCASCADE_TOPIC').'</option>';
			if (array_key_exists('may_open_topic',$topic_info))
				$moderator_actions.='<option value="open_topic">'.do_lang('OPEN_TOPIC').'</option>';
			if (array_key_exists('may_close_topic',$topic_info))
				$moderator_actions.='<option value="close_topic">'.do_lang('CLOSE_TOPIC').'</option>';
			if (array_key_exists('may_edit_poll',$topic_info))
				$moderator_actions.='<option value="edit_poll">'.do_lang('EDIT_TOPIC_POLL').'</option>';
			if (array_key_exists('may_delete_poll',$topic_info))
				$moderator_actions.='<option value="delete_poll">'.do_lang('DELETE_TOPIC_POLL').'</option>';
			if (array_key_exists('may_attach_poll',$topic_info))
				$moderator_actions.='<option value="add_poll">'.do_lang('ADD_TOPIC_POLL').'</option>';
			if ((has_specific_permission(get_member(),'view_content_history')) && ($GLOBALS['FORUM_DB']->query_value('f_post_history','COUNT(*)',array('h_topic_id'=>$id))!=0))
				$moderator_actions.='<option value="topic_history">'.do_lang('POST_HISTORY').'</option>';
			if ((array_key_exists('may_make_personal',$topic_info)) && (!is_null($forum_id)))
				$moderator_actions.='<option value="make_personal">'.do_lang('MAKE_PERSONAL').'</option>';

			if ($GLOBALS['XSS_DETECT']) ocp_mark_as_escaped($moderator_actions);

			// Marked post actions
			$map=array('page'=>'topics','id'=>$id);
			$test=get_param_integer('kfs'.(is_null($forum_id)?'':strval($forum_id)),-1);
			if (($test!=-1) && ($test!=0)) $map['kfs'.(is_null($forum_id)?'':strval($forum_id))]=$test;
			$action_url=build_url($map,get_module_zone('topics'),NULL,false,true);
			$marked_post_actions='';
			if (array_key_exists('may_move_posts',$topic_info))
			{
				$marked_post_actions.='<option value="move_posts_a">'.do_lang('MERGE_POSTS').'</option>';
				$marked_post_actions.='<option value="move_posts_b">'.do_lang('SPLIT_POSTS').'</option>';
			}
			if (array_key_exists('may_delete_posts',$topic_info))
				$marked_post_actions.='<option value="delete_posts">'.do_lang('DELETE_POSTS').'</option>';
			if (array_key_exists('may_validate_posts',$topic_info))
				$marked_post_actions.='<option value="validate_posts">'.do_lang('VALIDATE_POSTS').'</option>';
			if ($may_reply)
				$marked_post_actions.='<option value="new_post">'.do_lang('QUOTE_POSTS').'</option>';

			if ($GLOBALS['XSS_DETECT']) ocp_mark_as_escaped($marked_post_actions);
		} else
		{
			$moderator_actions='';
			$marked_post_actions='';
		}

		$max_rows=$topic_info['max_rows'];
		require_code('templates_results_browser');
		if ($max_rows>$max)
		{
			$results_browser=results_browser(do_lang_tempcode('FORUM_POSTS'),$id,$start,'start',$max,'max',$max_rows,NULL,'misc',true,false);
		} else
		{
			$results_browser=new ocp_tempcode();
		}

		// Members viewing this topic
		$members=is_null($id)?array():get_members_viewing('topicview','',strval($id),true);
		$num_guests=0;
		$num_members=0;
		if (is_null($members))
		{
			$members_viewing=new ocp_tempcode();
		} else
		{
			$members_viewing=new ocp_tempcode();
			foreach ($members as $member_id=>$at_details)
			{
				$username=$at_details['mt_cache_username'];

				if (is_guest($member_id))
				{
					$num_guests++;
				} else
				{
					$num_members++;
					$profile_url=$GLOBALS['FORUM_DRIVER']->member_profile_link($member_id,false,true);
					$map=array('PROFILE_URL'=>$profile_url,'USERNAME'=>$username);
					if ((has_specific_permission(get_member(),'show_user_browsing')) || ((in_array($at_details['the_page'],array('topics','topicview'))) && ($at_details['the_id']==strval($id))))
					{
						$map['AT']=escape_html($at_details['the_title']);
					}
					$map['COLOUR']=get_group_colour(ocf_get_member_primary_group($member_id));
					$members_viewing->attach(do_template('OCF_USER_MEMBER',$map));
				}
			}
			if ($members_viewing->is_empty()) $members_viewing=do_lang_tempcode('NONE_EM');
		}

		if (!is_null($id))
			breadcrumb_add_segment($tree,do_lang_tempcode('VIEW_TOPIC'));

		if (is_null($id)) // Just inline personal posts
		{
         $root_forum_name=$GLOBALS['FORUM_DB']->query_value('f_forums','f_name',array('id'=>db_get_first_id()));
         $tree=hyperlink(build_url(array('page'=>'forumview','id'=>db_get_first_id()),get_module_zone('forumview')),escape_html($root_forum_name),false,false,do_lang('GO_BACKWARDS_TO'));
	      breadcrumb_add_segment($tree,do_lang('INLINE_PERSONAL_POSTS'));
		}

		if ($topic_info['validated']==0)
		{
			$warning_details=do_template('WARNING_TABLE',array('WARNING'=>do_lang_tempcode((get_param_integer('redirected',0)==1)?'UNVALIDATED_TEXT_NON_DIRECT':'UNVALIDATED_TEXT')));
		} else $warning_details=new ocp_tempcode();

		$topic_tpl=do_template('OCF_TOPIC_WRAP',array('_GUID'=>'bb201d5d59559e5e2bd60e7cf2e6f7e9','TITLE'=>$topic_info['title'],'MAY_DOUBLE_POST'=>has_specific_permission(get_member(),'double_post'),'LAST_POSTER'=>array_key_exists('last_poster',$topic_info)?(is_null($topic_info['last_poster'])?'':strval($topic_info['last_poster'])):'','WARNING_DETAILS'=>$warning_details,'MAX'=>strval($max),'MAY_CHANGE_MAX'=>array_key_exists('may_change_max',$topic_info),'ACTION_URL'=>$action_url,'NUM_GUESTS'=>integer_format($num_guests),'NUM_MEMBERS'=>integer_format($num_members),'MEMBERS_VIEWING'=>$members_viewing,'RESULTS_BROWSER'=>$results_browser,'MODERATOR_ACTIONS'=>$moderator_actions,'MARKED_POST_ACTIONS'=>$marked_post_actions,'QUICK_REPLY'=>$quick_reply,'TREE'=>$tree,'POLL'=>$poll,'SCREEN_BUTTONS'=>$buttons,'POSTS'=>$posts));
		if (is_null($id)) // Just inline personal posts
		{
			$title=get_page_title('INLINE_PERSONAL_POSTS');
		} else
		{
			if (is_null($forum_id))
			{
				$title=get_page_title(do_lang_tempcode('NAMED_PERSONAL_TOPIC',escape_html($topic_info['title'])),false,NULL,do_lang_tempcode('READING_PERSONAL_TOPIC'));
			} else
			{
				if (addon_installed('awards'))
				{
					require_code('awards');
					$awards=find_awards_for('topic',strval($id));
				} else $awards=array();

				$title=get_page_title(do_lang_tempcode('NAMED_TOPIC',escape_html($topic_info['title'])),false,NULL,NULL,$awards);
			}
		}
		return ocf_wrapper($title,$topic_tpl,true,false,$forum_id);
	}

}


