<?php
/**
 * Author: Alin Marcu
 * Copyright 2019 Alin Marcu
 * Author URI: https://deconf.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit();

if ( ! class_exists( 'SEIWP_Site_Verification' ) ) {

	final class SEIWP_Site_Verification {

		private $seiwp;

		public function __construct() {
			$this->seiwp = SEIWP();
			/**
			 * Add verification META
			 */
			add_action( 'wp_head', array( $this, 'add_meta_tag' ) );
		}

		public function add_meta_tag() {
			if ( isset( $this->seiwp->config->options['site_verification_meta'] ) && $this->seiwp->config->options['site_verification_meta'] )
				echo $this->seiwp->config->options['site_verification_meta'] . "\n";
		}
	}
}