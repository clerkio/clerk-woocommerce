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
		$params = [
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

		$this->api->addProduct( $params );
	}
}

new Clerk_Product_Sync();