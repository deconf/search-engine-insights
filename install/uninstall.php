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

class SEIWP_Uninstall {

	public static function uninstall() {
		global $wpdb;
		/**
		 * Cleanup Network install
		 */
		if ( is_multisite() ) {
			foreach ( SEIWP_Tools::get_sites( array( 'number' => apply_filters( 'seiwp_sites_limit', 100 ) ) ) as $blog ) {
				switch_to_blog( $blog['blog_id'] );
				$sqlquery = $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'seiwp_cache_%%'" );
				delete_option( 'seiwp_options' );
				restore_current_blog();
			}
			delete_site_option( 'seiwp_network_options' );
		/**
		 * Cleanup Single install
		 */
		} else {
			$sqlquery = $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'seiwp_cache_%%'" );
			delete_option( 'seiwp_options' );
		}
		SEIWP_Tools::unset_cookie( 'default_metric' );
		SEIWP_Tools::unset_cookie( 'default_dimension' );
		SEIWP_Tools::unset_cookie( 'default_view' );
	}
}
