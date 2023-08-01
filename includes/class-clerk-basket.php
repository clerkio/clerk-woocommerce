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

/**
 * Clerk_Logger Class
 *
 * Clerk Module Core Class
 */
class Clerk_Basket {

	/**
	 * Error and Warning Logger
	 *
	 * @var $logger Clerk_Logger
	 */
	protected $logger;

	/**
	 * Clerk_Basket constructor.
	 */
	public function __construct() {
		$this->init_hooks();
		include_once __DIR__ . '/class-clerk-logger.php';
		$this->logger = new Clerk_Logger();
	}

	/**
	 * Init hooks
	 */
	private function init_hooks() {

		$options = get_option( 'clerk_options' );
		if ( ! isset( $options['collect_baskets'] ) ) {
			return;
		}

		add_filter( 'woocommerce_add_to_cart_redirect', array( $this, 'update_basket' ) );
		add_filter( 'template_redirect', array( $this, 'update_basket' ) );

	}

	/**
	 * If collect basket is enabled, track baskets for abandoned cart support.
	 *
	 * @param string $url Add to cart action url.
	 */
	public function update_basket( $url ) {

		try {

			$options = get_option( 'clerk_options' );

			global $current_user;
			global $woocommerce;

			$items = $woocommerce->cart->get_cart();
			$email = (string) $current_user->user_email;

			$add_to_cart_param = false;
			$add_to_cart_param = ( null !== filter_input( INPUT_POST, 'add-to-cart', FILTER_SANITIZE_STRING ) ) ? filter_input( INPUT_POST, 'add-to-cart', FILTER_SANITIZE_STRING ) : $add_to_cart_param;
			$add_to_cart_param = ( null !== filter_input( INPUT_GET, 'add-to-cart', FILTER_SANITIZE_STRING ) ) ? filter_input( INPUT_GET, 'add-to-cart', FILTER_SANITIZE_STRING ) : $add_to_cart_param;

			$removed_item_param = false;
			$removed_item_param = ( null !== filter_input( INPUT_POST, 'removed_item', FILTER_SANITIZE_STRING ) ) ? filter_input( INPUT_POST, 'removed_item', FILTER_SANITIZE_STRING ) : $removed_item_param;
			$removed_item_param = ( null !== filter_input( INPUT_GET, 'removed_item', FILTER_SANITIZE_STRING ) ) ? filter_input( INPUT_GET, 'removed_item', FILTER_SANITIZE_STRING ) : $removed_item_param;

			$product_id_param = false;
			$product_id_param = ( null !== filter_input( INPUT_POST, 'product_id', FILTER_SANITIZE_STRING ) ) ? filter_input( INPUT_POST, 'product_id', FILTER_SANITIZE_STRING ) : $product_id_param;
			$product_id_param = ( null !== filter_input( INPUT_GET, 'product_id', FILTER_SANITIZE_STRING ) ) ? filter_input( INPUT_GET, 'product_id', FILTER_SANITIZE_STRING ) : $product_id_param;

			if ( false === $add_to_cart_param || false === $removed_item_param || false === $product_id_param ) {
				return $url;
			}

			if ( empty( $add_to_cart_param ) || ! is_numeric( $add_to_cart_param ) ) {
				if ( empty( $removed_item_param ) || ! is_numeric( $removed_item_param ) ) {
					if ( empty( $product_id_param ) || ! is_numeric( $product_id_param ) ) {
						return $url;
					}
				}
			}

			$_product_ids = array();

			foreach ( $items as $item => $values ) {
				if ( ! in_array( $values['data']->get_id(), $_product_ids, true ) ) {
					array_push( $_product_ids, $values['data']->get_id() );
				}
			}

			if ( count( $_product_ids ) > 0 ) {

				if ( ! empty( $email ) ) {

					$_endpoint = 'https://api.clerk.io/v2/log/basket/set';

					$data_string = wp_json_encode(
						array(
							'key'      => $options['public_key'],
							'products' => $_product_ids,
							'email'    => $email,
						)
					);

					$args = array(
						'body'   => $data_string,
						'method' => 'POST',
					);

					wp_remote_request( $_endpoint, $args );

				}
			}
		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR update_basket', array( 'error' => $e->getMessage() ) );

		}

	}

}

new Clerk_Basket();
