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

use Google\Service\Exception as GoogleServiceException;

if ( ! class_exists( 'SEIWP_GAPI_Controller' ) ) {

	final class SEIWP_GAPI_Controller {

		public $client;

		public $service;

		public $timeshift;

		public $managequota;

		private $seiwp;

		private $access = array( '445209225034-q1dg4p5se5rh3dkvtpvj323tlr5ibt1q.apps.googleusercontent.com', 'secret' );

		/**
		 * Google API Client Initialization
		 */
		public function __construct() {
			$this->seiwp = SEIWP();
			include_once ( SEIWP_DIR . 'tools/vendor/autoload.php' );
			$this->client = new Deconf\SEIWP\Google\Client();

			// add Proxy server settings to Guzzle, if defined

			if ( defined( 'WP_PROXY_HOST' ) && defined( 'WP_PROXY_PORT' ) ) {
				$httpoptions = array();
				$httpoptions [ 'proxy' ] = "'" . WP_PROXY_HOST . ":". WP_PROXY_PORT ."'";
				if ( defined( 'WP_PROXY_USERNAME' ) && defined( 'WP_PROXY_PASSWORD' ) ) {
					$httpoptions [ 'auth' ] = array( WP_PROXY_USERNAME, WP_PROXY_PASSWORD );
				}
				$httpClient = new Deconf\SEIWP\GuzzleHttp\Client( $httpoptions );
				$this->client->setHttpClient( $httpClient );
			}

			$this->client->setScopes( array( 'https://www.googleapis.com/auth/webmasters', 'https://www.googleapis.com/auth/siteverification' ) );
			$this->client->setAccessType( 'offline' );
			$this->client->setApprovalPrompt( 'force' );
			$this->client->setApplicationName( 'SEIWP ' . SEIWP_CURRENT_VERSION );
			$security = wp_create_nonce( 'seiwp_security' );
			if ( is_multisite() && $this->seiwp->config->options['network_mode'] ) {
				$state_uri = network_admin_url( 'admin.php?page=seiwp_setup' ) . '&seiwp_security=' . $security;
			} else {
				$state_uri = admin_url( 'admin.php?page=seiwp_setup' ) . '&seiwp_security=' . $security;
			}
			$this->client->setState( $state_uri );
			$this->managequota = 'u' . get_current_user_id() . 's' . get_current_blog_id();
			if ( $this->seiwp->config->options['user_api'] ) {
				$this->client->setClientId( $this->seiwp->config->options['client_id'] );
				$this->client->setClientSecret( $this->seiwp->config->options['client_secret'] );
				$this->client->setRedirectUri( SEIWP_URL . 'tools/oauth2callback.php' );
				define( 'SEIWP_OAUTH2_REVOKE_URI', 'https://oauth2.googleapis.com/revoke' );
				define( 'SEIWP_OAUTH2_TOKEN_URI', 'https://oauth2.googleapis.com/token' );
			} else {
				$this->client->setClientId( $this->access[0] );
				$this->client->setClientSecret( $this->access[1] );
				$this->client->setRedirectUri( SEIWP_ENDPOINT_URL . 'oauth2callback.php' );
				define( 'SEIWP_OAUTH2_REVOKE_URI', SEIWP_ENDPOINT_URL . 'seiwp-revoke.php' );
				define( 'SEIWP_OAUTH2_TOKEN_URI', SEIWP_ENDPOINT_URL . 'seiwp-token.php' );
			}

			/**
			 * SEIWP Endpoint support
			 */
			if ( $this->seiwp->config->options['token'] ) {
				$token = $this->seiwp->config->options['token'];
				if ( $token ) {
					try {
						$array_token = (array)$token;
						$this->client->setAccessToken( $array_token );
						if ( $this->client->isAccessTokenExpired() ) {
							$creds = $this->client->fetchAccessTokenWithRefreshToken( $this->client->getRefreshToken() );
							if ( $creds && isset( $creds['access_token'] ) ) {
								$this->seiwp->config->options['token'] = $this->client->getAccessToken();
							} else {
								$timeout = $this->get_timeouts( 'midnight' );
								SEIWP_Tools::set_error( $creds, $timeout );
								if ( isset( $creds['error'] ) && 'invalid_grant' == $creds['error'] ){
									$this->reset_token();
								}
							}
						}
					} catch ( GoogleServiceException $e ) {
						$timeout = $this->get_timeouts( 'midnight' );
						SEIWP_Tools::set_error( $e, $timeout );
						$this->reset_token();
					} catch ( Exception $e ) {
						$timeout = $this->get_timeouts( 'midnight' );
						SEIWP_Tools::set_error( $e, $timeout );
						$this->reset_token();
					}
					if ( is_multisite() && $this->seiwp->config->options['network_mode'] ) {
						$this->seiwp->config->set_plugin_options( true );
					} else {
						$this->seiwp->config->set_plugin_options();
					}
				}
			}

			$this->service = new Deconf\SEIWP\Google\Service\SearchConsole( $this->client );

		}

		public function authenticate( $access_code ) {

			try {
				$this->client->fetchAccessTokenWithAuthCode( $access_code );
				return $this->client->getAccessToken();
			} catch ( GoogleServiceException $e ) {
				$timeout = $this->get_timeouts( 'midnight' );
				SEIWP_Tools::set_error( $e, $timeout );
			} catch ( Exception $e ) {
				$timeout = $this->get_timeouts( 'midnight' );
				SEIWP_Tools::set_error( $e, $timeout );
			}
		}

		/**
		 * Handles the token reset process
		 *
		 * @param
		 *            $all
		 */
		public function reset_token( $all = false ) {

			$token = $this->client->getAccessToken();

			if ( $token ) {
				$this->client->revokeToken( $token );
			}

			if ( $all ){
				$this->seiwp->config->options['site_jail'] = "";
				$this->seiwp->config->options['sites_list'] = array();
			}

			$this->seiwp->config->options['token'] = "";
			$this->seiwp->config->options['sites_list_locked'] = 0;

			if ( is_multisite() && $this->seiwp->config->options['network_mode'] ) {
				$this->seiwp->config->set_plugin_options( true );
			} else {
				$this->seiwp->config->set_plugin_options();
			}
		}

		/**
		 * Handles errors returned by Google API Client to avoid unnecessary requests
		 *
		 * @return boolean
		 */
		public function gapi_errors_handler() {
			$errors = SEIWP_Tools::get_cache( 'gapi_errors' );
			if ( false === $errors || ! isset( $errors[0] ) ) { // invalid error
				return false;
			}
			if ( isset( $errors[1][0]['reason'] ) && ( 'invalidParameter' == $errors[1][0]['reason'] || 'badRequest' == $errors[1][0]['reason'] || 'invalidCredentials' == $errors[1][0]['reason'] || 'insufficientPermissions' == $errors[1][0]['reason'] || 'required' == $errors[1][0]['reason'] ) ) {
				$this->reset_token();
				return true;
			}
			if ( 400 == $errors[0] || 401 == $errors[0] || 403 == $errors[0] ) {
				$this->reset_token();
				return true;
			}
			/**
			 * Back-off system for subsequent requests - an Auth error generated after a Service request
			 *  The native back-off system for Service requests is covered by the Google API Client
			 */
			if ( isset( $errors[1][0]['reason'] ) && ( 'authError' == $errors[1][0]['reason'] ) ) {
				if ( $this->seiwp->config->options['api_backoff'] <= 5 ) {
					usleep( $this->seiwp->config->options['api_backoff'] * 1000000 + rand( 100000, 1000000 ) );
					$this->seiwp->config->options['api_backoff'] = $this->seiwp->config->options['api_backoff'] + 1;
					$this->seiwp->config->set_plugin_options();
					return false;
				} else {
					return true;
				}
			}
			if ( 500 == $errors[0] || 503 == $errors[0] || $errors[0] < - 50 ) {
				return true;
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
				$newtime = strtotime( ' +5 minutes', $local_time );
				return $newtime - $local_time;
			}
		}

		/**
		 * Verifies the current site for Google Search Console
		 *
		 * @return boolean
		 */
		public function verify_property() {
			try {
				if ( empty( $this->seiwp->config->options['site_verification_meta'] ) ) {
					$site = new Deconf\SEIWP\Google\Service\SiteVerification\SiteVerificationWebResourceGettokenRequestSite();
					$site->setIdentifier( SEIWP_SITE_URL );
					$site->setType( 'SITE' );
					$request = new Deconf\SEIWP\Google\Service\SiteVerification\SiteVerificationWebResourceGettokenRequest();
					$request->setSite( $site );
					$request->setVerificationMethod( 'META' );
					$service = new Deconf\SEIWP\Google\Service\SiteVerification( $this->client );
					$webResource = $service->webResource;
					$result = $webResource->getToken( $request );
					$this->seiwp->config->options['site_verification_meta'] = $result->token;
					$this->seiwp->config->set_plugin_options();
				}
				$site = new Deconf\SEIWP\Google\Service\SiteVerification\SiteVerificationWebResourceResourceSite();
				$site->setIdentifier( SEIWP_SITE_URL );
				$site->setType( 'SITE' );
				$request = new Deconf\SEIWP\Google\Service\SiteVerification\SiteVerificationWebResourceResource();
				$request->setSite( $site );
				$service = new Deconf\SEIWP\Google\Service\SiteVerification( $this->client );
				$webResource = $service->webResource;
				$result = $webResource->insert( 'META', $request );
				return true;
			} catch ( Exception $e ) {
				return false;
			}
		}

		/**
		 * Deletes the Google Search Console Property corresponding to current site
		 *
		 * @return boolean || array
		 */
		public function delete_property() {
			try {
				$url = SEIWP_SITE_URL;
				$this->service->sites->delete( $url );
				return true;
			} catch ( GoogleServiceException $e ) {
				$timeout = $this->get_timeouts( 'midnight' );
				SEIWP_Tools::set_error( $e, $timeout );
			} catch ( Exception $e ) {
				$timeout = $this->get_timeouts( 'midnight' );
				SEIWP_Tools::set_error( $e, $timeout );
			}
		}

		/**
		 * Adds a Google Search Console Property for the current site
		 *
		 * @return boolean || array
		 */
		public function add_property() {
			try {
				$url = SEIWP_SITE_URL;
				$this->service->sites->add( $url );
				return true;
			} catch ( GoogleServiceException $e ) {
				$timeout = $this->get_timeouts( 'midnight' );
				SEIWP_Tools::set_error( $e, $timeout );
				return false;
			} catch ( Exception $e ) {
				$timeout = $this->get_timeouts( 'midnight' );
				SEIWP_Tools::set_error( $e, $timeout );
				return false;
			}
		}

		/**
		 * Retrieves all Search Engine Insights Properties with their details
		 *
		 * @return array
		 */
		public function get_sites_info() {
			try {
				$sites_list = array();
				$startindex = 1;
				$totalresults = 65535; // use something big
				while ( $startindex < $totalresults ) {
					$sites = $this->service->sites->listSites();
					$totalresults = $sites->count();
					if ( $totalresults > 0 ) {
						$siteentry = $sites->getSiteEntry();
						foreach ( $siteentry as $site ) {
							$sites_list[] = array( $site->getSiteUrl(), $site->getPermissionLevel() );
							$startindex++;
						}
					}
				}
				SEIWP_Tools::delete_cache( 'last_error' );
				return $sites_list;
			} catch ( GoogleServiceException $e ) {
				$timeout = $this->get_timeouts( 'midnight' );
				SEIWP_Tools::set_error( $e, $timeout );
			} catch ( Exception $e ) {
				$timeout = $this->get_timeouts( 'midnight' );
				SEIWP_Tools::set_error( $e, $timeout );
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
		 * @return int|Google\Service\SearchConsole
		 */
		private function handle_searchanalytics_reports( $projectId, $from, $to, $dimensions, $options, $filters, $serial ) {
			try {
				$transient = SEIWP_Tools::get_cache( $serial );
				if ( false === $transient ) {
					if ( $this->gapi_errors_handler() ) {
						return - 23;
					}
					$options['samplingLevel'] = 'HIGHER_PRECISION';
					$request = new Deconf\SEIWP\Google\Service\SearchConsole\SearchAnalyticsQueryRequest();
					$request->setStartDate( $from );
					$request->setEndDate( $to );
					if ( $dimensions ) {
						$request->setDimensions( $dimensions );
					}
					if ( is_array( $filters ) ) {
						$dimensionfiltergroup = new Deconf\SEIWP\Google\Service\SearchConsole\ApiDimensionFilterGroup();
						$filtergroup = new Deconf\SEIWP\Google\Service\SearchConsole\ApiDimensionFilter();
						$filtergroup->setDimension( $filters['dimension'] );
						$filtergroup->setExpression( $filters['expression'] );
						$filtergroup->setOperator( $filters['operator'] );
						$dimensionfiltergroup->setFilters( array( $filtergroup ) );
						$request->setDimensionFilterGroups( array( $dimensionfiltergroup ) );
					}
					$data = $this->service->searchanalytics->query( $projectId, $request );
					SEIWP_Tools::set_cache( $serial, $data, $this->get_timeouts( 'daily' ) );
				} else {
					$data = $transient;
				}
			} catch ( GoogleServiceException $e ) {
				$timeout = $this->get_timeouts( 'midnight' );
				SEIWP_Tools::set_error( $e, $timeout );
				return $e->getCode();
			} catch ( Exception $e ) {
				$timeout = $this->get_timeouts( 'midnight' );
				SEIWP_Tools::set_error( $e, $timeout );
				return $e->getCode();
			}
			$this->seiwp->config->options['api_backoff'] = 0;
			$this->seiwp->config->set_plugin_options();
			if ( $data->getRows() > 0 ) {
				return $data;
			} else {
				$data->rows = array();
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
			if ( empty( $data->rows ) ) {
				// unable to render it as an Area Chart, returns a numeric value to be handled by reportsx.js
				return - 21;
			}
			$seiwp_data = array( array( __( "Date", 'search-engine-insights' ), $title ) );
			foreach ( $data->getRows() as $row ) {
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
			$seiwp_data[0] = empty( $data->getRows() ) ? 0 : SEIWP_Tools::number_to_kmb( $data->getRows()[0]->getImpressions() );
			$seiwp_data[1] = empty( $data->getRows() ) ? 0 : SEIWP_Tools::number_to_kmb( $data->getRows()[0]->getClicks() );
			$seiwp_data[2] = empty( $data->getRows() ) ? 0 : number_format_i18n( $data->getRows()[0]->getPosition(), 2 );
			$seiwp_data[3] = empty( $data->getRows() ) ? '0%' : number_format_i18n( $data->getRows()[0]->getCtr(), 2 ) . '%';
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
			foreach ( $data->getRows() as $row ) {
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
			foreach ( $data->getRows() as $row ) {
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
			foreach ( $data->getRows() as $row ) {
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
			$dimensions = 'query';
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
			if ( empty( $data->rows ) ) {
				// unable to render as an Org Chart, returns a numeric value to be handled by reportsx.js
				return - 21;
			}
			$res_data['Impressions'] = $data->getRows()[0]->getImpressions() ? SEIWP_Tools::number_to_kmb( $data->getRows()[0]->getImpressions() ) : 0;
			$res_data['Clicks'] = $data->getRows()[0]->getClicks() ? SEIWP_Tools::number_to_kmb( $data->getRows()[0]->getClicks() ) : 0;
			$res_data['Position'] = $data->getRows()[0]->getPosition() ? number_format_i18n( $data->getRows()[0]->getPosition(), 2 ) : 0;
			$res_data['CTR'] = $data->getRows()[0]->getCtr() ? number_format_i18n( $data->getRows()[0]->getCtr(), 2 ) . '%' : '0%';
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
		 * @return number|Google\Service\SearchConsole
		 */
		public function get( $projectId, $query, $from = false, $to = false, $filter = '', $metric = 'sessions' ) {
			if ( empty( $projectId ) ) {
				wp_die( - 26 );
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
				return $this->get_pages( $projectId, $from, $to, $metric, $filter  );
			}
			if ( 'channelGrouping' == $query || 'deviceCategory' == $query ) {
				return $this->get_orgchart_data( $projectId, $from, $to, $query, $metric, $filter );
			}
			if ( 'keywords' == $query ) {
				return $this->get_keywords( $projectId, $from, $to, $metric, $filter );
			}
			wp_die( - 27 );
		}
	}
}
