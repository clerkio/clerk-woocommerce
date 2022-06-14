<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $woocommerce;

$cart_url     = $woocommerce->cart->get_cart_url();
$checkout_url = $woocommerce->cart->get_checkout_url();
/** @var WC_Product $product */
?>
<div id="clerk_powerstep" class="clerk-popup" style="display: none;">
    <span class="clerk-popup-close">×</span>
    <div class="clerk_powerstep_header">
        <h2><?php printf( esc_html__( 'You added %s to your shopping cart.', 'clerk' ), $product->get_name() ); ?></h2>
    </div>
    <div class="clerk_powerstep_image">
		<?php echo $product->get_image(); ?>
    </div>
    <div class="clerk_powerstep_clear actions">
        <button class="action powerstep-cart" onclick="location.href = '<?php echo esc_attr( $cart_url ) ?>';"
                type="button" title="<?php echo esc_attr__( 'Cart', 'clerk' ) ?>">
            <span><?php echo esc_html__( 'Cart', 'clerk' ) ?></span>
        </button>
        <button class="action clerk_powerstep_button clerk-powerstep-close"><?php echo esc_html__( 'Continue Shopping', 'clerk' ); ?></button>
    </div>
    <div class="clerk_powerstep_templates">
        <?php
        $index = 0;
        $class_string = 'clerk_powerstep_';
        $filter_string = '';
        $unique_filter = (isset($options['powerstep_excl_duplicates']) && $options['powerstep_excl_duplicates']) ? true : false;

        foreach ( get_powerstep_templates() as $template ) :
            ?>
            <span class="clerk <?php if($unique_filter){ echo $class_string.(string)$index; } ?>"
                    <?php if($index > 0 && $unique_filter){ echo 'data-exclude-from="'.$filter_string.'"'; }?>
                    data-template="@<?php echo esc_attr( $template ); ?>"
                    data-products="[<?php echo esc_attr( $product->get_id() ); ?>]"
                    data-category="<?php echo esc_attr( reset( $product->get_category_ids() ) ); ?>"
            ></span>
            <?php
            if($index > 0){
                $filter_string .= ', ';
            }
            $filter_string .= '.'.$class_string.(string)$index;
            $index++;
        endforeach; ?>
    </div>
    <style>
        .clerk_powerstep_button {
            float: right;
        }
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
        .clerk_powerstep_header, .clerk_powerstep_image {
            text-align: center;
        }
        .clerk_powerstep_image img{
            display: inline;
        }
        .clerk_powerstep_clear {
            overflow: hidden;
        }
    </style>
</div>