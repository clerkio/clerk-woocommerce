<?php
/**
 * Plugin Name: Clerk
 * Plugin URI: https://clerk.io/
 * Description: Clerk.io Turns More Browsers Into Buyers
 * Version: 4.1.6
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
 * Clerk_Api Class
 *
 * Clerk Module Core Class
 */
class Clerk_Api {

	/**
	 * Base Url String
	 *
	 * @var $baseurl Clerk Api Base URL.
	 */
	protected $baseurl = 'https://api.clerk.io/v2/';

	/**
	 * Error and Warning Logger
	 *
	 * @var $logger Clerk_Logger
	 */
	protected $logger;

	/**
	 * Clerk_Admin_Settings constructor.
	 */
	public function __construct() {

		include_once __DIR__ . '/class-clerk-logger.php';
		include_once __DIR__ . '/clerk-multi-lang-helpers.php';
		if ( clerk_is_wpml_enabled() ) {
			do_action( 'wpml_multilingual_options', 'clerk_options' );
		}
		$this->logger = new Clerk_Logger();
	}

	/**
	 * Post Received Token for Verification
	 *
	 * @param array|void $data Data.
	 */
	public function verify_token( $data = null ) {

		if ( ! $data ) {
			return false;
		}

		try {

			$options    = clerk_get_options();
			$public_key = $options['public_key'];

			$endpoint = 'token/verify';

			$data['key'] = $public_key;

			$response = $this->get( $endpoint, $data );

			if ( ! $response ) {
				return array();
			} else {
				return $response;
			}
		} catch ( Exception $e ) {
			$this->logger->error( 'ERROR verify_token', array( 'error' => $e->getMessage() ) );
		}
	}

	/**
	 * Remove product
	 *
	 * @param string|integer $product_id Product ID.
	 */
	public function remove_product( $product_id ) {

		try {

			$options = clerk_get_options();

			$params = array(
				'key'         => $options['public_key'],
				'private_key' => $options['private_key'],
				'products'    => wp_json_encode( array( $product_id ) ),
			);

			$this->get( 'product/remove', $params );
			$this->logger->log( 'Removed products ', array( 'params' => $params['products'] ) );

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR remove_product', array( 'error' => $e->getMessage() ) );

		}
	}

	/**
	 * Add product to Clerk
	 *
	 * @param array $product_params Product Info.
	 */
	public function add_product( $product_params ) {

		try {

			$options = clerk_get_options();

			$params = array(
				'key'         => $options['public_key'],
				'private_key' => $options['private_key'],
				'products'    => array( $product_params ),
			);

			$this->post( 'products', $params );
			$name = $params['products']['name'] ?? '';
			$this->logger->log( 'Created products ' . $name, array( 'params' => $params['products'] ) );

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR add_product', array( 'error' => $e->getMessage() ) );

		}
	}

	/**
	 * Get contents from Clerk
	 *
	 * @return array|WP_Error
	 */
	public function get_content() {

		try {

			$contents = get_transient( 'clerk_api_contents' );

			if ( $contents ) {
				return $contents;
			}

			$options = clerk_get_options();

			$params = array(
				'key'         => $options['public_key'],
				'private_key' => $options['private_key'],
			);

			$request = $this->get( 'client/account/content/list', $params );

			if ( is_wp_error( $request ) ) {
				return false;
			}

			$body = wp_remote_retrieve_body( $request );
			$json = json_decode( $body );

			if ( 'ok' === $json->status ) {
				set_transient( 'clerk_api_contents', $json, 14400 );
			}

			return $json;

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR get_content', array( 'error' => $e->getMessage() ) );

		}
	}

	/**
	 * Perform a GET request
	 *
	 * @param string $endpoint Api endpoint.
	 * @param array  $params Url parameters.
	 *
	 * @return array|WP_Error
	 */
	private function get( $endpoint, $params = array() ) {

		try {

			$url      = $this->baseurl . $endpoint . '?' . http_build_query( $params );
			$response = wp_safe_remote_get( $url );

			$this->logger->log(
				'GET request',
				array(
					'endpoint' => $endpoint,
					'params'   => $params,
					'response' => $response,
				)
			);

			return $response;

		} catch ( Exception $e ) {

			$this->logger->error( 'GET request failed', array( 'error' => $e->getMessage() ) );

		}
	}

	/**
	 * Perform a POST request
	 *
	 * @param string $endpoint Api endpoint.
	 * @param array  $params Url parameters.
	 */
	private function post( $endpoint, $params = array() ) {

		try {

			$url = $this->baseurl . $endpoint;

			$response = wp_safe_remote_post(
				$url,
				array(
					'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
					'body'    => wp_json_encode( $params ),
				)
			);

			$this->logger->log(
				'POST request',
				array(
					'endpoint' => $endpoint,
					'params'   => $params,
					'response' => $response,
				)
			);

		} catch ( Exception $e ) {

			$this->logger->error( 'POST request failed', array( 'error' => $e->getMessage() ) );

		}
	}
}
