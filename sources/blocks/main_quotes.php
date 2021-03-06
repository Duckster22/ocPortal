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
 * @package		random_quotes
 */

class Block_main_quotes
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
		$info['parameters']=array('param','title');
		return $info;
	}

	/**
	 * Standard modular cache function.
	 *
	 * @return ?array	Map of cache details (cache_on and ttl) (NULL: module is disabled).
	 */
	function cacheing_environment()
	{
		$info=array();
		$info['cache_on']='array(array_key_exists(\'title\',$map)?$map[\'title\']:do_lang(\'QUOTES\'),has_actual_page_access(get_member(),\'quotes\',\'adminzone\'),array_key_exists(\'param\',$map)?$map[\'param\']:\'quotes\')';
		$info['ttl']=5;
		return $info;
	}

	/**
	 * Standard modular run function.
	 *
	 * @param  array		A map of parameters.
	 * @return tempcode	The result of execution.
	 */
	function run($map)
	{
		$file=array_key_exists('param',$map)?$map['param']:'quotes';
		$title=array_key_exists('title',$map)?$map['title']:do_lang('QUOTES');

		require_code('textfiles');
		require_lang('quotes');

		$place=_find_text_file_path($file,'');

		if (!file_exists($place)) warn_exit(do_lang_tempcode('DIRECTORY_NOT_FOUND',escape_html($place)));
		$edit_url=new ocp_tempcode();
		if (($file=='quotes') && (has_actual_page_access(get_member(),'quotes','adminzone')))
		{
			$edit_url=build_url(array('page'=>'quotes'),'adminzone');
		}
		return do_template('BLOCK_MAIN_QUOTES',array('_GUID'=>'7cab7422f603f7b1197c940de48b99aa','TITLE'=>$title,'EDIT_URL'=>$edit_url,'FILE'=>$file,'CONTENT'=>comcode_to_tempcode($this->get_random_line($place),NULL,true)));
	}

	/**
	 * Get a random line from a file.
	 *
	 * @param  PATH			The filename
	 * @return string			The random line
	 */
	function get_random_line($filename)
	{
		$myfile=@fopen(filter_naughty($filename,true),'rt');
		if ($myfile===false) return '';
		$i=0;
		$line=array();
		while (true)
		{
			$line[$i]=fgets($myfile,1024);
			if (($line[$i]===false) || (is_null($line[$i]))) break;
			$i++;
		}
		if ($i==0) return '';
		$r=mt_rand(0,$i-1);
		fclose($myfile);
		return trim($line[$r]);
	}

}


