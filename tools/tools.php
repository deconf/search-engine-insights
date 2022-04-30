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

if ( ! class_exists( 'SEIWP_Tools' ) ) {

	class SEIWP_Tools {

		/**
		 * Loads ISO 3166 country codes
		 * @return array
		 */
		public static function get_countrycodes() {
			include 'iso3166.php';
			return $country_codes;
		}

		/**
		 * Tries to guess the default property to be assigned to a site
		 * @param string $sites
		 * @return string
		 */
		public static function guess_default_domain( $sites ) {
			$siteurl = SEIWP_SITE_URL;
			if ( ! empty( $sites ) ) {
				foreach ( $sites as $item ) {
					if ( $item[0] == $siteurl && 'siteUnverifiedUser' != $item[1] ) {
						return $item[0];
					}
				}
				return '';
			} else {
				return '';
			}
		}

		/**
		 * Get property details based on site
		 * @param array $sites
		 * @param string $site
		 * @return array
		 */
		public static function get_selected_site( $sites, $site ) {
			if ( ! empty( $sites ) ) {
				foreach ( $sites as $item ) {
					if ( $item[0] == $site ) {
						return $item;
					}
				}
			}
			return '';
		}

		/**
		 * Extract the root domain from a URL
		 * @return string
		 */
		public static function get_root_domain() {
			$url = site_url();
			$root = explode( '/', $url );
			preg_match( '/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', str_ireplace( 'www', '', isset( $root[2] ) ? $root[2] : $url ), $root );
			if ( isset( $root['domain'] ) ) {
				return $root['domain'];
			} else {
				return '';
			}
		}

		/**
		 * Simple function to remove the protocol
		 * @param string $domain
		 * @return string
		 */
		public static function strip_protocol( $domain ) {
			return str_replace( array( "https://", "http://", " " ), "", $domain );
		}

		/**
		 * Generates a color variation of a base color
		 * @param string $colour
		 * @param int $per
		 * @return string
		 */
		public static function colourVariator( $colour, $per ) {
			$colour = substr( $colour, 1 );
			$rgb = '';
			$per = $per / 100 * 255;
			if ( $per < 0 ) {
				// Darker
				$per = abs( $per );
				for ( $x = 0; $x < 3; $x++ ) {
					$c = hexdec( substr( $colour, ( 2 * $x ), 2 ) ) - $per;
					$c = ( $c < 0 ) ? 0 : dechex( $c );
					$rgb .= ( strlen( $c ) < 2 ) ? '0' . $c : $c;
				}
			} else {
				// Lighter
				for ( $x = 0; $x < 3; $x++ ) {
					$c = hexdec( substr( $colour, ( 2 * $x ), 2 ) ) + $per;
					$c = ( $c > 255 ) ? 'ff' : dechex( $c );
					$rgb .= ( strlen( $c ) < 2 ) ? '0' . $c : $c;
				}
			}
			return '#' . $rgb;
		}

		/**
		 * Generates multiple color variations from a base color
		 * @param string $base
		 * @return array
		 */
		public static function variations( $base ) {
			$variations[] = $base;
			$variations[] = self::colourVariator( $base, - 10 );
			$variations[] = self::colourVariator( $base, + 10 );
			$variations[] = self::colourVariator( $base, + 20 );
			$variations[] = self::colourVariator( $base, - 20 );
			$variations[] = self::colourVariator( $base, + 30 );
			$variations[] = self::colourVariator( $base, - 30 );
			return $variations;
		}

		/**
		 * Determines if a user has access to a specific feature
		 * @param string $access_level
		 * @param boolean $flag
		 * @return boolean
		 */
		public static function check_roles( $access_level, $flag = false) {
			if ( is_user_logged_in() && isset( $access_level ) ) {
				$current_user = wp_get_current_user();
				$roles = (array) $current_user->roles;
				if ( ( current_user_can( 'manage_options' ) ) && ! $flag ) {
					return true;
				}
				if ( count( array_intersect( $roles, $access_level ) ) > 0 ) {
					return true;
				} else {
					return false;
				}
			}
		}

		/**
		 * Cookie cleanup on uninstall
		 * @param string $name
		 */
		public static function unset_cookie( $name ) {
			$name = 'seiwp_wg_' . $name;
			setcookie( $name, '', time() - 3600, '/' );
			$name = 'seiwp_ir_' . $name;
			setcookie( $name, '', time() - 3600, '/' );
		}

		/**
		 * Cache Helper function. I don't use transients because cleanup plugins can break their functionality
		 * @param string $name
		 * @param mixed $value
		 * @param number $expiration
		 */
		public static function set_cache( $name, $value, $expiration = 0) {
			$option = array( 'value' => $value, 'expires' => time() + (int) $expiration );
			update_option( 'seiwp_cache_' . $name, $option, 'no' );
		}

		/**
		 * Cache Helper function. I don't use transients because cleanup plugins can break their functionality
		 * @param string $name
		 * @param mixed $value
		 * @param number $expiration
		 */
		public static function delete_cache( $name ) {
			delete_option( 'seiwp_cache_' . $name );
		}

		/**
		 * Cache Helper function. I don't use transients because cleanup plugins can break their functionality
		 * @param string $name
		 * @param mixed $value
		 * @param number $expiration
		 */
		public static function get_cache( $name ) {
			$option = get_option( 'seiwp_cache_' . $name );

			if ( false === $option || ! isset( $option['value'] ) || ! isset( $option['expires'] ) ) {
				return false;
			}

			if ( $option['expires'] < time() ) {
				delete_option( 'seiwp_cache_' . $name );
				return false;
			} else {
				return $option['value'];
			}
		}

		/**
		 * Cache Helper function. I don't use transients because cleanup plugins can break their functionality
		 */
		public static function clear_cache() {
			global $wpdb;
			$sqlquery = $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'seiwp_cache_qr%%'" );
		}

		/**
		 * Wrapper function to retrieve all sites from a network
		 * @param string|array $args
		 * @return array
		 */
		public static function get_sites( $args ) { // Use wp_get_sites() if WP version is lower than 4.6.0
			global $wp_version;
			if ( version_compare( $wp_version, '4.6.0', '<' ) ) {
				return wp_get_sites( $args );
			} else {
				foreach ( get_sites( $args ) as $blog ) {
					$blogs[] = (array) $blog; // Convert WP_Site object to array
				}
				return $blogs;
			}
		}

		/**
		 * Loads a view file
		 *
		 * $data parameter will be available in the template file as $data['value']
		 *
		 * @param string $template - Template file to load
		 * @param array $data - data to pass along to the template
		 * @return boolean - If template file was found
		 **/
		public static function load_view( $path, $data = array()) {
			if ( file_exists( SEIWP_DIR . $path ) ) {
				require_once ( SEIWP_DIR . $path );
				return true;
			}
			return false;
		}

		/**
		 * Doing it wrong function
		 */
		public static function doing_it_wrong( $function, $message, $version ) {
			if ( WP_DEBUG && apply_filters( 'doing_it_wrong_trigger_error', true ) ) {
				if ( is_null( $version ) ) {
					$version = '';
				} else {
					/* translators: %s: version number */
					$version = sprintf( __( 'This message was added in version %s.', 'search-engine-insights' ), $version );
				}

				/* translators: Developer debugging message. 1: PHP function name, 2: Explanatory message, 3: Version information message */
				trigger_error( sprintf( __( '%1$s was called <strong>incorrectly</strong>. %2$s %3$s', 'search-engine-insights' ), $function, $message, $version ) );
			}
		}

		/**
		 * Error management system
		 * @param object $e
		 * @param int $timeout
		 */
		public static function set_error( $e, $timeout ) {
			if ( is_object( $e ) ) {
				self::set_cache( 'last_error', date( 'Y-m-d H:i:s' ) . ': ' . esc_html( print_r( $e, true ) ), $timeout );
				if ( method_exists( $e, 'getCode' ) && method_exists( $e, 'getErrors' ) ) {
					$errors = (array) $e->getErrors();
					self::set_cache( 'gapi_errors', array( (int) $e->getCode(), $errors ), $timeout );
				}
			} else {
				self::set_cache( 'last_error', date( 'Y-m-d H:i:s' ) . ': ' . esc_html( $e ), $timeout );
			}
			// Count Errors until midnight
			$midnight = strtotime( "tomorrow 00:00:00" ); // UTC midnight
			$midnight = $midnight + 8 * 3600; // UTC 8 AM
			$tomidnight = $midnight - time();
			$errors_count = self::get_cache( 'errors_count' );
			$errors_count = (int) $errors_count + 1;
			self::set_cache( 'errors_count', $errors_count, $tomidnight );
		}

		/**
		 * Anonymize sensitive data before displaying or reporting
		 * @param array $options
		 * @return string
		 */
		public static function anonymize_options( $options ) {
			global $wp_version;

			if ( defined( 'SCRIPT_DEBUG' ) and SCRIPT_DEBUG ) {
				return $options; //don't hide credentials when DEBUG is enabled
			}

			$options['wp_version'] = $wp_version;
			$options['seiwp_version'] = SEIWP_CURRENT_VERSION;
			if ( $options['token'] ) {
				$options['token'] = 'HIDDEN';
			}
			if ( $options['client_secret'] ) {
				$options['client_secret'] = 'HIDDEN';
			}

			return $options;
		}

		/**
		 * System details for the Debug screen
		 * @return string
		 */
		public static function system_info() {
			$info = '';
			// Server Software
			$server_soft = "-";
			if ( isset( $_SERVER['SERVER_SOFTWARE'] ) ) {
				$server_soft = $_SERVER['SERVER_SOFTWARE'];
			}
			$info .= 'Server Info: ' . $server_soft . "\n";
			// PHP version
			if ( defined( 'PHP_VERSION' ) ) {
				$info .= 'PHP Version: ' . PHP_VERSION . "\n";
			} else if ( defined( 'HHVM_VERSION' ) ) {
				$info .= 'HHVM Version: ' . HHVM_VERSION . "\n";
			} else {
				$info .= 'Other Version: ' . '-' . "\n";
			}
			// cURL Info
			if ( function_exists( 'curl_version' ) && function_exists( 'curl_exec' ) ) {
				$curl_version = curl_version();
				if ( ! empty( $curl_version ) ) {
					$curl_ver = $curl_version['version'] . " " . $curl_version['ssl_version'];
				} else {
					$curl_ver = '-';
				}
			} else {
				$curl_ver = '-';
			}
			$info .= 'cURL Info: ' . $curl_ver . "\n";
			// Gzip
			if ( is_callable( 'gzopen' ) ) {
				$gzip = true;
			} else {
				$gzip = false;
			}
			$gzip_status = ( $gzip ) ? 'Yes' : 'No';
			$info .= 'Gzip: ' . $gzip_status . "\n";

			return $info;
		}

		/**
		 * Follows the SCRIPT_DEBUG settings
		 * @param string $script
		 * @return string
		 */
		public static function script_debug_suffix() {
			if ( defined( 'SCRIPT_DEBUG' ) and SCRIPT_DEBUG ) {
				return '';
			} else {
				return '.min';
			}
		}

		public static function number_to_kmb( $number ) {
			$number_format = '';

			if ( $number < 1000 ) {
				// Anything less than a thousand
				$number_format = number_format_i18n( $number );
			} else if ( $number < 1000000 ) {
				// Anything less than a milion
				$number_format = number_format_i18n( $number / 1000, 2 ) . 'K';
			} else if ( $number < 1000000000 ) {
				// Anything less than a billion
				$number_format = number_format_i18n( $number / 1000000, 2 ) . 'M';
			} else {
				// At least a billion
				$number_format = number_format_i18n( $number / 1000000000, 2 ) . 'B';
			}

			return $number_format;
		}
	}
}
