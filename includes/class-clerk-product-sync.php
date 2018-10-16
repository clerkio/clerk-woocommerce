<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Clerk_Product_Sync {
	/** @var Clerk_Api */
	protected $api;

	public function __construct() {
		$this->includes();
		$this->initHooks();

		$this->api = new Clerk_Api();
	}

	private function includes() {
		require_once( __DIR__ . '/class-clerk-api.php' );
	}

	private function initHooks() {
		add_action( 'save_post_product', [ $this, 'save_product' ], 10, 2 );
		add_action( 'before_delete_post', [ $this, 'remove_product' ] );
	}

	public function save_product( $post_id, $post ) {
		if ( ! $post ) {
			return;
		}

		if ( ! $product = wc_get_product( $post ) ) {
			return;
		}

		if ( clerk_check_version() ) {
			if ( $product->get_status() === 'publish' ) {
				//Send product to Clerk
				$this->add_product( $product );
			} elseif ( ! $product->get_status() === 'draft' ) {
				//Remove product
				$this->remove_product( $product->get_id() );
			}
		} else {
			//Fix for WooCommerce 2.6
			if ( $product->post->status === 'publish' ) {
				//Send product to Clerk
				$this->add_product( $product );
			} elseif ( ! $product->post->status === 'draft' ) {
				//Remove product
				$this->remove_product( $product->get_id() );
			}
		}

	}

	/**
	 * Remove product from Clerk
	 *
	 * @param $post_id
	 */
	public function remove_product( $post_id ) {
		//Remove product from Clerk
		$this->api->removeProduct( $post_id );
	}

	/**
	 * Add product in Clerk
	 *
	 * @param WC_Product $product
	 */
	private function add_product( WC_Product $product ) {
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

        $params = [
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

        $additional_fields = array_filter($this->getAdditionalFields(), 'strlen');

        //Append additional fields
        foreach ($additional_fields as $field) {
            $params[$field] = $product->get_attribute($field);
        }

        $this->api->addProduct( $params );
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

new Clerk_Product_Sync();