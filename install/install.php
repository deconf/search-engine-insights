<?php
/**
 * Author: Alin Marcu
 * Author URI: https://deconf.com
 * Copyright 2013 Alin Marcu
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit();

class SEIWP_Install {

	public static function install() {
		$options = array();
		$options['client_id'] = '';
		$options['client_secret'] = '';
		$options['access_front'][] = 'administrator';
		$options['access_back'][] = 'administrator';
		$options['site_jail'] = '';
		$options['theme_color'] = '#2c5fb2';
		$options['switch_profile'] = 0;
		$options['user_api'] = 0;
		$options['token'] = '';
		$options['sites_list'] = array();
		$options['network_mode'] = 0;
		$options['backend_item_reports'] = 1;
		$options['frontend_item_reports'] = 0;
		$options['dashboard_widget'] = 1;
		$options['api_backoff'] = 0;
		$options['maps_api_key'] = '';
		$options['with_endpoint'] = 1;
		$options['site_verification_meta'] = '';
		$options['sites_list_locked'] = 0;
		add_option( 'seiwp_options', json_encode( $options ) );
	}
}
