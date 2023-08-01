<?php
/**
 * Plugin Name: Clerk
 * Plugin URI: https://clerk.io/
 * Description: Clerk.io Turns More Browsers Into Buyers
 * Version: 4.1.0
 * Author: Clerk.io
 * Author URI: https://clerk.io
 *
 * Text Domain: clerk
 * Domain Path: /i18n/languages/
 * License: MIT
 *
 * @package clerkio/clerk-woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Clerk' ) ) {

	/**
	 * Clerk Class
	 *
	 * Clerk Module Core Class
	 *
	 * @since 1.0.0
	 */
	class Clerk {
		/**
		 * Clerk class constructor.
		 */
		public function __construct() {
			$this->includes();
			$this->init_hooks();

			if ( ! defined( 'CLERK_PLUGIN_FILE' ) ) {
				define( 'CLERK_PLUGIN_FILE', __FILE__ );
			}
		}

		/**
		 * Include front end controllers
		 */
		private function includes() {
			// Backend.
			include_once __DIR__ . '/includes/class-clerk-admin-settings.php';
			include_once __DIR__ . '/includes/class-clerk-product-sync.php';
			include_once __DIR__ . '/includes/class-clerk-rest-api.php';
			include_once __DIR__ . '/includes/clerk-legacy-helpers.php';
			include_once __DIR__ . '/includes/class-clerk-logger.php';

			// Frontend.
			include_once __DIR__ . '/includes/clerk-template-functions.php';
			include_once __DIR__ . '/includes/class-clerk-visitor-tracking.php';
			include_once __DIR__ . '/includes/class-clerk-sales-tracking.php';
			include_once __DIR__ . '/includes/class-clerk-search.php';
			include_once __DIR__ . '/includes/widgets/class-clerk-widget-search.php';
			include_once __DIR__ . '/includes/widgets/class-clerk-widget-content.php';
			include_once __DIR__ . '/includes/class-clerk-powerstep.php';
			include_once __DIR__ . '/includes/class-clerk-basket.php';
			include_once __DIR__ . '/includes/class-clerk-exit-intent.php';
			include_once __DIR__ . '/includes/class-clerk-content.php';
		}

		/**
		 * Set up hooks
		 */
		private function init_hooks() {
			// Register widgets.
			add_action(
				'widgets_init',
				function () {
					register_widget( 'Clerk_Widget_Search' );
					register_widget( 'Clerk_Widget_Content' );
				}
			);

			add_action(
				'plugins_loaded',
				function () {
					load_plugin_textdomain( 'clerk', false, plugin_basename( dirname( __FILE__ ) ) . '/i18n/languages' );
				}
			);
		}
	}

}

new Clerk();
