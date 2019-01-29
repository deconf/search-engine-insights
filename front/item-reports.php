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

if ( ! class_exists( 'SEIWP_Frontend_Item_Reports' ) ) {

	final class SEIWP_Frontend_Item_Reports {

		private $seiwp;

		public function __construct() {
			$this->seiwp = SEIWP();

			add_action( 'admin_bar_menu', array( $this, 'custom_adminbar_node' ), 999 );
		}

		function custom_adminbar_node( $wp_admin_bar ) {
			if ( SEIWP_Tools::check_roles( $this->seiwp->config->options['access_front'] ) && $this->seiwp->config->options['frontend_item_reports'] ) {
				/* @formatter:off */
				$args = array( 	'id' => 'seiwp-1',
					'title' => '<span class="ab-icon"></span><span class="">' . __( "Search Engine", 'search-engine-insights' ) . '</span>',
					'href' => '#1',
				);
				/* @formatter:on */
				$wp_admin_bar->add_node( $args );
			}
		}
	}
}
