<?php
/**
 * Plugin Name: Clerk
 * Plugin URI: https://clerk.io/
 * Description: Clerk.io Turns More Browsers Into Buyers
 * Version: 4.1.7
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

require_once dirname( dirname( __FILE__ ) ) . '/includes/clerk-multi-lang-helpers.php';
if ( clerk_is_wpml_enabled() ) {
	do_action( 'wpml_multilingual_options', 'clerk_options' );
}

$cart_url     = wc_get_cart_url();
$checkout_url = wc_get_checkout_url();
$options      = clerk_get_options();


if ( isset( $options['powerstep_custom_text_enabled'] ) && isset( $product ) ) {
	$product_name     = $product->get_name();
	$title_message    = esc_html__( ' added to cart!', 'clerk' );
	$title_html       = "<span class='clerk_powerstep_product_name'>$product_name</span>$title_message";
	$back_button_text = esc_html__( 'Back', 'clerk' );
	$cart_button_text = esc_html__( 'Go to cart', 'clerk' );
	if ( isset( $options['powerstep_custom_text_title'] ) ) {
		if ( str_contains( $options['powerstep_custom_text_title'], 'PRODUCT_NAME' ) ) {
			$translated_array = explode( 'PRODUCT_NAME', $options['powerstep_custom_text_title'] );
			$pre_trans        = $translated_array[0];
			$post_trans       = $translated_array[1];
			$title_html       = "<div class='clerk_powerstep_product_name_wrap'>$pre_trans<span class='clerk_powerstep_product_name'>$product_name</span>$post_trans</div>";
		}
	}
	if ( isset( $options['powerstep_custom_text_back'] ) ) {
		$back_button_text = $options['powerstep_custom_text_back'];
	}
	if ( isset( $options['powerstep_custom_text_cart'] ) ) {
		$cart_button_text = $options['powerstep_custom_text_cart'];
	}
}
?>
<div class="powerstep-success">
	<div class="powerstep-product">
		<?php echo wp_kses_post( $product->get_image() ); ?>
		<?php echo wp_kses_post( $title_html ); ?>
	</div>
	<div class="powerstep-actions">
		<br>
		<button class="button alt powerstep-cart" onclick="location.href = '<?php echo esc_attr( $cart_url ); ?>';"
				type="button" title="<?php echo esc_attr( $cart_button_text ); ?>">
			<span><?php echo esc_attr( $cart_button_text ); ?></span>
		</button>
		<button class="button" onclick="window.history.back();" type="button"
				title="<?php echo esc_attr( $back_button_text ); ?>">
			<span><?php echo esc_attr( $back_button_text ); ?></span>
		</button>
	</div>
</div>
<div class="powerstep-templates">
	<?php
	$index              = 0;
	$class_string       = 'clerk_powerstep_';
	$filter_string      = '';
	$unique_filter      = ( isset( $options['powerstep_excl_duplicates'] ) && $options['powerstep_excl_duplicates'] ) ? true : false;
	$product_categories = $product->get_category_ids();
	foreach ( get_powerstep_templates() as $template ) :
		?>
		<span class="clerk
		<?php
		if ( $unique_filter ) {
			echo esc_attr( $class_string . (string) $index );
		}
		?>
		"
		<?php
		if ( $index > 0 && $unique_filter ) {
			echo 'data-exclude-from="' . esc_attr( $filter_string ) . '"';
		}
		?>
			data-template="@<?php echo esc_attr( $template ); ?>"
			data-products="[<?php echo esc_attr( $product->get_id() ); ?>]"
			data-category="<?php echo esc_attr( reset( $product_categories ) ); ?>"
		></span>
		<?php
		if ( $index > 0 ) {
			$filter_string .= ', ';
		}
		$filter_string .= '.' . $class_string . (string) $index;
		++$index;
	endforeach;
	?>
</div>
