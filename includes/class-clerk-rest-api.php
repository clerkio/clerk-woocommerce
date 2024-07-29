<?php
/**
 * Plugin Name: Clerk
 * Plugin URI: https://clerk.io/
 * Description: Clerk.io Turns More Browsers Into Buyers
 * Version: 4.1.9
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
	 * Clerk Api Interface
	 *
	 * @var Clerk_Logger
	 */
	protected $logger;

	/**
	 * Optional language iso param
	 *
	 * @var string|null
	 */
	protected $lang_iso;

	/**
	 * Clerk_Rest_Api constructor.
	 */
	public function __construct() {
		$this->init_hooks();
		include_once __DIR__ . '/class-clerk-logger.php';
		include_once __DIR__ . '/clerk-multi-lang-helpers.php';
		include_once __DIR__ . '/class-clerk-api.php';
		if ( clerk_is_wpml_enabled() ) {
			do_action( 'wpml_multilingual_options', 'clerk_options' );
		}
		$this->logger = new Clerk_Logger();
		$this->api    = new Clerk_Api();
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

		// Page endpoint.
		register_rest_route(
			'clerk',
			'/page',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => array( $this, 'page_endpoint_callback' ),
				'permission_callback' => '__return_true',
			)
		);

		// Page RTU endpoint.
		register_rest_route(
			'clerk',
			'/page-rtu',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => array( $this, 'pagertu_endpoint_callback' ),
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

		// Rotatekey endpoint.
		register_rest_route(
			'clerk',
			'/rotatekey',
			array(
				'methods'             => array( 'POST' ),
				'callback'            => array( $this, 'rotatekey_endpoint_callback' ),
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
	 * @return bool|void
	 */
	public function rest_pre_serve_request( $served, $result, $request ) {

		try {

			// Determine if this is a clerk request.
			$attributes = $request->get_attributes();
			if ( ! $attributes ) {
				return false;
			}
			if ( is_array( $attributes['callback'] ) && $attributes['callback'][0] instanceof $this ) {
				// Embed links inside the request.
				if ( $request->get_param( '_embed' ) ) {
					$result = $this->response_to_data( $result, esc_url_raw( wp_unslash( $request->get_param( '_embed' ) ) ) );
				} else {
					return false;
				}

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

				echo wp_json_encode( json_decode( $result ) );

				return true;
			}

			return false;

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR rest_pre_serve_request', array( 'error' => $e->getMessage() ) );

		}
	}

	/**
	 * Handle product endpoint
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return array|WP_REST_Response
	 */
	public function product_endpoint_callback( WP_REST_Request $request ) {

		try {

			if ( ! $this->validate_request( $request ) ) {
				return $this->get_unathorized_response();
			}

			$options = clerk_get_options();

			$limit   = $request->get_param( 'limit' ) ? $request->get_param( 'limit' ) : -1;
			$page    = ( $request->get_param( 'page' ) !== null ) ? $request->get_param( 'page' ) : 0;
			$orderby = $request->get_param( 'orderby' ) ? $request->get_param( 'orderby' ) : 'name';
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

			$image_size_setting = isset( $options['data_sync_image_size'] ) ? $options['data_sync_image_size'] : 'medium';

			$product_tax_classes = WC_Tax::get_tax_classes();
			$product_tax_rates   = array();

			if ( $product_tax_classes ) {
				if ( ! in_array( '', $product_tax_classes, true ) ) {
					array_unshift( $product_tax_classes, '' );
				}

				foreach ( $product_tax_classes as $tax_class ) {
					$taxes = WC_Tax::get_rates_for_tax_class( $tax_class );
					if ( ! empty( $taxes ) ) {
						foreach ( $taxes as $key => $tax ) {
							$product_tax_rates[] = (array) $tax;
						}
					}
				}
			}

			foreach ( $products->products as $product ) {

				$taxonomies     = array( 'product_cat', 'product_brand', 'pwb-brand' );
				$categories     = array();
				$category_names = array();
				foreach ( $taxonomies as $taxonomy ) {
					if ( taxonomy_exists( $taxonomy ) ) {
						$taxa_term_array = wp_get_post_terms( $product->get_id(), $taxonomy );
						$categories      = array_merge( $categories, wp_list_pluck( $taxa_term_array, 'term_id' ) );
						$category_names  = array_merge( $category_names, wp_list_pluck( $taxa_term_array, 'name' ) );
					}
				}

				$stock_quantity      = null;
				$product_array       = array();
				$price               = 0;
				$list_price          = 0;
				$price_excl_tax      = 0;
				$list_price_excl_tax = 0;

				$on_sale = $product->is_on_sale();

				if ( $product->is_type( 'variable' ) ) {
					/**
					 * Variable product sync fields
					 * Will sync the lowest price, and set the sale flag if that variant is on sale.
					 */
					$product_array['variant_images']               = array();
					$product_array['variant_prices']               = array();
					$product_array['variant_list_prices']          = array();
					$product_array['variant_prices_excl_tax']      = array();
					$product_array['variant_list_prices_excl_tax'] = array();
					$product_array['variant_skus']                 = array();
					$product_array['variant_ids']                  = array();
					$product_array['variant_options']              = array();
					$product_array['variant_stocks']               = array();
					$display_price                                 = array();
					$regular_price                                 = array();
					$display_price_excl_tax                        = array();
					$regular_price_excl_tax                        = array();
					$stock_quantity                                = 0;

					$variations = $product->get_available_variations();

					foreach ( $variations as $variation ) {

						$variation = (array) $variation;

						if ( ! array_key_exists( 'variation_id', $variation ) ) {
							continue;
						}
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

						$variant_price      = $variation_obj->get_price();
						$variant_list_price = $variation_obj->get_regular_price();

						$variant_price_incl_tax      = wc_get_price_including_tax( $variation_obj, array( 'price' => $variant_price ) );
						$variant_list_price_incl_tax = wc_get_price_including_tax( $variation_obj, array( 'price' => $variant_list_price ) );

						$variant_price_excl_tax      = wc_get_price_excluding_tax( $variation_obj, array( 'price' => $variant_price ) );
						$variant_list_price_excl_tax = wc_get_price_excluding_tax( $variation_obj, array( 'price' => $variant_list_price ) );

						$variant_image = wp_get_attachment_image_src( $variation_obj->get_image_id(), $image_size_setting );
						if ( ! $variant_image ) {
							if ( function_exists( 'wc_placeholder_img_src' ) ) {
								$variant_image = wc_placeholder_img_src( $image_size_setting );
							} else {
								$variant_image = '';
							}
						} else {
							$variant_image = $variant_image[0];
						}

						$product_array['variant_images'][]               = $variant_image;
						$product_array['variant_skus'][]                 = $variation['sku'];
						$product_array['variant_ids'][]                  = $variation['variation_id'];
						$product_array['variant_stocks'][]               = ( null !== $variation_obj->get_stock_quantity() ) ? $variation_obj->get_stock_quantity() : 0;
						$product_array['variant_prices'][]               = $variant_price_incl_tax;
						$product_array['variant_list_prices'][]          = $variant_list_price_incl_tax;
						$product_array['variant_prices_excl_tax'][]      = $variant_price_excl_tax;
						$product_array['variant_list_prices_excl_tax'][] = $variant_list_price_excl_tax;

						$display_price[ $variant_id ]          = $variant_price_incl_tax;
						$regular_price[ $variant_id ]          = $variant_list_price_incl_tax;
						$display_price_excl_tax[ $variant_id ] = $variant_price_excl_tax;
						$regular_price_excl_tax[ $variant_id ] = $variant_list_price_excl_tax;
					}

					if ( ! empty( $display_price ) ) {
						$lowest_display_price = array_keys( $display_price, min( $display_price ), true ); // Find the corresponding product ID.
						$price                = $display_price[ $lowest_display_price[0] ]; // Get the lowest price.
						$list_price           = $regular_price[ $lowest_display_price[0] ]; // Get the corresponding list price (regular price).

						$lowest_display_price_excl_tax = array_keys( $display_price_excl_tax, min( $display_price_excl_tax ), true );
						$price_excl_tax                = $display_price_excl_tax[ $lowest_display_price_excl_tax[0] ];
						$list_price_excl_tax           = $regular_price_excl_tax[ $lowest_display_price_excl_tax[0] ];
					}

					$price      = ( $price > 0 ) ? $price : wc_get_price_including_tax( $product, array( 'price' => $product->get_price() ) );
					$list_price = ( $list_price > 0 ) ? $list_price : wc_get_price_including_tax( $product, array( 'price' => $product->get_regular_price() ) );

					$price_excl_tax      = ( $price_excl_tax > 0 ) ? $price_excl_tax : wc_get_price_excluding_tax( $product, array( 'price' => $product->get_price() ) );
					$list_price_excl_tax = ( $list_price_excl_tax > 0 ) ? $list_price_excl_tax : wc_get_price_excluding_tax( $product, array( 'price' => $product->get_regular_price() ) );

					if ( $price === $list_price ) {
						$on_sale = false; // Remove the sale flag if the cheapest variant is not on sale.
					}
				}
				if ( $product->is_type( 'simple' ) || $product->is_type( 'grouped' ) ) {
					/**
					 * Default single / grouped product sync fields
					 */
					$price      = wc_get_price_including_tax( $product, array( 'price' => $product->get_price() ) );
					$list_price = wc_get_price_including_tax( $product, array( 'price' => $product->get_regular_price() ) );

					$price_excl_tax      = wc_get_price_excluding_tax( $product, array( 'price' => $product->get_price() ) );
					$list_price_excl_tax = wc_get_price_excluding_tax( $product, array( 'price' => $product->get_regular_price() ) );

					if ( $product->is_type( 'grouped' ) ) {
						if ( $price === $list_price || $price === 0 ) {

							$tmp_children_prices         = array();
							$tmp_children_regular_prices = array();

							$child_ids = $product->get_children();

							foreach ( $child_ids as $key => $value ) {
								$child = wc_get_product( $value );
								if ( empty( $child ) ) {
									continue;
								}
								if ( ! is_object( $child ) ) {
									continue;
								}
								if ( ! method_exists( $child, 'get_regular_price' ) || ! method_exists( $child, 'get_price' ) ) {
									continue;
								}

								$normal_price = $child->get_price();
								$reg_price    = $child->get_regular_price();

								if ( is_numeric( $reg_price ) ) {
									$tmp_children_regular_prices[] = (float) $reg_price;
								}

								if ( is_numeric( $normal_price ) ) {
									$tmp_children_prices[] = (float) $normal_price;
								}
							}
							if ( ! empty( $tmp_children_regular_prices ) ) {
								$raw_regular_price = array_sum( $tmp_children_regular_prices );
								if ( is_numeric( $raw_regular_price ) ) {
									$list_price_excl_tax = wc_get_price_excluding_tax( $product, array( 'price' => $raw_regular_price ) );
									$list_price          = wc_get_price_including_tax( $product, array( 'price' => $raw_regular_price ) );
								}
							}
							if ( ! empty( $tmp_children_prices ) ) {
								$raw_price = array_sum( $tmp_children_prices );
								if ( is_numeric( $raw_price ) ) {
									$price_excl_tax = wc_get_price_excluding_tax( $product, array( 'price' => $raw_price ) );
									$price          = wc_get_price_including_tax( $product, array( 'price' => $raw_price ) );
								}
							}
						}
					}

					$stock_quantity = $product->get_stock_quantity();
				}

				if ( $product->is_type( 'bundle' ) ) {
					$price               = $product->get_min_raw_price() ? wc_get_price_including_tax( $product, array( 'price' => $product->get_min_raw_price() ) ) : null;
					$list_price          = $product->get_min_raw_regular_price() ? wc_get_price_including_tax( $product, array( 'price' => $product->get_min_raw_regular_price() ) ) : null;
					$price_excl_tax      = $product->get_min_raw_price() ? wc_get_price_excluding_tax( $product, array( 'price' => $product->get_min_raw_price() ) ) : null;
					$list_price_excl_tax = $product->get_min_raw_regular_price() ? wc_get_price_excluding_tax( $product, array( 'price' => $product->get_min_raw_regular_price() ) ) : null;
					$bundled_items       = $product->get_bundled_items();
					$stock_quantity      = $product->get_stock_quantity();
					if ( ! $price || ! $list_price ) {
						$price               = 0;
						$list_price          = 0;
						$price_excl_tax      = 0;
						$list_price_excl_tax = 0;
						foreach ( $bundled_items as $item ) {
							if ( method_exists( $item, 'is_taxable' ) ) {
								$price               += wc_get_price_including_tax( $item, array( 'price' => $item->get_price() ) );
								$list_price          += wc_get_price_including_tax( $item, array( 'price' => $item->get_regular_price() ) );
								$price_excl_tax      += wc_get_price_excluding_tax( $item, array( 'price' => $item->get_price() ) );
								$list_price_excl_tax += wc_get_price_excluding_tax( $item, array( 'price' => $item->get_regular_price() ) );
							} else {
								$price               += (float) $item->get_price();
								$list_price          += (float) $item->get_regular_price();
								$price_excl_tax      += (float) $item->get_price();
								$list_price_excl_tax += (float) $item->get_regular_price();
							}
						}
					}
				}

				$supported_product_types = array(
					'simple',
					'grouped',
					'bundle',
					'variable',
				);

				// Fallback for getting price from custom created product types.
				if ( ! in_array( $product->get_type(), $supported_product_types, true ) ) {
					if ( method_exists( $product, 'get_price' ) ) {
						if ( method_exists( $product, 'is_taxable' ) ) {
							$price          = wc_get_price_including_tax( $product, array( 'price' => $product->get_price() ) );
							$price_excl_tax = wc_get_price_excluding_tax( $product, array( 'price' => $product->get_price() ) );
						} else {
							$price          = $product->get_price();
							$price_excl_tax = $product->get_price();
						}
					}
					if ( method_exists( $product, 'get_regular_price' ) ) {
						if ( method_exists( $product, 'is_taxable' ) ) {
							$list_price          = wc_get_price_including_tax( $product, array( 'price' => $product->get_regular_price() ) );
							$list_price_excl_tax = wc_get_price_excluding_tax( $product, array( 'price' => $product->get_regular_price() ) );
						} else {
							$list_price          = $product->get_regular_price();
							$list_price_excl_tax = $product->get_regular_price();
						}
					}
				}

				$product_image = wp_get_attachment_image_src( $product->get_image_id(), $image_size_setting );
				if ( ! $product_image ) {
					if ( function_exists( 'wc_placeholder_img_src' ) ) {
						$product_image = wc_placeholder_img_src( $image_size_setting );
					} else {
						$product_image = '';
					}
				} else {
					$product_image = $product_image[0];
				}

				$product_tags_data = get_the_terms( $product->get_id(), 'product_tag' );
				$product_tags      = array();
				if ( ! empty( $product_tags_data ) && ! is_wp_error( $product_tags_data ) ) {
					foreach ( $product_tags_data as $tag ) {
						$product_tags[] = $tag->slug;
					}
				}

				$product_array['id']                  = $product->get_id();
				$product_array['name']                = $product->get_name();
				$product_array['description']         = get_post_field( 'post_content', $product->get_id() );
				$product_array['price']               = (float) $price;
				$product_array['list_price']          = (float) $list_price;
				$product_array['price_excl_tax']      = (float) $price_excl_tax;
				$product_array['list_price_excl_tax'] = (float) $list_price_excl_tax;
				$product_array['image']               = $product_image;
				$product_array['url']                 = $product->get_permalink();
				$product_array['categories']          = $categories;
				$product_array['category_names']      = $category_names;
				$product_array['sku']                 = $product->get_sku();
				$product_array['on_sale']             = $on_sale;
				$product_array['type']                = $product->get_type();
				$product_array['visibility']          = $product->get_catalog_visibility();
				$product_array['created_at']          = strtotime( $product->get_date_created() );
				$product_array['stock']               = ( is_numeric( $stock_quantity ) ) ? $stock_quantity : 1;
				$product_array['managing_stock']      = $product->managing_stock();
				$product_array['backorders']          = $product->get_backorders();
				$product_array['stock_status']        = $product->get_stock_status();
				$product_array['tags']                = $product_tags;

				if ( method_exists( $product, 'get_price_html' ) ) {
					$product_array['price_html'] = $product->get_price_html();
				}

				if ( method_exists( $product, 'get_average_rating' ) ) {
					$product_array['product_rating'] = $product->get_average_rating();
				}

				if ( method_exists( $product, 'get_rating_count' ) ) {
					$product_array['product_rating_count'] = $product->get_rating_count();
				}

				if ( method_exists( $product, 'get_review_count' ) ) {
					$product_array['product_review_count'] = $product->get_review_count();
				}

				$lang_info = apply_filters( 'wpml_post_language_details', null, $product->get_id() );
				if ( is_array( $lang_info ) && array_key_exists( 'language_code', $lang_info ) ) {
					$product_array['language_code'] = $lang_info['language_code'];
				}

				if ( ! empty( $product_tax_rates ) ) {
					foreach ( $product_tax_rates as $tax_rate ) {
						if ( $tax_rate['tax_rate_class'] === $product->get_tax_class() ) {
							$product_array['tax_rate'] = (float) $tax_rate['tax_rate'];
						}
					}
				}

				$product_array = $this->resolve_unit_measure( $product, $product_array );

				$additional_fields = $this->get_additional_fields();

				if ( in_array( 'short_description', $additional_fields, true ) ) {
					$product_array['short_description'] = $product->get_short_description();
				}

				if ( in_array( 'all_images', $additional_fields, true ) ) {
					$product_array['all_images'] = array();
					foreach ( get_intermediate_image_sizes() as $key => $image_size ) {
						$image_path = wp_get_attachment_image_src( $product->get_image_id(), $image_size );
						if ( ! is_wp_error( $image_path ) && is_array( $image_path ) && ! empty( $image_path ) ) {
							$image_path = $image_path[0];
							if ( ! in_array( $product_array['all_images'], $image_path, true ) ) {
								$product_array['all_images'][] = $image_path;
							}
						}
					}
				}

				if ( in_array( 'gallery_images', $additional_fields, true ) ) {
					$product_array['gallery_images'] = array();
					$product_image_ids               = $product->get_gallery_image_ids();
					if ( ! empty( $product_image_ids ) ) {
						foreach ( $product_image_ids as $product_img_id ) {
							$image_path = wp_get_attachment_url( $product_img_id );
							if ( ! is_wp_error( $image_path ) && $image_path ) {
								$product_array['gallery_images'][] = $image_path;
							}
						}
					}
				}

				$product_array = $this->query_custom_fields( $product, $this->get_additional_fields(), $product_array );

				$product_array = apply_filters( 'clerk_product_array', $product_array, $product );

				if ( ! empty( $product_array ) ) {
					$final_products_array[] = $product_array;
				}
			}

			$this->logger->log( 'Successfully generated JSON with ' . count( $final_products_array ) . ' products', array( 'error' => 'None' ) );

			header( 'User-Agent: ClerkExtensionBot WooCommerce/v' . get_bloginfo( 'version' ) . ' Clerk/v' . get_file_data( CLERK_PLUGIN_FILE, array( 'version' ), 'plugin' )[0] . ' PHP/v' . phpversion() );
			return $final_products_array;

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR product_endpoint_callback', array( 'error' => $e->getMessage() ) );

		}
	}

	/**
	 * Get Custom Fields for Product
	 *
	 * @param WC_Product $product Product Object.
	 * @param array      $fields Fields Array.
	 * @param array      $product_data Product Data Array.
	 */
	public function query_custom_fields( $product, $fields, $product_data ) {
		$product_type = $product->get_type();
		$fields       = array_values( array_filter( array_diff( $fields, array_keys( $product_data ) ) ) );

		foreach ( $fields as $field ) {
			$attribute_value = $this->resolve_attribute_product( $product, $field );
			if ( isset( $attribute_value ) ) {
				$product_data[ $this->clerk_friendly_attributes( $field ) ] = $this->format_attribute( $attribute_value, $field );
			}
		}

		if ( 'variable' === $product_type ) {
			$variations = $product->get_available_variations();
			foreach ( $variations as $variation ) {
				$variant = new WC_Product_variation( $variation['variation_id'] );
				foreach ( $fields as $field ) {
					$attribute_value = $this->format_attribute( $this->resolve_attribute_product( $variant, $field ), $field );
					if ( ! isset( $attribute_value ) || empty( $attribute_value ) ) {
						if ( ! array_key_exists( $field, $variation ) ) {
							continue;
						} else {
							$attribute_value = $this->format_attribute( $variation[ $field ], $field );
						}
					}
					$product_data = $this->flatten_attribute( $product_data, $field, $attribute_value );
				}
			}
		}
		if ( 'grouped' === $product_type ) {
			$child_product_ids = $product->get_children();
			foreach ( $child_product_ids as $child_id ) {
				$child = wc_get_product( $child_id );
				if ( empty( $child ) || ! is_object( $child ) ) {
					continue;
				}
				foreach ( $fields as $field ) {
					$attribute_value = $this->format_attribute( $this->resolve_attribute_product( $child, $field ), $field );
					if ( ! isset( $attribute_value ) || empty( $attribute_value ) ) {
						continue;
					}
					$product_data = $this->flatten_attribute( $product_data, $field, $attribute_value );
				}
			}
		}
		return $product_data;
	}

	/**
	 * Flatten attribute if arrey before appending
	 *
	 * @param array  $product_data Product object data.
	 * @param string $field Product attribute slug.
	 * @param mixed  $attribute_value Product attribute value.
	 * @return array
	 */
	public function flatten_attribute( $product_data, $field, $attribute_value ) {
		$child_key = 'child_' . $this->clerk_friendly_attributes( $field ) . 's';
		if ( ! isset( $product_data[ $child_key ] ) ) {
			$product_data[ $child_key ] = array();
		}
		if ( is_array( $attribute_value ) ) {
			$attribute_value            = array_values( $attribute_value );
			$product_data[ $child_key ] = array_merge( $product_data[ $child_key ], $attribute_value );
		} else {
			$product_data[ $child_key ][] = $attribute_value;
		}
		return $product_data;
	}

	/**
	 * Format Attribute Value
	 *
	 * @param mixed  $attribute_value Product Attribute Value.
	 * @param string $field Field Slug.
	 */
	public function format_attribute( $attribute_value, $field ) {
		if ( is_object( $attribute_value ) ) {
			$attribute_value = (array) $attribute_value;
		}
		if ( is_array( $attribute_value ) && count( $attribute_value ) === 1 ) {
			$attribute_value = $attribute_value[0];
		}
		if ( is_string( $attribute_value ) && ! in_array( $field, $this->get_additional_fields_raw(), true ) ) {
			$attribute_value = array_map( array( $this, 'trim_whitespace_in_attribute' ), explode( ',', $attribute_value ) );
		}
		if ( is_array( $attribute_value ) && count( $attribute_value ) === 1 ) {
			$attribute_value = $attribute_value[0];
		}
		return $attribute_value;
	}

	/**
	 * Get Product Unit Measure Data
	 *
	 * @param WC_Product|WC_Product_variation $product Product Object.
	 * @param array                           $product_data Product Data.
	 *
	 * @return array $product_data Product Data.
	 */
	public function resolve_unit_measure( $product, $product_data ) {
		try {

			$unit_price_data = get_post_meta( $product->get_id(), '_wc_price_calculator', true );
			$unit_type       = null;
			if ( ! empty( $unit_price_data ) ) {
				if ( array_key_exists( 'calculator_type', $unit_price_data ) ) {
					$unit_type = $unit_price_data['calculator_type'];
				}
				if ( $unit_type && array_key_exists( $unit_type, $unit_price_data ) ) {
					$product_data['unit']                  = $unit_price_data[ $unit_type ]['pricing']['unit'];
					$product_data['unit_label']            = $unit_price_data[ $unit_type ]['pricing']['label'];
					$product_data['unit_type']             = $unit_type;
					$product_data['unit_type_description'] = $unit_price_data[ $unit_type ][ $unit_type ]['label'];
					$product_data['unit_enabled']          = ( 'yes' === $unit_price_data[ $unit_type ]['pricing']['enabled'] ) ? true : false;
				}
			}
		} catch ( Exception $e ) {
				$this->logger->error( 'ERROR resolve_unit_measure', array( 'error' => $e->getMessage() ) );
		}
		return $product_data;
	}

	/**
	 * Get Attribute Value with Valid Method
	 *
	 * @param WC_Product|WC_Product_variation $product Product Object.
	 * @param string                          $field Field Slug.
	 *
	 * @return mixed Attribute Value.
	 */
	public function resolve_attribute_product( $product, $field ) {

		if ( $product->get_attribute( $field ) ) {
			return $product->get_attribute( $field );
		}
		if ( isset( $product->$field ) ) {
			return $product->$field;
		}
		if ( get_post_meta( $product->get_id(), $field, true ) ) {
			return get_post_meta( $product->get_id(), $field, true );
		}
		if ( function_exists( 'get_field' ) && null !== get_field( $field, $product->get_id() ) ) {
			return get_field( $field, $product->get_id() );
		}
		if ( ! is_wp_error( wp_get_post_terms( $product->get_id(), strtolower( $field ), array( 'fields' => 'names' ) ) ) ) {
			return wp_get_post_terms( $product->get_id(), strtolower( $field ), array( 'fields' => 'names' ) );
		}
		if ( isset( $product->get_data()[ $field ] ) ) {
			return $product->get_data()[ $field ];
		}
	}

	/**
	 * Check URL for Danish Language Characters and handle-ize
	 *
	 * @param mixed $attribute Attribute.
	 * @return string
	 */
	public function clerk_friendly_attributes( $attribute ) {
		$attribute = strtolower( $attribute );
		$attribute = str_replace( 'æ', 'ae', $attribute );
		$attribute = str_replace( 'ø', 'oe', $attribute );
		$attribute = str_replace( 'å', 'aa', $attribute );
		$attribute = str_replace( '-', '_', $attribute );
		return rawurlencode( $attribute );
	}

	/**
	 * Call function for Post/Page content for real time updates.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public function pagertu_endpoint_callback( WP_REST_Request $request ) {

		try {

			if ( ! $this->validate_request( $request, true ) ) {
				return $this->get_unathorized_response();
			}

			$options = clerk_get_options();

			if ( ! isset( $options['realtime_updates_pages'] ) ) {
				return array();
			}

			$pages_done = false;

			$post_types             = array( 'post', 'page' );
			$page_additional_fields = explode( ',', $options['page_additional_fields'] );

			if ( isset( $options['page_additional_types'] ) ) {
				$additional_types      = preg_replace( '/\s+/', '', $options['page_additional_types'] );
				$additional_types_list = explode( ',', $additional_types );
				$post_types            = array_values( array_unique( array_merge( $post_types, $additional_types_list ) ) );
			}

			$post_query_args = array(
				'post_status' => 'publish',
				'numberposts' => 25,
				'post_type'   => $post_types,
				'offset'      => 0,
			);

			$page_count = 0;

			while ( ! $pages_done ) {
				$pages       = get_posts( $post_query_args );
				$posts_array = array();
				if ( empty( $pages ) ) {
					$pages_done = true;
				}
				foreach ( $pages as $page ) {
					if ( empty( $page->post_content ) ) {
						continue;
					}
					$url = get_permalink( $page->ID ) ?? $page->guid;
					if ( empty( $url ) ) {
						continue;
					}

					$page_draft = array(
						'id'    => $page->ID,
						'type'  => $page->post_type,
						'url'   => $url,
						'title' => $page->post_title,
						'text'  => gettype( $page->post_content ) === 'string' ? wp_strip_all_tags( $page->post_content ) : '',
						'image' => get_the_post_thumbnail_url( $page->ID ),
					);

					if ( ! $this->validate_page( $page_draft ) ) {
						continue;
					}

					foreach ( $page_additional_fields as $page_additional_field ) {
						$page_additional_field = str_replace( ' ', '', $page_additional_field );
						if ( ! empty( $page_additional_field ) ) {
							$page_draft[ $page_additional_field ] = $page->{ $page_additional_field };
						}
					}

					++$page_count;
					$post_query_args['offset'] += 25;
					$posts_array[]              = $page_draft;
				}

				$this->api->add_posts( $posts_array );
			}

			$this->logger->log( 'Successfully synced ' . $page_count . ' pages', array( 'error' => 'None' ) );
			header( 'User-Agent: ClerkExtensionBot WooCommerce/v' . get_bloginfo( 'version' ) . ' Clerk/v' . get_file_data( CLERK_PLUGIN_FILE, array( 'version' ), 'plugin' )[0] . ' PHP/v' . phpversion() );

			return array(
				'page_count' => $page_count,
			);

		} catch ( Exception $e ) {
			$this->logger->error( 'ERROR pagertu_endpoint_callback', array( 'error' => $e->getMessage() ) );
		}
	}

	/**
	 * Call function for Post/Page content
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public function page_endpoint_callback( WP_REST_Request $request ) {

		try {

			if ( ! $this->validate_request( $request ) ) {
				return $this->get_unathorized_response();
			}

			$options = clerk_get_options();

			if ( ! isset( $options['include_pages'] ) ) {
				return array();
			}

			$limit  = $request->get_param( 'limit' ) ? $request->get_param( 'limit' ) : 100;
			$offset = ( $request->get_param( 'page' ) !== null ) ? ( $request->get_param( 'page' ) * $limit ) : 0;

			$post_types = array( 'post', 'page' );

			if ( isset( $options['page_additional_types'] ) ) {
				$additional_types      = preg_replace( '/\s+/', '', $options['page_additional_types'] );
				$additional_types_list = explode( ',', $additional_types );
				$post_types            = array_values( array_unique( array_merge( $post_types, $additional_types_list ) ) );
			}

			$post_query_args = array(
				'post_status' => 'publish',
				'numberposts' => $limit,
				'post_type'   => $post_types,
			);

			if ( $offset > 0 ) {
				$post_query_args['offset'] = $offset;
			}

			$pages = get_posts( $post_query_args );

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

					$page_draft = array(
						'id'    => $page->ID,
						'type'  => $page->post_type,
						'url'   => $url,
						'title' => $page->post_title,
						'text'  => gettype( $page->post_content ) === 'string' ? wp_strip_all_tags( $page->post_content ) : '',
						'image' => get_the_post_thumbnail_url( $page->ID ),
					);

					if ( ! $this->validate_page( $page_draft ) ) {

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
			$this->logger->log( 'Successfully generated JSON with ' . count( $final_post_array ) . ' pages', array( 'error' => 'None' ) );
			header( 'User-Agent: ClerkExtensionBot WooCommerce/v' . get_bloginfo( 'version' ) . ' Clerk/v' . get_file_data( CLERK_PLUGIN_FILE, array( 'version' ), 'plugin' )[0] . ' PHP/v' . phpversion() );
			return $final_post_array;

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

		try {

			if ( ! $this->validate_request( $request ) ) {
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
				'additional_fields_raw',
				'additional_fields_trim',
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
			$options  = clerk_get_options();

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

		try {

			if ( ! $this->validate_request( $request ) ) {
				return $this->get_unathorized_response();
			}

			$options = clerk_get_options();
			$body    = $request->get_body(); // JSON blob string without public_key & private_key.

			$settings = array();

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
				'additional_fields_raw',
				'additional_fields_trim',
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
					clerk_update_options( $update_array, $this->lang_iso );

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
	 * Callback function for setting Configuration content
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public function rotatekey_endpoint_callback( WP_REST_Request $request ) {

		try {

			if ( ! $this->validate_request( $request ) ) {
				return $this->get_unathorized_response();
			}

			$options = clerk_get_options();

			$body = $request->get_body(); // JSON blob string without public_key & private_key.

			$body_array   = array();
			$settings     = array();
			$update_array = array();

			// Array with all Clerk setttings (68) attributes without public_key & private_key.
			$settings_arguments = array(
				'private_key',
			);

			if ( $body ) {

				$body_array = json_decode( $body, true ); // Array of body request Raw input json data.

				// Check if the recent json decoded value is a JSON type.
				if ( json_last_error() === JSON_ERROR_NONE ) {

					$clerk_private_key       = $body_array['clerk_private_key'];
					$settings['private_key'] = $clerk_private_key;

					$update_array                = $options;
					$update_array['private_key'] = $clerk_private_key;

					// Update the database with the all new and old Clerk settings inclusive public_key & private_key.
					clerk_update_options( $update_array, $this->lang_iso );

					$this->logger->log( 'Clerk rotatekey', array( '' => '' ) );

				}
			}

			$this->logger->log( 'Successfully generated category JSON with ' . count( $settings ) . ' settings', array( 'error' => 'None' ) );

			header( 'User-Agent: ClerkExtensionBot WooCommerce/v' . get_bloginfo( 'version' ) . ' Clerk/v' . get_file_data( CLERK_PLUGIN_FILE, array( 'version' ), 'plugin' )[0] . ' PHP/v' . phpversion() );

			// Return Clerk settings without public_key & private_key.
			return $settings;

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR rotatekey_endpoint_callback', array( 'error' => $e->getMessage() ) );

		}
	}

	/**
	 * Callback function for Customer content
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public function customer_endpoint_callback( WP_REST_Request $request ) {

		try {

			if ( ! $this->validate_request( $request ) ) {
				return $this->get_unathorized_response();
			}
			$options  = clerk_get_options();
			$continue = array_key_exists( 'customer_sync_enabled', $options );

			if ( ! $continue ) {
				return array();
			}

			$subscriber_query = new WP_User_Query( array( 'role' => 'Subscriber' ) );
			$customer_query   = new WP_User_Query( array( 'role' => 'Customer' ) );

			$subscribers = $subscriber_query->get_results();
			$customers   = $customer_query->get_results();

			$users = array_merge( $customers, $subscribers );

			$final_customer_array = array();

			if ( isset( $options['customer_sync_customer_fields'] ) && $options['customer_sync_customer_fields'] ) {

				$customer_additional_fields = explode( ',', str_replace( ' ', '', $options['customer_sync_customer_fields'] ) );

			} else {

				$customer_additional_fields = array();

			}

			foreach ( $users as $user ) {

				$_customer_class = new WP_User( $user->ID );

				$customer_roles = $_customer_class->roles;
				if ( is_array( $customer_roles ) ) {
					$customer_roles = array_values( $customer_roles );
				}

				if ( ! $customer_roles ) {
					$customer_roles = array();
				}

				$_customer          = array();
				$_customer['name']  = $user->data->display_name;
				$_customer['id']    = $user->data->ID;
				$_customer['email'] = $user->data->user_email;
				$_customer['roles'] = $customer_roles;

				$user_meta = get_user_meta( $user->ID );

				foreach ( $customer_additional_fields as $customer_additional_field ) {
					if ( isset( $user_meta[ $customer_additional_field ] ) ) {
						$_customer[ $customer_additional_field ] = $user_meta[ $customer_additional_field ][0];
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
	 * @param array $page Page.
	 * @return bool
	 */
	public function validate_page( $page ) {

		$required_fields = array( 'title', 'text', 'type', 'id' );
		foreach ( $page as $key => $content ) {

			if ( empty( $content ) && in_array( $key, $required_fields, true ) ) {

				return false;

			}
		}

		return true;
	}


	/**
	 * Force context from lang param.
	 *
	 * @param mixed|array $query Query.
	 * @return void
	 */
	public function force_language_context( $query = null ) {
		if ( clerk_is_wpml_enabled() && $this->lang_iso && ! is_admin() ) {
			do_action( 'wpml_switch_language', $this->lang_iso );
			do_action( 'wpml_multilingual_options', 'clerk_options' );
		}
	}

	/**
	 * Validate request
	 *
	 * @param WP_REST_Request|void|null $request Request.
	 * @param bool                      $force_legacy_auth Legacy auth flag.
	 *
	 * @return bool
	 */
	private function validate_request( $request, $force_legacy_auth = false ) {

		try {

			$this->lang_iso = $request->get_param( 'lang' );
			$this->force_language_context( null );
			add_action( 'pre_get_posts', array( $this, 'force_language_context' ) );

			$options = clerk_get_options();

			if ( $force_legacy_auth && isset( $options['public_key'] ) && isset( $options['private_key'] ) ) {
				$pub_param  = $request->get_param( 'public_key' );
				$priv_param = $request->get_param( 'private_key' );
				if ( $this->timing_safe_equals( $options['public_key'], $pub_param ) && $this->timing_safe_equals( $options['private_key'], $priv_param ) ) {
					return true;
				}
				return false;
			}

			$use_legacy_auth = array_key_exists( 'legacy_auth_enabled', $options );

			$request_method_string = $request->get_method();

			if ( 'POST' !== $request_method_string ) {
				$this->logger->warn( 'Using Incorrect Request Method', array( 'response' => false ) );
				return false;
			}

			$public_key  = '';
			$private_key = '';

			$token = $this->get_header_token( $request );

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

			if ( ! $use_legacy_auth ) {
				if ( $this->timing_safe_equals( $options['public_key'], $public_key ) && $this->validate_jwt( $token ) ) {
					return true;
				}
			} else {
				if ( $this->timing_safe_equals( $options['public_key'], $public_key ) && $this->timing_safe_equals( $options['private_key'], $private_key ) ) {
					return true;
				}
			}

			$this->logger->warn( 'Failed to validate API Keys', array( 'response' => false ) );

			return false;

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR validate_request', array( 'error' => $e->getMessage() ) );

		}
	}

	/**
	 * Validate token from request.
	 *
	 * @param string|null $token_string Request.
	 * @return boolean
	 */
	private function validate_jwt( $token_string = null ) {

		if ( ! $token_string || ! is_string( $token_string ) ) {
			return false;
		}

		$options = clerk_get_options();

		$query_params = array(
			'token' => $token_string,
			'key'   => $options['public_key'],
		);

		$rsp_array = $this->api->verify_token( $query_params );

		if ( ! $rsp_array ) {
			return false;
		}

		try {
			$rsp_body = json_decode( $rsp_array['body'], true );

			if ( isset( $rsp_body['status'] ) && 'ok' === $rsp_body['status'] ) {
				return true;
			}

			return false;

		} catch ( \Exception $e ) {

			$this->logger->error( 'validate_jwt ERROR', array( 'error' => $e->getMessage() ) );

		}
	}

	/**
	 * Get Token from Request Header
	 *
	 * @param WP_REST_Request|void|null $request Request.
	 * @return string
	 * @throws Exception Request Exception.
	 */
	private function get_header_token( $request ) {
		try {

			$token       = '';
			$auth_header = $request->get_header( 'X-Clerk-Authorization' );

			if ( is_string( $auth_header ) ) {
				$prefix = explode( ' ', $auth_header )[0];
				if ( 'Bearer' !== $prefix ) {
					throw new Exception( 'Invalid token prefix' );
				}

				$token = count( explode( ' ', $auth_header ) ) > 1 ? explode( ' ', $auth_header )[1] : $token;
			}

			return $token;

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR validate_request', array( 'error' => $e->getMessage() ) );

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
	 * Get unauthorized response
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

			$options = clerk_get_options();

			$additional_fields = $options['additional_fields'];

			$fields = explode( ',', $additional_fields );

			foreach ( $fields as $key => $field ) {
				if ( ! empty( $field ) ) {
					$fields[ $key ] = str_replace( ' ', '_', $field );
				}
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
	 * Get additional fields for product export
	 *
	 * @return array
	 */
	private function get_additional_fields_raw() {

		try {

			$options = clerk_get_options();

			if ( ! is_array( $options ) ) {
				return array();
			}

			if ( array_key_exists( 'additional_fields_raw', $options ) ) {
				$additional_fields = $options['additional_fields_raw'];
				$fields            = explode( ',', $additional_fields );
			} else {
				$fields = array();
			}

			return $fields;

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR get_additional_fields_raw', array( 'error' => $e->getMessage() ) );

		}
	}

	/**
	 * Trim whitespace from product attributes
	 *
	 * @param string|void $attribute_value Attribute Value.
	 * @return string|void
	 */
	private function trim_whitespace_in_attribute( $attribute_value = null ) {

		try {

			$options = clerk_get_options();

			if ( ! is_array( $options ) ) {
				return;
			}

			if ( ! is_string( $attribute_value ) ) {
				return $attribute_value;
			}

			if ( isset( $options['additional_fields_trim'] ) ) {
				return trim( $attribute_value );
			} else {
				return str_replace( ' ', '', $attribute_value );
			}
		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR trim_whitespace_in_attribute', array( 'error' => $e->getMessage() ) );

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

		$categories = array();
		try {

			if ( ! $this->validate_request( $request ) ) {
				return $this->get_unathorized_response();
			}

			$limit   = $request->get_param( 'limit' ) ? $request->get_param( 'limit' ) : 0;
			$page    = $request->get_param( 'page' ) ? $request->get_param( 'page' ) - 1 : 0;
			$offset  = (int) $request->get_param( 'page' ) * $limit;
			$orderby = $request->get_param( 'orderby' ) ? $request->get_param( 'orderby' ) : 'date';
			$order   = $request->get_param( 'order' ) ? $request->get_param( 'order' ) : 'DESC';

			$taxonomies  = array( 'product_cat' );
			$plugin_taxa = array( 'product_brand', 'pwb-brand', 'berocket_brand' );

			foreach ( $plugin_taxa as $taxonomy ) {
				if ( taxonomy_exists( $taxonomy ) ) {
					$taxonomies[] = $taxonomy;
				}
			}

			$args = array(
				'number'     => $limit,
				'orderby'    => $orderby,
				'order'      => $order,
				'offset'     => $offset,
				'hide_empty' => true,
				'taxonomy'   => $taxonomies,
			);

			$product_categories = get_terms( $args );

			foreach ( $product_categories as $product_category ) {
				$category = array(
					'id'   => $product_category->term_id,
					'name' => $product_category->name,
					'url'  => get_term_link( $product_category ),
					'type' => $product_category->taxonomy,
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

		$order_array = array();
		try {

			if ( ! $this->validate_request( $request ) ) {
				return $this->get_unathorized_response();
			}

			$options = clerk_get_options();

			if ( isset( $options['disable_order_synchronization'] ) && $options['disable_order_synchronization'] ) {
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
				if ( isset( $options['collect_emails'] ) && $options['collect_emails'] ) {
					// billing_email is a protected property in 3.0.
					if ( clerk_check_version() ) {
						$order_object['email'] = $order->get_billing_email();
					} else {
						$order_object['email'] = $order->billing_email;
					}
				}

				// id is a protected property in 3.0.
				$order_object['id'] = $order->get_id();

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

			if ( ! $this->validate_request( $request ) ) {
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

			if ( ! $this->validate_request( $request ) ) {
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
