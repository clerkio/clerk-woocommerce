<?php

class Clerk_Content {
	/**
	 * Clerk_Content constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_archive_description', [ $this, 'clerk_woocommerce_archive_description' ], 99 );
		add_action( 'woocommerce_after_cart_table', [ $this, 'clerk_woocommerce_after_cart_table' ], 99 );
		add_filter( 'wc_get_template', [ $this, 'clerk_wc_get_template' ], 99, 2 );
	}

	/**
	 * Add content to category if enabled
	 */
	public function clerk_woocommerce_archive_description() {
		$category = get_queried_object();
		$options  = get_option( 'clerk_options' );

		if ( isset( $options['category_enabled'] ) && $options['category_enabled'] ) :
			?>
            <span class="clerk" data-template="@<?php echo $options['category_content']; ?>"
                  data-category="<?php echo $category->term_id; ?>"></span>
		<?php
		endif;
	}

	/**
	 * Add content after cart if enabled
	 */
	public function clerk_woocommerce_after_cart_table() {
		global $woocommerce;
		$items = $woocommerce->cart->get_cart();

		$options  = get_option( 'clerk_options' );
		$products = array();

		foreach ( $items as $item => $values ) {
			$products[] = $values['product_id'];
		}

		if ( isset( $options['cart_enabled'] ) && $options['cart_enabled'] ) :
			?>
            <span class="clerk" data-template="@<?php echo $options['cart_content']; ?>"
                  data-products="<?php echo json_encode( $products ); ?>"></span>
		<?php
		endif;
	}

	/**
	 * Rewrite related products template if enabled
	 *
	 * @param $located
	 * @param $template_name
	 *
	 * @return string
	 */
	public function clerk_wc_get_template( $located, $template_name ) {
		if ( $template_name === 'single-product/related.php' ) {
			$options = get_option( 'clerk_options' );

			if ( isset( $options['product_enabled'] ) && $options['product_enabled'] ) :
				return clerk_locate_template( 'clerk-related-products.php' );
			endif;
		}

		return $located;
	}
}

new Clerk_Content();