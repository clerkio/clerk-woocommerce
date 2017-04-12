<?php

class Clerk_Api {
	/**
	 * @var string
	 */
	protected $baseurl = 'http://api.clerk.io/v2/product/';

	/**
	 * Remove product
	 *
	 * @param $product_id
	 */
	public function removeProduct( $product_id ) {
		$options = get_option( 'clerk_options' );

		$params = [
			'key'         => $options['public_key'],
			'private_key' => $options['private_key'],
			'products'    => $product_id,
		];

		$this->get( 'remove', $params );
	}

	public function addProduct( $product_params ) {
		$options = get_option( 'clerk_options' );

		$params = [
			'key'         => $options['public_key'],
			'private_key' => $options['private_key'],
			'products'    => [ $product_params ],
		];

		$this->post( 'add', $params );
	}

	/**
	 * Perform a GET request
	 *
	 * @param string $endpoint
	 * @param array $params
	 */
	private function get( $endpoint, $params = [] ) {
		$url      = $this->baseurl . $endpoint . http_build_query( $params );
		$response = wp_safe_remote_get( $url );
	}

	/**
	 * Perform a POST request
	 *
	 * @param string $endpoint
	 * @param array $params
	 */
	private function post( $endpoint, $params = [] ) {
		$url = $this->baseurl . $endpoint;

		$response = wp_safe_remote_post( $url, [
			'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'body'    => json_encode( $params ),
		] );
	}
}