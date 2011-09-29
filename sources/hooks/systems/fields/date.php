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
 * @package		core_fields
 */

class Hook_fields_date
{

	// ==============
	// Module: search
	// ==============

	/**
	 * Get special Tempcode for inputting this field.
	 *
	 * @param  array			The row for the field to input
	 * @return ?array			List of specially encoded input detail rows (NULL: nothing special)
	 */
	function get_search_inputter($row)
	{
		return NULL;
	}

	/**
	 * Get special SQL from POSTed parameters for this field.
	 *
	 * @param  array			The row for the field to input
	 * @param  integer		We're processing for the ith row
	 * @return ?array			Tuple of SQL details (array: extra trans fields to search, array: extra plain fields to search, string: an extra table segment for a join, string: the name of the field to use as a title, if this is the title, extra WHERE clause stuff) (NULL: nothing special)
	 */
	function inputted_to_sql_for_search($row,$i)
	{
		return exact_match_sql($row,$i);
	}

	// ===================
	// Backend: fields API
	// ===================

	/**
	 * Get some info bits relating to our field type, that helps us look it up / set defaults.
	 *
	 * @param  ?array			The field details (NULL: new field)
	 * @param  ?boolean		Whether a default value cannot be blank (NULL: don't "lock in" a new default value)
	 * @param  ?string		The given default value as a string (NULL: don't "lock in" a new default value)
	 * @return array			Tuple of details (row-type,default-value-to-use,db row-type)
	 */
	function get_field_value_row_bits($field,$required=NULL,$default=NULL)
	{
		unset($field);
		if (!is_null($required))
		{
			if (($required) && ($default=='')) $default=date('Y-m-d H:i:s',servertime_to_usertime());
		}
		return array('short_unescaped',$default,'short');
	}

	/**
	 * Convert a field value to something renderable.
	 *
	 * @param  array			The field details
	 * @param  mixed			The raw value
	 * @return mixed			Rendered field (tempcode or string)
	 */
	function render_field_value($field,$ev)
	{
		if (is_object($ev)) return $ev;

		if ($ev!='')
		{
			if (strpos(strtolower($ev),'now')!==false)
			{
				$time=time();
			} else
			{
				// Y-m-d H:i:s
				$bits=explode(' ',$ev,2);
				$date_bits=explode((strpos($bits[0],'-')!==false)?'-':'/',$bits[0],3);
				if (!array_key_exists(1,$date_bits)) $date_bits[1]=date('m');
				if (!array_key_exists(2,$date_bits)) $date_bits[2]=date('Y');
				$time_bits=explode(':',$bits[1],3);
				if (!array_key_exists(1,$time_bits)) $time_bits[1]='00';
				if (!array_key_exists(2,$time_bits)) $time_bits[2]='00';
				$time=mktime(intval($time_bits[0]),intval($time_bits[1]),intval($time_bits[2]),intval($date_bits[1]),intval($date_bits[2]),intval($date_bits[0]));
				$time=servertime_to_usertime($time);
			}
			$ev=get_timezoned_date($time,true,false,true,true);
		}
		return escape_html($ev);
	}

	// ======================
	// Frontend: fields input
	// ======================

	/**
	 * Get form inputter.
	 *
	 * @param  string			The field name
	 * @param  string			The field description
	 * @param  array			The field details
	 * @param  ?string		The actual current value of the field (NULL: none)
	 * @param  boolean		Whether this is for a new entry
	 * @return ?tempcode		The Tempcode for the input field (NULL: skip the field - it's not input)
	 */
	function get_field_inputter($_cf_name,$_cf_description,$field,$actual_value,$new)
	{
		$time=mixed();
		
		if ((is_null($actual_value)) || ($actual_value==''))
		{
			$time=NULL;
		} elseif (strpos(strtolower($actual_value),'now')!==false)
		{
			$time=time();
		}
		else
		{
			// Y-m-d H:i:s
			$bits=explode(' ',$actual_value,2);
			$date_bits=explode((strpos($bits[0],'-')!==false)?'-':'/',$bits[0],3);
			if (!array_key_exists(1,$date_bits)) $date_bits[1]=date('m');
			if (!array_key_exists(2,$date_bits)) $date_bits[2]=date('Y');
			if (!array_key_exists(1,$bits)) $bits[1]='0';
			$time_bits=explode(':',$bits[1],3);
			if (!array_key_exists(1,$time_bits)) $time_bits[1]='00';
			if (!array_key_exists(2,$time_bits)) $time_bits[2]='00';
			//$time=mktime(intval($time_bits[0]),intval($time_bits[1]),intval($time_bits[2]),intval($date_bits[1]),intval($date_bits[2]),intval($date_bits[0]));
			
			$time=array(intval($time_bits[1]),intval($time_bits[0]),intval($date_bits[1]),intval($date_bits[2]),intval($date_bits[0]));
		}
		/*
		$min_year=1902; // 1902 is based on signed integer limit
		$max_year=2037; // 2037 is based on signed integer limit
		$years_to_show=$max_year-$min_year;
		^^^ NONSENSE: Integers not used to save!
		*/
		$min_year=NULL;
		$years_to_show=NULL;
		return form_input_date($_cf_name,$_cf_description,'field_'.strval($field['id']),$field['cf_required']==0,($field['cf_required']==0) && ($actual_value==''),true,$time,$years_to_show,$min_year);
	}

	/**
	 * Find the posted value from the get_field_inputter field
	 *
	 * @param  boolean		Whether we were editing (because on edit, it could be a fractional edit)
	 * @param  array			The field details
	 * @param  string			Where the files will be uploaded to
	 * @param  ?string		Former value of field (NULL: none)
	 * @return string			The value
	 */
	function inputted_to_field_value($editing,$field,$upload_dir='uploads/catalogues',$old_value=NULL)
	{
		$id=$field['id'];
		$stub='field_'.strval($id);

		$year=post_param_integer($stub.'_year',NULL);
		if (is_null($year)) return $editing?STRING_MAGIC_NULL:'';
		$month=post_param_integer($stub.'_month',NULL);
		if (is_null($month)) return $editing?STRING_MAGIC_NULL:'';
		$day=post_param_integer($stub.'_day',NULL);
		if (is_null($day)) return $editing?STRING_MAGIC_NULL:'';
		$hour=post_param_integer($stub.'_hour',0);
		$minute=post_param_integer($stub.'_minute',0);

		return strval($year).'-'.strval($month).'-'.strval($day).' '.strval($hour).':'.strval($minute);

		/*$temp=get_input_date($tmp_name);
		if (is_null($temp)) return $editing?STRING_MAGIC_NULL:'';

		return date('Y-m-d H:i:s',$temp);*/
	}

}

