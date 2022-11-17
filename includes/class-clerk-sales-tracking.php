<?php
/**
 * Plugin Name: Clerk
 * Plugin URI: https://clerk.io/
 * Description: Clerk.io Turns More Browsers Into Buyers
 * Version: 4.0.0
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
 * Clerk_Sales_Tracking Class
 *
 * Clerk Module Core Class
 */
class Clerk_Sales_Tracking {


	/**
	 * Error and Warning Logger
	 *
	 * @var $logger Clerk_Logger
	 */
	protected $logger;

	/**
	 * Clerk_Sales_Tracking constructor.
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
		add_action( 'woocommerce_thankyou', array( $this, 'add_sales_tracking' ) );
	}

	/**
	 * Include sales tracking
	 *
	 * @param string|int $order_id Order ID.
	 */
	public function add_sales_tracking( $order_id ) {

		try {

			$order = wc_get_order( $order_id );

			$products = array();
			$items    = $order->get_items();            // Iterate products, adding to products array.
			foreach ( $items as $item_id => $item ) {
				// Option: Including or excluding Taxes.
				$inc_tax = true;
				// Option: Round at item level (or not).
				$round           = false; // Not rounded at item level ("true"  for rounding at item level).
				$item_line_total = $order->get_line_total( $item, $inc_tax, $round ); // Get line total - discounted.
				$item_quantity   = $item->get_quantity();
				$product_id      = $item['product_id']; // $item->get_product_id(); this gets the variant ID.
				$products[]      = array(
					'id'       => $product_id,
					'quantity' => $item_quantity,
					'price'    => $item_line_total / $item_quantity,
				);
			}

			$order_array = array(
				'id'       => $order_id,
				'email'    => $order->get_billing_email(),
				'products' => $products,
			);

			$order_array = apply_filters( 'clerk_tracking_order_array', $order_array, $order );
			?>
			<span
					class="clerk"
					data-api="log/sale"
					data-sale="<?php echo esc_html( $order_array['id'] ); ?>"
					data-email="<?php echo esc_html( $order_array['email'] ); ?>"
					data-products='<?php echo wp_json_encode( $order_array['products'] ); ?>'>
			</span>
			<script type="text/javascript">
			(function () {
				var clerk_no_productids = [];
				Clerk('cart', 'set', clerk_no_productids);
			})();
			</script>
			<?php

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR add_sales_tracking', array( 'error' => $e->getMessage() ) );

		}

	}
}

new Clerk_Sales_Tracking();
