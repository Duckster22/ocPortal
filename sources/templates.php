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
 * @package		core
 */

/**
 * Standard code module initialisation function.
 */
function init__templates()
{
	global $SKIP_TITLING;
	$SKIP_TITLING=false;
}

/**
 * Get the tempcode for a standard box (CSS driven), with the specified content entered. Please rarely use this function; it is not good to assume people want anythings in one of these boxes... use templates instead
 *
 * @param  tempcode		The content being put inside the box
 * @param  mixed			The title of the standard box, string or Tempcode (blank: titleless standard box)
 * @param  ID_TEXT		The type of the box. Refers to a template (STANDARDBOX_type)
 * @param  string			The CSS width
 * @param  string			'|' separated list of options (meaning dependant upon templates interpretation)
 * @param  string			'|' separated list of meta information (key|value|key|value|...)
 * @param  string			'|' separated list of link information (linkhtml|...)
 * @param  string			Link to be added to the header of the box
 * @return tempcode		The contents, put inside a standard box, according to the other parameters
 */
function put_in_standard_box($content,$title='',$type='default',$width='',$options='',$meta='',$links='',$top_links='')
{
	if ($type=='') $type='default';

	$_meta=array();
	if ($meta!='')
	{
		$meta_bits=explode('|',$meta);
		if (count($meta_bits)%2==1) unset($meta_bits[count($meta_bits)-1]);
		for ($i=0;$i<count($meta_bits);$i+=2)
			$_meta[]=array('KEY'=>$meta_bits[$i+0],'VALUE'=>$meta_bits[$i+1]);
	}

	$_links=array();
	if ($links!='')
	{
		$_links=explode('|',$links);
		if ($_links[count($_links)-1]=='') array_pop($_links);
	}

	$_options=explode('|',$options);

	if ($width=='auto') $width='';
	if (is_numeric($width)) $width=strval(intval($width)).'px';

	return do_template('STANDARDBOX_'.filter_naughty($type),array('WIDTH'=>$width,'CONTENT'=>$content,'LINKS'=>$_links,'META'=>$_meta,'OPTIONS'=>$_options,'TITLE'=>$title,'TOP_LINKS'=>$top_links),NULL,true);
}

/**
 * Get the tempcode for a page title. (Ones below the page header, not in the browser title bar.)
 *
 * @param  mixed			The title to use (usually, a language string code, see below)
 * @param  boolean		Whether the given title is actually a language string code, and hence gets dereferenced
 * @param  ?array			Parameters sent to the language string (NULL: none)
 * @param  ?tempcode		Separate title to put into the 'currently viewing' data (NULL: use $title)
 * @param  ?array			Awards to say this has won (NULL: none)
 * @return tempcode		The title tempcode
 */
function get_screen_title($title,$dereference_lang=true,$params=NULL,$user_online_title=NULL,$awards=NULL)
{
	global $TITLE_CALLED;
	$TITLE_CALLED=true;

	global $SKIP_TITLING;
	if ($SKIP_TITLING) return new ocp_tempcode();

	if (($dereference_lang) && (strpos($title,' ')!==false)) $dereference_lang=false;

	if ($params===NULL) $params=array();

	$our_help_term='';
	$our_help_url='';

	if ($dereference_lang)
	{
		$our_help_term=$title;
		$_title=do_lang_tempcode($title,array_key_exists(0,$params)?$params[0]:NULL,array_key_exists(1,$params)?$params[1]:NULL,array_key_exists(2,$params)?$params[2]:NULL);
	} else $_title=is_object($title)?$title:make_string_tempcode($title);

	if (function_exists('get_session_id'))
	{
		$GLOBALS['SITE_DB']->query_update('sessions',array('the_title'=>is_null($user_online_title)?substr($_title->evaluate(),0,255):$user_online_title->evaluate(),'the_zone'=>get_zone_name(),'the_page'=>substr(get_page_name(),0,80),'the_type'=>substr(get_param('type','',true),0,80),'last_activity'=>time(),'the_id'=>substr(get_param('id','',true),0,80)),array('the_session'=>get_session_id()),'',1,NULL,false,true);
	}

	global $DISPLAYED_TITLE;
	$DISPLAYED_TITLE=$_title;

	if ($our_help_url!='')
	{
		global $HELP_URL;
		$HELP_URL=$our_help_url.'#'.$our_help_term;
	}

	if ($awards===NULL) $awards=array();

	return do_template('SCREEN_TITLE',array('_GUID'=>'847ffbe4823eca6d2d5eac42828ee552','AWARDS'=>$awards,'TITLE'=>$_title,'HELP_URL'=>$our_help_url,'HELP_TERM'=>$our_help_term));
}

/**
 * Get the tempcode for a hyperlink.
 *
 * @param  mixed			The URL to put in the hyperlink (URLPATH or tempcode)
 * @param  mixed			The hyperlinks caption (either tempcode or string)
 * @param  boolean		Whether the link is an external one (by default, the external template makes it open in a new window)
 * @param  boolean		Whether to escape the hyperlink caption (only applies if it is not passed as tempcode)
 * @param  mixed			Link title (either tempcode or string) (blank: none)
 * @param  ?string		The access key to use (NULL: none)
 * @param  ?tempcode		Data to post (NULL: an ordinary link)
 * @param  ?string		Rel (link type) (NULL: no special type)
 * @param  ?ID_TEXT		Open in overlay with the default link/form target being as follows (e.g. _top or _self) (NULL: an ordinary link)
 * @return tempcode		The generated hyperlink
 */
function hyperlink($url,$caption,$external=false,$escape=false,$title='',$accesskey=NULL,$post_data=NULL,$rel=NULL,$overlay=NULL)
{
	if (((is_object($caption)) && ($caption->is_empty())) || ((!is_object($caption)) && ($caption=='')))
		$caption=do_lang('NA');

	if ($post_data!==NULL)
	{
		$tpl='HYPERLINK_BUTTON';
	} else
	{
		$tpl='HYPERLINK';
	}
	return do_template($tpl,array('OVERLAY'=>$overlay,'REL'=>$rel,'POST_DATA'=>$post_data,'ACCESSKEY'=>$accesskey,'NEW_WINDOW'=>$external,'TITLE'=>$title,'URL'=>$url,'CAPTION'=>(($escape)/* && (is_object($caption))*/) ?escape_html($caption):$caption));
}

/**
 * Get the tempcode for a paragraph. This function should only be used with escaped text strings that need to be put into a paragraph, not with sections of HTML. Remember, paragraphs are literally that, and should only be used with templates that don't assume that they are going to put the given parameters into paragraphs themselves.
 *
 * @param  mixed			The text to put into the paragraph (string or tempcode)
 * @param  string			GUID for call
 * @param  ?string		CSS classname (NULL: none)
 * @return tempcode		The generated paragraph
 */
function paragraph($text,$guid='',$class=NULL)
{
	return do_template('PARAGRAPH',array('_GUID'=>$guid,'TEXT'=>$text,'CLASS'=>$class));
}

/**
 * Get the tempcode for a div. Similar to paragraph, but may contain more formatting (such as <br />'s)
 *
 * @param  tempcode		The tempcode to put into a div
 * @param  string			GUID for call
 * @return tempcode		The generated div with contents
 */
function div($tempcode,$guid='')
{
	return do_template('DIV',array('_GUID'=>'2b0459e48a6b6420b716e05f21a933ad','TEMPCODE'=>$tempcode));
}

/**
 * Get the tempcode for an info page.
 *
 * @param  tempcode		The title of the info page
 * @param  mixed			The text to put on the info page (string, or language-tempcode)
 * @return tempcode		The info page
 */
function inform_screen($title,$text)
{
	return do_template('INFORM_SCREEN',array('_GUID'=>'6e0aec9eb8a1daca60f322f213ddd2ee','TITLE'=>$title,'TEXT'=>$text));
}

/**
 * Get the tempcode for a warn page.
 *
 * @param  tempcode		The title of the warn page
 * @param  mixed			The text to put on the warn page (either tempcode or string)
 * @param  boolean		Whether to provide a back button
 * @return tempcode		The warn page
 */
function warn_screen($title,$text,$provide_back=true)
{
	require_code('failure');

	$text_eval=is_object($text)?$text->evaluate():$text;

	if ($text_eval==do_lang('MISSING_RESOURCE'))
	{
		$GLOBALS['HTTP_STATUS_CODE']='404';
		if (!headers_sent())
		{
			if ((!browser_matches('ie')) && (strpos(ocp_srv('SERVER_SOFTWARE'),'IIS')===false)) header('HTTP/1.0 404 Not Found');
		}
		if (ocp_srv('HTTP_REFERER')!='')
		{
			relay_error_notification($text_eval.' '.do_lang('REFERRER',ocp_srv('HTTP_REFERER'),substr(get_browser_string(),0,255)),false,'error_occurred_missing_resource');
		}
	}

	if (get_param_integer('keep_fatalistic',0)==1) fatal_exit($text);

	return do_template('WARN_SCREEN',array('_GUID'=>'a762a7ac8cd08623a0ed6413d9250d97','TITLE'=>$title,'WEBSERVICE_RESULT'=>get_webservice_result($text),'TEXT'=>$text,'PROVIDE_BACK'=>$provide_back));
}

/**
 * Get the tempcode for a hidden form element.
 *
 * @param  ID_TEXT		The name which this input field is for
 * @param  string			The value for this input field
 * @return tempcode		The input field
 */
function form_input_hidden($name,$value)
{
	return do_template('FORM_SCREEN_INPUT_HIDDEN'.((strpos($value,chr(10))!==false)?'_2':''),array('_GUID'=>'1b39e13d1a09573c67522e2f3b7ebf14','NAME'=>$name,'VALUE'=>$value));
}

/**
 * Get the tempcode for a group of list entry. May be attached directly to form_input_list_entry (i.e. this is a group node in a shared tree), and also fed into form_input_list.
 *
 * @param  mixed			The title for the group
 * @param  tempcode		List entries for group
 * @return tempcode		The group
 */
function form_input_list_group($title,$entries)
{
	return do_template('FORM_SCREEN_INPUT_LIST_GROUP',array('_GUID'=>'dx76a2685d0fba5f819ef160b0816d03','TITLE'=>$title,'ENTRIES'=>$entries));
}

/**
 * Get the tempcode for a list entry. (You would gather together the outputs of several of these functions, then put them in as the $content in a form_input_list function call).
 *
 * @param  string			The value for this entry
 * @param  boolean		Whether this entry is selected by default or not
 * @param  mixed			The text associated with this choice (blank: just use name for text)
 * @param  boolean		Whether this entry will be put as red (marking it as important somehow)
 * @param  boolean		Whether this list entry is disabled (like a header in a list)
 * @return tempcode		The input field
 */
function form_input_list_entry($value,$selected=false,$text='',$red=false,$disabled=false)
{
	if ((!is_object($text)) && ($text=='')) $text=$value;

	if (function_exists('filter_form_field_default')) // Don't include just for this (may not be used on a full input form), preserve memory
		$selected=(filter_form_field_default($value,$selected?'1':'')=='1');

	return do_template('FORM_SCREEN_INPUT_LIST_ENTRY',array('_GUID'=>'dd76a2685d0fba5f819ef160b0816d03','SELECTED'=>$selected,'DISABLED'=>$disabled,'CLASS'=>$red?'criticalfield':'','NAME'=>is_integer($value)?strval($value):$value,'TEXT'=>$text));
}

/**
 * Display some raw text so that it is repeated as raw visually in HTML.
 *
 * @param  string			Input
 * @return tempcode		Output
 */
function with_whitespace($in)
{
	if ($in=='') return new ocp_tempcode();
	return do_template('WITH_WHITESPACE',array('_GUID'=>'be3b74901d5522d4e67ff6313ad61643','CONTENT'=>$in));
}

/**
 * Redirect the user - transparently, storing a message that will be shown on their destination page.
 *
 * @param  tempcode		Title to display on redirect page
 * @param  mixed			Destination URL (may be Tempcode)
 * @param  mixed			Message to show (may be Tempcode)
 * @param  boolean		For intermediatory hops, don't mark so as to read status messages - save them up for the next hop (which will not be intermediatory)
 * @param  ID_TEXT	Code of message type to show
 * @set    warn inform fatal
 * @return tempcode		Redirection message (likely to not actually be seen due to instant redirection)
 */
function redirect_screen($title,$url,$text,$intermediatory_hop=false,$msg_type='inform')
{
	require_code('templates_redirect_screen');
	return _redirect_screen($title,$url,$text,$intermediatory_hop,$msg_type);
}

