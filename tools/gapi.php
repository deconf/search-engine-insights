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
use Deconf\SEIWP\Google\Service\Exception as GoogleServiceException;
if ( ! class_exists( 'SEIWP_GAPI_Controller' ) ) {

	final class SEIWP_GAPI_Controller {

		public $client;

		public $service;

		public $timeshift;

		public $managequota;

		private $seiwp;

		private $redirect_uri;

		private $state;

		private $quotauser;

		private $client_id;

		private $client_secret;

		private $token_uri;

		private $revoke_uri;

		/**
		 * Google API Client Initialization
		 */
		public function __construct() {
			$this->seiwp = SEIWP();
			$this->quotauser = 'u' . get_current_user_id() . 's' . get_current_blog_id();
			$security = wp_create_nonce( 'seiwp_state' );
			if ( $this->seiwp->config->options['user_api'] ) {
				$this->client_id = $this->seiwp->config->options['client_id'];
				$this->client_secret = $this->seiwp->config->options['client_secret'];
				$this->redirect_uri = SEIWP_URL . 'tools/oauth2callback.php';
				$this->token_uri = 'https://oauth2.googleapis.com/token';
				$this->revoke_uri = 'https://oauth2.googleapis.com/revoke';
				$this->state = $security;
			} else {
				$this->client_id = '445209225034-q1dg4p5se5rh3dkvtpvj323tlr5ibt1q.apps.googleusercontent.com';
				$this->client_secret = 'GOCSPX';
				$this->redirect_uri = SEIWP_ENDPOINT_URL . 'oauth2callback.php';
				$this->token_uri = SEIWP_ENDPOINT_URL . 'seiwp-token.php';
				$this->revoke_uri = SEIWP_ENDPOINT_URL . 'seiwp-revoke.php';
				if ( is_multisite() && $this->seiwp->config->options['network_mode'] ) {
					$state_uri = network_admin_url( 'admin.php?page=seiwp_setup' ) . '&state=' . $security;
				} else {
					$state_uri = admin_url( 'admin.php?page=seiwp_setup' ) . '&state=' . $security;
				}
				$this->state = $state_uri;
			}
			if ( $this->seiwp->config->options['token'] ) {
				$this->refresh_token();
			}
		}

		/**
		 * Creates the oauth2 link for Google API authorization
		 * @return string
		 */
		public function createAuthUrl() {
			$scope = 'https://www.googleapis.com/auth/webmasters https://www.googleapis.com/auth/siteverification';
			// @formatter:off
			$auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?';
			$query_arr = array(
				'client_id' => $this->client_id,
				'redirect_uri' => $this->redirect_uri,
				'response_type' => 'code',
				'scope' => $scope,
				'state' => $this->state,
				'access_type' => 'offline',
				'prompt' => 'consent',
			);
			// @formatter:on
			$auth_url = $auth_url . http_build_query( $query_arr );
			return $auth_url;
		}

		/**
		 * Handles the exchange of an access code with a token
		 * @param string $access_code
		 * @return string|mixed
		 */
		public function authenticate( $access_code ) {
			// @formatter:off
			$token_data = array(
				'client_id' => $this->client_id,
				'client_secret' => $this->client_secret,
				'code' => $access_code,
				'redirect_uri' => $this->redirect_uri,
				'grant_type' => 'authorization_code',
			);
			$request_args = array( 'body' => $token_data, 'headers' => array( 'Referer' => SEIWP_CURRENT_VERSION ) );
			// @formatter:on
			$response = wp_remote_post( $this->token_uri, $request_args );
			if ( is_wp_error( $response ) ) {
				$timeout = $this->get_timeouts();
				SEIWP_Tools::set_error( $response, $timeout );
				return false;
			}
			$body = wp_remote_retrieve_body( $response );
			$token = json_decode( $body, true );
			if ( isset( $token['error'] ) ) {
				$timeout = $this->get_timeouts();
				$error = new WP_Error();
				if ( isset( $token['error']['code'] ) && isset( $token['error']['code'] ) && isset( $token['error']['status'] ) ) {
					$error->add( $token['error']['code'], $token['error']['message'], array( $token['error']['status'], 'trying to exchange access code for token' ) );
				} else if ( isset( $token['error'] ) && isset( $token['error_description'] ) ) {
					$error->add( $token['error'], $token['error_description'], 'trying to exchange access code for token' );
				} else if ( isset( $token['error']['code'] ) && isset( $token['error']['message'] ) ) {
					$error->add( $token['error']['code'], $token['error']['message'], 'trying to get site META' );
				}
				SEIWP_Tools::set_error( $error, $timeout );
				return false;
			}
			if ( isset( $token['access_token'] ) ) {
				return $token;
			} else {
				return false;
			}
		}

		/**
		 * Handles the token refresh process
		 * @return string|number|boolean
		 */
		public function refresh_token() {
			$token = (array) $this->seiwp->config->options['token'];
			$refresh_token = $token['refresh_token'];
			$challenge = ( isset( $token['challenge'] ) && '' != $token['challenge'] ) ? $token['challenge'] : '';
			if ( ! $token || ! isset( $token['expires_in'] ) || ( $token['created'] + ( $token['expires_in'] - 30 ) ) < time() ) {
				// @formatter:off
				$post_data = array(
					'client_id' => $this->client_id,
					'client_secret' => $this->seiwp->config->options['user_api'] ? $this->client_secret : $this->client_secret . '-' . $challenge,
					'refresh_token' => $refresh_token,
					'grant_type' => 'refresh_token'
				);
				// @formatter:on
				$request_args = array( 'body' => $post_data, 'headers' => array( 'Referer' => SEIWP_CURRENT_VERSION ) );
				if ( $this->seiwp->config->options['user_api'] ) {
					$token_uri = 'https://oauth2.googleapis.com/token';
				} else {
					$token_uri = $challenge ? 'https://oauth2.googleapis.com/token' : SEIWP_ENDPOINT_URL . 'seiwp-token.php';
				}
				$response = wp_remote_post( $token_uri, $request_args );
				if ( is_wp_error( $response ) ) {
					$timeout = $this->get_timeouts();
					SEIWP_Tools::set_error( $response, $timeout );
				} else {
					$body = wp_remote_retrieve_body( $response );
					if ( is_string( $body ) && ! empty( $body ) ) {
						$newtoken = json_decode( $body, true );
						if ( isset( $newtoken['error'] ) ) {
							$timeout = $this->get_timeouts();
							$error = new WP_Error();
							if ( isset( $newtoken['error']['code'] ) && isset( $newtoken['error']['code'] ) && isset( $newtoken['error']['status'] ) ) {
								$error->add( $newtoken['error']['code'], $newtoken['error']['message'], array( $newtoken['error']['status'], 'trying to refresh token' ) );
							} else if ( isset( $newtoken['error'] ) && isset( $newtoken['error_description'] ) ) {
								$error->add( $newtoken['error'], $newtoken['error_description'], 'trying to refresh token' );
							} else if ( isset( $newtoken['error']['code'] ) && isset( $newtoken['error']['message'] ) ) {
								$error->add( $newtoken['error']['code'], $newtoken['error']['message'], 'trying to get site META' );
							}
							SEIWP_Tools::set_error( $error, $timeout );
							return false;
						}
						if ( ! empty( $newtoken ) && isset( $newtoken['access_token'] ) ) {
							if ( ! isset( $newtoken['created'] ) ) {
								$newtoken['created'] = time();
							}
							if ( ! isset( $newtoken['refresh_token'] ) ) {
								$newtoken['refresh_token'] = $refresh_token;
							}
							if ( ! isset( $newtoken['challenge'] ) && '' != $challenge ) {
								$newtoken['challenge'] = $challenge;
							}
							$this->seiwp->config->options['token'] = $newtoken;
						} else {
							$this->seiwp->config->options['token'] = false;
						}
					} else {
						$this->seiwp->config->options['token'] = false;
					}
					if ( is_multisite() && $this->seiwp->config->options['network_mode'] ) {
						$this->seiwp->config->set_plugin_options( true );
					} else {
						$this->seiwp->config->set_plugin_options();
					}
				}
			}
			if ( $this->seiwp->config->options['token'] ) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Handles the token reset process
		 *
		 * @param
		 *            $all
		 */
		public function reset_token( $all = false, $valid_token = true ) {
			if ( $all ) {
				$this->seiwp->config->options['sites_list'] = array();
				$this->seiwp->config->options['site_jail'] = '';
			}
			$this->seiwp->config->options['token'] = false;
			$this->seiwp->config->options['sites_list_locked'] = 0;
			if ( is_multisite() && $this->seiwp->config->options['network_mode'] ) {
				$this->seiwp->config->set_plugin_options( true );
			} else {
				$this->seiwp->config->set_plugin_options();
			}
		}

		/**
		 * Handles the token revoke process
		 *
		 * @param
		 *            $all
		 */
		public function revoke_token( $all = false, $valid_token = true ) {
			// See notes don't use unless mandatory
			$token = (array) $this->seiwp->config->options['token'];
			if ( isset( $token['refresh_token'] ) && $valid_token ) {
				// @formatter:off
				$post_data = array(
					'token' => $token['refresh_token'],
				);
				// @formatter:on
				$request_args = array( 'body' => $post_data, 'headers' => array( 'Referer' => SEIWP_CURRENT_VERSION ) );
				$response = wp_remote_post( $this->revoke_uri, $request_args );
			}
		}

		/**
		 * Handles errors returned by GAPI Library
		 *
		 * @return boolean
		 */
		public function api_errors_handler() {
			$errors = SEIWP_Tools::get_cache( 'api_errors' );
			// Proceed as normal if we don't know the error
			if ( false === $errors || ! isset( $errors[0] ) ) {
				return false;
			}
			// Reset the token since these are unrecoverable errors and need user intervention
			// We can also add 'INVALID_ARGUMENT'
			if ( isset( $errors[2][0] ) && ( 'INVALID_ARGUMENTS' == $errors[2][0] || 'UNAUTHENTICATED' == $errors[2][0] || 'PERMISSION_DENIED' == $errors[2][0] ) ) {
				$this->reset_token( false, false );
				return $errors[0];
			}
			// Reset the token since these are unrecoverable errors and need user intervention
			// We can also add 'invalid_grant'
			if ( isset( $errors[0] ) && ( 'invalid_grant' == $errors[0] || 'invalid_token' == $errors[0] ) ) {
				$this->reset_token( false, false );
				return $errors[0];
			}
			if ( 401 == $errors[0] || 403 == $errors[0] ) {
				return $errors[0];
			}
			// Back-off processing until the error timeouts, usually at midnight
			if ( isset( $errors[1][0]['reason'] ) && ( 'dailyLimitExceeded' == $errors[1][0]['reason'] || 'userRateLimitExceeded' == $errors[1][0]['reason'] || 'rateLimitExceeded' == $errors[1][0]['reason'] || 'quotaExceeded' == $errors[1][0]['reason'] ) ) {
				return $errors[0];
			}
			// Back-off system for subsequent requests - an Auth error generated after a Service request
			if ( isset( $errors[1][0]['reason'] ) && ( 'authError' == $errors[1][0]['reason'] ) ) {
				if ( $this->seiwp->config->options['api_backoff'] <= 5 ) {
					usleep( $this->seiwp->config->options['api_backoff'] * 1000000 + rand( 100000, 1000000 ) );
					$this->seiwp->config->options['api_backoff'] = $this->seiwp->config->options['api_backoff'] + 1;
					$this->seiwp->config->set_plugin_options();
					return false;
				} else {
					return $errors[0];
				}
			}
			if ( 500 == $errors[0] || 503 == $errors[0] ) {
				return $errors[0];
			}
			return false;
		}

		/**
		 * Calculates proper timeouts for each GAPI query
		 *
		 * @param
		 *            $interval
		 * @return number
		 */
		public function get_timeouts( $interval = '' ) {
			$local_time = time() + $this->timeshift;
			if ( 'daily' == $interval ) {
				$nextday = explode( '-', date( 'n-j-Y', strtotime( ' +1 day', $local_time ) ) );
				$midnight = mktime( 0, 0, 0, $nextday[0], $nextday[1], $nextday[2] );
				return $midnight - $local_time;
			} else if ( 'midnight' == $interval ) {
				$midnight = strtotime( "tomorrow 00:00:00" ); // UTC midnight
				$midnight = $midnight + 8 * 3600; // UTC 8 AM
				return $midnight - time();
			} else if ( 'hourly' == $interval ) {
				$nexthour = explode( '-', date( 'H-n-j-Y', strtotime( ' +1 hour', $local_time ) ) );
				$newhour = mktime( $nexthour[0], 0, 0, $nexthour[1], $nexthour[2], $nexthour[3] );
				return $newhour - $local_time;
			} else {
				return 0;
			}
		}

		/**
		 * Verifies the current site for Google Search Console
		 *
		 * @return boolean
		 */
		public function verify_site() {
			$token = (array) $this->seiwp->config->options['token'];
			$access_token = $token['access_token'];
			$headers = array( 'Authorization' => 'Bearer ' . $access_token, 'Content-Type' => 'application/json', 'Referer' => SEIWP_CURRENT_VERSION );
			if ( empty( $this->seiwp->config->options['site_verification_meta'] ) ) {
				$body = array( 'site' => array( 'type' => 'SITE', 'identifier' => SEIWP_SITE_URL ), 'verificationMethod' => 'META' );
				$request_args = array( 'method' => 'POST', 'headers' => $headers );
				$request_url = 'https://www.googleapis.com/siteVerification/v1/token';
				$response = wp_remote_request( $request_url, $request_args );
				if ( is_wp_error( $response ) ) {
					$timeout = $this->get_timeouts();
					SEIWP_Tools::set_error( $response, $timeout );
					return false;
				}
				$body = wp_remote_retrieve_body( $response );
				$response = json_decode( $body, true );
				if ( isset( $response['error'] ) ) {
					$timeout = $this->get_timeouts();
					$error = new WP_Error();
					if ( isset( $response['error']['code'] ) && isset( $response['error']['status'] ) ) {
						$error->add( $response['error']['code'], $response['error']['message'], array( $response['error']['status'], 'trying to get site META' ) );
					} else if ( isset( $response['error'] ) && isset( $response['error_description'] ) ) {
						$error->add( $response['error'], $response['error_description'], 'trying to get site META' );
					} else if ( isset( $response['error']['code'] ) && isset( $response['error']['message'] ) ) {
						$error->add( $response['error']['code'], $response['error']['message'], 'trying to get site META' );
					}
					SEIWP_Tools::set_error( $error, $timeout );
					return false;
				}
				if ( isset( $response['token'] ) ) {
					$this->seiwp->config->options['site_verification_meta'] = $response['token'];
					$this->seiwp->config->set_plugin_options();
				}
			}
			$request_body = array( 'site' => array( 'type' => 'SITE', 'identifier' => SEIWP_SITE_URL ) );
			$request_args = array( 'method' => 'POST', 'headers' => $headers, 'body' => json_encode( $request_body ) );
			$request_url = 'https://www.googleapis.com/siteVerification/v1/webResource?verificationMethod=META';
			$response = wp_remote_request( $request_url, $request_args );
			if ( is_wp_error( $response ) ) {
				$timeout = $this->get_timeouts();
				SEIWP_Tools::set_error( $response, $timeout );
				return false;
			}
			$body = wp_remote_retrieve_body( $response );
			$response = json_decode( $body, true );
			if ( isset( $response['error'] ) ) {
				$timeout = $this->get_timeouts();
				$error = new WP_Error();
				if ( isset( $response['error']['code'] ) && isset( $response['error']['status'] ) ) {
					$error->add( $response['error']['code'], $response['error']['message'], array( $response['error']['status'], 'trying to verify site' ) );
				} else if ( isset( $response['error'] ) && isset( $response['error_description'] ) ) {
					$error->add( $response['error'], $response['error_description'], 'trying to verify site' );
				} else if ( isset( $response['error']['code'] ) && isset( $response['error']['message'] ) ) {
					$error->add( $response['error']['code'], $response['error']['message'], 'trying to verify site' );
				}
				SEIWP_Tools::set_error( $error, $timeout );
				return false;
			}
			if ( isarray( $response ) ) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Deletes the Google Search Console Property corresponding to current site
		 *
		 * @return boolean || array
		 */
		public function delete_site() {
			$token = (array) $this->seiwp->config->options['token'];
			$access_token = $token['access_token'];
			$headers = array( 'Authorization' => 'Bearer ' . $access_token, 'Content-Type' => 'application/json', 'Content-Length' => 0, 'Referer' => SEIWP_CURRENT_VERSION );
			$request_args = array( 'method' => 'DELETE', 'headers' => $headers );
			$request_url = 'https://www.googleapis.com/webmasters/v3/sites/' . urlencode( SEIWP_SITE_URL );
			$response = wp_remote_request( $request_url, $request_args );
			if ( is_wp_error( $response ) ) {
				$timeout = $this->get_timeouts();
				SEIWP_Tools::set_error( $response, $timeout );
				return false;
			}
			$body = wp_remote_retrieve_body( $response );
			$response = json_decode( $body, true );
			if ( isset( $response['error'] ) ) {
				$timeout = $this->get_timeouts();
				$error = new WP_Error();
				if ( isset( $response['error']['code'] ) && isset( $response['error']['status'] ) ) {
					$error->add( $response['error']['code'], $response['error']['message'], array( $response['error']['status'], 'trying to delete site' ) );
				} else if ( isset( $response['error'] ) && isset( $response['error_description'] ) ) {
					$error->add( $response['error'], $response['error_description'], 'trying to delete site' );
				} else if ( isset( $response['error']['code'] ) && isset( $response['error']['message'] ) ) {
					$error->add( $response['error']['code'], $response['error']['message'], 'trying to delete site' );
				}
				SEIWP_Tools::set_error( $error, $timeout );
				return false;
			}
			return true;
		}

		/**
		 * Adds a Google Search Console Property for the current site
		 *
		 * @return boolean || array
		 */
		public function add_site() {
			$token = (array) $this->seiwp->config->options['token'];
			$access_token = $token['access_token'];
			$headers = array( 'Authorization' => 'Bearer ' . $access_token, 'Content-Type' => 'application/json', 'Content-Length' => 0, 'Referer' => SEIWP_CURRENT_VERSION );
			$request_args = array( 'method' => 'PUT', 'headers' => $headers );
			$request_url = 'https://www.googleapis.com/webmasters/v3/sites/' . urlencode( SEIWP_SITE_URL );
			$response = wp_remote_request( $request_url, $request_args );
			if ( is_wp_error( $response ) ) {
				$timeout = $this->get_timeouts();
				SEIWP_Tools::set_error( $response, $timeout );
				return false;
			}
			$body = wp_remote_retrieve_body( $response );
			$response = json_decode( $body, true );
			if ( isset( $response['error'] ) ) {
				$timeout = $this->get_timeouts();
				$error = new WP_Error();
				if ( isset( $response['error']['code'] ) && isset( $response['error']['status'] ) ) {
					$error->add( $response['error']['code'], $response['error']['message'], array( $response['error']['status'], 'trying to add property' ) );
				} else if ( isset( $response['error'] ) && isset( $response['error_description'] ) ) {
					$error->add( $response['error'], $response['error_description'], 'trying to add site' );
				} else if ( isset( $response['error']['code'] ) && isset( $response['error']['message'] ) ) {
					$error->add( $response['error']['code'], $response['error']['message'], 'trying to add site' );
				}
				SEIWP_Tools::set_error( $error, $timeout );
				return false;
			}
			return true;
		}

		/**
		 * Retrieves all Search Engine Insights Properties with their details
		 *
		 * @return array
		 */
		public function get_sites() {
			$token = (array) $this->seiwp->config->options['token'];
			$access_token = $token['access_token'];
			$headers = array( 'Authorization' => 'Bearer ' . $access_token, 'Content-Type' => 'application/json', 'Content-Length' => 0, 'Referer' => SEIWP_CURRENT_VERSION );
			$request_args = array( 'method' => 'GET', 'headers' => $headers );
			$request_url = 'https://www.googleapis.com/webmasters/v3/sites';
			$response = wp_remote_request( $request_url, $request_args );
			if ( is_wp_error( $response ) ) {
				$timeout = $this->get_timeouts();
				SEIWP_Tools::set_error( $response, $timeout );
				return false;
			}
			$body = wp_remote_retrieve_body( $response );
			$response = json_decode( $body, true );
			if ( isset( $response['error'] ) ) {
				$timeout = $this->get_timeouts();
				$error = new WP_Error();
				if ( isset( $response['error']['code'] ) && isset( $response['error']['status'] ) ) {
					$error->add( $response['error']['code'], $response['error']['message'], array( $response['error']['status'], 'trying to get sites list' ) );
				} else if ( isset( $response['error'] ) && isset( $response['error_description'] ) ) {
					$error->add( $response['error'], $response['error_description'], 'trying to get sites list' );
				} else if ( isset( $response['error']['code'] ) && isset( $response['error']['message'] ) ) {
					$error->add( $response['error']['code'], $response['error']['message'], 'trying to get sites list' );
				}
				SEIWP_Tools::set_error( $error, $timeout );
				return false;
			}
			if ( isset( $response['siteEntry'] ) ) {
				$sites_list = array();
				foreach ( $response['siteEntry'] as $site ) {
					$sites_list[] = array( $site['siteUrl'], $site['permissionLevel'] );
				}
			}
			if ( ! empty( $sites_list ) ) {
				return $sites_list;
			} else {
				return false;
			}
		}

		/**
		 * Gets and stores Search Analytics Reports
		 *
		 * @param
		 *            $projecId
		 * @param
		 *            $from
		 * @param
		 *            $to
		 * @param
		 *            $dimensions
		 * @param
		 *            $options
		 * @param
		 * 											$filters
		 * @param
		 *            $serial
		 * @return int|Deconf\SEIWP\Google\Service\SearchConsole
		 */
		private function handle_searchanalytics_reports( $projectId, $from, $to, $dimensions, $options, $filters, $serial ) {
			$transient = SEIWP_Tools::get_cache( $serial );
			if ( false === $transient ) {
				if ( $this->api_errors_handler() ) {
					return $this->api_errors_handler();
				}
				$options['samplingLevel'] = 'HIGHER_PRECISION';
				$token = (array) $this->seiwp->config->options['token'];
				if ( isset( $token['access_token'] ) ) {
					$access_token = $token['access_token'];
				} else {
					return 624;
				}
				// @formatter:off
					$headers = array(
						'Authorization' => 'Bearer ' . $access_token,
						'Content-Type' => 'application/json',
					);
					// @formatter:on
				$request_body = array( 'startDate' => $from, 'endDate' => $to );
				if ( $dimensions ) {
					if ( is_array( $dimensions ) ) {
						$request_body['dimensions'] = array();
						foreach ( $dimensions as $dimension ) {
							$request_body['dimensions'][] = $dimension;
						}
					} else {
						$request_body['dimensions'] = array();
						$request_body['dimensions'][] = $dimensions;
					}
				}
				if ( is_array( $filters ) ) {
					$request_body['dimensionFilterGroups'] = array( 'filters' => $filters );
				}
				$itr = 0;
				$data['rows'] = array();
				do {

					$limit = 25000;
					$request_body['startRow'] = $itr * $limit;
					$request_body['rowLimit'] = $limit;

					$request_body_json = json_encode( $request_body );
					$args = array( 'headers' => $headers, 'body' => $request_body_json );
					$request_url = 'https://searchconsole.googleapis.com/webmasters/v3/sites/' . urlencode( $projectId ) . '/searchAnalytics/query';
					$response = wp_remote_post( $request_url, $args );
					if ( is_wp_error( $response ) ) {
						$timeout = $this->get_timeouts();
						SEIWP_Tools::set_error( $response, $timeout );
						return $response->get_error_code();
					} else {
						$response_body = wp_remote_retrieve_body( $response );
						$response_data = json_decode( $response_body, true );
						if ( isset( $response_data['error'] ) ) {
							$timeout = $this->get_timeouts();
							$error = new WP_Error();
							if ( isset( $response_data['error']['code'] ) && isset( $response_data['error']['status'] ) ) {
								$error->add( $response_data['error']['code'], $response_data['error']['message'], array( $response_data['error']['status'], 'trying to refresh token' ) );
							} else if ( isset( $response_data['error'] ) && isset( $response_data['error_description'] ) ) {
								$error->add( $response_data['error'], $response_data['error_description'], 'trying to refresh token' );
							} else if ( isset( $response_data['error']['code'] ) && isset( $response_data['error']['message'] ) ) {
								$error->add( $response_data['error']['code'], $response_data['error']['message'], 'trying to get sites list' );
							}
							SEIWP_Tools::set_error( $error, $timeout );
							return $error->get_error_code();
						}
						if (isset( $response_data['rows'] )){
						$data['rows'] = array_merge($data['rows'], $response_data['rows']);
						}
					}

					$itr++;

				} while ( isset( $response_data['rows'] ) && count( $response_data['rows'] ) == $limit );

				SEIWP_Tools::set_cache( $serial, $data, $this->get_timeouts( 'daily' ) );

			} else {
				$data = $transient;
			}
			$this->seiwp->config->options['api_backoff'] = 0;
			$this->seiwp->config->set_plugin_options();
			if ( isset( $data['rows'] ) ) {
				return $data;
			} else {
				$data['rows'] = array();
				return $data;
			}
		}

		/**
		 * Generates serials for cache using crc32() to avoid exceeding option name lengths
		 *
		 * @param
		 *            $serial
		 * @return string
		 */
		public function get_serial( $serial ) {
			return sprintf( "%u", crc32( $serial ) );
		}

		/**
		 * Search Analytics data for Area Charts
		 *
		 * @param
		 *            $projectId
		 * @param
		 *            $from
		 * @param
		 *            $to
		 * @param
		 *            $query
		 * @param
		 *            $filter
		 * @return array|int
		 */
		private function get_areachart_data( $projectId, $from, $to, $query, $filter = '' ) {
			switch ( $query ) {
				case 'clicks' :
					$title = __( "Clicks", 'search-engine-insights' );
					break;
				case 'ctr' :
					$title = __( "Click Through Rate", 'search-engine-insights' );
					break;
				case 'position' :
					$title = __( "Position", 'search-engine-insights' );
					break;
				default :
					$title = __( "Impressions", 'search-engine-insights' );
			}
			$dimensions[] = 'date';
			$options = array( 'quotaUser' => $this->managequota . 'p' . $projectId );
			if ( $filter ) {
				$filters['dimension'] = 'page';
				$filters['operator'] = 'equals';
				$filters['expression'] = $filter; // SEIWP! $filter
			} else {
				$filters = '';
			}
			$serial = 'qr2_' . $this->get_serial( $projectId . $from . $to . $filter );
			$data = $this->handle_searchanalytics_reports( $projectId, $from, $to, $dimensions, $options, $filters, $serial );
			if ( is_numeric( $data ) ) {
				return $data;
			}
			if ( empty( $data['rows'] ) ) {
				// unable to render it as an Area Chart, returns a numeric value to be handled by reportsx.js
				return 621;
			}
			$seiwp_data = array( array( __( "Date", 'search-engine-insights' ), $title ) );
			foreach ( $data['rows'] as $row ) {
				/*
				 * translators:
				 * Example: 'l, F j, Y' will become 'Thusday, November 17, 2015'
				 * For details see: http://php.net/manual/en/function.date.php#refsect1-function.date-parameters
				 */
				$seiwp_data[] = array( date_i18n( __( 'l, F j, Y', 'search-engine-insights' ), strtotime( $row['keys'][0] ) ), round( $row[$query], 2 ) );
			}
			return $seiwp_data;
		}

		/**
		 * Search Analytics Summary
		 *
		 * @param
		 *            $projectId
		 * @param
		 *            $from
		 * @param
		 *            $to
		 * @param
		 *            $filter
		 * @return array|int
		 */
		private function get_summary( $projectId, $from, $to, $filter = '' ) {
			$options = array( 'quotaUser' => $this->managequota . 'p' . $projectId );
			$dimensions = '';
			if ( $filter ) {
				$filters['dimension'] = 'page';
				$filters['operator'] = 'equals';
				$filters['expression'] = $filter; // SEIWP! $filter
			} else {
				$filters = '';
			}
			$serial = 'qr3_' . $this->get_serial( $projectId . $from . $to . $filter . '0' );
			$data = $this->handle_searchanalytics_reports( $projectId, $from, $to, $dimensions, $options, $filters, $serial );
			if ( is_numeric( $data ) ) {
				return $data;
			}
			// i18n support
			$seiwp_data[0] = empty( $data['rows'] ) ? 0 : SEIWP_Tools::number_to_kmb( $data['rows'][0]['impressions'] );
			$seiwp_data[1] = empty( $data['rows'] ) ? 0 : SEIWP_Tools::number_to_kmb( $data['rows'][0]['clicks'] );
			$seiwp_data[2] = empty( $data['rows'] ) ? 0 : number_format_i18n( $data['rows'][0]['position'], 2 );
			$seiwp_data[3] = empty( $data['rows'] ) ? '0%' : number_format_i18n( $data['rows'][0]['ctr'], 2 ) . '%';
			return $seiwp_data;
		}

		/**
		 * Search Analytics data for Table Charts (pages)
		 *
		 * @param
		 *            $projectId
		 * @param
		 *            $from
		 * @param
		 *            $to
		 * @param
		 *            $filter
		 * @param
		 *            $metric
		 * @return array|int
		 */
		private function get_pages( $projectId, $from, $to, $metric, $filter = '' ) {
			$metrics = $metric;
			$dimensions[] = 'page';
			$options = array( 'sort' => '-' . $metrics, 'quotaUser' => $this->managequota . 'p' . $projectId );
			if ( $filter ) {
				$filters['dimension'] = 'page';
				$filters['operator'] = 'equals';
				$filters['expression'] = $filter; // SEIWP! $filter
			} else {
				$filters = '';
			}
			$serial = 'qr4_' . $this->get_serial( $projectId . $from . $to . $filter . $metric );
			$data = $this->handle_searchanalytics_reports( $projectId, $from, $to, $dimensions, $options, $filters, $serial );
			if ( is_numeric( $data ) ) {
				return $data;
			}
			$seiwp_data = array( array( __( "Pages", 'search-engine-insights' ), __( ucfirst( $metric ), 'search-engine-insights' ) ) );
			foreach ( $data['rows'] as $row ) {
				$seiwp_data[] = array( esc_html( $row['keys'][0] ), (float) $row[$metric] );
			}
			return $seiwp_data;
		}

		/**
		 * Search Analytics data for Table Charts (queries)
		 *
		 * @param
		 *            $projectId
		 * @param
		 *            $from
		 * @param
		 *            $to
		 * @param
		 *            $filter
		 * @param
		 *            $metric
		 * @return array|int
		 */
		private function get_keywords( $projectId, $from, $to, $metric, $filter = '' ) {
			$metrics = $metric;
			$dimensions[] = 'query';
			$options = array( 'sort' => '-' . $metrics, 'quotaUser' => $this->managequota . 'p' . $projectId );
			if ( $filter ) {
				$filters['dimension'] = 'page';
				$filters['operator'] = 'equals';
				$filters['expression'] = $filter; // SEIWP! $filter
			} else {
				$filters = '';
			}
			$serial = 'qr6_' . $this->get_serial( $projectId . $from . $to . $filter . $metric );
			$data = $this->handle_searchanalytics_reports( $projectId, $from, $to, $dimensions, $options, $filters, $serial );
			if ( is_numeric( $data ) ) {
				return $data;
			}
			$seiwp_data = array( array( __( "Keywords", 'search-engine-insights' ), __( ucfirst( $metric ), 'search-engine-insights' ) ) );
			foreach ( $data['rows'] as $row ) {
				$seiwp_data[] = array( esc_html( $row['keys'][0] ), (float) $row[$metric] );
			}
			return $seiwp_data;
		}

		/**
		 * Search Analytics data for Location reports
		 *
		 * @param
		 *            $projectId
		 * @param
		 *            $from
		 * @param
		 *            $to
		 * @param
		 *            $filter
		 * @param
		 *            $metric
		 * @return array|int
		 */
		private function get_locations( $projectId, $from, $to, $metric, $filter = '' ) {
			$metrics = $metric;
			$options = "";
			$title = __( "Countries", 'search-engine-insights' );
			$serial = 'qr7_' . $this->get_serial( $projectId . $from . $to . $filter . $metric );
			$dimensions[] = 'country';
			$options = array( 'sort' => '-' . $metrics, 'quotaUser' => $this->managequota . 'p' . $projectId );
			if ( $filter ) {
				$filters['dimension'] = 'page';
				$filters['operator'] = 'equals';
				$filters['expression'] = $filter; // SEIWP! $filter
			} else {
				$filters = '';
			}
			$data = $this->handle_searchanalytics_reports( $projectId, $from, $to, $dimensions, $options, $filters, $serial );
			if ( is_numeric( $data ) ) {
				return $data;
			}
			$country_codes = SEIWP_Tools::get_countrycodes();
			$seiwp_data = array( array( $title, __( ucfirst( $metric ), 'search-engine-insights' ) ) );
			foreach ( $data['rows'] as $row ) {
				if ( isset( $country_codes[strtoupper( $row['keys'][0] )] ) ) {
					$seiwp_data[] = array( esc_html( $country_codes[strtoupper( $row['keys'][0] )] ), (float) $row[$metric] );
				}
			}
			return $seiwp_data;
		}

		/**
		 * Search Analytics data for Org Charts (site performance)
		 *
		 * @param
		 *            $projectId
		 * @param
		 *            $from
		 * @param
		 *            $to
		 * @param
		 *            $query
		 * @param
		 *            $filter
		 * @param
		 *            $metric
		 * @return array|int
		 */
		private function get_orgchart_data( $projectId, $from, $to, $query, $metric, $filter = '' ) {
			$options = array( 'quotaUser' => $this->managequota . 'p' . $projectId );
			$dimensions = '';
			if ( $filter ) {
				$filters['dimension'] = 'page';
				$filters['operator'] = 'equals';
				$filters['expression'] = $filter; // SEIWP! $filter
			} else {
				$filters = '';
			}
			$serial = 'qr3_' . $this->get_serial( $projectId . $from . $to . $filter . '0' );
			$data = $this->handle_searchanalytics_reports( $projectId, $from, $to, $dimensions, $options, $filters, $serial );
			if ( is_numeric( $data ) ) {
				return $data;
			}
			if ( empty( $data['rows'] ) ) {
				// unable to render as an Org Chart, returns a numeric value to be handled by reportsx.js
				return 621;
			}

   $res_data[__( "Impressions", 'search-engine-insights' )] = $data['rows'][0]['impressions'] ? SEIWP_Tools::number_to_kmb( $data['rows'][0]['impressions'] ) : 0;
			$res_data[__( "Clicks", 'search-engine-insights' )] = $data['rows'][0]['clicks'] ? SEIWP_Tools::number_to_kmb( $data['rows'][0]['clicks'] ) : 0;
			$res_data[__( "Position", 'search-engine-insights' )] = $data['rows'][0]['position'] ? number_format_i18n( $data['rows'][0]['position'], 2 ) : 0;
			$res_data[__( "CTR", 'search-engine-insights' )] = $data['rows'][0]['ctr'] ? number_format_i18n( $data['rows'][0]['ctr'], 2 ) . '%' : '0%';

			if ( is_array( $filters ) ) {
				$block = __( "Page Performance", 'search-engine-insights' );
			} else {
				$block = __( "Site Performance", 'search-engine-insights' );
			}
			$seiwp_data = array( array( '<div">' . $block . '</div><div></div>', "" ) );
			foreach ( $res_data as $key => $value ) {
				$seiwp_data[] = array( '<div>' . esc_html( $key ) . '</div><div>' . $value . '</div>', '<div>' . $block . '</div><div></div>' );
			}
			return $seiwp_data;
		}

		/**
		 * Handles ajax requests and calls the needed methods
		 * @param
		 * 		$projectId
		 * @param
		 * 		$query
		 * @param
		 * 		$from
		 * @param
		 * 		$to
		 * @param
		 * 		$filter
		 * @param
		 *   $metric
		 * @return number|Deconf\SEIWP\Google\Service\SearchConsole
		 */
		public function get( $projectId, $query, $from = false, $to = false, $filter = '', $metric = 'sessions' ) {

			if ( empty( $projectId ) ) {
				wp_die( 626 );
			}
			if ( 'summary' == $query ) {
				return $this->get_summary( $projectId, $from, $to, $filter );
			}
			if ( in_array( $query, array( 'impressions', 'clicks', 'position', 'ctr' ) ) ) {
				return $this->get_areachart_data( $projectId, $from, $to, $query, $filter );
			}
			if ( 'locations' == $query ) {
				return $this->get_locations( $projectId, $from, $to, $metric, $filter );
			}
			if ( 'pages' == $query ) {
				return $this->get_pages( $projectId, $from, $to, $metric, $filter );
			}
			if ( 'channelGrouping' == $query || 'deviceCategory' == $query ) {
				return $this->get_orgchart_data( $projectId, $from, $to, $query, $metric, $filter );
			}
			if ( 'keywords' == $query ) {
				return $this->get_keywords( $projectId, $from, $to, $metric, $filter );
			}
			wp_die( 627 );
		}
	}
}
