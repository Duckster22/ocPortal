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
 * @package		core_feedback_features
 */

class Hook_comments
{

	/**
	 * Standard modular run function for snippet hooks. Generates XHTML to insert into a page using AJAX.
	 *
	 * @return tempcode  The snippet
	 */
	function run()
	{
		if (get_option('is_on_comments')=='0') warn_exit(do_lang_tempcode('INTERNAL_ERROR'));

		$serialized_options=get_param('serialized_options',false,true);
		$hash=get_param('hash');

		if (best_hash($serialized_options,get_site_salt())!=$hash) warn_exit(do_lang_tempcode('INTERNAL_ERROR'));

		list($topic_id,$num_to_show_limit,$allow_comments,$invisible_if_no_comments,$forum,$reverse,$may_reply,$highlight_by_user,$allow_reviews)=unserialize($serialized_options);

		$posts=array_map('intval',explode(',',get_param('ids',false,true)));

		$_parent_id=get_param('id','');
		$parent_id=($_parent_id=='')?mixed():intval($_parent_id);

		require_code('topics');
		$renderer=new OCP_Topic();
		return $renderer->render_posts_from_topic($topic_id,$num_to_show_limit,$allow_comments,$invisible_if_no_comments,$forum,NULL,$reverse,$may_reply,$highlight_by_user,$allow_reviews,$posts,$parent_id);
	}

}

