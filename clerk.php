<?php
/**
 * Plugin Name: Clerk
 * Plugin URI: https://clerk.io/
 * Description: Clerk.io Turns More Browsers Into Buyers
 * Version: 1.0.0
 * Author: Clerk.io
 * Author URI: https://clerk.io
 *
 * Text Domain: clerk
 * Domain Path: /i18n/languages/
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
		}

		private function includes() {
			//Backend
			require_once( __DIR__ . '/includes/class-clerk-admin-settings.php' );
			require_once( __DIR__ . '/includes/class-clerk-product-sync.php' );
			require_once( __DIR__ . '/includes/class-clerk-rest-api.php' );

			//Frontend
			require_once( __DIR__ . '/includes/clerk-template-functions.php' );
			require_once( __DIR__ . '/includes/class-clerk-visitor-tracking.php' );
			require_once( __DIR__ . '/includes/class-clerk-sales-tracking.php' );
			require_once( __DIR__ . '/includes/class-clerk-search.php' );
			require_once( __DIR__ . '/includes/widgets/class-clerk-widget-search.php' );
			require_once( __DIR__ . '/includes/class-clerk-powerstep.php');
		}

		private function initHooks() {
			//Register search widget
			add_action( 'widgets_init', function () {
				register_widget( 'Clerk_Widget_Search' );
			} );


		}
	}

}

new Clerk();