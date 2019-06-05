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

if ( ! class_exists( 'SEIWP_GAPI_Controller' ) ) {

	final class SEIWP_GAPI_Controller {

		public $client;

		public $service;

		public $timeshift;

		public $managequota;

		private $seiwp;

		private $access = array( '445209225034-2r8o69gufdi5558ne2kcpq7gn6s4ar8k.apps.googleusercontent.com', '' );

		/**
		 * Google API Client Initialization
		 */
		public function __construct() {
			$this->seiwp = SEIWP();

			include_once ( SEIWP_DIR . 'tools/src/SEI/autoload.php' );
			$config = new SEI_Config();
			$config->setCacheClass( 'SEI_Cache_Null' );
			if ( function_exists( 'curl_version' ) ) {
				$curlversion = curl_version();
				$curl_options = array();
				if ( isset( $curlversion['version'] ) ) {
					$rightversion = ( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) && version_compare( $curlversion['version'], '7.10.8' ) >= 0;
				} else {
					$rightversion = false;
				}

				if ( $rightversion && defined( 'SEIWP_IP_VERSION' ) && SEIWP_IP_VERSION ) {
					$curl_options[CURLOPT_IPRESOLVE] = SEIWP_IP_VERSION; // Force CURL_IPRESOLVE_V4 or CURL_IPRESOLVE_V6
				}

				// add Proxy server settings to curl, if defined
				if ( defined( 'WP_PROXY_HOST' ) && defined( 'WP_PROXY_PORT' ) ) {
					$curl_options[CURLOPT_PROXY] = WP_PROXY_HOST;
					$curl_options[CURLOPT_PROXYPORT] = WP_PROXY_PORT;
				}

				if ( defined( 'WP_PROXY_USERNAME' ) && defined( 'WP_PROXY_PASSWORD' ) ) {
					$curl_options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
					$curl_options[CURLOPT_PROXYUSERPWD] = WP_PROXY_USERNAME . ':' . WP_PROXY_PASSWORD;
				}

				$curl_options = apply_filters( 'seiwp_curl_options', $curl_options );
				if ( ! empty( $curl_options ) ) {
					$config->setClassConfig( 'SEI_IO_Curl', 'options', $curl_options );
				}
			}
			$this->client = new SEI_Client( $config );
			$this->client->setScopes( array( 'https://www.googleapis.com/auth/webmasters', 'https://www.googleapis.com/auth/siteverification' ) );
			$this->client->setAccessType( 'offline' );
			$this->client->setApplicationName( 'SEIWP ' . SEIWP_CURRENT_VERSION );
			$this->client->setRedirectUri( 'urn:ietf:wg:oauth:2.0:oob' );
			$this->managequota = 'u' . get_current_user_id() . 's' . get_current_blog_id();

			if ( $this->seiwp->config->options['user_api'] ) {
				$this->client->setClientId( $this->seiwp->config->options['client_id'] );
				$this->client->setClientSecret( $this->seiwp->config->options['client_secret'] );
			} else {
				$this->client->setClientId( $this->access[0] );
				$this->client->setClientSecret( $this->access[1] );
			}

			/**
			 * SEIWP Endpoint support
			 */
			add_action( 'seiwp_endpoint_support', array( $this, 'add_endpoint_support' ) );

			$this->service = new SEI_Service_Webmasters( $this->client );
			if ( $this->seiwp->config->options['token'] ) {
				$token = $this->seiwp->config->options['token'];
				if ( $token ) {
					try {
						$this->client->setAccessToken( $token );
						if ( $this->client->isAccessTokenExpired() ) {
							$refreshtoken = $this->client->getRefreshToken();
							$this->client->refreshToken( $refreshtoken );
						}
						$this->seiwp->config->options['token'] = $this->client->getAccessToken();
					} catch ( SEI_IO_Exception $e ) {
						$timeout = $this->get_timeouts( 'midnight' );
						SEIWP_Tools::set_error( $e, $timeout );
					} catch ( SEI_Service_Exception $e ) {
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
		}

		/*
		 * Setup the Google API Client to make all authorization calls to SEIWP Authentication Endpoint, in order to keep the API Client Secret private.
		 * Otherwise, the client secret would be public, generating serious security issues. All other requests won't be passed through the EndPoint.
		 */
		public function add_endpoint_support( $request ) {
			if ( $this->seiwp->config->options['with_endpoint'] && ! $this->seiwp->config->options['user_api'] ) {

				$url = $request->getUrl();

				if ( in_array( $url, array( 'https://accounts.google.com/o/oauth2/token', 'https://accounts.google.com/o/oauth2/revoke' ) ) ) {
					if ( get_class( $this->client->getIo() ) != 'SEI_IO_Stream' ) {
						$curl_old_options = $this->client->getClassConfig( 'SEI_IO_Curl' );
						$curl_options = $curl_old_options['options'];
						$curl_options[CURLOPT_SSL_VERIFYPEER] = 0;
						$this->client->setClassConfig( 'SEI_IO_Curl', 'options', $curl_options );
					} else {
						add_filter( 'seiwp_endpoint_stream_options', array( $this, 'add_endpoint_stream_ssl' ), 10 );
					}
				} else {
					if ( get_class( $this->client->getIo() ) != 'SEI_IO_Stream' ) {
						$curl_old_options = $this->client->getClassConfig( 'SEI_IO_Curl' );
						$curl_options = $curl_old_options['options'];
						if ( isset( $curl_options[CURLOPT_SSL_VERIFYPEER] ) ) {
							unset( $curl_options[CURLOPT_SSL_VERIFYPEER] );
							if ( empty( $curl_options ) ) {
								$this->client->setClassConfig( 'SEI_IO_Curl', 'options', '' );
							} else {
								$this->client->setClassConfig( 'SEI_IO_Curl', 'options', $curl_options );
							}
						}
					}
				}

				$url = str_replace( 'https://accounts.google.com/o/oauth2/token', SEIWP_ENDPOINT_URL . 'seiwp-token.php', $url );

				$url = str_replace( 'https://accounts.google.com/o/oauth2/revoke', SEIWP_ENDPOINT_URL . 'seiwp-revoke.php', $url );

				$request->setUrl( $url );

				if ( ! $request->getUserAgent() ) {
					$request->setUserAgent( $this->client->getApplicationName() );
				}
			}
		}

		public function add_endpoint_stream_ssl( $requestSslContext ) {
			return array( "verify_peer" => false );
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

			if ( 500 == $errors[0] || 503 == $errors[0] || 400 == $errors[0] || 401 == $errors[0] || 403 == $errors[0] || $errors[0] < - 50 ) {
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
		public function get_timeouts( $interval = '') {
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
		 * Generates and retrieves the Access Code
		 */
		public function token_request() {
			$data['authUrl'] = $this->client->createAuthUrl();
			SEIWP_Tools::load_view( 'admin/views/access-code.php', $data );
		}

		/**
		 * Verifies the current site for Google Search Console
		 *
		 * @return boolean
		 */
		public function verify_property() {
			try {

				if ( empty( $this->seiwp->config->options['site_verification_meta'] ) ) {

					$site = new SEI_Service_SiteVerification_SiteVerificationWebResourceGettokenRequestSite();
					$site->setIdentifier( SEIWP_SITE_URL );
					$site->setType( 'SITE' );

					$request = new SEI_Service_SiteVerification_SiteVerificationWebResourceGettokenRequest();
					$request->setSite( $site );
					$request->setVerificationMethod( 'META' );

					$service = new SEI_Service_SiteVerification( $this->client );
					$webResource = $service->webResource;
					$result = $webResource->getToken( $request );

					$this->seiwp->config->options['site_verification_meta'] = $result->token;
					$this->seiwp->config->set_plugin_options();
				}

				$site = new SEI_Service_SiteVerification_SiteVerificationWebResourceResourceSite();
				$site->setIdentifier( SEIWP_SITE_URL );
				$site->setType( 'SITE' );

				$request = new SEI_Service_SiteVerification_SiteVerificationWebResourceResource();
				$request->setSite( $site );

				$service = new SEI_Service_SiteVerification( $this->client );
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
			} catch ( SEI_IO_Exception $e ) {
				$timeout = $this->get_timeouts( 'midnight' );
				SEIWP_Tools::set_error( $e, $timeout );
				return $sites_list;
			} catch ( SEI_Service_Exception $e ) {
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
			} catch ( SEI_IO_Exception $e ) {
				$timeout = $this->get_timeouts( 'midnight' );
				SEIWP_Tools::set_error( $e, $timeout );
				return false;
			} catch ( SEI_Service_Exception $e ) {
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
			} catch ( SEI_IO_Exception $e ) {
				$timeout = $this->get_timeouts( 'midnight' );
				SEIWP_Tools::set_error( $e, $timeout );
				return $sites_list;
			} catch ( SEI_Service_Exception $e ) {
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
		public function reset_token( $all = true) {
			$this->seiwp->config->options['token'] = "";
			$this->seiwp->config->options['sites_list_locked'] = 0;
			if ( $all ) {
				$this->seiwp->config->options['site_jail'] = "";
				$this->seiwp->config->options['sites_list'] = array();
				try {
					$this->client->revokeToken();
				} catch ( Exception $e ) {
					if ( is_multisite() && $this->seiwp->config->options['network_mode'] ) {
						$this->seiwp->config->set_plugin_options( true );
					} else {
						$this->seiwp->config->set_plugin_options();
					}
				}
			}
			if ( is_multisite() && $this->seiwp->config->options['network_mode'] ) {
				$this->seiwp->config->set_plugin_options( true );
			} else {
				$this->seiwp->config->set_plugin_options();
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
		 * @return int|SEI_Service_Webmasters
		 */
		private function handle_searchanalytics_reports( $projectId, $from, $to, $dimensions, $options, $filters, $serial ) {
			try {

				$transient = SEIWP_Tools::get_cache( $serial );
				if ( false === $transient ) {
					if ( $this->gapi_errors_handler() ) {
						return - 23;
					}

					$options['samplingLevel'] = 'HIGHER_PRECISION';
					$request = new SEI_Service_Webmasters_SearchAnalyticsQueryRequest();
					$request->setStartDate( $from );
					$request->setEndDate( $to );
					$request->setDimensions( $dimensions );
					if ( is_array( $filters ) ) {
						$dimensionfiltergroup = new SEI_Service_Webmasters_ApiDimensionFilterGroup();
						$filtergroup = new SEI_Service_Webmasters_ApiDimensionFilter();
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
			} catch ( SEI_Service_Exception $e ) {
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
		private function get_areachart_data( $projectId, $from, $to, $query, $filter = '') {
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

			$metrics = $query;

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
		private function get_summary( $projectId, $from, $to, $filter = '') {
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
		private function get_pages( $projectId, $from, $to, $filter = '', $metric ) {
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
		private function get_keywords( $projectId, $from, $to, $filter = '', $metric ) {
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
		private function get_locations( $projectId, $from, $to, $filter = '', $metric ) {
			$metrics = $metric;
			$options = "";
			$title = __( "Countries", 'search-engine-insights' );
			$serial = 'qr7_' . $this->get_serial( $projectId . $from . $to . $filter . $metric );
			$dimensions[] = 'country';
			$local_filter = '';

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
		private function get_orgchart_data( $projectId, $from, $to, $query, $filter = '', $metric ) {
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
		 * @return number|SEI_Service_Webmasters
		 */
		public function get( $projectId, $query, $from = false, $to = false, $filter = '', $metric = 'sessions') {
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
				return $this->get_locations( $projectId, $from, $to, $filter, $metric );
			}

			if ( 'pages' == $query ) {
				return $this->get_pages( $projectId, $from, $to, $filter, $metric );
			}

			if ( 'channelGrouping' == $query || 'deviceCategory' == $query ) {
				return $this->get_orgchart_data( $projectId, $from, $to, $query, $filter, $metric );
			}

			if ( 'keywords' == $query ) {
				return $this->get_keywords( $projectId, $from, $to, $filter, $metric );
			}

			wp_die( - 27 );
		}
	}
}
