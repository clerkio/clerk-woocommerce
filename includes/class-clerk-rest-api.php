<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

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
		register_rest_route( 'clerk', '/product', [
			'methods'  => 'GET',
			'callback' => [ $this, 'product_endpoint_callback' ],
		] );

		//Category endpoint
		register_rest_route( 'clerk', '/category', [
			'methods'  => 'GET',
			'callback' => [ $this, 'category_endpoint_callback' ],
		] );

		//Order endpoint
		register_rest_route( 'clerk', '/order', [
			'methods'  => 'GET',
			'callback' => [ $this, 'order_endpoint_callback' ],
		] );

		//Customer endpoint
		register_rest_route( 'clerk', '/customer', [
			'methods'  => 'GET',
			'callback' => [ $this, 'customer_endpoint_callback' ],
		] );

		//Version endpoint
		register_rest_route( 'clerk', '/version', [
			'methods'  => 'GET',
			'callback' => [ $this, 'version_endpoint_callback' ],
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
		$page    = ( $request->get_param( 'page' ) !== null ) ? $request->get_param( 'page' ) + 1 : 1;
		$orderby = $request->get_param( 'orderby' ) ? $request->get_param( 'orderby' ) : 'date';
		$order   = $request->get_param( 'order' ) ? $request->get_param( 'order' ) : 'DESC';

		$products = clerk_get_products( array(
			'limit'   => $limit,
			'page'    => $page,
			'orderby' => $orderby,
			'order'   => $order,
			'status'  => array( 'publish' ),
		) );

		$productsArray = [];

		foreach ( $products as $product ) {
			/** @var WC_Product $product */
			$categories = wp_get_post_terms($product->get_id(), 'product_cat');

            $on_sale = $product->is_on_sale();

            if ( $product->is_type( 'variable' ) ) {
                /**
                 * Variable product sync fields
                 * Will sync the lowest price, and set the sale flag if that variant is on sale.
                 */
                $variation = $product->get_available_variations();
                $displayPrice = array();
                $regularPrice = array();
                foreach ($variation as $v){
                    $vId = $v['variation_id'];
                    $displayPrice[$vId] = $v['display_price'];
                    $regularPrice[$vId] = $v['display_regular_price'];
                }
                $lowestDisplayPrice = array_keys($displayPrice, min($displayPrice)); // Find the corresponding product ID

                $price = $displayPrice[$lowestDisplayPrice[0]]; // Get the lowest price
                $list_price = $regularPrice[$lowestDisplayPrice[0]]; // Get the corresponding list price (regular price)

                if($price === $list_price) $on_sale = false; // Remove the sale flag if the cheapest variant is not on sale

            } else {
                /**
                 * Default single product sync fields
                 */
                $price      = $product->get_price();
                $list_price = $product->get_regular_price();
            }

            $productArray = [
                'id'          => $product->get_id(),
                'name'        => $product->get_name(),
                'description' => get_post_field('post_content', $product->get_id()),
                'price'       => (float) $price,
                'list_price'  => (float) $list_price,
                'image'       => wp_get_attachment_url( $product->get_image_id() ),
                'url'         => $product->get_permalink(),
                'categories'  => wp_list_pluck($categories, 'term_id'),
                'sku'         => $product->get_sku(),
                'on_sale'     => $on_sale,
                'type'        => $product->get_type(),
            ];

            //Append additional fields
            foreach ( $additional_fields as $field ) {
                $productArray[ $field ] = $product->get_attribute( $field );
            }

            $productArray = apply_filters( 'clerk_product_array', $productArray, $product );

            $productsArray[] = $productArray;
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

		$limit   = $request->get_param( 'limit' ) ? $request->get_param( 'limit' ) : 0;
		$page    = $request->get_param( 'page' ) ? $request->get_param( 'page' ) - 1 : 0;
		$offset  = (int) $request->get_param( 'page' ) * $limit;
		$orderby = $request->get_param( 'orderby' ) ? $request->get_param( 'orderby' ) : 'date';
		$order   = $request->get_param( 'order' ) ? $request->get_param( 'order' ) : 'DESC';

		$args = [
			'number'     => $limit,
			'orderby'    => $orderby,
			'order'      => $order,
			'offset'     => $offset,
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

			$subcategories             = get_term_children( $product_category->term_id, 'product_cat' );
			$category['subcategories'] = $subcategories;

			$category = apply_filters( 'clerk_category_array', $category, $product_category );

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

		$options = get_option( 'clerk_options' );

		if ( $options['disable_order_synchronization'] ) {
			return [];
		}

		$limit = $request->get_param( 'limit' ) ? $request->get_param( 'limit' ) : - 1;
		$page  = $request->get_param( 'page' ) ? $request->get_param( 'page' ) + 1 : 1;

		$orders = wc_get_orders( [
			'limit'  => $limit,
			'offset' => ( $page - 1 ) * $limit,
			'type'   => 'shop_order_refund'
		] );

		$order_array = [];

		foreach ( $orders as $order ) {
			/** @var WC_Order $order */
			$order_items = [];
			$valid       = true;

			//Get order products
			foreach ( $order->get_items() as $item ) {
				if ( $item['qty'] > 0 ) {
					if ( $item['line_subtotal'] > 0 ) {
						$order_items[] = array(
							'id'       => $item['product_id'],
							'quantity' => $item['qty'],
							'price'    => ( $item['line_subtotal'] / $item['qty'] ),
						);
					}
				}
			}

			if ( empty( $order_items ) ) {
				$valid = false;
			}

			$order_object = [
				'email'    => $order->billing_email,
				'products' => $order_items,
				'time'     => strtotime( $order->order_date ),
				'class'    => get_class( $order )
			];

			//id is a protected property in 3.0
			if ( clerk_check_version() ) {
				$order_object['id'] = $order->get_id();
			} else {
				$order_object['id'] = $order->id;
			}

			if ( $order->customer_id > 0 ) {
				$order_object['customer'] = $order->customer_id;
			}

			if ( $valid ) {
				$order_object  = apply_filters( 'clerk_order_array', $order_object, $order );
				$order_array[] = $order_object;
			}
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
	 * Handle version endpoint
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function version_endpoint_callback( WP_REST_Request $request ) {
		if ( ! $this->validateRequest( $request ) ) {
			return $this->getUnathorizedResponse();
		}

		$response = array(
			'platform' => 'WooCommerce',
			'version'  => reset( get_file_data( CLERK_PLUGIN_FILE, array( 'version' ), 'plugin' ) ),
		);

		return $response;
	}

	/**
	 * Validate request
	 *
	 * @param $request
	 *
	 * @return bool
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

	/**
	 * Get additional fields for product export
	 *
	 * @return array
	 */
	private function getAdditionalFields() {
		$options = get_option( 'clerk_options' );

		$additional_fields = $options['additional_fields'];

		$fields = explode( ',', $additional_fields );

		return $fields;
	}
}

new Clerk_Rest_Api();