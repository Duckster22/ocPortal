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
 * @package		pointstore
 */

/**
 * Module page class.
 */
class Module_pointstore
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		$info=array();
		$info['author']='Allen Ellis';
		$info['organisation']='ocProducts';
		$info['hacked_by']=NULL;
		$info['hack_version']=NULL;
		$info['version']=4;
		$info['locked']=false;
		$info['update_require_upgrade']=1;
		return $info;
	}

	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		$GLOBALS['SITE_DB']->drop_if_exists('prices');
		$GLOBALS['SITE_DB']->drop_if_exists('sales');
		$GLOBALS['SITE_DB']->drop_if_exists('pstore_customs');
		$GLOBALS['SITE_DB']->drop_if_exists('pstore_permissions');

		delete_config_option('is_on_banner_buy');
		delete_config_option('initial_banner_hits');
		delete_config_option('is_on_pop3_buy');
		delete_config_option('initial_quota');
		delete_config_option('max_quota');
		delete_config_option('mail_server');
		delete_config_option('pop_url');
		delete_config_option('quota_url');
		delete_config_option('is_on_forw_buy');
		delete_config_option('forw_url');
		delete_config_option('highlight_name');
		delete_config_option('topic_pin');
		delete_config_option('maximum_gamble_multiplier');
		delete_config_option('average_gamble_multiplier');
		delete_config_option('banner_setup');
		delete_config_option('banner_imp');
		delete_config_option('banner_hit');
		delete_config_option('quota');
		delete_config_option('text');
		delete_config_option('is_on_flagrant_buy');
		delete_config_option('is_on_highlight_name_buy');
		delete_config_option('is_on_topic_pin_buy');
		delete_config_option('is_on_gambling_buy');
		delete_config_option('minimum_gamble_amount');
		delete_config_option('maximum_gamble_amount');

		delete_menu_item_simple('_SEARCH:pointstore:type=misc');
	}

	/**
	 * Standard modular install function.
	 *
	 * @param  ?integer	What version we're upgrading from (NULL: new install)
	 * @param  ?integer	What hack version we're upgrading from (NULL: new-install/not-upgrading-from-a-hacked-version)
	 */
	function install($upgrade_from=NULL,$upgrade_from_hack=NULL)
	{
		if (($upgrade_from<4) && (!is_null($upgrade_from)))
		{
			delete_config_option('is_on_shop');
		}

		if (($upgrade_from<3) || (is_null($upgrade_from)))
		{
			//  Highlighted names
				add_config_option('ENABLE_PURCHASE','is_on_highlight_name_buy','tick','return (get_forum_type()!=\'ocf\')?false:\'1\';','POINTSTORE','NAME_HIGHLIGHTING');
				add_config_option('COST_highlight_name','highlight_name','integer','return (get_forum_type()!=\'ocf\')?false:\'2000\';','POINTSTORE','NAME_HIGHLIGHTING');
			//  Topic pinning
				add_config_option('ENABLE_PURCHASE','is_on_topic_pin_buy','tick','return (!addon_installed(\'ocf_forum\'))?false:\'1\';','POINTSTORE','TOPIC_PINNING');
				add_config_option('COST_topic_pin','topic_pin','integer','return (!addon_installed(\'ocf_forum\'))?false:\'180\';','POINTSTORE','TOPIC_PINNING');
			//  Gambling
				add_config_option('ENABLE_PURCHASE','is_on_gambling_buy','tick','return \'1\';','POINTSTORE','GAMBLING');
				add_config_option('MINIMUM_GAMBLE_AMOUNT','minimum_gamble_amount','integer','return \'6\';','POINTSTORE','GAMBLING');
				add_config_option('MAXIMUM_GAMBLE_AMOUNT','maximum_gamble_amount','integer','return \'200\';','POINTSTORE','GAMBLING');
				add_config_option('MAXIMUM_GAMBLE_MULTIPLIER','maximum_gamble_multiplier','integer','return \'200\';','POINTSTORE','GAMBLING');
				add_config_option('AVERAGE_GAMBLE_MULTIPLIER','average_gamble_multiplier','integer','return \'85\';','POINTSTORE','GAMBLING');
			//  Banners
				add_config_option('COST_banner_setup','banner_setup','integer','return (!addon_installed(\'banners\'))?false:\'750\';','POINTSTORE','BANNERS');
				add_config_option('COST_banner_imp','banner_imp','integer','return (!addon_installed(\'banners\'))?false:\'700\';','POINTSTORE','BANNERS');
				add_config_option('COST_banner_hit','banner_hit','integer','return (!addon_installed(\'banners\'))?false:\'20\';','POINTSTORE','BANNERS');
			//  POP3
				add_config_option('COST_quota','quota','integer','return \'2\';','POINTSTORE','POP3');
			//  Flagrant
				add_config_option('COST_text','text','integer','return (!addon_installed(\'flagrant\'))?false:\'700\';','POINTSTORE','FLAGRANT_MESSAGE');
			// Custom
				$GLOBALS['SITE_DB']->create_table('pstore_customs',array(
					'id'=>'*AUTO',
					'c_title'=>'SHORT_TRANS',
					'c_description'=>'LONG_TRANS',
					'c_enabled'=>'BINARY',
					'c_cost'=>'INTEGER',
					'c_one_per_member'=>'BINARY',
				));
			// Permissions
				$GLOBALS['SITE_DB']->create_table('pstore_permissions',array(
					'id'=>'*AUTO',
					'p_title'=>'SHORT_TRANS',
					'p_description'=>'LONG_TRANS',
					'p_enabled'=>'BINARY',
					'p_cost'=>'INTEGER',
					'p_hours'=>'INTEGER',
					'p_type'=>'ID_TEXT', // msp,member_category_access,member_page_access,member_zone_access
					'p_specific_permission'=>'ID_TEXT', // sp only
					'p_zone'=>'ID_TEXT', // zone and page only
					'p_page'=>'ID_TEXT', // page and ?sp only
					'p_module'=>'ID_TEXT', // category and ?sp only
					'p_category'=>'ID_TEXT', // category and ?sp only
				));
		}

		if (is_null($upgrade_from))
		{
			$GLOBALS['SITE_DB']->create_table('prices',array(
				'name'=>'*ID_TEXT',
				'price'=>'INTEGER'
			));

			$GLOBALS['SITE_DB']->create_table('sales',array(
				'id'=>'*AUTO',
				'date_and_time'=>'TIME',
				'memberid'=>'USER',
				'purchasetype'=>'ID_TEXT',
				'details'=>'SHORT_TEXT',
				'details2'=>'SHORT_TEXT'
			));

			// Pointstore Options
			//  Banners
				add_config_option('ENABLE_PURCHASE','is_on_banner_buy','tick','return (!addon_installed(\'banners\'))?false:\'1\';','POINTSTORE','BANNERS');
				add_config_option('HITS_ALLOCATED','initial_banner_hits','integer','return (!addon_installed(\'banners\'))?false:\'100\';','POINTSTORE','BANNERS');
			//  POP3
				add_config_option('ENABLE_PURCHASE','is_on_pop3_buy','tick','return \'0\';','POINTSTORE','POP3',1);
				add_config_option('QUOTA','initial_quota','integer','return \'200\';','POINTSTORE','POP3',1);
				add_config_option('MAX_QUOTA','max_quota','integer','return \'10000\';','POINTSTORE','POP3',1);
				add_config_option('MAIL_SERVER','mail_server','line','return \'mail.\'.get_domain();','POINTSTORE','POP3',1);
				add_config_option('POP3_MAINTAIN_URL','pop_url','line','return \'http://\'.get_domain().\':2082/frontend/x/mail/addpop2.html\';','POINTSTORE','POP3',1);
				add_config_option('QUOTA_MAINTAIN_URL','quota_url','line','return \'http://\'.get_domain().\':2082/frontend/x/mail/pops.html\';','POINTSTORE','POP3',1);
			//  Forwarding
				add_config_option('ENABLE_PURCHASE','is_on_forw_buy','tick','return \'0\';','POINTSTORE','FORWARDING',1);
				add_config_option('FORW_MAINTAIN_URL','forw_url','line','return \'http://\'.get_domain().\':2082/frontend/x/mail/addfwd.html\';','POINTSTORE','FORWARDING',1);
			//  Flagrant
				add_config_option('ENABLE_PURCHASE','is_on_flagrant_buy','tick','return (!addon_installed(\'flagrant\'))?false:\'1\';','POINTSTORE','FLAGRANT_MESSAGE');

			require_lang('pointstore');
			add_menu_item_simple('main_community',NULL,'POINT_STORE','_SEARCH:pointstore:type=misc');
		}
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return is_guest()?array():array('!'=>'POINT_STORE');
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		require_code('pointstore');
		require_lang('pointstore');
		require_lang('points');
		require_code('points');
		require_css('points');

		$title=get_page_title('POINT_STORE');

		$type=get_param('type','misc');
		$hook=get_param('id','');

		// Not logged in
		if (is_guest())
		{
			access_denied('NOT_AS_GUEST');
		}

		if ($hook!='')
		{
			require_code('hooks/modules/pointstore/'.filter_naughty_harsh($hook),true);
			$object=object_factory('Hook_pointstore_'.filter_naughty_harsh($hook));
			$object->init();
			breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('POINT_STORE'))));
			if (method_exists($object,$type)) return call_user_func(array($object,$type));
		}

		if ($type=='misc') return $this->do_module_gui();
		return new ocp_tempcode();
	}

	/**
	 * The UI to choose a section of the point-store.
	 *
	 * @return tempcode		The UI
	 */
	function do_module_gui()
	{
		$title=get_page_title('POINT_STORE');

		$points_left=available_points(get_member());

		$items=new ocp_tempcode();

		$_hooks=find_all_hooks('modules','pointstore');
		foreach (array_keys($_hooks) as $hook)
		{
			require_code('hooks/modules/pointstore/'.filter_naughty_harsh($hook),true);
			$object=object_factory('Hook_pointstore_'.filter_naughty_harsh($hook),true);
			if (is_null($object)) continue;
			$object->init();
			$tpls=$object->info();
			foreach ($tpls as $tpl)
			{
				$item=do_template('POINTSTORE_ITEM',array('_GUID'=>'1316f918b3c19331d5d8e55402a7ae45','ITEM'=>$tpl));
				$items->attach($item);
			}
		}

		if (get_option('is_on_forw_buy')=='1')
		{
			$forwarding_url=build_url(array('page'=>'_SELF','type'=>'newforwarding','id'=>'forwarding'),'_SELF');

			if ($GLOBALS['SITE_DB']->query_value_null_ok_full('SELECT COUNT(*) FROM '.get_table_prefix().'prices WHERE name LIKE \''.db_encode_like('forw_%').'\'')>0)
				$_pointstore_mail_forwarding_link=$forwarding_url;
			else $_pointstore_mail_forwarding_link=NULL;
			$pointstore_mail_forwarding_link=do_template('POINTSTORE_MFORWARDING_LINK',array('_GUID'=>'e93666809dc3e47e3660245711f545ee','FORWARDING_URL'=>$_pointstore_mail_forwarding_link));

		} else $pointstore_mail_forwarding_link=new ocp_tempcode();
		if (get_option('is_on_pop3_buy')=='1')
		{
			$pop3_url=build_url(array('page'=>'_SELF','type'=>'pop3info','id'=>'pop3'),'_SELF');

			if ($GLOBALS['SITE_DB']->query_value_null_ok_full('SELECT COUNT(*) FROM '.get_table_prefix().'prices WHERE name LIKE \''.db_encode_like('pop3_%').'\'')>0)
				$_pointstore_mail_pop3_link=$pop3_url;
			else $_pointstore_mail_pop3_link=NULL;
			$pointstore_mail_pop3_link=do_template('POINTSTORE_MPOP3_LINK',array('_GUID'=>'42925a17262704450e451ad8502bce0d','POP3_URL'=>$_pointstore_mail_pop3_link));

		} else $pointstore_mail_pop3_link=new ocp_tempcode();

		if ((!$pointstore_mail_pop3_link->is_empty()) || (!$pointstore_mail_pop3_link->is_empty()))
		{
			$mail_tpl=do_template('POINTSTORE_MAIL',array('_GUID'=>'4a024f39a4065197b2268ecd2923b8d6','POINTSTORE_MAIL_POP3_LINK'=>$pointstore_mail_pop3_link,'POINTSTORE_MAIL_FORWARDING_LINK'=>$pointstore_mail_forwarding_link));
			$items->attach(do_template('POINTSTORE_ITEM',array('_GUID'=>'815b00b651757d4052cb494ed6a8d926','ITEM'=>$mail_tpl)));
		}

		$username=$GLOBALS['FORUM_DRIVER']->get_username(get_member());
		return do_template('POINTSTORE_SCREEN',array('_GUID'=>'1b66923dd1a3da6afb934a07909b8aa7','TITLE'=>$title,'ITEMS'=>$items,'POINTS_LEFT'=>integer_format($points_left),'USERNAME'=>$username));
	}

}


