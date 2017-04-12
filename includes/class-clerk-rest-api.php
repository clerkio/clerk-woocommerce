<?php

class Clerk_Rest_Api extends WP_REST_Server {
	/**
	 * Clerk_Rest_Api constructor.
	 */
	public function __construct() {
		$this->initHooks();
	}

	/**
	 * Init hooks
	 */
	private function initHooks() {
		add_action( 'rest_api_init', [ $this, 'add_rest_api_routes' ] );
		add_filter( 'rest_pre_serve_request', [ $this, 'rest_pre_serve_request' ], 10, 3 );
	}

	/**
	 * Add REST API routes
	 */
	public function add_rest_api_routes() {
		//Product endpoint
		register_rest_route( 'clerk/resource', '/product', [
			'methods'  => 'GET',
			'callback' => [ $this, 'product_endpoint_callback' ],
		] );

		//Category endpoint
		register_rest_route( 'clerk/resource', '/category', [
			'methods'  => 'GET',
			'callback' => [ $this, 'category_endpoint_callback' ],
		] );

		//Order endpoint
		register_rest_route( 'clerk/resource', '/order', [
			'methods'  => 'GET',
			'callback' => [ $this, 'order_endpoint_callback' ],
		] );

		//Customer endpoint
		register_rest_route( 'clerk/resource', '/customer', [
			'methods'  => 'GET',
			'callback' => [ $this, 'customer_endpoint_callback' ],
		] );
	}

	/**
	 * Serve request, taking into account the debug parameter
	 *
	 * @param $served
	 * @param $result
	 * @param $request
	 *
	 * @return bool|string
	 */
	public function rest_pre_serve_request( $served, $result, $request ) {
		//Determine if this this is a clerk request
		if ( $attributes = $request->get_attributes() ) {
			if ( is_array( $attributes['callback'] ) && $attributes['callback'][0] instanceof $this ) {
				// Embed links inside the request.
				$result = $this->response_to_data( $result, isset( $_GET['_embed'] ) );

				if ( $request->get_param( 'debug' ) && $request->get_param( 'debug' ) == true ) {
					$result = wp_json_encode( $result, JSON_PRETTY_PRINT );
				} else {
					$result = wp_json_encode( $result );
				}

				$json_error_message = $this->get_json_last_error();
				if ( $json_error_message ) {
					$json_error_obj = new WP_Error( 'rest_encode_error', $json_error_message,
						array( 'status' => 500 ) );
					$result         = $this->error_to_response( $json_error_obj );
					$result         = wp_json_encode( $result->data[0] );
				}

				echo $result;

				return true;
			}
		}

		return false;
	}

	/**
	 * Handle product endpoint
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return array|WP_REST_Response
	 */
	public function product_endpoint_callback( WP_REST_Request $request ) {
		if ( ! $this->validateRequest( $request ) ) {
			return $this->getUnathorizedResponse();
		}

		$limit   = $request->get_param( 'limit' ) ? $request->get_param( 'limit' ) : - 1;
		$page    = $request->get_param( 'page' ) ? $request->get_param( 'page' ) : 1;
		$orderby = $request->get_param( 'orderby' ) ? $request->get_param( 'orderby' ) : 'date';
		$order   = $request->get_param( 'order' ) ? $request->get_param( 'order' ) : 'DESC';

		$products = wc_get_products( [
			'limit'   => $limit,
			'page'    => $page,
			'orderby' => $orderby,
			'order'   => $order,
		] );

		$productsArray = [];

		foreach ( $products as $product ) {
			/** @var WC_Product $product */
			$productsArray[] = [
				'id'          => $product->get_id(),
				'name'        => $product->get_name(),
				'description' => $product->get_description(),
				'price'       => $product->get_price(),
				'list_price'  => $product->get_regular_price(),
				'image'       => wp_get_attachment_url( $product->get_image_id() ),
				'url'         => $product->get_permalink(),
				'categories'  => $product->get_category_ids(),
				'sku'         => $product->get_sku(),
				'on_sale'     => $product->is_on_sale(),
			];
		}

		return $productsArray;
	}

	/**
	 * Handle category endpoint
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return array|WP_REST_Response
	 */
	public function category_endpoint_callback( WP_REST_Request $request ) {
		if ( ! $this->validateRequest( $request ) ) {
			return $this->getUnathorizedResponse();
		}

		$limit = $request->get_param( 'limit' ) ? $request->get_param( 'limit' ) : 0;
//        $offset = $request->get_param('page') ? $request->get_param('page') - 1 : 0;
		$orderby = $request->get_param( 'orderby' ) ? $request->get_param( 'orderby' ) : 'date';
		$order   = $request->get_param( 'order' ) ? $request->get_param( 'order' ) : 'DESC';

		$args = [
			'number'     => $limit,
			'orderby'    => $orderby,
			'order'      => $order,
//            'offset'     => $offset,
			'hide_empty' => true,
		];

		$product_categories = get_terms( 'product_cat', $args );

		$categories = [];

		foreach ( $product_categories as $product_category ) {
			$category = [
				'id'   => $product_category->term_id,
				'name' => $product_category->name,
				'url'  => get_term_link( $product_category ),
			];

			if ( $product_category->parent > 0 ) {
				$category['parent'] = $product_category->parent;
			}

			$subcategories = get_term_children( $product_category->term_id, 'product_cat' );
//            if (count($subcategories) > 0) {
			$category['subcategories'] = $subcategories;
//            }

			$categories[] = $category;
		}

		return $categories;
	}

	/**
	 * Handle order endpoint
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return array|WP_REST_Response
	 */
	public function order_endpoint_callback( WP_REST_Request $request ) {
		if ( ! $this->validateRequest( $request ) ) {
			return $this->getUnathorizedResponse();
		}

		$orders = wc_get_orders( [] );

		$order_array = [];

		foreach ( $orders as $order ) {
			/** @var WC_Order $order */
			$order_items = [];

			//Get order products
			foreach ( $order->get_items() as $item ) {
				$order_items[] = $item->get_product_id();
			}

			$order_object = [
				'id'       => $order->get_id(),
				'products' => $order_items,
				'time'     => strtotime( $order->get_date_created() ),
				'email'    => $order->get_billing_email(),
			];

			if ( $order->get_customer_id() > 0 ) {
				$order_object['customer'] = $order->get_customer_id();
			}

			$order_array[] = $order_object;
		}

		return $order_array;
	}

	/**
	 * Handle customer endpoint
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return array|WP_REST_Response
	 */
	public function customer_endpoint_callback( WP_REST_Request $request ) {
		if ( ! $this->validateRequest( $request ) ) {
			return $this->getUnathorizedResponse();
		}
	}

	/**
	 * Validate request
	 *
	 * @param $request
	 */
	private function validateRequest( $request ) {
		$options = get_option( 'clerk_options' );

		$public_key  = $request->get_param( 'key' );
		$private_key = $request->get_param( 'private_key' );

		if ( $public_key === $options['public_key'] && $private_key === $options['private_key'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Get unathorized response
	 *
	 * @return WP_REST_Response
	 */
	private function getUnathorizedResponse() {
		$response = new WP_REST_Response( [
			'code'        => 403,
			'message'     => 'Invalid keys supplied',
			'description' => __( 'The supplied public or private key is invalid', 'clerk' ),
			'how_to_fix'  => __( 'Ensure that the proper keys are set up in the configuration', 'clerk' ),
		] );
		$response->set_status( 403 );

		return $response;
	}
}

new Clerk_Rest_Api();