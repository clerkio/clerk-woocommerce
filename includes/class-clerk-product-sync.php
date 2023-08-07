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
 * Clerk_Product_Sync Class
 *
 * Clerk Module Core Class
 */
class Clerk_Product_Sync {


	/**
	 * Clerk Api Interface
	 *
	 * @var Clerk_Api
	 */
	protected $api;

	/**
	 * Error and Warning Logger
	 *
	 * @var $logger Clerk_Logger
	 */
	protected $logger;

	/**
	 * Clerk_Product_Sync constructor.
	 */
	public function __construct() {
		$this->includes();
		$this->init_hooks();
		$this->logger = new Clerk_Logger();
		$this->api    = new Clerk_Api();
	}

	/**
	 * Clerk_Product_Sync Includes.
	 */
	private function includes() {
		include_once __DIR__ . '/class-clerk-api.php';
		include_once __DIR__ . '/class-clerk-logger.php';
	}

	/**
	 * Init hooks
	 */
	private function init_hooks() {

		add_action( 'woocommerce_new_product', array( $this, 'save_product' ), 100, 3 );
		// This hook will run before the price is updated if there is a module modifying the price via a hook.
		// save_post with a high enough prio defer score.
		// add_action( 'woocommerce_update_product', array( $this, 'save_product' ), 1000, 3 ); .
		add_action( 'save_post', array( $this, 'pre_save_post' ), 1000, 3 );
		add_action( 'woocommerce_product_import_inserted_product_object', array( $this, 'pre_save_product' ), 10, 3 );
		add_action( 'before_delete_post', array( $this, 'remove_product' ) );
	}

	/**
	 * Update Product from Import
	 *
	 * @param object|void $product Product Object.
	 * @param array|mixed $data Meta data.
	 */
	public function pre_save_product( $product = null, $data = null ) {
		try {
			if ( $product ) {
				if ( is_a( $product, 'WC_Product' ) ) {
					$product_id = $product->get_id();
					$this->save_product( $product_id );
				}
			}
		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR pre_save_product', array( 'error' => $e->getMessage() ) );
		}
	}
	/**
	 * Update Product from Import
	 *
	 * @param int|void     $post_id Product Id.
	 * @param WP_Post|void $post Post Object.
	 * @param bool|void    $update Whether an existing post is being updated.
	 */
	public function pre_save_post( $post_id = null, $post = null, $update = null ) {
		try {
			if ( $post_id ) {
				$product = wc_get_product( $post_id );
				if ( is_a( $product, 'WC_Product' ) ) {
					$this->save_product( $post_id );
				}
			}
		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR pre_save_post', array( 'error' => $e->getMessage() ) );
		}
	}
	/**
	 * Update Product
	 *
	 * @param integer $product_id Product ID.
	 */
	public function save_product( $product_id = null ) {
		$options = get_option( 'clerk_options' );

		if ( ! is_array( $options ) ) {
			return;
		}

		try {

			if ( isset( $options ) ) {
				if ( ! array_key_exists( 'realtime_updates', $options ) ) {
					return;
				}
			}

			if ( is_int( $product_id ) ) {
				$product = wc_get_product( $product_id );
				if ( ! is_a( $product, 'WC_Product' ) ) {
					return;
				}
			} else {
				return;
			}

			if ( clerk_check_version() ) {

				// Don't send variations when parent is not published.
				if ( $product->is_type( 'variation' ) ) {
					$parent = wc_get_product( $product->get_parent_id() );

					if ( ! $parent ) {
						return;
					}

					if ( $parent->get_status() !== 'publish' ) {
						$this->remove_product( $product->get_id() );
						return;
					}
				}

				if ( $product->get_status() === 'publish' ) {
					// Send product to Clerk.
					$this->add_product( $product );

					// check all groups for this product *sigh*.
					$grouped_products = wc_get_products(
						array(
							'limit' => -1,
							'type'  => 'grouped',
						)
					);
					foreach ( $grouped_products as $grouped_product ) {
						$childrenids = $grouped_product->get_children();
						foreach ( $childrenids as $childid ) {
							if ( $product->get_id() === $childid ) {
								$this->add_product( $grouped_product );
							}
						}
					}
				} else {
					// Remove product.
					$this->remove_product( $product->get_id() );
				}
			} else {
				// Fix for WooCommerce 2.6.
				if ( 'publish' === $product->post->status ) {
					// Send product to Clerk.
					$this->add_product( $product );

					// check all groups for this product *sigh*.
					$grouped_products = wc_get_products(
						array(
							'limit' => -1,
							'type'  => 'grouped',
						)
					);
					foreach ( $grouped_products as $grouped_product ) {
						$childrenids = $grouped_product->get_children();
						foreach ( $childrenids as $childid ) {
							if ( $product->get_id() === $childid ) {
								$this->add_product( $grouped_product );
							}
						}
					}
				} elseif ( 'draft' !== $product->post->status ) {
					// Remove product.
					$this->remove_product( $product->get_id() );
				}
			}
		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR save_product', array( 'error' => $e->getMessage() ) );
		}
	}

	/**
	 * Remove product from Clerk
	 *
	 * @param integer $post_id Post Id.
	 */
	public function remove_product( $post_id ) {
		try {
			$options = get_option( 'clerk_options' );

			if ( ! is_array( $options ) ) {
				return;
			}

			if ( 1 !== (int) $options['realtime_updates'] ) {
				return;
			}
			// Remove product from Clerk.
			$this->api->remove_product( $post_id );
		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR remove_product', array( 'error' => $e->getMessage() ) );
		}
	}

	/**
	 * Add product in Clerk
	 *
	 * @param WC_Product $product Product Object.
	 */
	private function add_product( WC_Product $product ) {
		$product_array = array();

		try {
			$options = get_option( 'clerk_options' );

			if ( ! is_array( $options ) ) {
				return;
			}

			if ( 1 !== (int) $options['realtime_updates'] ) {
				return;
			}

			$categories = wp_get_post_terms( $product->get_id(), 'product_cat' );

			$on_sale             = $product->is_on_sale();
			$price               = 0;
			$list_price          = 0;
			$price_excl_tax      = 0;
			$list_price_excl_tax = 0;

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
				$variations                                    = $product->get_available_variations( 'objects' );
				$stock_quantity                                = 0;
				$display_price                                 = array();
				$regular_price                                 = array();
				$display_price_excl_tax                        = array();
				$regular_price_excl_tax                        = array();

				foreach ( $variations as $variation ) {

					$variation = (array) $variation;

					$is_available = false;
					if ( array_key_exists( 'is_in_stock', $variation ) && array_key_exists( 'is_purchasable', $variation ) && array_key_exists( 'backorders_allowed', $variation ) ) {
						$is_available = ( $variation['is_in_stock'] && $variation['is_purchasable'] ) || ( $variation['backorders_allowed'] && $variation['is_purchasable'] ) ? true : false;
					}

					if ( ! isset( $options['outofstock_products'] ) ) {
						if ( ! $is_available ) {
							continue;
						}
					}

					if ( ! array_key_exists( 'variation_id', $variation ) ) {
						continue;
					}

					$variation       = new WC_Product_variation( $variation['variation_id'] );
					$stock_quantity += $variation->get_stock_quantity();

					if ( ! empty( $variation->get_attributes() ) ) {
						$options_array                      = array_values( $variation->get_attributes() );
						$options_array                      = array_filter(
							$options_array,
							function ( $var ) {
								return ( 'boolean' !== gettype( $var ) && null !== $var && '' !== $var && 'Yes' !== $var && 'No' !== $var );
							}
						);
						$options_string                     = implode( ' ', $options_array );
						$product_array['variant_options'][] = $options_string;
					}

					$variant_id = $variation->get_id();

					$variant_price      = $variation->get_price();
					$variant_list_price = $variation->get_regular_price();

					$variant_price_incl_tax      = wc_get_price_including_tax( $variation, array( 'price' => $variant_price ) );
					$variant_list_price_incl_tax = wc_get_price_including_tax( $variation, array( 'price' => $variant_list_price ) );

					$variant_price_excl_tax      = wc_get_price_excluding_tax( $variation, array( 'price' => $variant_price ) );
					$variant_list_price_excl_tax = wc_get_price_excluding_tax( $variation, array( 'price' => $variant_list_price ) );

					$product_array['variant_images'][]               = wp_get_attachment_image_url( $variation->get_image_id() );
					$product_array['variant_skus'][]                 = $variation->get_sku();
					$product_array['variant_ids'][]                  = $variant_id;
					$product_array['variant_stocks'][]               = ( null !== $variation->get_stock_quantity() ) ? $variation->get_stock_quantity() : 0;
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
				 * Default single product sync fields
				 */
				$price      = wc_get_price_including_tax( $product, array( 'price' => $product->get_price() ) );
				$list_price = wc_get_price_including_tax( $product, array( 'price' => $product->get_regular_price() ) );

				$price_excl_tax      = wc_get_price_excluding_tax( $product, array( 'price' => $product->get_price() ) );
				$list_price_excl_tax = wc_get_price_excluding_tax( $product, array( 'price' => $product->get_regular_price() ) );

				$stock_quantity = $product->get_stock_quantity();
			}

			if ( $product->is_type( 'bundle' ) ) {
				$price               = $product->min_raw_price ? wc_get_price_including_tax( $product, array( 'price' => $product->min_raw_price ) ) : null;
				$list_price          = $product->min_raw_regular_price ? wc_get_price_including_tax( $product, array( 'price' => $product->min_raw_regular_price ) ) : null;
				$price_excl_tax      = $product->min_raw_price ? wc_get_price_excluding_tax( $product, array( 'price' => $product->min_raw_price ) ) : null;
				$list_price_excl_tax = $product->min_raw_regular_price ? wc_get_price_excluding_tax( $product, array( 'price' => $product->min_raw_regular_price ) ) : null;
				$bundled_items       = $product->get_bundled_items();
				$stock_quantity      = $product->get_stock_quantity();
				if ( ! $price || ! $list_price ) {
					$price               = 0;
					$list_price          = 0;
					$price_excl_tax      = 0;
					$list_price_excl_tax = 0;
					foreach ( $bundled_items as $item ) {
						$price               += wc_get_price_including_tax( $item, array( 'price' => $item->get_price() ) );
						$list_price          += wc_get_price_including_tax( $item, array( 'price' => $item->get_regular_price() ) );
						$price_excl_tax      += wc_get_price_excluding_tax( $item, array( 'price' => $item->get_price() ) );
						$list_price_excl_tax += wc_get_price_excluding_tax( $item, array( 'price' => $item->get_regular_price() ) );
					}
				}
			}

			$supported_product_types = array(
				'simple',
				'grouped',
				'bundle',
				'variable',
			);

			// Use default method for getting price if type is custom.

			if ( ! in_array( $product->get_type(), $supported_product_types, true ) ) {
				if ( method_exists( $product, 'get_price' ) ) {
					$price          = wc_get_price_including_tax( $product, array( 'price' => $product->get_price() ) );
					$price_excl_tax = wc_get_price_excluding_tax( $product, array( 'price' => $product->get_price() ) );
				}
				if ( method_exists( $product, 'get_regular_price' ) ) {
					$list_price          = wc_get_price_including_tax( $product, array( 'price' => $product->get_regular_price() ) );
					$list_price_excl_tax = wc_get_price_excluding_tax( $product, array( 'price' => $product->get_regular_price() ) );
				}
			}

			if ( ! isset( $options['outofstock_products'] ) ) {
				if ( $product->get_stock_status() !== 'instock' ) {
					return;
				}
			}

			$image_size_setting = isset( $options['data_sync_image_size'] ) ? $options['data_sync_image_size'] : 'medium';

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

			$product_array['id']                  = $product->get_id();
			$product_array['name']                = $product->get_name();
			$product_array['description']         = get_post_field( 'post_content', $product->get_id() );
			$product_array['price']               = (float) $price;
			$product_array['list_price']          = (float) $list_price;
			$product_array['price_excl_tax']      = (float) $price_excl_tax;
			$product_array['list_price_excl_tax'] = (float) $list_price_excl_tax;
			$product_array['image']               = $product_image;
			$product_array['url']                 = $product->get_permalink();
			$product_array['categories']          = wp_list_pluck( $categories, 'term_id' );
			$product_array['sku']                 = $product->get_sku();
			$product_array['on_sale']             = $on_sale;
			$product_array['type']                = $product->get_type();
			$product_array['visibility']          = $product->get_catalog_visibility();
			$product_array['created_at']          = strtotime( $product->get_date_created() );
			$product_array['all_images']          = array();
			$product_array['stock']               = ( null !== $stock_quantity ) ? $stock_quantity : 1;
			$product_array['managing_stock']      = $product->managing_stock();
			$product_array['backorders']          = $product->get_backorders();
			$product_array['stock_status']        = $product->get_stock_status();

			if ( ! empty( $product->get_stock_quantity() ) ) {

				$product_array['stock'] = ( null !== $product->get_stock_quantity() ) ? $product->get_stock_quantity() : 1;
			} elseif ( isset( $stock_quantity ) ) {

				$product_array['stock'] = $stock_quantity;
			}

			$exempted_fields = (array) $this->get_additional_fields_raw();

			// Append additional fields.
			foreach ( $this->get_additional_fields() as $field ) {

				if ( '' === $field ) {
					continue;
				}

				if ( 'short_description' === $field ) {
					$product_array['short_description'] = $product->get_short_description();
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

						if ( ! in_array( $field, $exempted_fields, true ) ) {
							$product_attribute_split = explode( ',', $product->get_attribute( $field ) );
							$product_array[ $this->clerk_friendly_attributes( $field ) ] = array_map( array( $this, 'trim_whitespace_in_attribute' ), $product_attribute_split );
						} else {
							$product_array[ $this->clerk_friendly_attributes( $field ) ] = $product->get_attribute( $field );
						}
					}

					// 21-10-2021 KKY - Additional Fields for Configurable and Grouped Products - additional fields.

					if ( $product->is_type( 'variable' ) ) {
						$variations       = $product->get_available_variations();
						$child_attributes = array();

						foreach ( $variations as $v ) {
							$collectinfo   = '';
							$variation_obj = new WC_Product_variation( $v['variation_id'] );

							if ( ! in_array( $field, $exempted_fields, true ) ) {
								$atribute_split = explode( ',', $variation_obj->get_attribute( $field ) );
								$attribute = array_map( array( $this, 'trim_whitespace_in_attribute' ), $atribute_split );
							} else {
								$attribute = $variation_obj->get_attribute( $field );
							}

							if ( is_array( $attribute ) ) {
								$collectinfo = $attribute[0];
							} else {
								$collectinfo = $attribute;
							}

							if ( '' === $collectinfo && isset( $variation_obj->get_data()[ $field ] ) ) {
								$collectinfo = $variation_obj->get_data()[ $field ];
							}

							$child_attributes[] = $collectinfo;
						}

						$product_array[ 'child_' . $this->clerk_friendly_attributes( $field ) . 's' ] = $child_attributes;
					}

					if ( $product->is_type( 'grouped' ) ) {
						$child_product_ids = $product->get_children();
						$child_attributes  = array();

						foreach ( $child_product_ids as $child_id ) {
							$collectinfo  = '';
							$childproduct = wc_get_product( $child_id );

							if ( ! in_array( $field, $exempted_fields, true ) ) {
								$atribute_split = explode( ',', $childproduct->get_attribute( $field ) );
								$attribute = array_map( array( $this, 'trim_whitespace_in_attribute' ), $atribute_split );
							} else {
								$attribute = $childproduct->get_attribute( $field );
							}

							if ( is_array( $attribute ) ) {
								$collectinfo = $attribute[0];
							} else {
								$collectinfo = $attribute;
							}

							if ( '' === $collectinfo && isset( $childproduct->$field ) ) {
								$collectinfo = $childproduct->$field;
							}

							$child_attributes[] = $collectinfo;
						}

						$product_array[ 'child_' . $this->clerk_friendly_attributes( $field ) . 's' ] = $child_attributes;
					}

					// 21-10-2021 KKY - Additional Fields for Configurable and Grouped Products - additional fields.

				} elseif ( get_post_meta( $product->get_id(), $field, true ) ) {

					$product_array[ str_replace( '-', '_', $this->clerk_friendly_attributes( $field ) ) ] = get_post_meta( $product->get_id(), $field, true );

					// 21-10-2021 KKY - Additional Fields for Configurable and Grouped Products - additional fields.

					if ( $product->is_type( 'variable' ) ) {
						$variations       = $product->get_available_variations( 'objects' );
						$child_attributes = array();
						foreach ( $variations as $variation ) {
							$collectinfo = '';
							$attribute   = get_post_meta( $variation->get_id(), $field, true );

							if ( is_array( $attribute ) ) {
								$collectinfo = $attribute[0];
							} else {
								$collectinfo = $attribute;
							}

							if ( '' === $collectinfo && isset( $variation->get_data()[ $field ] ) ) {
								$collectinfo = $variation->get_data()[ $field ];
							}

							$child_attributes[] = $collectinfo;
						}

						$product_array[ 'child_' . $this->clerk_friendly_attributes( $field ) . 's' ] = $child_attributes;
					}

					if ( $product->is_type( 'grouped' ) ) {
						$child_product_ids = $product->get_children();
						$child_attributes  = array();

						foreach ( $child_product_ids as $child_id ) {
							$collectinfo  = '';
							$childproduct = wc_get_product( $child_id );

							$attribute = get_post_meta( $childproduct->get_id(), $field, true );

							if ( is_array( $attribute ) ) {
								$collectinfo = $attribute[0];
							} else {
								$collectinfo = $attribute;
							}

							if ( '' === $collectinfo && isset( $childproduct->$field ) ) {
								$collectinfo = $childproduct->$field;
							}

							$child_attributes[] = $collectinfo;
						}

						$product_array[ 'child_' . $this->clerk_friendly_attributes( $field ) . 's' ] = $child_attributes;
					}

					// 21-10-2021 KKY - Additional Fields for Configurable and Grouped Products - additional fields.

				} elseif ( wp_get_post_terms( $product->get_id(), strtolower( $field ), array( 'fields' => 'names' ) ) ) {

					$attribute_field = wp_get_post_terms( $product->get_id(), strtolower( $field ), array( 'fields' => 'names' ) );

					if ( is_object( $attribute_field ) ) {
						$attribute_field = (array) $attribute_field;
					}

					if ( ! array_key_exists( 'errors', $attribute_field ) ) {

						if ( is_array( $attribute_field ) ) {
							$attribute_field = array_values( $attribute_field );
						}

						$product_array[ strtolower( $this->clerk_friendly_attributes( $field ) ) ] = $attribute_field;

						// 21-10-2021 KKY - Additional Fields for Configurable and Grouped Products - additional fields.

						if ( $product->is_type( 'variable' ) ) {
							$variations       = $product->get_available_variations( 'objects' );
							$child_attributes = array();

							foreach ( $variations as $variation ) {
								$collectinfo = '';

								$attribute_field = wp_get_post_terms( $variation->get_id(), strtolower( $field ), array( 'fields' => 'names' ) );

								if ( is_object( $attribute_field ) ) {
									$attribute_field = (array) $attribute_field;
								}

								if ( ! array_key_exists( 'errors', $attribute_field ) ) {

									$attribute = $attribute_field;

									if ( is_array( $atribute ) && count( $atribute ) > 0 ) {
										$collectinfo = $attribute[0];
									} else {
										$collectinfo = $attribute;
									}

									if ( '' === $collectinfo && isset( $variation->get_data()[ $field ] ) ) {
										$collectinfo = $variation->get_data()[ $field ];
									}

									if ( $$collectinfo ) {
										$child_attributes[] = $collectinfo;
									}
								}
							}
							if ( ! empty( $child_atributes ) ) {
								$product_array[ 'child_' . strtolower( $this->clerk_friendly_attributes( $field ) ) . 's' ] = $child_attributes;
							}
						}

						if ( $product->is_type( 'grouped' ) ) {
							$child_product_ids = $product->get_children();
							$child_attributes  = array();

							foreach ( $child_product_ids as $child_id ) {
								$collectinfo  = '';
								$childproduct = wc_get_product( $child_id );

								$attribute_field = wp_get_post_terms( $childproduct->get_id(), strtolower( $field ), array( 'fields' => 'names' ) );

								if ( is_array( $atribute ) && count( $atribute ) > 0 ) {
									$collectinfo = $attribute[0];
								} else {
									$collectinfo = $attribute;
								}

								if ( '' === $collectinfo && isset( $childproduct->$field ) ) {
									$collectinfo = $childproduct->$field;
								}
								if ( $collectinfo ) {
									$child_attributes[] = $collectinfo;
								}
							}
							if ( ! empty( $child_atributes ) ) {
								$product_array[ 'child_' . strtolower( $this->clerk_friendly_attributes( $field ) ) . 's' ] = $child_attributes;
							}
						}
					}
				}
			}

			// 22-10-2021 KKY.

			$params = '';

			$params = apply_filters( 'clerk_product_sync_array', $product_array, $product );
			$this->api->add_product( $params );
		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR add_product', array( 'error' => $e->getMessage() ) );
		}
	}


	/**
	 * Check URL for Danish Language Characters and handle-ize
	 *
	 * @param mixed $attribute Attribute.
	 */
	private function clerk_friendly_attributes( $attribute ) {
		$attribute = strtolower( $attribute );
		$attribute = str_replace( 'æ', 'ae', $attribute );
		$attribute = str_replace( 'ø', 'oe', $attribute );
		$attribute = str_replace( 'å', 'aa', $attribute );
		return rawurlencode( $attribute );
	}

	/**
	 * Get additional fields for product export
	 *
	 * @return array
	 */
	private function get_additional_fields() {
		try {

			$options = get_option( 'clerk_options' );

			if ( ! is_array( $options ) ) {
				return array();
			}

			$additional_fields = $options['additional_fields'];

			$fields = explode( ',', $additional_fields );

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

			$options = get_option( 'clerk_options' );

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

			$options = get_option( 'clerk_options' );

			if ( ! is_array( $options ) ) {
				return '';
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

}

$clerk_product_sync = new Clerk_Product_Sync();
