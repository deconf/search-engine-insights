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

if ( ! class_exists( 'SEIWP_Config' ) ) {

	final class SEIWP_Config {

		public $options;

		public function __construct() {
			/**
			 * Get plugin options
			 */
			$this->get_plugin_options();
			/**
			 * Provide language packs for all available Network languages
			 */
			if ( is_multisite() ) {
				add_filter( 'plugins_update_check_locales', array( $this, 'translation_updates' ), 10, 1 );
			}
		}

		/**
		 * Helper function to update language packs for an entire network
		 */
		public function translation_updates( $locales ) {
			$languages = get_available_languages();
			return array_values( $languages );
		}

		/**
		 * Validates options before storing
		 */
		private function validate_data( $options ) {
			/* @formatter:off */
			$numerics = array( 	'with_endpoint',
																							'switch_profile',
																							'backend_item_reports',
																							'dashboard_widget',
																							'frontend_item_reports',
																							'user_api',
																							'api_backoff',
																							'with_endpoint',
			);
			foreach ( $numerics as $key ) {
				if ( isset( $options[$key] ) ) {
					$options[$key] = (int) $options[$key];
				}
			}

			$texts = array( 'client_id',
																			'client_secret',
																			'maps_api_key',
																			'site_jail',
																			'theme_color',
			);
			foreach ( $texts as $key ) {
				if ( isset( $options[$key] ) ) {
					$options[$key] = sanitize_text_field( $options[$key] );
				}
			}
			/* @formatter:on */

			return $options;
		}

		/**
		 * Helper function to store options based on WordPress setup (Single or Network)
		 * @param boolean $network_settings
		 */
		public function set_plugin_options( $network_settings = false) {
			// Handle Network Mode
			$options = $this->options;
			$get_network_options = get_site_option( 'seiwp_network_options' );
			$old_network_options = (array) json_decode( $get_network_options );

			if ( is_multisite() ) {
				if ( $network_settings ) { // Retrieve network options, clear blog options, store both to db
					$network_options['token'] = $this->options['token'];
					$options['token'] = '';
					if ( is_network_admin() ) {
						$network_options['sites_list'] = $this->options['sites_list'];
						$options['sites_list'] = array();
						$network_options['client_id'] = $this->options['client_id'];
						$options['client_id'] = '';
						$network_options['client_secret'] = $this->options['client_secret'];
						$options['client_secret'] = '';
						$network_options['user_api'] = $this->options['user_api'];
						$options['user_api'] = 0;
						$network_options['network_mode'] = $this->options['network_mode'];
						//unset( $options['network_mode'] );
						if ( isset( $this->options['network_tableid'] ) ) {
							$network_options['network_tableid'] = $this->options['network_tableid'];
							//unset( $options['network_tableid'] );
						}
					}
					$merged_options = array_merge( $old_network_options, $network_options );
					update_site_option( 'seiwp_network_options', json_encode( $this->validate_data( $merged_options ) ) );
				}
			}
			update_option( 'seiwp_options', json_encode( $this->validate_data( $options ) ) );
		}

		/**
		 * Retrieve plugin options
		 */
		private function get_plugin_options() {
			global $blog_id;

			if ( ! get_option( 'seiwp_options' ) ) {
				SEIWP_Install::install();
			}
			$this->options = (array) json_decode( get_option( 'seiwp_options' ) );
			// Maintain Compatibility
			$this->maintain_compatibility();
			// Handle Network Mode
			if ( is_multisite() ) {
				$get_network_options = get_site_option( 'seiwp_network_options' );
				$network_options = (array) json_decode( $get_network_options );
				if ( isset( $network_options['network_mode'] ) && ( $network_options['network_mode'] ) ) {
					if ( ! is_network_admin() && ! empty( $network_options['sites_list'] ) && isset( $network_options['network_tableid']->$blog_id ) ) {
						$network_options['sites_list'] = array( 0 => SEIWP_Tools::get_selected_site( $network_options['sites_list'], $network_options['network_tableid']->$blog_id ) );
						$network_options['site_jail'] = $network_options['sites_list'][0][0];
					}
					$this->options = array_merge( $this->options, $network_options );
				} else {
					$this->options['network_mode'] = 0;
				}
			}
		}

		/**
		 * Helps maintaining backwards compatibility
		 */
		private function maintain_compatibility() {
			$flag = false;

			$prevver = get_option( 'seiwp_version' );
			if ( $prevver && SEIWP_CURRENT_VERSION != $prevver ) {
				$flag = true;
				update_option( 'seiwp_version', SEIWP_CURRENT_VERSION );
				update_option( 'seiwp_got_updated', true );
				SEIWP_Tools::clear_cache();
				SEIWP_Tools::delete_cache( 'last_error' );
				if ( is_multisite() ) { // Cleanup errors and cookies on the entire network
					foreach ( SEIWP_Tools::get_sites( array( 'number' => apply_filters( 'seiwp_sites_limit', 100 ) ) ) as $blog ) {
						switch_to_blog( $blog['blog_id'] );
						SEIWP_Tools::delete_cache( 'gapi_errors' );
						restore_current_blog();
					}
				} else {
					SEIWP_Tools::delete_cache( 'gapi_errors' );
				}
			}

			/* @formatter:off */
			$zeros = array('sites_list_locked');
			foreach ( $zeros as $key ) {
				if ( ! isset( $this->options[$key] ) ) {
					$this->options[$key] = 0;
					$flag = true;
				}
			}

			$unsets = array();
			foreach ( $unsets as $key ) {
				if ( isset( $this->options[$key] ) ) {
					unset( $this->options[$key] );
					$flag = true;
				}
			}

			$empties = array('site_verification_meta');
			foreach ( $empties as $key ) {
				if ( ! isset( $this->options[$key] ) ) {
					$this->options[$key] = '';
					$flag = true;
				}
			}

			$ones = array();
			foreach ( $ones as $key ) {
				if ( ! isset( $this->options[$key] ) ) {
					$this->options[$key] = 1;
					$flag = true;
				}
			}

			$arrays = array( 	'access_front',
								'access_back',
								'sites_list',
			);
			foreach ( $arrays as $key ) {
				if ( ! is_array( $this->options[$key] ) ) {
					$this->options[$key] = array();
					$flag = true;
				}
			}
			if ( empty( $this->options['access_front'] ) ) {
				$this->options['access_front'][] = 'administrator';
			}
			if ( empty( $this->options['access_back'] ) ) {
				$this->options['access_back'][] = 'administrator';
			}

			/* @formatter:on */

			if ( $flag ) {
				$this->set_plugin_options( true );
			}
		}
	}
}

