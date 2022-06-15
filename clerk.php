<?php
/**
 * Plugin Name: Clerk
 * Plugin URI: https://clerk.io/
 * Description: Clerk.io Turns More Browsers Into Buyers
 * Version: 3.7.0
 * Author: Clerk.io
 * Author URI: https://clerk.io
 *
 * Text Domain: clerk
 * Domain Path: /i18n/languages/
 * License: MIT
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Clerk' ) ) {

	class Clerk {
		public function __construct() {
			$this->includes();
			$this->initHooks();

			if ( ! defined( 'CLERK_PLUGIN_FILE' ) ) {
				define( 'CLERK_PLUGIN_FILE', __FILE__ );
			}
		}

		private function includes() {
			//Backend
			require_once( __DIR__ . '/includes/class-clerk-admin-settings.php' );
			require_once( __DIR__ . '/includes/class-clerk-product-sync.php' );
			require_once( __DIR__ . '/includes/class-clerk-rest-api.php' );
			require_once( __DIR__ . '/includes/clerk-legacy-helpers.php' );
            require_once( __DIR__ . '/includes/class-clerk-logger.php' );

			//Frontend
			require_once( __DIR__ . '/includes/clerk-template-functions.php' );
			require_once( __DIR__ . '/includes/class-clerk-visitor-tracking.php' );
			require_once( __DIR__ . '/includes/class-clerk-sales-tracking.php' );
			require_once( __DIR__ . '/includes/class-clerk-search.php' );
			require_once( __DIR__ . '/includes/widgets/class-clerk-widget-search.php' );
			require_once( __DIR__ . '/includes/widgets/class-clerk-widget-content.php' );
			require_once( __DIR__ . '/includes/class-clerk-powerstep.php' );
			require_once( __DIR__ . '/includes/class-clerk-basket.php' );
			require_once( __DIR__ . '/includes/class-clerk-exit-intent.php' );
			require_once( __DIR__ . '/includes/class-clerk-content.php' );
		}

		/**
		 * Set up hooks
		 */
		private function initHooks() {
			//Register widgets
			add_action( 'widgets_init', function () {
				register_widget( 'Clerk_Widget_Search' );
				register_widget( 'Clerk_Widget_Content' );
			} );

			add_action( 'plugins_loaded', function () {
				load_plugin_textdomain('clerk', false, plugin_basename( dirname( __FILE__ ) ) . '/i18n/languages' );
			} );
		}
	}

}

new Clerk();
