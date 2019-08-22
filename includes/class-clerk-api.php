<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Clerk_Api {
	/**
	 * @var string
	 */
	protected $baseurl = 'http://api.clerk.io/v2/';
    protected $logger;
    public function __construct() {

        require_once( __DIR__ . '/class-clerk-logger.php' );
        $this->logger = new ClerkLogger();

    }

	/**
	 * Remove product
	 *
	 * @param $product_id
	 */
	public function removeProduct( $product_id ) {

        try {

            $options = get_option('clerk_options');

            $params = [
                'key' => $options['public_key'],
                'private_key' => $options['private_key'],
                'products' => array($product_id),
            ];

            $this->get('product/remove', $params);
            $this->logger->log('Removed products ', ['params' => $params['products']]);

        } catch (Exception $e) {

            $this->logger->error('ERROR removeProduct', ['error' => $e->getMessage()]);

        }

	}

	/**
	 * Add product to Clerk
	 *
	 * @param $product_params
	 */
	public function addProduct( $product_params ) {

        try {

            $options = get_option('clerk_options');

            $params = [
                'key' => $options['public_key'],
                'private_key' => $options['private_key'],
                'products' => [$product_params],
            ];

            $this->post('product/add', $params);
            $this->logger->log('Created products ' . $params['products']['name'], ['params' => $params['products']]);

        } catch (Exception $e) {

            $this->logger->error('ERROR addProduct', ['error' => $e->getMessage()]);

        }

    }

	/**
	 * Get contents from Clerk
	 *
	 * @return array|WP_Error
	 */
	public function getContent() {

        try {

            $contents = get_transient('clerk_api_contents');

            if ($contents) {
                return $contents;
            }

            $options = get_option('clerk_options');

            $params = [
                'key' => $options['public_key'],
                'private_key' => $options['private_key'],
            ];

            $request = $this->get('client/account/content/list', $params);

            if (is_wp_error($request)) {
                return false;
            }

            $body = wp_remote_retrieve_body($request);
            $json = json_decode($body);

            if ($json->status === 'ok') {
                set_transient('clerk_api_contents', $json, 14400);
            }

            return $json;

        } catch (Exception $e) {

            $this->logger->error('ERROR getContent', ['error' => $e->getMessage()]);

        }

	}

	/**
	 * Perform a GET request
	 *
	 * @param string $endpoint
	 * @param array $params
	 *
	 * @return array|WP_Error
	 */
	private function get( $endpoint, $params = [] ) {

        try {

            $url = $this->baseurl . $endpoint . '?' . http_build_query($params);
            $response = wp_safe_remote_get($url);

            $this->logger->log('GET request', ['endpoint' => $endpoint, 'params' => $params, 'response' => $response]);

            return $response;

        } catch (Exception $e) {

            $this->logger->error('GET request failed', ['error' => $e->getMessage()]);

        }

	}

	/**
	 * Perform a POST request
	 *
	 * @param string $endpoint
	 * @param array $params
     */
    private function post($endpoint, $params = [])
    {

        try {

            $url = $this->baseurl . $endpoint;

            $response = wp_safe_remote_post($url, [
                'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
                'body' => json_encode($params),
            ]);

            $this->logger->log('POST request', ['endpoint' => $endpoint, 'params' => $params, 'response' => $response]);

        } catch (Exception $e) {

            $this->logger->error('POST request failed', ['error' => $e->getMessage()]);

        }

    }
}