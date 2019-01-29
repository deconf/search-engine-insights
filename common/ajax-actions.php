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

if ( ! class_exists( 'SEIWP_Common_Ajax' ) ) {

	final class SEIWP_Common_Ajax {

		private $seiwp;

		public function __construct() {
			$this->seiwp = SEIWP();

			if ( SEIWP_Tools::check_roles( $this->seiwp->config->options['access_back'] ) || SEIWP_Tools::check_roles( $this->seiwp->config->options['access_front'] ) ) {
				add_action( 'wp_ajax_seiwp_set_error', array( $this, 'ajax_set_error' ) );
			}
		}

		/**
		 * Ajax handler for storing JavaScript Errors
		 *
		 * @return int
		 */
		public function ajax_set_error() {
			if ( ! isset( $_POST['seiwp_security_set_error'] ) || ! ( wp_verify_nonce( $_POST['seiwp_security_set_error'], 'seiwp_backend_item_reports' ) || wp_verify_nonce( $_POST['seiwp_security_set_error'], 'seiwp_frontend_item_reports' ) ) ) {
				wp_die( - 40 );
			}
			$timeout = 24 * 60 * 60;
			SEIWP_Tools::set_error( $_POST['response'], $timeout );
			wp_die();
		}
	}
}
