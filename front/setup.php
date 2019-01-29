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

if ( ! class_exists( 'SEIWP_Frontend_Setup' ) ) {

	final class SEIWP_Frontend_Setup {

		private $seiwp;

		public function __construct() {
			$this->seiwp = SEIWP();
			/**
			 * Styles & Scripts
			 */
			add_action( 'wp_enqueue_scripts', array( $this, 'load_styles_scripts' ) );
		}
		/**
		 * Styles & Scripts conditional loading
		 *
		 * @param
		 *            $hook
		 */
		public function load_styles_scripts() {
			$lang = get_bloginfo( 'language' );
			$lang = explode( '-', $lang );
			$lang = $lang[0];
			/**
			 * Item reports Styles & Scripts
			 */
			if ( SEIWP_Tools::check_roles( $this->seiwp->config->options['access_front'] ) && $this->seiwp->config->options['frontend_item_reports'] ) {

				wp_enqueue_style( 'seiwp-nprogress', SEIWP_URL . 'common/nprogress/nprogress'.SEIWP_Tools::script_debug_suffix().'.css', null, SEIWP_CURRENT_VERSION );

				wp_enqueue_style( 'seiwp-frontend-item-reports', SEIWP_URL . 'front/css/item-reports'.SEIWP_Tools::script_debug_suffix().'.css', null, SEIWP_CURRENT_VERSION );

				wp_enqueue_style( 'seiwp-daterangepicker', SEIWP_URL . 'common/daterangepicker/daterangepicker'.SEIWP_Tools::script_debug_suffix().'.css', null, SEIWP_CURRENT_VERSION );

				wp_enqueue_style( "wp-jquery-ui-dialog" );

				wp_register_script( 'googlecharts', 'https://www.gstatic.com/charts/loader.js', array(), null );

				wp_enqueue_script( 'seiwp-nprogress', SEIWP_URL . 'common/nprogress/nprogress'.SEIWP_Tools::script_debug_suffix().'.js', array( 'jquery' ), SEIWP_CURRENT_VERSION );

				wp_enqueue_script( 'seiwp-moment', SEIWP_URL . 'common/daterangepicker/moment'.SEIWP_Tools::script_debug_suffix().'.js', array( 'jquery' ), SEIWP_CURRENT_VERSION );

				wp_enqueue_script( 'seiwp-daterangepicker', SEIWP_URL . 'common/daterangepicker/daterangepicker'.SEIWP_Tools::script_debug_suffix().'.js', array( 'jquery' ), SEIWP_CURRENT_VERSION );

				wp_enqueue_script( 'seiwp-frontend-item-reports', SEIWP_URL . 'common/js/reports'.SEIWP_Tools::script_debug_suffix().'.js', array( 'seiwp-nprogress', 'seiwp-moment', 'seiwp-daterangepicker','googlecharts', 'jquery', 'jquery-ui-dialog' ), SEIWP_CURRENT_VERSION, true );

				/* @formatter:off */
				wp_localize_script( 'seiwp-frontend-item-reports', 'seiwpItemData', array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'security' => wp_create_nonce( 'seiwp_frontend_item_reports' ),
					'reportList' => array(
						'impressions' => __( "Impressions", 'search-engine-insights' ),
						'clicks' => __( "Clicks", 'search-engine-insights' ),
						'position' => __( "Position", 'search-engine-insights' ),
						'ctr' => __( "CTR", 'search-engine-insights' ),
						'locations' => __( "Location", 'search-engine-insights' ),
						'keywords' => __( "Keywords", 'search-engine-insights' ),
					),
					'i18n' => array(
							__( "A JavaScript Error is blocking plugin resources!", 'search-engine-insights' ), //0
							__( "", 'search-engine-insights' ),
							__( "", 'search-engine-insights' ),
							__( "", 'search-engine-insights' ),
							__( "", 'search-engine-insights' ),
							__( "Impressions", 'search-engine-insights' ),
							__( "Clicks", 'search-engine-insights' ),
							__( "Position", 'search-engine-insights' ),
							__( "CTR", 'search-engine-insights' ),
							__( "Server Errors", 'search-engine-insights' ),
							__( "Not Found", 'search-engine-insights' ),
							__( "Invalid response", 'search-engine-insights' ),
							__( "Processing data, please check again in a few days", 'search-engine-insights' ),
							__( "This report is unavailable", 'search-engine-insights' ),
							__( "report generated by", 'search-engine-insights' ), //14
							__( "This plugin needs an authorization:", 'search-engine-insights' ) . ' <strong>' . __( "authorize the plugin", 'search-engine-insights' ) . '</strong>!',
					),
					'colorVariations' => SEIWP_Tools::variations( $this->seiwp->config->options['theme_color'] ),
					'mapsApiKey' => apply_filters( 'seiwp_maps_api_key', $this->seiwp->config->options['maps_api_key'] ),
					'language' => get_bloginfo( 'language' ),
					'filter' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]",
					'propertyList' => false,
					'scope' => 'front-item',
				 )
				);
				/* @formatter:on */
			}
		}
	}
}
