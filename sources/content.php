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
 * @package		core
 */

/**
 * Get meta details of a content item
 *
 * @param  ID_TEXT		Content type
 * @param  ID_TEXT		Content ID
 * @return array			Tuple: title, submitter, content hook info
 */
function content_get_details($content_type,$content_id)
{
	require_code('hooks/systems/content_meta_aware/'.$content_type);
	$cma_ob=object_factory('Hook_content_meta_aware_'.$content_type);
	$cma_info=$cma_ob->info();

	$db=$GLOBALS[(substr($cma_info['table'],0,2)=='f_')?'FORUM_DB':'SITE_DB'];

	$content_row=content_get_row($content_id,$cma_info);
	if (is_null($content_row)) return array(NULL,NULL,NULL,NULL);

	if (strpos($cma_info['title_field'],'CALL:')!==false)
	{
		$content_title=call_user_func(trim(substr($cma_info['title_field'],5)),array('id'=>$content_id));
	} else
	{
		$_content_title=$content_row[$cma_info['title_field']];
		$content_title=$cma_info['title_field_dereference']?get_translated_text($_content_title,$db):$_content_title;
	}
	if (isset($cma_info['submitter_field']))
	{
		$submitter_id=$content_row[$cma_info['submitter_field']];
	} else
	{
		$submitter_id=$GLOBALS['FORUM_DRIVER']->get_guest_id();
	}

	return array($content_title,$submitter_id,$cma_info,$content_row);
}

/**
 * Get the content row of a content item.
 *
 * @param  ID_TEXT			The content ID
 * @param  array				The info array for the content type
 * @return ?array				The row (NULL: not found)
 */
function content_get_row($content_id,$cma_info)
{
	$id_is_string=array_key_exists('id_is_string',$info)?$info['id_is_string']:false;
	if (is_array($info['id_field']))
	{
		$bits=explode(':',$content_id);
		$where=array();
		foreach ($bits as $i=>$bit)
		{
			$where[$info['id_field'][$i]]=$id_is_string?$bit:intval($bit);
		}
	} else
	{
		if ($id_is_string)
		{
			$where=array($info['id_field']=>$content_id);
		} else
		{
			$where=array($info['id_field']=>intval($content_id));
		}
	}
	$_content=$info['connection']->query_select($info['table'].' r',array('r.*'),$where,'',1);
	return array_key_exists(0,$_content)?$_content[0]:NULL;
}