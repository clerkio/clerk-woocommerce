<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $woocommerce;

$cart_url     = $woocommerce->cart->get_cart_url();
$checkout_url = $woocommerce->cart->get_checkout_url();

/** @var WC_Product $product */
?>
<div class="powerstep-success">
    <div class="powerstep-product">
		<?php echo $product->get_image(); ?>
		<?php printf( esc_html__( 'You added %s to your shopping cart.', 'clerk' ), $product->get_name() ); ?>
    </div>
    <div class="powerstep-actions">
        <br>
        <button class="action powerstep-cart" onclick="location.href = '<?php echo esc_attr( $cart_url ) ?>';"
                type="button" title="<?php echo esc_attr__( 'Cart', 'clerk' ) ?>">
            <span><?php echo esc_html__( 'Cart', 'clerk' ) ?></span>
        </button>
        <button class="action" onclick="location.href = '<?php echo esc_attr( $checkout_url ); ?>';" type="button"
                title="<?php echo esc_attr__( 'Proceed to Checkout', 'clerk' ); ?>">
            <span><?php echo esc_html__( 'Proceed to Checkout', 'clerk' ); ?></span>
        </button>
    </div>
</div>
<div class="powerstep-templates">
	<?php
    $index = 0;
    $class_string = 'clerk_powerstep_';
    $filter_string = '';
    $unique_filter = (isset($options['powerstep_excl_duplicates']) && $options['powerstep_excl_duplicates']) ? true : false;
    foreach ( get_powerstep_templates() as $template ) :
        $count++;
        $id = 'clerk_'.time().$count;
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