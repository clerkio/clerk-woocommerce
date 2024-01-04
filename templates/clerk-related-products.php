<?php
/**
 * Plugin Name: Clerk
 * Plugin URI: https://clerk.io/
 * Description: Clerk.io Turns More Browsers Into Buyers
 * Version: 4.1.6
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

$options = get_option( 'clerk_options' );

if ( isset( $options['product_enabled'] ) && $options['product_enabled'] ) :
	$contents      = explode( ',', $options['product_content'] );
	$index         = 0;
	$class_string  = 'clerk_';
	$filter_string = '';
	$unique_filter = ( isset( $options['product_excl_duplicates'] ) && $options['product_excl_duplicates'] ) ? true : false;
	foreach ( $contents as $content ) :
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
			data-template="@<?php echo esc_attr( str_replace( ' ', '', $content ) ); ?>"
			data-products="[<?php echo get_the_ID(); ?>]"></span>
		<?php
		if ( $index > 0 ) {
			$filter_string .= ', ';
		}
		$filter_string .= '.' . $class_string . (string) $index;
		++$index;
	endforeach;
endif;
?>
