<?php
/**
 * Plugin Name: Clerk
 * Plugin URI: https://clerk.io/
 * Description: Clerk.io Turns More Browsers Into Buyers
 * Version: 4.1.5
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

/**
 * Clerk_Exit_Intent Class
 *
 * Clerk Module Core Class
 */
class Clerk_Exit_Intent {

	/**
	 * Error and Warning Logger
	 *
	 * @var $logger Clerk_Logger
	 */
	protected $logger;

	/**
	 * Clerk_Exit_Intent constructor.
	 */
	public function __construct() {
		$this->init_hooks();
		include_once __DIR__ . '/class-clerk-logger.php';
		include_once __DIR__ . '/clerk-multi-lang-helpers.php';
		if ( clerk_is_wpml_enabled() ) {
			do_action( 'wpml_multilingual_options', 'clerk_options' );
		}
		$this->logger = new Clerk_Logger();
	}

	/**
	 * Init hooks
	 */
	private function init_hooks() {
		add_action( 'wp_footer', array( $this, 'add_exit_intent' ) );
	}

	/**
	 * Include exit intent
	 */
	public function add_exit_intent() {

		try {

			$options = get_option( 'clerk_options' );

			if ( isset( $options['exit_intent_enabled'] ) && $options['exit_intent_enabled'] ) {

				$templates = explode( ',', $options['exit_intent_template'] );

				foreach ( $templates as $template ) {

					?>
					<span
					class="clerk"
					data-template="@<?php echo esc_attr( str_replace( ' ', '', $template ) ); ?>"
					data-exit-intent="true">
					</span>
					<?php

				}
			}
		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR add_exit_intent', array( 'error' => $e->getMessage() ) );

		}
	}
}

new Clerk_Exit_Intent();
