<?php
/**
 * Plugin Name: Clerk
 * Plugin URI: https://clerk.io/
 * Description: Clerk.io Turns More Browsers Into Buyers
 * Version: 3.8.3
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
 * Clerk_Rest_Api Class
 *
 * Clerk Module Core Class
 */
class Clerk_Rest_Api extends WP_REST_Server {

	/**
	 * Clerk Api Interface
	 *
	 * @var Clerk_Api
	 */
	protected $api;

	/**
	 * Clerk_Rest_Api constructor.
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
		add_action( 'rest_api_init', array( $this, 'add_rest_api_routes' ) );
		add_filter( 'rest_pre_serve_request', array( $this, 'rest_pre_serve_request' ), 10, 3 );
	}

	/**
	 * Initiator function
	 */
	public function __ini() {
		$this->init_hooks();
		$this->logger = new Clerk_Logger();
	}

	/**
	 * Add REST API routes
	 */
	public function add_rest_api_routes() {
		// Clerk setting get configuration endpoint.
		register_rest_route(
			'clerk',
			'/getconfig',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => array( $this, 'getconfig_endpoint_callback' ),
				'permission_callback' => '__return_true',
			)
		);

		// Clerk setting set configuration endpoint.
		register_rest_route(
			'clerk',
			'/setconfig',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'setconfig_endpoint_callback' ),
				'permission_callback' => '__return_true',
			)
		);

		// Product endpoint.
		register_rest_route(
			'clerk',
			'/product',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => array( $this, 'product_endpoint_callback' ),
				'permission_callback' => '__return_true',
			)
		);

		// Product endpoint.
		register_rest_route(
			'clerk',
			'/page',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => array( $this, 'page_endpoint_callback' ),
				'permission_callback' => '__return_true',
			)
		);

		// Category endpoint.
		register_rest_route(
			'clerk',
			'/category',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => array( $this, 'category_endpoint_callback' ),
				'permission_callback' => '__return_true',
			)
		);

		// Order endpoint.
		register_rest_route(
			'clerk',
			'/order',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => array( $this, 'order_endpoint_callback' ),
				'permission_callback' => '__return_true',
			)
		);

		// Customer endpoint.
		register_rest_route(
			'clerk',
			'/customer',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => array( $this, 'customer_endpoint_callback' ),
				'permission_callback' => '__return_true',
			)
		);

		// Version endpoint.
		register_rest_route(
			'clerk',
			'/version',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => array( $this, 'version_endpoint_callback' ),
				'permission_callback' => '__return_true',
			)
		);

		// Version endpoint.
		register_rest_route(
			'clerk',
			'/plugin',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => array( $this, 'plugin_endpoint_callback' ),
				'permission_callback' => '__return_true',
			)
		);

		// Log endpoint.
		register_rest_route(
			'clerk',
			'/log',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => array( $this, 'log_endpoint_callback' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Serve request, taking into account the debug parameter
	 *
	 * @param object|void $served Served.
	 * @param mixed       $result Result.
	 * @param object      $request Request.
	 *
	 * @return bool|string
	 */
	public function rest_pre_serve_request( $served, $result, $request ) {

		try {

			// Determine if this this is a clerk request.
			if ( $attributes === $request->get_attributes() ) {
				if ( is_array( $attributes['callback'] ) && $attributes['callback'][0] instanceof $this ) {
					// Embed links inside the request.
					$result = $this->response_to_data( $result, isset( $_GET['_embed'] ) );

					if ( $request->get_param( 'debug' ) && true === $request->get_param( 'debug' ) ) {
						$result = wp_json_encode( $result, JSON_PRETTY_PRINT );
					} else {
						$result = wp_json_encode( $result );
					}

					$json_error_message = $this->get_json_last_error();
					if ( $json_error_message ) {
						$json_error_obj = new WP_Error(
							'rest_encode_error',
							$json_error_message,
							array( 'status' => 500 )
						);
						$result         = $this->error_to_response( $json_error_obj );
						$result         = wp_json_encode( $result->data[0] );
					}

					echo esc_html( $result );

					return true;
				}
			}

			return false;

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR rest_pre_serve_request', array( 'error' => $e->getMessage() ) );

		}
	}

	/**
	 * Filter out invalid Variant Option Values
	 *
	 * @param mixed $var Variable.
	 */
	public function clerk_filter_null_attributes( $var ) {
		return ( null !== $var && false !== $var && '' !== $var );
	}

	/**
	 * Handle product endpoint
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return array|WP_REST_Response
	 */
	public function product_endpoint_callback( WP_REST_Request $request ) {
		$options = get_option( 'clerk_options' );

		try {

			if ( ! $this->validateRequest( $request ) ) {
				return $this->get_unathorized_response();
			}

			$limit   = $request->get_param( 'limit' ) ? $request->get_param( 'limit' ) : -1;
			$page    = ( $request->get_param( 'page' ) !== null ) ? $request->get_param( 'page' ) : 0;
			$orderby = $request->get_param( 'orderby' ) ? $request->get_param( 'orderby' ) : 'product_id';
			$order   = $request->get_param( 'order' ) ? $request->get_param( 'order' ) : 'ASC';

			$offset = ( $request->get_param( 'page' ) === 0 ) ? 0 : $page * $limit;

			if ( ! isset( $options['outofstock_products'] ) ) {
				$products = clerk_get_products(
					array(
						'limit'        => $limit,
						'page'         => $page,
						'orderby'      => $orderby,
						'order'        => $order,
						'status'       => array( 'publish' ),
						'stock_status' => 'instock',
						'paginate'     => true,
						'offset'       => $offset,
					)
				);
			} else {
				$products = clerk_get_products(
					array(
						'limit'    => $limit,
						'page'     => $page,
						'orderby'  => $orderby,
						'order'    => $order,
						'status'   => array( 'publish' ),
						'paginate' => true,
						'offset'   => $offset,
					)
				);
			}

			$final_products_array = array();

			foreach ( $products->products as $product ) {

				$stock_quantity = null;
				$product_array  = array();
				$categories     = wp_get_post_terms( $product->get_id(), 'product_cat' );

				$on_sale = $product->is_on_sale();

				if ( $product->is_type( 'variable' ) ) {
					/**
					 * Variable product sync fields
					 * Will sync the lowest price, and set the sale flag if that variant is on sale.
					 */
					$product_array['variant_images']      = array();
					$product_array['variant_prices']      = array();
					$product_array['variant_list_prices'] = array();
					$product_array['variant_skus']        = array();
					$product_array['variant_ids']         = array();
					$product_array['variant_options']     = array();
					$product_array['variant_stocks']      = array();
					$display_price                        = array();
					$regular_price                        = array();
					$stock_quantity                       = 0;

					$variations = $product->get_available_variations();

					foreach ( $variations as $variation ) {
						$variant_id   = $variation['variation_id'];
						$is_available = false;
						if ( array_key_exists( 'is_in_stock', $variation ) && array_key_exists( 'is_purchasable', $variation ) && array_key_exists( 'backorders_allowed', $variation ) ) {
							$is_available = ( $variation['is_in_stock'] && $variation['is_purchasable'] ) || ( $variation['backorders_allowed'] && $variation['is_purchasable'] ) ? true : false;
						}

						if ( ! isset( $options['outofstock_products'] ) ) {
							if ( ! $is_available ) {
								continue;
							}
						}

						$variation_obj   = new WC_Product_variation( $variation['variation_id'] );
						$stock_quantity += $variation_obj->get_stock_quantity();
						if ( isset( $variation['attributes'] ) ) {
							$options_array                      = array_values( $variation['attributes'] );
							$options_array                      = array_filter(
								$options_array,
								function ( $var ) {
									return ( 'boolean' !== gettype( $var ) && null !== $var && '' !== $var && 'Yes' !== $var && 'No' !== $var );
								}
							);
							$options_string                     = implode( ' ', $options_array );
							$product_array['variant_options'][] = $options_string;
						}

						$product_array['variant_images'][]      = $variation['image']['url'];
						$product_array['variant_skus'][]        = $variation['sku'];
						$product_array['variant_ids'][]         = $variation['variation_id'];
						$product_array['variant_stocks'][]      = ( null !== $variation_obj->get_stock_quantity() ) ? $variation_obj->get_stock_quantity() : 0;
						$product_array['variant_prices'][]      = $variation['display_price'];
						$product_array['variant_list_prices'][] = $variation['display_regular_price'];

						$display_price[ $variant_id ] = $variation['display_price'];
						$regular_price[ $variant_id ] = $variation['display_regular_price'];
					}

					if ( ! empty( $display_price ) ) {
						$lowest_display_price = array_keys( $display_price, min( $display_price ), true ); // Find the corresponding product ID.
						$price                = $display_price[ $lowest_display_price[0] ]; // Get the lowest price.
						$list_price           = $regular_price[ $lowest_display_price[0] ]; // Get the corresponding list price (regular price).
					}

					$price      = ( $price > 0 ) ? $price : $product->get_price();
					$list_price = ( $list_price > 0 ) ? $list_price : $product->get_regular_price();

					if ( $price === $list_price ) {
						$on_sale = false; // Remove the sale flag if the cheapest variant is not on sale.
					}
				}
				if ( $product->is_type( 'simple' ) || $product->is_type( 'grouped' ) ) {
					/**
					 * Default single / grouped product sync fields
					 */
					$price          = $product->get_price();
					$list_price     = $product->get_regular_price();
					$stock_quantity = $product->get_stock_quantity();
				}

				if ( $product->is_type( 'bundle' ) ) {
					$price          = $product->min_raw_price;
					$list_price     = $product->min_raw_regular_price;
					$bundled_items  = $product->get_bundled_items();
					$stock_quantity = $product->get_stock_quantity();
					if ( ! $price ) {
						$price = 0;
						foreach ( $bundled_items as $item ) {
							$price += $item->get_price();
						}
					}
					if ( ! $list_price ) {
						$list_price = 0;
						foreach ( $bundled_items as $item ) {
							$list_price += $item->get_regular_price();
						}
					}
				}

				$image_size_setting = isset( $options['data_sync_image_size'] ) ? $options['data_sync_image_size'] : 'medium';
				$product_image      = wp_get_attachment_image_src( $product->get_image_id(), $image_size_setting );
				if ( ! $product_image ) {
					if ( function_exists( 'wc_placeholder_img_src' ) ) {
						$product_image = wc_placeholder_img_src( $image_size_setting );
					} else {
						$product_image = '';
					}
				} else {
					$product_image = $product_image[0];
				}
				$product_array['id']             = $product->get_id();
				$product_array['name']           = $product->get_name();
				$product_array['description']    = get_post_field( 'post_content', $product->get_id() );
				$product_array['price']          = (float) $price;
				$product_array['list_price']     = (float) $list_price;
				$product_array['image']          = $product_image;
				$product_array['url']            = $product->get_permalink();
				$product_array['categories']     = wp_list_pluck( $categories, 'term_id' );
				$product_array['sku']            = $product->get_sku();
				$product_array['on_sale']        = $on_sale;
				$product_array['all_images']     = array();
				$product_array['type']           = $product->get_type();
				$product_array['created_at']     = strtotime( $product->get_date_created() );
				$product_array['stock']          = ( is_numeric( $stock_quantity ) ) ? $stock_quantity : 1;
				$product_array['managing_stock'] = $product->managing_stock();
				$product_array['backorders']     = $product->get_backorders();
				$product_array['stock_status']   = $product->get_stock_status();

				// Append additional fields.
				foreach ( $this->get_additional_fields() as $field ) {

					if ( '' === $field ) {
						continue;
					}

					if ( 'all_images' === $field ) {
						foreach ( get_intermediate_image_sizes() as $key => $image_size ) {
							if ( ! in_array( wp_get_attachment_image_src( $product->get_image_id(), $image_size )[0], $product_array['all_images'], true ) ) {
								array_push( $product_array['all_images'], wp_get_attachment_image_src( $product->get_image_id(), $image_size )[0] );
							}
						}
						continue;
					}

					if ( $product->get_attribute( $field ) || isset( $product->$field ) ) {
						if ( ! isset( $product_array[ $this->clerk_friendly_attributes( $field ) ] ) ) {
							$product_array[ str_replace( '-', '_', $this->clerk_friendly_attributes( $field ) ) ] = str_replace( ' ', '', explode( ',', $product->get_attribute( $field ) ) );
						}

						// 21-10-2021 KKY - Additional Fields for Configurable and Grouped Products - additional fields.

						if ( $product->is_type( 'variable' ) ) {
							$variations      = $product->get_available_variations();
							$child_atributes = array();
							foreach ( $variations as $v ) {
								$collectinfo   = '';
								$variation_obj = new WC_Product_variation( $v['variation_id'] );
								$atribute      = str_replace( ' ', '', explode( ',', $variation_obj->get_attribute( $field ) ) );

								if ( is_array( $atribute ) ) {
									$collectinfo = $atribute[0];
								} else {
									$collectinfo = $atribute;
								}

								if ( '' === $collectinfo && isset( $variation_obj->get_data()[ $field ] ) ) {
									$collectinfo = $variation_obj->get_data()[ $field ];
								}

								$child_atributes[] = $collectinfo;
							}

							$product_array[ 'child_' . $this->clerk_friendly_attributes( $field ) . 's' ] = $child_atributes;
						}

						if ( $product->is_type( 'grouped' ) ) {
							$child_product_ids = $product->get_children();
							$child_atributes   = array();
							foreach ( $child_product_ids as $child_id ) {
								$collectinfo  = '';
								$childproduct = wc_get_product( $child_id );
								$atribute     = str_replace( ' ', '', explode( ',', $childproduct->get_attribute( $field ) ) );

								if ( is_array( $atribute ) ) {
									$collectinfo = $atribute[0];
								} else {
									$collectinfo = $atribute;
								}

								if ( '' === $collectinfo && isset( $childproduct->$field ) ) {
									$collectinfo = $childproduct->$field;
								}

								$child_atributes[] = $collectinfo;
							}

							$product_array[ 'child_' . str_replace( '-', '_', $this->clerk_friendly_attributes( $field ) ) . 's' ] = $child_atributes;
						}

						// 21-10-2021 KKY - Additional Fields for Configurable and Grouped Products - additional fields.

					} elseif ( get_post_meta( $product->get_id(), $field, true ) ) {

						$product_array[ $this->clerk_friendly_attributes( $field ) ] = get_post_meta( $product->get_id(), $field, true );

						// 21-10-2021 KKY - Additional Fields for Configurable and Grouped Products - additional fields.

						if ( $product->is_type( 'variable' ) ) {
							$variations      = $product->get_available_variations();
							$child_atributes = array();

							foreach ( $variations as $v ) {
								$collectinfo   = '';
								$variation_obj = new WC_Product_variation( $v['variation_id'] );

								$atribute = get_post_meta( $variation_obj->get_id(), $field, true );

								if ( is_array( $atribute ) ) {
									$collectinfo = $atribute[0];
								} else {
									$collectinfo = $atribute;
								}

								if ( '' === $collectinfo && isset( $variation_obj->get_data()[ $field ] ) ) {
									$collectinfo = $variation_obj->get_data()[ $field ];
								}

								$child_atributes[] = $collectinfo;

							}

							$product_array[ 'child_' . str_replace( '-', '_', $this->clerk_friendly_attributes( $field ) ) . 's' ] = $child_atributes;
						}

						if ( $product->is_type( 'grouped' ) ) {
							$child_product_ids = $product->get_children();
							$child_atributes   = array();

							foreach ( $child_product_ids as $child_id ) {
								$collectinfo  = '';
								$childproduct = wc_get_product( $child_id );
								$atribute     = get_post_meta( $childproduct->get_id(), $field, true );

								if ( is_array( $atribute ) ) {
									$collectinfo = $atribute[0];
								} else {
									$collectinfo = $atribute;
								}

								if ( '' === $collectinfo && isset( $childproduct->$field ) ) {
									$collectinfo = $childproduct->$field;
								}

								$child_atributes[] = $collectinfo;

							}

							$product_array[ 'child_' . str_replace( '-', '_', $this->clerk_friendly_attributes( $field ) ) . 's' ] = $child_atributes;
						}

						// 21-10-2021 KKY - Additional Fields for Configurable and Grouped Products - additional fields.

					} elseif ( wp_get_post_terms( $product->get_id(), strtolower( $field ), array( 'fields' => 'names' ) ) ) {

						$attrubutefield = wp_get_post_terms( $product->get_id(), strtolower( $field ), array( 'fields' => 'names' ) );

						if ( ! property_exists( $attrubutefield, 'errors' ) ) {

							$product_array[ strtolower( $this->clerk_friendly_attributes( $field ) ) ] = $attrubutefield;

							// 21-10-2021 KKY - Additional Fields for Configurable and Grouped Products - additional fields.

							if ( $product->is_type( 'variable' ) ) {
								$variations      = $product->get_available_variations();
								$child_atributes = array();

								foreach ( $variations as $v ) {
									$collectinfo   = '';
									$variation_obj = new WC_Product_variation( $v['variation_id'] );

									$attrubutefield = wp_get_post_terms( $variation_obj->get_id(), strtolower( $field ), array( 'fields' => 'names' ) );

									if ( ! property_exists( $attrubutefield, 'errors' ) ) {

										$atribute = $attrubutefield;

										if ( is_array( $atribute ) ) {
											$collectinfo = $atribute[0];
										} else {
											$collectinfo = $atribute;
										}

										if ( '' === $collectinfo && isset( $variation_obj->get_data()[ $field ] ) ) {
											$collectinfo = $variation_obj->get_data()[ $field ];
										}

										$child_atributes[] = $collectinfo;

									}
								}

								$product_array[ 'child_' . strtolower( str_replace( '-', '_', $this->clerk_friendly_attributes( $field ) ) ) . 's' ] = $child_atributes;
							}

							if ( $product->is_type( 'grouped' ) ) {
								$child_product_ids = $product->get_children();
								$child_atributes   = array();
								foreach ( $child_product_ids as $child_id ) {
									$collectinfo    = '';
									$childproduct   = wc_get_product( $child_id );
									$attrubutefield = wp_get_post_terms( $childproduct->get_id(), strtolower( $field ), array( 'fields' => 'names' ) );
									$atribute       = $attrubutefield;

									if ( is_array( $atribute ) ) {
										$collectinfo = $atribute[0];
									} else {
										$collectinfo = $atribute;
									}

									if ( '' === $collectinfo && isset( $childproduct->$field ) ) {
										$collectinfo = $childproduct->$field;
									}

									$child_atributes[] = $collectinfo;
								}
								$product_array[ 'child_' . strtolower( str_replace( '-', '_', $this->clerk_friendly_attributes( $field ) ) ) . 's' ] = $child_atributes;
							}
						}
					}
				}

				$product_array = apply_filters( 'clerk_product_array', $product_array, $product );

				$final_products_array[] = $product_array;

			}

			$this->logger->log( 'Successfully generated JSON with ' . count( $final_products_array ) . ' products', array( 'error' => 'None' ) );

			header( 'User-Agent: ClerkExtensionBot WooCommerce/v' . get_bloginfo( 'version' ) . ' Clerk/v' . get_file_data( CLERK_PLUGIN_FILE, array( 'version' ), 'plugin' )[0] . ' PHP/v' . phpversion() );
			return $final_products_array;

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR product_endpoint_callback', array( 'error' => $e->getMessage() ) );

		}
	}


	/**
	 * Check URL for Danish Language Characters and handle-ize
	 *
	 * @param mixed $attribute Attribute.
	 */
	public function clerk_friendly_attributes( $attribute ) {
		$attribute = strtolower( $attribute );
		$attribute = str_replace( 'æ', 'ae', $attribute );
		$attribute = str_replace( 'ø', 'oe', $attribute );
		$attribute = str_replace( 'å', 'aa', $attribute );
		return rawurlencode( $attribute );
	}

	/**
	 * Call function for Post/Page content
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public function page_endpoint_callback( WP_REST_Request $request ) {
		$options = get_option( 'clerk_options' );

		try {

			if ( ! isset( $options['include_pages'] ) ) {
				return array();
			}

			if ( ! $this->validateRequest( $request ) ) {
				return $this->get_unathorized_response();
			}

			$post_types            = array( 'post', 'page' );
			$additional_types_list = array();
			if ( isset( $options['page_additional_types'] ) ) {
				$additional_types      = preg_replace( '/\s+/', '', $options['page_additional_types'] );
				$additional_types_list = explode( ',', $additional_types );
				$post_types            = array_values( array_unique( array_merge( $post_types, $additional_types_list ) ) );
			}

			$pages = get_posts(
				array(
					'post_status' => 'publish',
					'numberposts' => -1,
					'post_type'   => $post_types,
				)
			);

			$pages = apply_filters( 'clerk_get_posts', $pages );

			$final_post_array = array();

			foreach ( $pages as $page ) {

				if ( ! empty( $page->post_content ) ) {

					$page_additional_fields = explode( ',', $options['page_additional_fields'] );

					$url = get_permalink( $page->ID );
					$url = empty( $url ) ? $page->guid : $url;
					if ( empty( $url ) ) {
						continue;
					}

					// Changed type output to be a direct print since the page type is sometimes used by clients with custom types to categorise them in searches, etc. Useful to have in raw form.
					$page_draft = array(
						'id'    => $page->ID,
						'type'  => $page->post_type,
						'url'   => $url,
						'title' => $page->post_title,
						'text'  => $page->post_content,
					);

					if ( ! $this->ValidatePage( $page_draft ) ) {

						continue;

					}

					foreach ( $page_additional_fields as $page_additional_field ) {
						$page_additional_field = str_replace( ' ', '', $page_additional_field );
						if ( ! empty( $page_additional_field ) ) {

							$page_draft[ $page_additional_field ] = $page->{ $page_additional_field};

						}
					}

					$final_post_array[] = $page_draft;

				}
			}
			$pages = apply_filters( 'clerk_get_pages', get_pages() );

			$final_page_array = array();

			foreach ( $pages as $page ) {

				if ( ! empty( $page->post_content ) ) {

					$page_additional_fields = explode( ',', $options['page_additional_fields'] );

					$url = get_permalink( $page->ID );
					$url = empty( $url ) ? $page->guid : $url;
					if ( empty( $url ) ) {
						continue;
					}
					// Changed type output to be a direct print since the page type is sometimes used by clients with custom types to categorise them in searches, etc. Useful to have in raw form.
					$page_draft = array(
						'id'    => $page->ID,
						'type'  => $page->post_type,
						'url'   => $url,
						'title' => $page->post_title,
						'text'  => $page->post_content,
						'image' => get_the_post_thumbnail_url( $page->ID ),
					);

					if ( ! $this->ValidatePage( $page_draft ) ) {

						continue;

					}

					foreach ( $page_additional_fields as $page_additional_field ) {
						$page_additional_field = str_replace( ' ', '', $page_additional_field );
						if ( ! empty( $page_additional_field ) ) {

							$page_draft[ $page_additional_field ] = $page->{ $page_additional_field};

						}
					}

					$final_page_array[] = apply_filters( 'clerk_page_array', $page_draft, $page );

				}
			}
			// Merge Pages and Posts into 1 array.
			$final_content_array = array_merge( $final_page_array, $final_post_array );
			$this->logger->log( 'Successfully generated JSON with ' . count( $final_content_array ) . ' pages', array( 'error' => 'None' ) );
			header( 'User-Agent: ClerkExtensionBot WooCommerce/v' . get_bloginfo( 'version' ) . ' Clerk/v' . get_file_data( CLERK_PLUGIN_FILE, array( 'version' ), 'plugin' )[0] . ' PHP/v' . phpversion() );
			return $final_content_array;

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR page_endpoint_callback', array( 'error' => $e->getMessage() ) );

		}
	}

	/**
	 * Callback function for getting Configuration content
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public function getconfig_endpoint_callback( WP_REST_Request $request ) {
		$options = get_option( 'clerk_options' );

		try {

			if ( ! $this->validateRequest( $request ) ) {
				return $this->get_unathorized_response();
			}

			$default_setting_keys = array(
				'lang',
				'import_url',
				'customer_sync_enabled',
				'customer_sync_customer_fields',
				'realtime_updates',
				'include_pages',
				'page_additional_fields',
				'page_additional_types',
				'outofstock_products',
				'collect_emails',
				'collect_emails_signup_message',
				'collect_baskets',
				'additional_fields',
				'disable_order_synchronization',
				'data_sync_image_size',
				'livesearch_enabled',
				'livesearch_include_suggestions',
				'livesearch_suggestions',
				'livesearch_include_categories',
				'livesearch_categories',
				'livesearch_include_pages',
				'livesearch_pages',
				'livesearch_pages_type',
				'livesearch_dropdown_position',
				'livesearch_field_selector',
				'livesearch_form_selector',
				'livesearch_template',
				'search_enabled',
				'search_page',
				'search_include_categories',
				'search_categories',
				'search_include_pages',
				'search_pages',
				'search_pages_type',
				'search_template',
				'search_no_results_text',
				'search_load_more_button',
				'faceted_navigation_enabled',
				'faceted_navigation',
				'faceted_navigation_design',
				'powerstep_enabled',
				'powerstep_type',
				'powerstep_page',
				'powerstep_templates',
				'powerstep_excl_duplicates',
				'exit_intent_enabled',
				'exit_intent_template',
				'category_enabled',
				'category_content',
				'category_excl_duplicates',
				'product_enabled',
				'product_content',
				'product_excl_duplicates',
				'cart_enabled',
				'cart_content',
				'cart_excl_duplicates',
				'log_enabled',
				'log_to',
				'log_level',
			);

			$settings = array();

			foreach ( $options as $key => $value ) {

				// Do not include public & private key.
				if ( 'public_key' !== $key && 'private_key' !== $key ) {

					$settings[ $key ] = $value;

				}
			}

			foreach ( $default_setting_keys as $setting ) {
				if ( ! array_key_exists( $setting, $settings ) ) {
					$settings[ $setting ] = '0';
				}
			}

			$this->logger->log( 'Successfully generated category JSON with ' . count( $settings ) . ' settings', array( 'error' => 'None' ) );

			header( 'User-Agent: ClerkExtensionBot WooCommerce/v' . get_bloginfo( 'version' ) . ' Clerk/v' . get_file_data( CLERK_PLUGIN_FILE, array( 'version' ), 'plugin' )[0] . ' PHP/v' . phpversion() );
			return $settings;

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR getconfig_endpoint_callback', array( 'error' => $e->getMessage() ) );

		}
	}

	/**
	 * Callback function for setting Configuration content
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public function setconfig_endpoint_callback( WP_REST_Request $request ) {

		$options = get_option( 'clerk_options' ); // Array of all values of settings.

		try {

			if ( ! $this->validateRequest( $request ) ) {
				return $this->get_unathorized_response();
			}

			$body = $request->get_body(); // JSON blob string without public_key & private_key.

			$body_array   = array();
			$settings     = array();
			$update_array = array();

			// Array with all Clerk setttings (68) attributes without public_key & private_key.
			$settings_arguments = array(
				'lang',
				'import_url',
				'customer_sync_enabled',
				'customer_sync_customer_fields',
				'realtime_updates',
				'include_pages',
				'page_additional_fields',
				'page_additional_types',
				'outofstock_products',
				'collect_emails',
				'collect_emails_signup_message',
				'collect_baskets',
				'additional_fields',
				'disable_order_synchronization',
				'data_sync_image_size',
				'livesearch_enabled',
				'livesearch_include_suggestions',
				'livesearch_suggestions',
				'livesearch_include_categories',
				'livesearch_categories',
				'livesearch_include_pages',
				'livesearch_pages',
				'livesearch_pages_type',
				'livesearch_dropdown_position',
				'livesearch_field_selector',
				'livesearch_form_selector',
				'livesearch_template',
				'search_enabled',
				'search_page',
				'search_include_categories',
				'search_categories',
				'search_include_pages',
				'search_pages',
				'search_pages_type',
				'search_template',
				'search_no_results_text',
				'search_load_more_button',
				'faceted_navigation_enabled',
				'faceted_navigation',
				'faceted_navigation_design',
				'powerstep_enabled',
				'powerstep_type',
				'powerstep_page',
				'powerstep_templates',
				'powerstep_excl_duplicates',
				'exit_intent_enabled',
				'exit_intent_template',
				'category_enabled',
				'category_content',
				'category_excl_duplicates',
				'product_enabled',
				'product_content',
				'product_excl_duplicates',
				'cart_enabled',
				'cart_content',
				'cart_excl_duplicates',
				'log_enabled',
				'log_to',
				'log_level',
			);

			if ( $body ) {

				$body_array = json_decode( $body, true ); // Array of body request Raw input json data.

				// Check if the recent json decoded value is a JSON type.
				if ( json_last_error() === JSON_ERROR_NONE ) {

					// We will find the settings names that has not been send with the body request and add them to an array.
					// so we can send the origin name values to the database as well.

					$arr_diff = array_diff_key( $options, $body_array ); // Array: Compare the keys of two arrays, and return the differences.

					// Add the arguments not in the body to the settings array.
					foreach ( $arr_diff as $key => $value ) {

						if ( 'public_key' !== $key && 'private_key' !== $key ) {

							$settings[ $key ] = $value;

						}
					}

					// Add the arguments from the request body data to the settings array.
					foreach ( $body_array as $key => $value ) {

						// Check if attributes from body data is a Clerk setting attribute.
						if ( in_array( $key, $settings_arguments, true ) ) {

							$settings[ $key ] = $value;

						}
					}

					// Final updated settings array.
					$update_array = $settings;

					// Add public_key & private_key before updating options.
					$update_array['public_key']  = $options['public_key'];
					$update_array['private_key'] = $options['private_key'];

					// Update the database with the all new and old Clerk settings inclusive public_key & private_key.
					update_option( 'clerk_options', $update_array );

					$this->logger->log( 'Clerk options', array( '' => '' ) );

				}
			}

			$this->logger->log( 'Successfully generated category JSON with ' . count( $settings ) . ' settings', array( 'error' => 'None' ) );

			header( 'User-Agent: ClerkExtensionBot WooCommerce/v' . get_bloginfo( 'version' ) . ' Clerk/v' . get_file_data( CLERK_PLUGIN_FILE, array( 'version' ), 'plugin' )[0] . ' PHP/v' . phpversion() );

			// Return Clerk settings without public_key & private_key.
			return $settings;

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR setconfig_endpoint_callback', array( 'error' => $e->getMessage() ) );

		}
	}

	/**
	 * Callback function for Customer content
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public function customer_endpoint_callback( WP_REST_Request $request ) {
		$options = get_option( 'clerk_options' );

		try {

			$continue = array_key_exists( 'customer_sync_enabled', $options );

			if ( ! $continue ) {
				return array();
			}

			if ( ! $this->validateRequest( $request ) ) {
				return $this->get_unathorized_response();
			}

			$subscriber_query = new WP_User_Query( array( 'role' => 'Subscriber' ) );
			$customer_query   = new WP_User_Query( array( 'role' => 'Customer' ) );

			$subscribers = $subscriber_query->get_results();
			$customers   = $customer_query->get_results();

			$users = array_merge( $customers, $subscribers );

			$final_customer_array = array();

			if ( null !== $options['customer_sync_customer_fields'] && $options['customer_sync_customer_fields'] ) {

				$customer_additional_fields = explode( ',', str_replace( ' ', '', $options['customer_sync_customer_fields'] ) );

			} else {

				$customer_additional_fields = array();

			}

			foreach ( $users as $user ) {

				$_customer          = array();
				$_customer['name']  = $user->data->display_name;
				$_customer['id']    = $user->data->ID;
				$_customer['email'] = $user->data->user_email;

				foreach ( $customer_additional_fields as $customer_additional_field ) {

					if ( isset( $user->data->{ $customer_additional_field} ) ) {

						$_customer[ $customer_additional_field ] = $user->data->{ $customer_additional_field};

					}
				}

				$final_customer_array[] = apply_filters( 'clerk_customer_array', $_customer, $user );

			}

			$this->logger->log( 'Successfully generated JSON with ' . count( $final_customer_array ) . ' customers', array( 'error' => 'None' ) );
			header( 'User-Agent: ClerkExtensionBot WooCommerce/v' . get_bloginfo( 'version' ) . ' Clerk/v' . get_file_data( CLERK_PLUGIN_FILE, array( 'version' ), 'plugin' )[0] . ' PHP/v' . phpversion() );
			return $final_customer_array;

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR customer_endpoint_callback', array( 'error' => $e->getMessage() ) );

		}
	}

	/**
	 * Validate page content
	 *
	 * @param object $page Page.
	 */
	public function ValidatePage( $page ) {

		$required_fields = array( 'title', 'text', 'type', 'id' );
		foreach ( $page as $key => $content ) {

			if ( empty( $content ) && in_array( $key, $required_fields, true ) ) {

				return false;

			}
		}

		return true;

	}

	/**
	 * Validate request
	 *
	 * @param WP_REST_Request|void|null $request Request.
	 *
	 * @return bool
	 */
	private function validateRequest( $request ) {

		try {

			$options = get_option( 'clerk_options' );

			$clerk_plugin_version = get_file_data( CLERK_PLUGIN_FILE, array( 'version' ), 'plugin' )[0];
			$public_key           = '';
			$private_key          = '';

			$body = json_decode( $request->get_body(), true );
			if ( $body ) {
				if ( is_array( $body ) ) {
					$public_key  = array_key_exists( 'key', $body ) ? $body['key'] : '';
					$private_key = array_key_exists( 'private_key', $body ) ? $body['private_key'] : '';
				}
			} else {
				$this->logger->warn( 'Failed to validate API Keys', array( 'response' => false ) );
				return false;
			}

			if ( $this->timing_safe_equals( $options['public_key'], $public_key ) && $this->timing_safe_equals( $options['private_key'], $private_key ) ) {

				return true;
			}

			$this->logger->warn( 'Failed to validate API Keys', array( 'response' => false ) );

			return false;

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR validateRequest', array( 'error' => $e->getMessage() ) );

		}

	}

	/**
	 * Compare Request Token with Settings Token in time-safe manner
	 *
	 * @param string $safe Safe server value.
	 * @param string $user User provided value.
	 *
	 * @return boolean
	 */
	private function timing_safe_equals( $safe, $user ) {
		$safe_value_length = strlen( $safe );
		$user_value_length = strlen( $user );

		if ( $user_value_length !== $safe_value_length ) {
			return false;
		}

		$result = 0;

		for ( $i = 0; $i < $user_value_length; $i++ ) {
			$result |= ( ord( $safe[ $i ] ) ^ ord( $user[ $i ] ) );
		}

		// They are only identical strings if $result is exactly 0...
		return 0 === $result;
	}

	/**
	 * Get unathorized response
	 *
	 * @return WP_REST_Response
	 */
	private function get_unathorized_response() {

		try {

			$response = new WP_REST_Response(
				array(
					'error' => array(
						'code'    => 403,
						'message' => __( 'The supplied public or private key is invalid', 'clerk' ),
					),
				)
			);
			$response->set_status( 403 );

			$this->logger->warn( 'The supplied public or private key is invalid', array( 'status' => 403 ) );

			return $response;

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR get_unathorized_response', array( 'error' => $e->getMessage() ) );

		}

	}

	/**
	 * Get additional fields for product export
	 *
	 * @return array
	 */
	private function get_additional_fields() {

		try {

			$options = get_option( 'clerk_options' );

			$additional_fields = $options['additional_fields'];

			$fields = explode( ',', $additional_fields );

			foreach ( $fields as $key => $field ) {

				$fields[ $key ] = str_replace( ' ', '_', $field );

			}

			if ( ! is_array( $fields ) ) {
				return array();
			}

			return $fields;

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR get_additional_fields', array( 'error' => $e->getMessage() ) );

		}

	}

	/**
	 * Handle category endpoint
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return array|WP_REST_Response
	 */
	public function category_endpoint_callback( WP_REST_Request $request ) {

		try {

			if ( ! $this->validateRequest( $request ) ) {
				return $this->get_unathorized_response();
			}

			$limit   = $request->get_param( 'limit' ) ? $request->get_param( 'limit' ) : 0;
			$page    = $request->get_param( 'page' ) ? $request->get_param( 'page' ) - 1 : 0;
			$offset  = (int) $request->get_param( 'page' ) * $limit;
			$orderby = $request->get_param( 'orderby' ) ? $request->get_param( 'orderby' ) : 'date';
			$order   = $request->get_param( 'order' ) ? $request->get_param( 'order' ) : 'DESC';

			$args = array(
				'number'     => $limit,
				'orderby'    => $orderby,
				'order'      => $order,
				'offset'     => $offset,
				'hide_empty' => true,
			);

			$product_categories = get_terms( 'product_cat', $args );

			$categories = array();

			foreach ( $product_categories as $product_category ) {
				$category = array(
					'id'   => $product_category->term_id,
					'name' => $product_category->name,
					'url'  => get_term_link( $product_category ),
				);

				if ( $product_category->parent > 0 ) {
					$category['parent'] = $product_category->parent;
				}

				$subcategories             = get_term_children( $product_category->term_id, 'product_cat' );
				$category['subcategories'] = $subcategories;

				$category = apply_filters( 'clerk_category_array', $category, $product_category );

				$categories[] = $category;
			}

			$this->logger->log( 'Successfully generated category JSON with ' . count( $categories ) . ' categories', array( 'error' => 'None' ) );

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR category_endpoint_callback', array( 'error' => $e->getMessage() ) );

		}
		header( 'User-Agent: ClerkExtensionBot WooCommerce/v' . get_bloginfo( 'version' ) . ' Clerk/v' . get_file_data( CLERK_PLUGIN_FILE, array( 'version' ), 'plugin' )[0] . ' PHP/v' . phpversion() );
		return $categories;
	}

	/**
	 * Handle order endpoint
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return array|WP_REST_Response
	 */
	public function order_endpoint_callback( WP_REST_Request $request ) {

		try {

			if ( ! $this->validateRequest( $request ) ) {
				return $this->get_unathorized_response();
			}

			$options = get_option( 'clerk_options' );

			if ( isset( $options['disable_order_synchronization'] ) && null !== $options['disable_order_synchronization'] && $options['disable_order_synchronization'] ) {
				return array();
			}

			$limit      = $request->get_param( 'limit' ) ? $request->get_param( 'limit' ) : -1;
			$page       = $request->get_param( 'page' ) ? $request->get_param( 'page' ) + 1 : 1;
			$start_date = $request->get_param( 'start_date' ) ? $request->get_param( 'start_date' ) : 'today - 200 years';
			$end_date   = $request->get_param( 'end_date' ) ? $request->get_param( 'end_date' ) : 'today + 1 day';

			$orders = wc_get_orders(
				array(
					'limit'      => $limit,
					'offset'     => ( $page - 1 ) * $limit,
					'type'       => 'shop_order',
					'status'     => 'completed',
					'date_query' => array(
						'after'  => gmdate( 'Y-m-d', strtotime( $start_date ) ),
						'before' => gmdate( 'Y-m-d', strtotime( $end_date ) ),
					),
				)
			);

			$order_array = array();

			foreach ( $orders as $order ) {

				$order_items = array();
				$valid       = true;

				// Get order products.
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

				$order_object = array(
					'products' => $order_items,
					'time'     => strtotime( gmdate( 'Y-m-d H:i:s', $order->get_date_created()->getOffsetTimestamp() ) ),
					'class'    => get_class( $order ),
				);

				// Include email if defined.
				if ( null !== $options['collect_emails'] && $options['collect_emails'] ) {
					// billing_email is a protected property in 3.0.
					if ( clerk_check_version() ) {
						$order_object['email'] = $order->get_billing_email();
					} else {
						$order_object['email'] = $order->billing_email;
					}
				}

				// id is a protected property in 3.0.
				if ( clerk_check_version() ) {
					$order_object['id'] = $order->get_id();
				} else {
					$order_object['id'] = $order->id;
				}

				if ( $order->get_customer_id() > 0 ) {
					$order_object['customer'] = $order->get_customer_id();
				}

				if ( $valid ) {
					$order_object  = apply_filters( 'clerk_order_array', $order_object, $order );
					$order_array[] = $order_object;
				}
			}

			$this->logger->log( 'Successfully generated order JSON with ' . count( $order_array ) . ' orders', array( 'error' => 'None' ) );

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR order_endpoint_callback', array( 'error' => $e->getMessage() ) );

		}
		header( 'User-Agent: ClerkExtensionBot WooCommerce/v' . get_bloginfo( 'version' ) . ' Clerk/v' . get_file_data( CLERK_PLUGIN_FILE, array( 'version' ), 'plugin' )[0] . ' PHP/v' . phpversion() );
		return $order_array;
	}

	/**
	 * Handle version endpoint
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response
	 */
	public function version_endpoint_callback( WP_REST_Request $request ) {

		try {

			if ( ! $this->validateRequest( $request ) ) {
				return $this->get_unathorized_response();
			}

			$response = new WP_REST_Response(
				array(
					'platform'         => 'WooCommerce',
					'platform_version' => get_bloginfo( 'version' ),
					'clerk_version'    => get_file_data( CLERK_PLUGIN_FILE, array( 'version' ), 'plugin' )[0],
					'php_version'      => phpversion(),
				)
			);
			$response->header( 'User-Agent', 'ClerkExtensionBot WooCommerce/v' . get_bloginfo( 'version' ) . ' Clerk/v' . get_file_data( CLERK_PLUGIN_FILE, array( 'version' ), 'plugin' )[0] . ' PHP/v' . phpversion() );

			$this->logger->log( 'Successfully generated Version JSON', array( 'response' => $response ) );

			return $response;

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR version_endpoint_callback', array( 'error' => $e->getMessage() ) );

		}

	}

	/**
	 * Handle plugin endpoint
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response
	 */
	public function plugin_endpoint_callback( WP_REST_Request $request ) {

		try {

			if ( ! $this->validateRequest( $request ) ) {
				return $this->get_unathorized_response();
			}

			$plugins = get_plugins();

			$response = new WP_REST_Response( $plugins );
			$response->header( 'User-Agent', 'ClerkExtensionBot WooCommerce/v' . get_bloginfo( 'version' ) . ' Clerk/v' . get_file_data( CLERK_PLUGIN_FILE, array( 'version' ), 'plugin' )[0] . ' PHP/v' . phpversion() );

			$this->logger->log( 'Successfully generated Plugin JSON', array( 'response' => $response ) );

			return $response;

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR plugin_endpoint_callback', array( 'error' => $e->getMessage() ) );

		}

	}

}

new Clerk_Rest_Api();
