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

if ( ! class_exists( 'SEIWP_Backend_Item_Reports' ) ) {

	final class SEIWP_Backend_Item_Reports {

		private $seiwp;

		public function __construct() {
			$this->seiwp = SEIWP();

			if ( SEIWP_Tools::check_roles( $this->seiwp->config->options['access_back'] ) && 1 == $this->seiwp->config->options['backend_item_reports'] ) {
				/**
				 * Add custom column in Posts List
				 */
				add_filter( 'manage_posts_columns', array( $this, 'add_columns' ), 99 );
				/**
				 * Populate custom column in Posts List
				 */
				add_action( 'manage_posts_custom_column', array( $this, 'add_icons' ), 99, 2 );
				/**
				 * Add custom column in Pages List
				 */
				add_filter( 'manage_pages_columns', array( $this, 'add_columns' ), 99 );
				/**
				 * Populate custom column in Pages List
				 */
				add_action( 'manage_pages_custom_column', array( $this, 'add_icons' ), 99, 2 );
			}
		}

		public function add_icons( $column, $id ) {
			global $wp_version;

			if ( 'seiwp_stats' != $column ) {
				return;
			}

			echo '<a id="seiwp-' . $id . '" title="' . get_the_title( $id ) . '" href="#' . $id . '" class="seiwp-icon dashicons-before dashicons-chart-bar">&nbsp;</a>';
		}

		public function add_columns( $columns ) {
			return array_merge( $columns, array( 'seiwp_stats' => '<span class="dashicons dashicons-seiwp" title="Search Engine Insights"></span>' ) );
		}
	}
}
