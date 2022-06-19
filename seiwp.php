<?php
/**
 * Plugin Name: Search Engine Insights
 * Plugin URI: https://deconf.com/search-engine-insights/
 * Description: Adds your website to Google Search Console. Displays Search Console reports on your Dashboard.
 * Author: Alin Marcu
 * Version: 2.1.3
 * Author URI: https://deconf.com
 * Text Domain: search-engine-insights
 * Domain Path: /languages
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit();

// Plugin Version
if ( ! defined( 'SEIWP_CURRENT_VERSION' ) ) {
	define( 'SEIWP_CURRENT_VERSION', '2.1.3' );
}

if ( ! defined( 'SEIWP_ENDPOINT_URL' ) ) {
	define( 'SEIWP_ENDPOINT_URL', 'https://api.deconf.com/seiwp/v2/' );
}

if ( ! defined( 'SEIWP_SITE_URL' ) ) {
	define( 'SEIWP_SITE_URL', site_url( '/' ) );
}

if ( ! class_exists( 'SEIWP_Manager' ) ) {

	final class SEIWP_Manager {

		private static $instance = null;

		public $config = null;

		public $frontend_actions = null;

		public $common_actions = null;

		public $backend_actions = null;

		public $frontend_item_reports = null;

		public $backend_setup = null;

		public $frontend_setup = null;

		public $backend_widgets = null;

		public $backend_item_reports = null;

		public $gapi_controller = null;

		/**
		 * Construct forbidden
		 */
		private function __construct() {
			if ( null !== self::$instance ) {
				_doing_it_wrong( __FUNCTION__, __( "This is not allowed, read the documentation!", 'search-engine-insights' ), '4.6' );
			}
		}

		/**
		 * Clone warning
		 */
		private function __clone() {
			_doing_it_wrong( __FUNCTION__, __( "This is not allowed, read the documentation!", 'search-engine-insights' ), '4.6' );
		}

		/**
		 * Wakeup warning
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( "This is not allowed, read the documentation!", 'search-engine-insights' ), '4.6' );
		}

		/**
		 * Creates a single instance for SEIWP and makes sure only one instance is present in memory.
		 *
		 * @return SEIWP_Manager
		 */
		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
				self::$instance->setup();
				self::$instance->config = new SEIWP_Config();
			}
			return self::$instance;
		}

		/**
		 * Defines constants and loads required resources
		 */
		private function setup() {

			// Plugin Path
			if ( ! defined( 'SEIWP_DIR' ) ) {
				define( 'SEIWP_DIR', plugin_dir_path( __FILE__ ) );
			}

			// Plugin URL
			if ( ! defined( 'SEIWP_URL' ) ) {
				define( 'SEIWP_URL', plugin_dir_url( __FILE__ ) );
			}

			// Plugin main File
			if ( ! defined( 'SEIWP_FILE' ) ) {
				define( 'SEIWP_FILE', __FILE__ );
			}

			/**
			 * Load Tools class
			 */
			include_once ( SEIWP_DIR . 'tools/tools.php' );

			/**
			 * Load Config class
			 */
			include_once ( SEIWP_DIR . 'config.php' );

			/**
			 * Load GAPI Controller class
			 */
			include_once ( SEIWP_DIR . 'tools/gapi.php' );

			/**
			 * Plugin i18n
			 */
			add_action( 'init', array( self::$instance, 'load_i18n' ) );

			/**
			 * Plugin Init
			 */
			add_action( 'init', array( self::$instance, 'load' ) );

			/**
			 * Include Install
			 */
			include_once ( SEIWP_DIR . 'install/install.php' );
			register_activation_hook( SEIWP_FILE, array( 'SEIWP_Install', 'install' ) );

			/**
			 * Include Uninstall
			 */
			include_once ( SEIWP_DIR . 'install/uninstall.php' );
			register_uninstall_hook( SEIWP_FILE, array( 'SEIWP_Uninstall', 'uninstall' ) );
		}

		/**
		 * Load i18n
		 */
		public function load_i18n() {
			load_plugin_textdomain( 'search-engine-insights', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}

		/**
		 * Conditional load
		 */
		public function load() {
			if ( is_admin() ) {
				if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
					if ( SEIWP_Tools::check_roles( self::$instance->config->options['access_back'] ) ) {
						/**
						 * Load Backend ajax actions
						 */
						include_once ( SEIWP_DIR . 'admin/ajax-actions.php' );
						self::$instance->backend_actions = new SEIWP_Backend_Ajax();
					}

					/**
					 * Load Frontend ajax actions
					 */
					include_once ( SEIWP_DIR . 'front/ajax-actions.php' );
					self::$instance->frontend_actions = new SEIWP_Frontend_Ajax();

					/**
					 * Load Common ajax actions
					 */
					include_once ( SEIWP_DIR . 'common/ajax-actions.php' );
					self::$instance->common_actions = new SEIWP_Common_Ajax();

					if ( self::$instance->config->options['backend_item_reports'] ) {
						/**
						 * Load Backend Item Reports for Quick Edit
						 */
						include_once ( SEIWP_DIR . 'admin/item-reports.php' );
						self::$instance->backend_item_reports = new SEIWP_Backend_Item_Reports();
					}
				} else if ( SEIWP_Tools::check_roles( self::$instance->config->options['access_back'] ) ) {
					/**
					 * Load Backend Setup
					 */
					include_once ( SEIWP_DIR . 'admin/setup.php' );
					self::$instance->backend_setup = new SEIWP_Backend_Setup();

					if ( self::$instance->config->options['dashboard_widget'] ) {
						/**
						 * Load Backend Widget
						 */
						include_once ( SEIWP_DIR . 'admin/widgets.php' );
						self::$instance->backend_widgets = new SEIWP_Backend_Widgets();
					}

					if ( self::$instance->config->options['backend_item_reports'] ) {
						/**
						 * Load Backend Item Reports
						 */
						include_once ( SEIWP_DIR . 'admin/item-reports.php' );
						self::$instance->backend_item_reports = new SEIWP_Backend_Item_Reports();
					}
				}
			} else {
				if ( SEIWP_Tools::check_roles( self::$instance->config->options['access_front'] ) ) {
					/**
					 * Load Frontend Setup
					 */
					include_once ( SEIWP_DIR . 'front/setup.php' );
					self::$instance->frontend_setup = new SEIWP_Frontend_Setup();

					if ( self::$instance->config->options['frontend_item_reports'] ) {
						/**
						 * Load Frontend Item Reports
						 */
						include_once ( SEIWP_DIR . 'front/item-reports.php' );
						self::$instance->frontend_item_reports = new SEIWP_Frontend_Item_Reports();
					}
				}

				if ( isset( self::$instance->config->options['site_verification_meta'] ) && self::$instance->config->options['site_verification_meta'] ) {
					/*
					 * Load site verification classes
					 */
					include_once ( SEIWP_DIR . 'front/verify/site-verification.php' );
					self::$instance->tracking = new SEIWP_Site_Verification();
				}
			}
		}
	}
}

/**
 * Returns a unique instance of SEIWP
 */
function SEIWP() {
	return SEIWP_Manager::instance();
}

/**
 * Start SEIWP
 */
SEIWP();
