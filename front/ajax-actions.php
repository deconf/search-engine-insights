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

if ( ! class_exists( 'SEIWP_Frontend_Ajax' ) ) {

	final class SEIWP_Frontend_Ajax {

		private $seiwp;

		public function __construct() {
			$this->seiwp = SEIWP();

			if ( SEIWP_Tools::check_roles( $this->seiwp->config->options['access_front'] ) && $this->seiwp->config->options['frontend_item_reports'] ) {
				/**
				 * Item Reports action
				 */
				add_action( 'wp_ajax_seiwp_frontend_item_reports', array( $this, 'ajax_item_reports' ) );
			}
		}

		/**
		 * Ajax handler for Item Reports
		 *
		 * @return string|int
		 */
		public function ajax_item_reports() {
			if ( ! isset( $_POST['seiwp_security_frontend_item_reports'] ) || ! wp_verify_nonce( $_POST['seiwp_security_frontend_item_reports'], 'seiwp_frontend_item_reports' ) ) {
				wp_die( - 30 );
			}

			$from = sanitize_option( 'date_format', $_POST['from'] );
			$to = sanitize_option( 'date_format', $_POST['to'] );
			$query = sanitize_text_field( $_POST['query'] );
			$uri =  sanitize_option( 'siteurl', $_POST['filter'] );
			if ( isset( $_POST['metric'] ) ) {
				$metric = sanitize_text_field( $_POST['metric'] );
			} else {
				$metric = 'impressions';
			}

			$query = sanitize_text_field( $_POST['query'] );
			if ( ob_get_length() ) {
				ob_clean();
			}

			if ( ! SEIWP_Tools::check_roles( $this->seiwp->config->options['access_front'] ) || 0 == $this->seiwp->config->options['frontend_item_reports'] ) {
				wp_die( - 31 );
			}

			if ( $this->seiwp->config->options['token'] && $this->seiwp->config->options['site_jail'] ) {
				if ( null === $this->seiwp->gapi_controller ) {
					$this->seiwp->gapi_controller = new SEIWP_GAPI_Controller();
				}
			} else {
				wp_die( - 24 );
			}

			if ( $this->seiwp->config->options['site_jail'] ) {
				$projectId = $this->seiwp->config->options['site_jail'];
			} else {
				wp_die( - 26 );
			}

			$this->seiwp->gapi_controller->timeshift = (int) current_time( 'timestamp' ) - time();

			// allow URL correction before sending an API request
			$filter = apply_filters( 'seiwp_frontenditem_uri', $uri );

			$queries = explode( ',', $query );

			$results = array();

			foreach ( $queries as $value ) {
				$results[] = $this->seiwp->gapi_controller->get( $projectId, $value, $from, $to, $filter, $metric );
			}

			wp_send_json( $results );
		}
	}
}
