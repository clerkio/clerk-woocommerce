<?php

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
		if ( ! $product = wc_get_product( $post ) ) {
			return;
		}

		if ( $product->get_status() === 'publish' ) {
			//Send product to Clerk
			$this->add_product( $product );
		} elseif ( ! $product->get_status() === 'draft' ) {
			//Remove product
			$this->remove_product( $product->get_id() );
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

		$params = [
			'id'          => $product->get_id(),
			'name'        => $product->get_name(),
			'description' => get_post_field('post_content', $product->get_id()),
			'price'       => (float) $product->get_price(),
			'list_price'  => (float) $product->get_regular_price(),
			'image'       => wp_get_attachment_url( $product->get_image_id() ),
			'url'         => $product->get_permalink(),
			'categories'  => wp_list_pluck($categories, 'term_id'),
			'sku'         => $product->get_sku(),
			'on_sale'     => $product->is_on_sale(),
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

        $fields = explode(',', $additional_fields);

        return $fields;
    }
}

new Clerk_Product_Sync();