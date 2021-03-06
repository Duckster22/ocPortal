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
 * @package		ocf_forum
 */

class Hook_Profiles_Tabs_posts
{

	/**
	 * Find whether this hook is active.
	 *
	 * @param  MEMBER			The ID of the member who is being viewed
	 * @param  MEMBER			The ID of the member who is doing the viewing
	 * @return boolean		Whether this hook is active
	 */
	function is_active($member_id_of,$member_id_viewing)
	{
		return true;
	}

	/**
	 * Standard modular render function for profile tab hooks.
	 *
	 * @param  MEMBER			The ID of the member who is being viewed
	 * @param  MEMBER			The ID of the member who is doing the viewing
	 * @param  boolean		Whether to leave the tab contents NULL, if tis hook supports it, so that AJAX can load it later
	 * @return array			A triple: The tab title, the tab contents, the suggested tab order
	 */
	function render_tab($member_id_of,$member_id_viewing,$leave_to_ajax_if_possible=false)
	{
		$title=do_lang_tempcode('FORUM_POSTS');

		$order=20;

		if ($leave_to_ajax_if_possible) return array($title,NULL,$order);

		require_code('ocf_topics');
		require_code('ocf_general');
		require_lang('ocf');

		$topics=new ocp_tempcode();
		if (!has_no_forum())
		{
			require_code('ocf_forumview');

			global $NON_CANONICAL_PARAMS;
			$NON_CANONICAL_PARAMS[]='start';
			$NON_CANONICAL_PARAMS[]='max';

			// Last 15 topics that member contributed to
			$n=get_param_integer('max',10);
			$start=get_param_integer('start',0);
			$forum1=NULL;//$GLOBALS['FORUM_DRIVER']->forum_id_from_name(get_option('comments_forum_name'));
			$tf=get_option('ticket_forum_name',true);
			if (!is_null($tf)) $forum2=$GLOBALS['FORUM_DRIVER']->forum_id_from_name($tf); else $forum2=NULL;
			$where_more='';
			if (!is_null($forum1)) $where_more.=' AND p_cache_forum_id<>'.strval((integer)$forum1);
			if (!is_null($forum2)) $where_more.=' AND p_cache_forum_id<>'.strval((integer)$forum2);
			$rows=$GLOBALS['FORUM_DB']->query('SELECT DISTINCT p_topic_id FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts WHERE p_poster='.strval((integer)$member_id_of).$where_more.' ORDER BY p_time DESC',$n,$start);
			if (count($rows)!=0)
			{
				$max_rows=$GLOBALS['FORUM_DB']->query_value_null_ok_full('SELECT COUNT(DISTINCT p_topic_id) FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts WHERE p_poster='.strval((integer)$member_id_of).$where_more);

				$where='';
				foreach ($rows as $row)
				{
					if ($where!='') $where.=' OR ';
					$where.='t.id='.strval((integer)$row['p_topic_id']);
				}
				$topic_rows=$GLOBALS['FORUM_DB']->query('SELECT t.*,lan.text_parsed AS _trans_post,l_time FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_topics t LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_read_logs l ON (t.id=l.l_topic_id AND l.l_member_id='.strval((integer)get_member()).') LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'translate lan ON t.t_cache_first_post=lan.id WHERE '.$where);
				$topic_rows_map=array();
				foreach ($topic_rows as $topic_row)
				{
					if (has_category_access(get_member(),'forums',strval($topic_row['t_forum_id'])))
						$topic_rows_map[$topic_row['id']]=$topic_row;
				}
				$hot_topic_definition=intval(get_option('hot_topic_definition'));
				foreach ($rows as $row)
				{
					if (array_key_exists($row['p_topic_id'],$topic_rows_map))
						$topics->attach(ocf_render_topic(ocf_get_topic_array($topic_rows_map[$row['p_topic_id']],get_member(),$hot_topic_definition,true),false));
				}
				if (!$topics->is_empty())
				{
					$forum_name=do_lang_tempcode('TOPICS_PARTICIPATED_IN',integer_format($start+1).'-'.integer_format($start+$n));
					$marker='';
					$tree=new ocp_tempcode();
					require_code('templates_results_browser');
					$results_browser=results_browser(do_lang_tempcode('FORUM_TOPICS'),NULL,$start,'start',$n,'max',$max_rows,NULL,'view',true,false,7,NULL,'tab__posts');
					$topics=do_template('OCF_FORUM_TOPIC_WRAPPER',array('_GUID'=>'8723270b128b4eea47ab3c756b342e14','ORDER'=>'','MAX'=>'15','MAY_CHANGE_MAX'=>false,'TREE'=>$tree,'ACTION_URL'=>get_self_url(),'BUTTONS'=>'','STARTER_TITLE'=>'','MARKER'=>$marker,'FORUM_NAME'=>$forum_name,'TOPICS'=>$topics,'RESULTS_BROWSER'=>$results_browser,'MODERATOR_ACTIONS'=>''));
				}
			}
		}

		$content=do_template('OCF_MEMBER_PROFILE_POSTS',array('MEMBER_ID'=>strval($member_id_of),'TOPICS'=>$topics));

		return array($title,$content,$order);
	}

}


