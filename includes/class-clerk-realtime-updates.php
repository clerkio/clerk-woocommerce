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
 * Clerk_Product_Sync Class
 *
 * Clerk Module Core Class
 */
class Clerk_Realtime_Updates {


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
	 * Language iso code string.
	 *
	 * @var string
	 */
	protected $lang_iso;

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
		include_once __DIR__ . '/clerk-multi-lang-helpers.php';
		if ( clerk_is_wpml_enabled() ) {
			do_action( 'wpml_multilingual_options', 'clerk_options' );
		}
	}

	/**
	 * Init hooks
	 */
	private function init_hooks() {

		add_action( 'woocommerce_new_product', array( $this, 'save_product' ), 100, 3 );
		// This hook will run before the price is updated if there is a module modifying the price via a hook.
		// save_post with a high enough priority defer score.
		add_action( 'save_post', array( $this, 'pre_save_post' ), 1000, 3 );

		add_action( 'before_delete_post', array( $this, 'pre_delete_post' ), 100, 3 );
		add_action( 'wp_trash_post', array( $this, 'pre_delete_post' ), 100, 3 );

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
				if ( is_a( $product, 'WC_Product' ) && ! is_a( $product, 'WC_Product_Variation' ) ) {
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
				if ( function_exists( 'wc_get_product' ) ) {
					$product = wc_get_product( $post_id );
					if ( is_a( $product, 'WC_Product' ) && ! is_a( $product, 'WC_Product_Variation' ) ) {
						  $this->save_product( $post_id );
						  return;
					}
				}

				$post_object = get_post( $post_id );
				if ( is_a( $post_object, 'WP_Post' ) ) {
					$this->save_blog_post( $post_object );
				}
			}
		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR pre_save_post', array( 'error' => $e->getMessage() ) );
		}
	}

	/**
	 * Update Product from Import
	 *
	 * @param int|void     $post_id Product Id.
	 * @param WP_Post|void $post Post Object.
	 * @param bool|void    $update Whether an existing post is being updated.
	 */
	public function pre_delete_post( $post_id = null, $post = null, $update = null ) {
		try {
			if ( $post_id ) {
				$this->api->delete_posts( array( $post_id ) );
			}
		} catch ( Exception $e ) {
			$this->logger->error( 'ERROR pre_delete_post', array( 'error' => $e->getMessage() ) );
		}
	}

	/**
	 * Update Post from import.
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	public function save_blog_post( $post ) {
		try {

			$options = $this->clerk_get_contextual_options( $post->ID );

			if ( ! is_array( $options ) || ! isset( $options ) ) {
				return;
			}

			if ( ! array_key_exists( 'realtime_updates_pages', $options ) ) {
				return;
			}

			$post_types = array( 'post', 'page' );
			if ( isset( $options['page_additional_types'] ) ) {
				$additional_types      = preg_replace( '/\s+/', '', $options['page_additional_types'] );
				$additional_types_list = explode( ',', $additional_types );
				$post_types            = array_values( array_unique( array_merge( $post_types, $additional_types_list ) ) );
			}

			if ( ! in_array( $post->post_type, $post_types, true ) ) {
				return;
			}

			$page_additional_fields = explode( ',', $options['page_additional_fields'] );

			$post_status = get_post_status( $post );

			if ( ! empty( $post->post_content ) && 'publish' === $post_status ) {

				$url = get_permalink( $post->ID );
				$url = empty( $url ) ? $post->guid : $url;

				$page_draft = array(
					'id'    => $post->ID,
					'type'  => $post_status,
					'url'   => $url,
					'title' => $post->post_title,
					'text'  => gettype( $post->post_content ) === 'string' ? wp_strip_all_tags( $post->post_content ) : '',
					'image' => get_the_post_thumbnail_url( $post->ID ),
				);

				foreach ( $page_additional_fields as $page_additional_field ) {
					$page_additional_field = str_replace( ' ', '', $page_additional_field );
					if ( ! empty( $page_additional_field ) ) {
						$page_draft[ $page_additional_field ] = $post->{ $page_additional_field };
					}
				}

				$this->api->add_posts( array( $page_draft ) );
			}
			if ( 'publish' !== $post_status ) {
				$this->api->delete_posts( array( $post->ID ) );
			}
		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR save_blog_post', array( 'error' => $e->getMessage() ) );
		}
	}

	/**
	 * Update Product
	 *
	 * @param integer $product_id Product ID.
	 */
	public function save_product( $product_id = null ) {

		if ( ! is_int( $product_id ) ) {
			return;
		}

		if ( ! function_exists( 'wc_get_product' ) ) {
			return;
		}

		$options = $this->clerk_get_contextual_options( $product_id );

		if ( ! is_array( $options ) || ! isset( $options ) ) {
			return;
		}

		if ( ! array_key_exists( 'realtime_updates', $options ) ) {
			return;
		}

		try {

			$product = wc_get_product( $product_id );
			if ( ! is_a( $product, 'WC_Product' ) ) {
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
	 * @param integer $product_id Post Id.
	 */
	public function remove_product( $product_id ) {
		try {

			if ( ! is_int( $product_id ) ) {
				return;
			}

			$options = $this->clerk_get_contextual_options( $product_id );

			if ( ! is_array( $options ) || ! isset( $options['realtime_updates'] ) ) {
				return;
			}

			if ( 1 !== (int) $options['realtime_updates'] ) {
				return;
			}
			// Remove product from Clerk.
			$this->api->remove_product( $product_id );
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

			$options = $this->clerk_get_contextual_options( $product->get_id() );

			if ( ! is_array( $options ) ) {
				return;
			}

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
				$stock_quantity                                = 0;
				$display_price                                 = array();
				$regular_price                                 = array();
				$display_price_excl_tax                        = array();
				$regular_price_excl_tax                        = array();
				$variations                                    = $product->get_available_variations();

				foreach ( $variations as $variation ) {

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

					$variant_image = wp_get_attachment_image_src( $variation->get_image_id(), $image_size_setting );
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

			// Use default method for getting price if type is custom.

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

			if ( ! isset( $options['outofstock_products'] ) ) {
				if ( $product->get_stock_status() !== 'instock' ) {
					return;
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
			$product_array['all_images']          = array();
			$product_array['stock']               = $stock_quantity ?? 0;
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

			if ( ! empty( $product->get_stock_quantity() ) ) {
				$product_array['stock'] = ( null !== $product->get_stock_quantity() ) ? $product->get_stock_quantity() : 1;
			} elseif ( isset( $stock_quantity ) ) {

				$product_array['stock'] = $stock_quantity;
			}

			$product_array = $this->resolve_unit_measure( $product, $product_array );

			$additional_fields = $this->get_additional_fields( $options );

			if ( in_array( 'short_description', $additional_fields, true ) ) {
				$product_array['short_description'] = $product->get_short_description();
			}

			if ( in_array( 'all_images', $additional_fields, true ) ) {
				$product_array['all_images'] = array();
				foreach ( get_intermediate_image_sizes() as $key => $image_size_setting ) {
					$image_path = wp_get_attachment_image_src( $product->get_image_id(), $image_size_setting );
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

			$product_array = $this->query_custom_fields( $product, $additional_fields, $product_array, $options );

			$product_array = apply_filters( 'clerk_product_sync_array', $product_array, $product );

			$this->api->add_product( $product_array );

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR add_product', array( 'error' => $e->getMessage() ) );
		}
	}

	/**
	 * Get Custom Fields for Product
	 *
	 * @param WC_Product $product Product Object.
	 * @param array      $fields Fields Array.
	 * @param array      $product_data Product Data Array.
	 * @param array      $options Module options.
	 * @return array
	 */
	private function query_custom_fields( $product, $fields, $product_data, $options ) {
		$product_type = $product->get_type();
		$fields       = array_values( array_filter( array_diff( $fields, array_keys( $product_data ) ) ) );

		foreach ( $fields as $field ) {
			$attribute_value = $this->resolve_attribute_product( $product, $field );
			if ( isset( $attribute_value ) ) {
				$product_data[ $this->clerk_friendly_attributes( $field ) ] = $this->format_attribute( $attribute_value, $field, $options );
			}
		}

		if ( 'variable' === $product_type ) {
			$variations = $product->get_available_variations();
			foreach ( $variations as $variation ) {
				$variant = new WC_Product_variation( $variation['variation_id'] );
				foreach ( $fields as $field ) {
					$attribute_value = $this->format_attribute( $this->resolve_attribute_product( $variant, $field ), $field, $options );
					if ( empty( $attribute_value ) ) {
						if ( ! array_key_exists( $field, $variation ) ) {
							continue;
						} else {
							$attribute_value = $this->format_attribute( $variation[ $field ], $field, $options );
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
				foreach ( $fields as $field ) {
					$attribute_value = $this->format_attribute( $this->resolve_attribute_product( $child, $field ), $field, $options );
					if ( empty( $attribute_value ) ) {
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
	private function flatten_attribute( $product_data, $field, $attribute_value ) {
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
	 * @param array  $options Module options.
	 * @return array|mixed
	 */
	private function format_attribute( $attribute_value, $field, $options ) {
		if ( is_object( $attribute_value ) ) {
			$attribute_value = (array) $attribute_value;
		}
		if ( is_array( $attribute_value ) && count( $attribute_value ) === 1 ) {
			$attribute_value = $attribute_value[0];
		}
		if ( is_string( $attribute_value ) && ! in_array( $field, $this->get_additional_fields_raw( $options ), true ) ) {
			$attribute_value = array_map( array( $this, 'trim_whitespace_in_attribute' ), explode( ',', $attribute_value ) );
		}
		if ( is_array( $attribute_value ) && count( $attribute_value ) === 1 ) {
			$attribute_value = $attribute_value[0];
		}
		return $attribute_value;
	}

	/**
	 * Get Attribute Value with Valid Method
	 *
	 * @param WC_Product|WC_Product_variation $product Product Object.
	 * @param string                          $field Field Slug.
	 *
	 * @return mixed Attribute Value.
	 */
	private function resolve_attribute_product( $product, $field ) {
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
	 * Get Product Unit Measure Data
	 *
	 * @param WC_Product|WC_Product_variation $product Product Object.
	 * @param array                           $product_data Product Data.
	 *
	 * @return array $product_data Product Data.
	 */
	private function resolve_unit_measure( $product, $product_data ) {
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
	 * Check URL for Danish Language Characters and handle-ize
	 *
	 * @param mixed $attribute Attribute.
	 */
	private function clerk_friendly_attributes( $attribute ) {
		$attribute = strtolower( $attribute );
		$attribute = str_replace( 'æ', 'ae', $attribute );
		$attribute = str_replace( 'ø', 'oe', $attribute );
		$attribute = str_replace( 'å', 'aa', $attribute );
		$attribute = str_replace( '-', '_', $attribute );
		return rawurlencode( $attribute );
	}

	/**
	 * Get additional fields for product export
	 *
	 * @param array $options Module options.
	 * @return array | void
	 */
	private function get_additional_fields( $options ) {
		try {

			if ( ! is_array( $options ) ) {
				return array();
			}

			$additional_fields = $options['additional_fields'];

			return explode( ',', $additional_fields );
		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR get_additional_fields', array( 'error' => $e->getMessage() ) );
		}
	}

	/**
	 * Get additional fields for product export
	 *
	 * @param array $options Module options.
	 * @return array | void
	 */
	private function get_additional_fields_raw( $options ) {
		try {

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

			if ( isset( $this->lang_iso ) ) {
				$options = get_option( 'clerk_options_' . $this->lang_iso );
			} else {
				$options = get_option( 'clerk_options' );
			}

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
	 * Get correct options for context.
	 *
	 * @param int|string $entity_id Entity id.
	 * @return false|mixed|null
	 */
	private function clerk_get_contextual_options( $entity_id ) {

		if ( clerk_wpml_all_scope_is_active() && clerk_wpml_get_product_lang( $entity_id ) ) {
			$lang_info      = clerk_wpml_get_product_lang( $entity_id );
			$this->lang_iso = $lang_info['language_code'];
			$options        = get_option( 'clerk_options_' . $this->lang_iso );
		} elseif ( clerk_is_pll_enabled() && clerk_pll_languages_list() ) {
			$lang_info = apply_filters( 'wpml_post_language_details', null, $entity_id );
			if ( is_array( $lang_info ) && array_key_exists( 'language_code', $lang_info ) ) {
				$this->lang_iso = $lang_info['language_code'];
				$options        = get_option( 'clerk_options_' . $this->lang_iso );
			}
		}

		if ( ! isset( $options ) ) {
			$options = get_option( 'clerk_options' );
		}

		return $options;
	}

}

$clerk_product_sync = new Clerk_Realtime_Updates();
