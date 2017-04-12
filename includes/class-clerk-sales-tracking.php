<?php

class Clerk_Sales_Tracking {
	/**
	 * Clerk_Sales_Tracking constructor.
	 */
	public function __construct() {
		$this->initHooks();
	}

	/**
	 * Init hooks
	 */
	private function initHooks() {
		add_action( 'woocommerce_thankyou', [ $this, 'add_sales_tracking' ] );
	}

	/**
	 * Include sales tracking
	 */
	public function add_sales_tracking( $order_id ) {
		$order = wc_get_order( $order_id );

		$products = [];
		$items    = $order->get_items();

		//Iterate products, adding to products array
		foreach ( $items as $item ) {
			$product = $item->get_product();

			$products[] = [
				'id'       => $item->get_id(),
				'quantity' => $item->get_quantity(),
				'price'    => $product->get_price(),
			];
		}
		?>
        <span
                class="clerk"
                data-api="log/sale"
                data-sale="<?php echo $order_id; ?>"
                data-email="<?php echo $order->get_billing_email(); ?>"
                data-products='<?php echo json_encode( $products ); ?>'>
        </span>
		<?php
	}
}

new Clerk_Sales_Tracking();