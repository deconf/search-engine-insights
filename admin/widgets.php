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

if ( ! class_exists( 'SEIWP_Backend_Widgets' ) ) {

	class SEIWP_Backend_Widgets {

		private $seiwp;

		public function __construct() {
			$this->seiwp = SEIWP();
			if ( SEIWP_Tools::check_roles( $this->seiwp->config->options['access_back'] ) && ( 1 == $this->seiwp->config->options['dashboard_widget'] ) ) {
				add_action( 'wp_dashboard_setup', array( $this, 'add_widget' ) );
			}
		}

		public function add_widget() {
			wp_add_dashboard_widget( 'seiwp-widget', __( "Search Engine Insights", 'search-engine-insights' ), array( $this, 'dashboard_widget' ), $control_callback = null );
		}

		public function dashboard_widget() {
			$projectId = 0;

			if ( empty( $this->seiwp->config->options['token'] ) ) {
				echo '<p>' . __( "This plugin needs an authorization:", 'search-engine-insights' ) . '</p><form action="' . menu_page_url( 'seiwp_setup', false ) . '" method="POST">' . get_submit_button( __( "Authorize Plugin", 'search-engine-insights' ), 'secondary' ) . '</form>';
				return;
			}

			if ( current_user_can( 'manage_options' ) ) {
				if ( $this->seiwp->config->options['site_jail'] ) {
					$projectId = $this->seiwp->config->options['site_jail'];
				} else {
					echo '<p>' . __( "An admin should asign a default Search Engine Insights Profile.", 'search-engine-insights' ) . '</p><form action="' . menu_page_url( 'seiwp_setup', false ) . '" method="POST">' . get_submit_button( __( "Select Domain", 'search-engine-insights' ), 'secondary' ) . '</form>';
					return;
				}
			} else {
				if ( $this->seiwp->config->options['site_jail'] ) {
					$projectId = $this->seiwp->config->options['site_jail'];
				} else {
					echo '<p>' . __( "An admin should asign a default Search Engine Insights Profile.", 'search-engine-insights' ) . '</p><form action="' . menu_page_url( 'seiwp_setup', false ) . '" method="POST">' . get_submit_button( __( "Select Domain", 'search-engine-insights' ), 'secondary' ) . '</form>';
					return;
				}
			}

			if ( ! ( $projectId ) ) {
				echo '<p>' . __( "Something went wrong while retrieving property data. You need to create and properly configure a Google Search Console account:", 'search-engine-insights' ) . '</p> <form action="https://deconf.com/how-to-set-up-google-analytics-on-your-website/" method="POST">' . get_submit_button( __( "Find out more!", 'search-engine-insights' ), 'secondary' ) . '</form>';
				return;
			}

			?>
<div id="seiwp-window-1"></div>
<?php
		}
	}
}
