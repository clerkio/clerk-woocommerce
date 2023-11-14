<?php
/**
 * Plugin Name: Clerk
 * Plugin URI: https://clerk.io/
 * Description: Clerk.io Turns More Browsers Into Buyers
 * Version: 4.1.0
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

$cart_url     = wc_get_cart_url();
$checkout_url = wc_get_checkout_url();
$options      = get_option( 'clerk_options' );

$product_name     = (string) $product->get_name();
$title_message    = esc_html__( ' added to cart!', 'clerk' );
$title_html       = "<span class='clerk_powerstep_product_name'>$product_name</span>$title_message";
$back_button_text = esc_html__( 'Back', 'clerk' );
$cart_button_text = esc_html__( 'Go to cart', 'clerk' );
if ( isset( $options['powerstep_custom_text_enabled'] ) ) {
	if ( isset( $options['powerstep_custom_text_title'] ) ) {
		if ( str_contains( $options['powerstep_custom_text_title'], 'PRODUCT_NAME' ) ) {
			$translated_array = explode( 'PRODUCT_NAME', $options['powerstep_custom_text_title'] );
			$pre_trans        = $translated_array[0];
			$post_trans       = $translated_array[1];
			$title_html       = "$pre_trans<span class='clerk_powerstep_product_name'>$product_name</span>$post_trans";
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
<div id="clerk_powerstep" class="clerk-popup" style="display: none;">
	<span class="clerk-popup-close">×</span>
	<div class="clerk_powerstep_header">
		<h2 class="clerk_powerstep_headline">
			<?php echo wp_kses_post( $title_html ); ?>
		</h2>
	</div>
	<div class="clerk_powerstep_image">
		<?php echo wp_kses_post( $product->get_image() ); ?>
	</div>
	<div class="clerk_powerstep_clear actions">
		<button class="button clerk-powerstep-close"><?php echo esc_html( $back_button_text ); ?></button>
		<button class="button alt powerstep-cart" onclick="location.href = '<?php echo esc_attr( $cart_url ); ?>';"
				type="button" title="<?php echo esc_attr( $cart_button_text ); ?>">
			<span><?php echo esc_attr( $cart_button_text ); ?></span>
		</button>
	</div>
	<div class="clerk_powerstep_templates">
		<?php
		$index         = 0;
		$class_string  = 'clerk_powerstep_';
		$filter_string = '';
		$unique_filter = ( isset( $options['powerstep_excl_duplicates'] ) && $options['powerstep_excl_duplicates'] ) ? true : false;

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
					data-category="<?php echo esc_attr( reset( $product->get_category_ids() ) ); ?>"
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
	<style>
		.clerk-popup-close {
			position: absolute;
			right: 8px;
			top: 3px;
			cursor: pointer;
			font-family: Arial;
			font-size: 32px;
			line-height: 1;
			color: gray;
		}
		.clerk-popup {
			position: fixed;
			top: 10%;
			z-index: 16777271;
			display: none;
			width: 90%;
			padding: 20px;
			margin: 0 5%;
			background-color: white;
			border: 1px solid #eee;
			border-radius: 5px;
			box-shadow: 0px 8px 40px 0px rgba(0,0,60,0.15);
		}
		.clerk_powerstep_headline {
			margin: 14px 0px 14px 0px;
			font-weight: 100;
		}
		.clerk_powerstep_product_name {
			font-weight: bold;
		}
		.clerk_powerstep_header, .clerk_powerstep_image {
			text-align: center;
		}

		.clerk_powerstep_image {
			margin: 0px 0px 24px 0px;
		}
		.clerk_powerstep_image img{
			display: inline;
			max-width: 25%;
		}
		.clerk_powerstep_clear {
			display: flex;
			flex-direction: row;
			justify-content: space-between;
			overflow: hidden;

		}

		:root{
			--clerk-popup-width:60ch
		}
		@media screen and (max-width:600px){
			:root{
				--clerk-popup-width:84vw
			}
			.clerk_powerstep_headline {
				margin: 1em 0 1em 0;
			}
			.clerk_powerstep_clear {
				font-size: 0.8em;
			}
		}
		@-webkit-keyframes popin{
			from{
				top:translate(-50%,-150%);
				opacity:0
			}
			to{
				transform:translate(-50%,-50%);
				opacity:1
			}
		}
		@keyframes popin{
			from{
				transform:translate(-50%,-150%);
				opacity:0
			}
			to{
				transform:translate(-50%,-50%);
				opacity:1
			}
		}
		.clerk-vert-spacer{
			margin-bottom:10px
		}
		.popin{
			animation:popin .5s ease-in-out
		}
		.clerk_hidden{
			display:none !important
		}
		.clerk-popup{
			width:clamp(var(--clerk-popup-width),60%,100ch) !important;
			top:50% !important;
			left:50% !important;
			transform:translate(-50%,-50%);
			margin:0 !important;
			border:none !important;
			border-radius:5px !important;
			max-height:calc(85vh);
			overflow-y:scroll;
			overflow-y:overlay;
			-ms-overflow-style:none;
			scrollbar-width:none;
			animation:popin .5s ease-in-out
		}
		.clerk-popup-close{
			right:15px !important;
			top:10px !important
		}
		#clerk-power-popup .price-box{
			display:flex;
			flex-direction:column
		}
		#clerk-power-popup .success-msg{
			margin-bottom:10px;
			margin-top:10px
		}
		#clerk-power-popup > * > *:first-child{
			font-size:clamp(1rem,.5714rem + 1.9048vw,2rem)
		}
		.clerk-popup::-webkit-scrollbar{
			display:none
		}

	</style>
</div>
