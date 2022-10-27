<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $woocommerce;

$cart_url     = $woocommerce->cart->get_cart_url();
$checkout_url = $woocommerce->cart->get_checkout_url();

/** @var WC_Product $product */
$options = get_option('clerk_options');

$product_name = (string)$product->get_name();
$title_message = esc_html__( ' added to cart!', 'clerk' );
$title_html = "<span class='clerk_powerstep_product_name'>$product_name</span>$title_message";
$back_button_text = esc_html__( 'Back', 'clerk' );
$cart_button_text = esc_html__( 'Go to cart', 'clerk' );
if(isset($options['powerstep_custom_text_enabled'])){
    if(isset($options['powerstep_custom_text_title'])){
        if(str_contains($options['powerstep_custom_text_title'], 'PRODUCT_NAME')){
            $translated_array = explode('PRODUCT_NAME', $options['powerstep_custom_text_title']);
            $pre_trans = $translated_array[0];
            $post_trans = $translated_array[1];
            $title_html = "$pre_trans<span class='clerk_powerstep_product_name'>$product_name</span>$post_trans";
        }
    }
    if(isset($options['powerstep_custom_text_back'])){
        $back_button_text = $options['powerstep_custom_text_back'];
    }
    if(isset($options['powerstep_custom_text_cart'])){
        $cart_button_text = $options['powerstep_custom_text_cart'];
    }
}
?>
<div class="powerstep-success">
    <div class="powerstep-product">
		<?php echo $product->get_image(); ?>
		<?php echo $title_html; ?>
    </div>
    <div class="powerstep-actions">
        <br>
        <button class="button alt powerstep-cart" onclick="location.href = '<?php echo esc_attr( $cart_url ); ?>';"
                type="button" title="<?php echo $cart_button_text; ?>">
            <span><?php echo $cart_button_text; ?></span>
        </button>
        <button class="button" onclick="window.history.back();" type="button"
                title="<?php echo $back_button_text; ?>">
            <span><?php echo $back_button_text; ?></span>
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