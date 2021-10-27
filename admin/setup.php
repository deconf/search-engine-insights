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

if ( ! class_exists( 'SEIWP_Backend_Setup' ) ) {

	final class SEIWP_Backend_Setup {

		private $seiwp;

		public function __construct() {
			$this->seiwp = SEIWP();
			/**
			 * Styles & Scripts
			 */
			add_action( 'admin_enqueue_scripts', array( $this, 'load_styles_scripts' ) );
			/**
			 * Site Menu
			 */
			add_action( 'admin_menu', array( $this, 'site_menu' ) );
			/**
			 * Network Menu
			 */
			add_action( 'network_admin_menu', array( $this, 'network_menu' ) );
			/**
			 * Setup link
			 */
			add_filter( "plugin_action_links_" . plugin_basename( SEIWP_DIR . 'seiwp.php' ), array( $this, 'setup_link' ) );
			/**
			 * Updated admin notice
			 */
			add_action( 'admin_notices', array( $this, 'admin_notice' ) );
		}

		/**
		 * Add Site Menu
		 */
		public function site_menu() {
			global $wp_version;
			if ( current_user_can( 'manage_options' ) ) {
				include ( SEIWP_DIR . 'admin/settings.php' );
				add_menu_page( __( "Search Engine", 'search-engine-insights' ), __( "Search Engine", 'search-engine-insights' ), 'manage_options', 'seiwp_setup', array( 'SEIWP_Settings', 'setup' ), 'dashicons-seiwp' );
				add_submenu_page( 'seiwp_setup', __( "Setup", 'search-engine-insights' ), __( "Setup", 'search-engine-insights' ), 'manage_options', 'seiwp_setup', array( 'SEIWP_Settings', 'setup' ) );
				add_submenu_page( 'seiwp_setup', __( "Settings", 'search-engine-insights' ), __( "Settings", 'search-engine-insights' ), 'manage_options', 'seiwp_settings', array( 'SEIWP_Settings', 'settings' ) );
				add_submenu_page( 'seiwp_setup', __( "Debug", 'search-engine-insights' ), __( "Debug", 'search-engine-insights' ), 'manage_options', 'seiwp_errors_debugging', array( 'SEIWP_Settings', 'errors_debugging' ) );
			}
		}

		/**
		 * Add Network Menu
		 */
		public function network_menu() {
			global $wp_version;
			if ( current_user_can( 'manage_network' ) ) {
				include ( SEIWP_DIR . 'admin/settings.php' );
				add_menu_page( __( "Search Engine", 'search-engine-insights' ), "Search Engine", 'manage_network', 'seiwp_setup', array( 'SEIWP_Settings', 'setup_network' ), 'dashicons-seiwp' );
				add_submenu_page( 'seiwp_setup', __( "Setup", 'search-engine-insights' ), __( "Setup", 'search-engine-insights' ), 'manage_network', 'seiwp_setup', array( 'SEIWP_Settings', 'setup_network' ) );
				add_submenu_page( 'seiwp_setup', __( "Debug", 'search-engine-insights' ), __( "Debug", 'search-engine-insights' ), 'manage_network', 'seiwp_errors_debugging', array( 'SEIWP_Settings', 'errors_debugging' ) );
			}
		}

		/**
		 * Styles & Scripts conditional loading (based on current URI)
		 *
		 * @param
		 *            $hook
		 */
		public function load_styles_scripts( $hook ) {
			$new_hook = explode( '_page_', $hook );

			if ( isset( $new_hook[1] ) ) {
				$new_hook = '_page_' . $new_hook[1];
			} else {
				$new_hook = $hook;
			}
			/**
			 * SEIWP main stylesheet
			 */
			wp_enqueue_style( 'seiwp', SEIWP_URL . 'admin/css/seiwp' . SEIWP_Tools::script_debug_suffix() . '.css', null, SEIWP_CURRENT_VERSION );
			/**
			 * SEIWP UI
			 */
			if ( SEIWP_Tools::get_cache( 'gapi_errors' ) ) {
				$ed_bubble = '!';
			} else {
				$ed_bubble = '';
			}

			wp_enqueue_script( 'seiwp-backend-ui', plugins_url( 'js/ui' . SEIWP_Tools::script_debug_suffix() . '.js', __FILE__ ), array( 'jquery' ), SEIWP_CURRENT_VERSION, true );

			/* @formatter:off */
			wp_localize_script( 'seiwp-backend-ui', 'seiwp_ui_data', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'security' => wp_create_nonce( 'seiwp_dismiss_notices' ),
				'ed_bubble' => $ed_bubble,
			)
			);
			/* @formatter:on */

			if ( $this->seiwp->config->options['switch_profile'] && count( $this->seiwp->config->options['sites_list'] ) > 1 ) {
				$properties = array();
				foreach ( $this->seiwp->config->options['sites_list'] as $items ) {
					if ( $items[0] && ( $items[1] == 'siteOwner' || $items[1] == 'siteRestrictedUser' ) ) {
						$properties[$items[0]] = esc_js( rtrim( $items[0], '/' ) ); // . ' &#8658; ' . $items[0] );
					}
				}
			} else {
				$properties = false;
			}
			/**
			 * Main Dashboard Widgets Styles & Scripts
			 */
			$widgets_hooks = array( 'index.php' );

			if ( in_array( $new_hook, $widgets_hooks ) ) {
				if ( SEIWP_Tools::check_roles( $this->seiwp->config->options['access_back'] ) && $this->seiwp->config->options['dashboard_widget'] ) {

					wp_enqueue_style( 'seiwp-nprogress', SEIWP_URL . 'common/nprogress/nprogress' . SEIWP_Tools::script_debug_suffix() . '.css', null, SEIWP_CURRENT_VERSION );

					wp_enqueue_style( 'seiwp-daterangepicker', SEIWP_URL . 'common/daterangepicker/daterangepicker' . SEIWP_Tools::script_debug_suffix() . '.css', null, SEIWP_CURRENT_VERSION );

					wp_enqueue_style( 'seiwp-backend-item-reports', SEIWP_URL . 'admin/css/admin-widgets' . SEIWP_Tools::script_debug_suffix() . '.css', null, SEIWP_CURRENT_VERSION );

					wp_register_script( 'googlecharts', 'https://www.gstatic.com/charts/loader.js', array(), null );

					wp_enqueue_script( 'seiwp-nprogress', SEIWP_URL . 'common/nprogress/nprogress' . SEIWP_Tools::script_debug_suffix() . '.js', array( 'jquery' ), SEIWP_CURRENT_VERSION );

					wp_enqueue_script( 'seiwp-moment', SEIWP_URL . 'common/daterangepicker/moment' . SEIWP_Tools::script_debug_suffix() . '.js', array( 'jquery' ), SEIWP_CURRENT_VERSION );

					wp_enqueue_script( 'seiwp-daterangepicker', SEIWP_URL . 'common/daterangepicker/daterangepicker' . SEIWP_Tools::script_debug_suffix() . '.js', array( 'jquery' ), SEIWP_CURRENT_VERSION );

					wp_enqueue_script( 'seiwp-backend-dashboard-reports', SEIWP_URL . 'common/js/reports' . SEIWP_Tools::script_debug_suffix() . '.js', array( 'jquery', 'googlecharts', 'seiwp-nprogress', 'seiwp-moment', 'seiwp-daterangepicker', 'jquery-ui-core', 'jquery-ui-position' ), SEIWP_CURRENT_VERSION, true );

					/* @formatter:off */

					wp_localize_script( 'seiwp-backend-dashboard-reports', 'seiwpItemData', array(
						'ajaxurl' => admin_url( 'admin-ajax.php' ),
						'security' => wp_create_nonce( 'seiwp_backend_item_reports' ),
						'reportList' => array(
							'impressions' => __( "Impressions", 'search-engine-insights' ),
							'clicks' => __( "Clicks", 'search-engine-insights' ),
							'position' => __( "Position", 'search-engine-insights' ),
							'ctr' => __( "CTR", 'search-engine-insights' ),
							'locations' => __( "Location", 'search-engine-insights' ),
							'pages' =>  __( "Pages", 'search-engine-insights' ),
							'keywords' => __( "Keywords", 'search-engine-insights' ),
						),
						'i18n' => array(
							__( "A JavaScript Error is blocking plugin resources!", 'search-engine-insights' ), //0
							__( "Search ...", 'search-engine-insights' ),
							__( "Download", 'search-engine-insights' ),
							__( "Search Engines", 'search-engine-insights' ),
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
							__( "This plugin needs an authorization:", 'search-engine-insights' ) . ' <a href="' . menu_page_url( 'seiwp_settings', false ) . '">' . __( "authorize the plugin", 'search-engine-insights' ) . '</a>.',
						),
						'colorVariations' => SEIWP_Tools::variations( $this->seiwp->config->options['theme_color'] ),
						'mapsApiKey' => apply_filters( 'seiwp_maps_api_key', $this->seiwp->config->options['maps_api_key'] ),
						'language' => get_bloginfo( 'language' ),
						'propertyList' => $properties,
						'scope' => 'admin-widgets',
					)

					);
					/* @formatter:on */
				}
			}
			/**
			 * Posts/Pages List Styles & Scripts
			 */
			$contentstats_hooks = array( 'edit.php' );
			if ( in_array( $hook, $contentstats_hooks ) ) {
				if ( SEIWP_Tools::check_roles( $this->seiwp->config->options['access_back'] ) && $this->seiwp->config->options['backend_item_reports'] ) {

					wp_enqueue_style( 'seiwp-nprogress', SEIWP_URL . 'common/nprogress/nprogress' . SEIWP_Tools::script_debug_suffix() . '.css', null, SEIWP_CURRENT_VERSION );

					wp_enqueue_style( 'seiwp-daterangepicker', SEIWP_URL . 'common/daterangepicker/daterangepicker' . SEIWP_Tools::script_debug_suffix() . '.css', null, SEIWP_CURRENT_VERSION );

					wp_enqueue_style( 'seiwp-backend-item-reports', SEIWP_URL . 'admin/css/item-reports' . SEIWP_Tools::script_debug_suffix() . '.css', null, SEIWP_CURRENT_VERSION );

					wp_enqueue_style( "wp-jquery-ui-dialog" );

					wp_register_script( 'googlecharts', 'https://www.gstatic.com/charts/loader.js', array(), null );

					wp_enqueue_script( 'seiwp-nprogress', SEIWP_URL . 'common/nprogress/nprogress' . SEIWP_Tools::script_debug_suffix() . '.js', array( 'jquery' ), SEIWP_CURRENT_VERSION );

					wp_enqueue_script( 'seiwp-moment', SEIWP_URL . 'common/daterangepicker/moment' . SEIWP_Tools::script_debug_suffix() . '.js', array( 'jquery' ), SEIWP_CURRENT_VERSION );

					wp_enqueue_script( 'seiwp-daterangepicker', SEIWP_URL . 'common/daterangepicker/daterangepicker' . SEIWP_Tools::script_debug_suffix() . '.js', array( 'jquery' ), SEIWP_CURRENT_VERSION );

					wp_enqueue_script( 'seiwp-backend-item-reports', SEIWP_URL . 'common/js/reports' . SEIWP_Tools::script_debug_suffix() . '.js', array( 'seiwp-nprogress', 'googlecharts', 'seiwp-moment', 'seiwp-daterangepicker', 'jquery', 'jquery-ui-dialog' ), SEIWP_CURRENT_VERSION, true );

					/* @formatter:off */
					wp_localize_script( 'seiwp-backend-item-reports', 'seiwpItemData', array(
						'ajaxurl' => admin_url( 'admin-ajax.php' ),
						'security' => wp_create_nonce( 'seiwp_backend_item_reports' ),
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
							__( "Download", 'search-engine-insights' ),
							__( "", 'search-engine-insights' ),
							__( "", 'search-engine-insights' ),
							__( "Impressions", 'search-engine-insights' ),
							__( "Clicks", 'search-engine-insights' ),
							__( "Postion", 'search-engine-insights' ),
							__( "CTR", 'search-engine-insights' ),
							__( "Server Errors", 'search-engine-insights' ),
							__( "Not Found", 'search-engine-insights' ),
							__( "Invalid response", 'search-engine-insights' ),
							__( "Processing data, please check again in a few days", 'search-engine-insights' ),
							__( "This report is unavailable", 'search-engine-insights' ),
							__( "report generated by", 'search-engine-insights' ), //14
							__( "This plugin needs an authorization:", 'search-engine-insights' ) . ' <a href="' . menu_page_url( 'seiwp_settings', false ) . '">' . __( "authorize the plugin", 'search-engine-insights' ) . '</a>.',
						),
						'colorVariations' => SEIWP_Tools::variations( $this->seiwp->config->options['theme_color'] ),
						'mapsApiKey' => apply_filters( 'seiwp_maps_api_key', $this->seiwp->config->options['maps_api_key'] ),
						'language' => get_bloginfo( 'language' ),
						'propertyList' => false,
						'scope' => 'admin-item',
						)
					);
					/* @formatter:on */
				}
			}
			/**
			 * Settings Styles & Scripts
			 */
			$settings_hooks = array( '_page_seiwp_setup', '_page_seiwp_settings', '_page_seiwp_frontend_settings', '_page_seiwp_errors_debugging' );

			if ( in_array( $new_hook, $settings_hooks ) ) {
				wp_enqueue_style( 'wp-color-picker' );
				wp_enqueue_script( 'wp-color-picker' );
				wp_enqueue_script( 'wp-color-picker-script-handle', plugins_url( 'js/wp-color-picker-script' . SEIWP_Tools::script_debug_suffix() . '.js', __FILE__ ), array( 'wp-color-picker' ), false, true );
				wp_enqueue_script( 'seiwp-settings', plugins_url( 'js/settings' . SEIWP_Tools::script_debug_suffix() . '.js', __FILE__ ), array( 'jquery' ), SEIWP_CURRENT_VERSION, true );
			}
		}

		/**
		 * Add "Settings" link in Plugins List
		 *
		 * @param
		 *            $links
		 * @return array
		 */
		public function setup_link( $links ) {
			$setup_link = '<a href="' . esc_url( get_admin_url( null, 'admin.php?page=seiwp_setup' ) ) . '">' . __( "Settings", 'search-engine-insights' ) . '</a>';
			array_unshift( $links, $setup_link );
			return $links;
		}

		/**
		 *  Add an admin notice after a manual or atuomatic update
		 */
		function admin_notice() {
			$currentScreen = get_current_screen();

			if ( ! current_user_can( 'manage_options' ) || strpos( $currentScreen->base, '_seiwp_' ) === false ) {
				return;
			}

			if ( get_option( 'seiwp_got_updated' ) ) :
				?>
<div id="seiwp-notice" class="notice is-dismissible">
	<p><?php echo sprintf( __('Search Engine Insights has been updated to version %s.', 'search-engine-insights' ), SEIWP_CURRENT_VERSION).' '.sprintf( __('For details, check out %1$s.', 'search-engine-insights' ), sprintf(' <a href="https://deconf.com/search-engine-insights/">%s</a>', __('the plugin documentation', 'search-engine-insights') ) ); ?></p>
</div>
<?php
			endif;

		}
	}
}
