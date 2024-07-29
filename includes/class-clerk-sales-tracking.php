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
		include_once __DIR__ . '/clerk-multi-lang-helpers.php';
		if ( clerk_is_wpml_enabled() ) {
			do_action( 'wpml_multilingual_options', 'clerk_options' );
		}
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

			if ( has_action( 'wc_aelia_cs_convert' ) && get_option( 'woocommerce_currency' ) ) {
				$rate = floatval( apply_filters( 'wc_aelia_cs_convert', 1000000, get_option( 'woocommerce_currency' ), get_woocommerce_currency() ) ) / 1000000;
			} else {
				$rate = 1;
			}

			foreach ( $items as $item_id => $item ) {
				$item_line_total = $order->get_line_total( $item, true, false ); // Get line total - discounted.
				$item_quantity   = $item->get_quantity();
				$product_id      = $item['product_id']; // $item->get_product_id(); this gets the variant ID.
				$products[]      = array(
					'id'       => $product_id,
					'quantity' => $item_quantity,
					'price'    => ( $item_line_total / $item_quantity ) / $rate,
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
			document.addEventListener('DOMContentLoaded', function(){
				Clerk('cart', 'set', []);
			});
			</script>
			<?php

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR add_sales_tracking', array( 'error' => $e->getMessage() ) );

		}
	}
}

new Clerk_Sales_Tracking();
