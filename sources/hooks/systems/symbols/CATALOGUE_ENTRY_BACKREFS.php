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
 * @package		catalogues
 */

class Hook_symbol_CATALOGUE_ENTRY_BACKREFS
{

	/**
	 * Standard modular run function for symbol hooks. Searches for tasks to perform.
    *
    * @param  array		Symbol parameters
    * @return string		Result
	 */
	function run($param)
	{
		$value='';
		if (array_key_exists(0,$param))
		{
			$limit=array_key_exists(1,$param)?intval($param[1]):NULL;
			$resolve=array_key_exists(2,$param)?$param[2]:'';

			$done=0;
			$results=$GLOBALS['SITE_DB']->query_select('catalogue_fields f JOIN '.get_table_prefix().'catalogue_efv_short s ON f.id=s.cf_id AND '.db_string_equal_to('cf_type','reference').' OR cf_type LIKE \''.db_encode_like('ck_%').'\'',array('ce_id'),array('cv_value'=>$param[0]));
			foreach ($results as $result)
			{
				if ($resolve!='')
				{
					$test=$GLOBALS['SITE_DB']->query_value_null_ok('catalogue_entry_linkage','content_id',array('content_type'=>$param[2],'catalogue_entry_id'=>$result['ce_id']));
					if (!is_null($test))
					{
						if ($value!='') $value.=',';
						$value.=$test;
						$done++;
					}
				} else
				{
					if ($value!='') $value.=',';
					$value.=strval($result['ce_id']);
					$done++;
				}
				
				if ((!is_null($limit)) && ($done==$limit)) break;
			}
		}
		return $value;
	}

}
