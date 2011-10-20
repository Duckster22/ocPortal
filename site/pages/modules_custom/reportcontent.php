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
 */

/**
 * Module page class.
 */
class Module_reportcontent
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
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		$GLOBALS['SITE_DB']->drop_if_exists('reported_content');

		delete_config_option('reported_times');
	}
	
	/**
	 * Standard modular install function.
	 *
	 * @param  ?integer	What version we're upgrading from (NULL: new install)
	 * @param  ?integer	What hack version we're upgrading from (NULL: new-install/not-upgrading-from-a-hacked-version)
	 */
	function install($upgrade_from=NULL,$upgrade_from_hack=NULL)
	{
		require_lang('reportcontent');
		
		$GLOBALS['SITE_DB']->create_table('reported_content',array(
			'r_session_id'=>'*AUTO_LINK',
			'r_content_type'=>'*ID_TEXT',
			'r_content_id'=>'*ID_TEXT',
			'r_counts'=>'BINARY', // If the content is marked unvalidated, r_counts is set to 0 for each row for it, so if it's revalidated the counts apply elsewhere
		));
		$GLOBALS['SITE_DB']->create_index('reported_content','reported_already',array('r_content_type','r_content_id'));

		add_config_option('REPORTED_TIMES','reported_times','integer','return \'3\';','FEATURE','REPORT_CONTENT');
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
		require_lang('reportcontent');
		require_lang('ocf');
		
		// Decide what we're doing
		$type=get_param('type','misc');

		if ($type=='misc') return $this->form();
		if ($type=='actual') return $this->actualiser();

		return new ocp_tempcode();
	}
	
	function form()
	{
		$title=get_page_title('REPORT_CONTENT');
		
		require_code('form_templates');
		
		$url=get_param('url',false,true);
		$content_type=get_param('content_type'); // Equates to a content_meta_aware hook
		$content_id=get_param('content_id');
		
		require_code('content');
		
		if (!is_null($GLOBALS['SITE_DB']->query_value_null_ok('reported_content','r_counts',array(
			'r_session_id'=>get_session_id(),
			'r_content_type'=>$content_type,
			'r_content_id'=>$content_id,
		))))
			warn_exit(do_lang_tempcode('ALREADY_REPORTED_CONTENT'));

		list($content_title,$poster_id,)=content_get_details($content_type,$content_id);
		if ($content_title=='') $content_title=$content_type.' #'.$content_id;
		$poster=$GLOBALS['FORUM_DRIVER']->get_username($poster_id);
		
		// Show form with input field and CAPTCHA, like forum's report post...

		$member=$poster;
		if (!is_guest($poster_id))
			$member='[page type="view" id="'.strval($poster_id).'" param="'.get_module_zone('members').'" caption="'.$poster.'"]members[/page]';

		$hidden_fields=build_keep_form_fields('',true);

		$text=paragraph(do_lang_tempcode('DESCRIPTION_REPORT_CONTENT',escape_html($content_title),escape_html(integer_format(intval(get_option('reported_times'))))));

		$specialisation=new ocp_tempcode();
		if (!is_guest())
		{
			$options=array();
			if (get_option('is_on_anonymous_posts')=='1')
				$options[]=array(do_lang_tempcode('_MAKE_ANONYMOUS_POST'),'anonymous',false,do_lang_tempcode('MAKE_ANONYMOUS_POST_DESCRIPTION'));
			$specialisation=form_input_various_ticks($options,'');
		} else $specialisation=new ocp_tempcode();
		if (addon_installed('captcha'))
		{
			require_code('captcha');
			if (use_captcha())
			{
				$specialisation->attach(form_input_captcha());
				$text->attach(paragraph(do_lang_tempcode('FORM_TIME_SECURITY')));
			}
		}

		if (addon_installed('points'))
		{
			$login_url=build_url(array('page'=>'login','type'=>'misc','redirect'=>get_self_url(true,true)),get_module_zone('login'));
			$_login_url=escape_html($login_url->evaluate());
			if ((is_guest()) && ((get_forum_type()!='ocf') || (has_actual_page_access(get_member(),'join')))) $text->attach(paragraph(do_lang_tempcode('NOT_LOGGED_IN_NO_CREDIT',$_login_url)));
		}

		$post_url=build_url(array('page'=>'_SELF','type'=>'actual'),'_SELF');

		$post=do_template('REPORTED_CONTENT_FCOMCODE',array('URL'=>$url,'CONTENT_ID'=>$content_id,'MEMBER'=>$member,'CONTENT_TITLE'=>$content_title,'POSTER'=>$poster));
		$posting_form=get_posting_form(do_lang('REPORT_CONTENT'),$post->evaluate(),$post_url,$hidden_fields,$specialisation,NULL,'',NULL,NULL,NULL,NULL,true,false);

		return do_template('POSTING_SCREEN',array('TITLE'=>$title,'JAVASCRIPT'=>function_exists('captcha_ajax_check')?captcha_ajax_check():'','TEXT'=>$text,'POSTING_FORM'=>$posting_form));
	}
	
	function actualiser()
	{
		$title=get_page_title('REPORT_CONTENT');

		// Test CAPTCHA
		if (addon_installed('captcha'))
		{
			require_code('captcha');
			enforce_captcha();
		}
		
		require_code('content');

		$content_type=post_param('content_type'); // Equates to a content_meta_aware hook
		$content_id=post_param('content_id');

		if (!is_null($GLOBALS['SITE_DB']->query_value_null_ok('reported_content','r_counts',array(
			'r_session_id'=>get_session_id(),
			'r_content_type'=>$content_type,
			'r_content_id'=>$content_id,
		))))
			warn_exit(do_lang_tempcode('ALREADY_REPORTED_CONTENT'));
		list($content_title,,$cma_info)=content_get_details($content_type,$content_id);

		// Create reported post...
		$forum_id=$GLOBALS['FORUM_DRIVER']->forum_id_from_name(get_option('reported_posts_forum'));
		if (is_null($forum_id)) warn_exit(do_lang_tempcode('ocf:NO_REPORTED_POST_FORUM'));
		// See if post already reported...
		$post=post_param('post');
		$anonymous=post_param_integer('anonymous',0);
		$topic_id=$GLOBALS['FORUM_DB']->query_value_null_ok('f_topics t LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts p ON p.id=t.t_cache_first_post_id','t.id',array('p.p_title'=>$content_title,'t.t_forum_id'=>$forum_id));
		require_code('ocf_topics_action');
		require_code('ocf_topics_action2');
		require_code('ocf_posts_action');
		require_code('ocf_posts_action2');
		if (!is_null($topic_id))
		{
			// Already a topic
		} else // New topic
		{
			$topic_id=ocf_make_topic($forum_id,'','',1,1,0,0,0,NULL,NULL,false);
		}
		$topic_title=do_lang('REPORTED_CONTENT_TITLE',$content_title);
		$post_id=ocf_make_post($topic_id,$content_title,$post,0,is_null($topic_id),1,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,false,true,$forum_id,true,$topic_title,0,NULL,$anonymous==1);

		// Add to reported_content table
		$GLOBALS['SITE_DB']->query_insert('reported_content',array(
			'r_session_id'=>get_session_id(),
			'r_content_type'=>$content_type,
			'r_content_id'=>$content_id,
			'r_counts'=>1,
		));

		// If hit threshold, mark down r_counts and unvalidate the content
		$count=$GLOBALS['SITE_DB']->query_value('reported_content','COUNT(*)',array(
			'r_content_type'=>$content_type,
			'r_content_id'=>$content_id,
			'r_counts'=>1,
		));
		if ($count>=intval(get_option('reported_times')))
		{
			// Mark as unvalidated
			if ((isset($cma_info['validated_field'])) && (strpos($cma_info['table'],'(')===false))
			{
				$db=$GLOBALS[(substr($cma_info['table'],0,2)=='f_')?'FORUM_DB':'SITE_DB'];
				$db->query_update($cma_info['table'],array($cma_info['validated_field']=>0),array($cma_info['id_field']=>$cma_info['id_field_numeric']?intval($content_id):$content_id));
			}

			$GLOBALS['SITE_DB']->query_update('reported_content',array('r_counts'=>0),array(
				'r_content_type'=>$content_type,
				'r_content_id'=>$content_id,
			));
		}

		// Done
		list($zone,$url_bits)=page_link_decode(str_replace('_WILD',$content_id,$cma_info['view_pagelink_pattern']));
		$url=build_url($url_bits,$zone);
		$_url=post_param('url','',true);
		if ($_url!='')
			$url=make_string_tempcode($_url);
		require_code('templates_redirect_screen');
		return redirect_screen($title,$url,do_lang_tempcode('SUCCESS'));
	}

}


/*HACKHACK...

Before this can be an official ocPortal feature new content_meta_aware hooks are needed. Currently for instance there's no 'post' one.
*/