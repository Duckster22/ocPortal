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
 * @package		cedi
 */

class Hook_awards_seedy_post
{

	/**
	 * Standard modular info function for award hooks. Provides information to allow task reporting, randomisation, and add-screen linking, to function.
	 *
	 * @return ?array	Map of award content-type info (NULL: disabled).
	 */
	function info()
	{
		$info=array();
		$info['connection']=$GLOBALS['SITE_DB'];
		$info['table']='seedy_posts';
		$info['date_field']='date_and_time';
		$info['id_field']='id';
		$info['add_url']=(has_submit_permission('low',get_member(),get_ip_address(),'cedi'))?build_url(array('page'=>'cedi','type'=>'add_post'),get_module_zone('cedi')):new ocp_tempcode();
		$info['category_field']='page_id';
		$info['category_type']='seedy_page';
		$info['parent_spec__table_name']='seedy_children';
		$info['parent_spec__parent_name']='parent_id';
		$info['parent_spec__field_name']='child_id';
		$info['parent_field_name']='page_id';
		$info['submitter_field']='the_user';
		$info['id_is_string']=false;
		require_lang('cedi');
		$info['title']=do_lang_tempcode('CEDI_POSTS');
		$info['validated_field']='validated';
		$info['category_is_string']=false;
		$info['archive_url']=build_url(array('page'=>'cedi'),get_module_zone('cedi'));
		$info['cms_page']='cedi';
		$info['supports_custom_fields']=true;

		return $info;
	}

	/**
	 * Standard modular run function for award hooks. Renders a content box for an award/randomisation.
	 *
	 * @param  array		The database row for the content
	 * @param  ID_TEXT	The zone to display in
	 * @return tempcode	Results
	 */
	function run($row,$zone)
	{
		require_code('cedi');

		return get_cedi_post_html($row,$zone);
	}

}


