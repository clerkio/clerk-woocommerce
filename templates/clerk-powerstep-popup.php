<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $woocommerce;

$cart_url = $woocommerce->cart->get_cart_url();
$checkout_url = $woocommerce->cart->get_checkout_url();
/** @var WC_Product $product */
?>
<div id="clerk_powerstep" style="display: none;">
    <div class="clerk_powerstep_header">
        <h2><?php printf(esc_html__('You added %s to your shopping cart.', 'clerk'), $product->get_name()); ?></h2>
    </div>
    <div class="clerk_powerstep_image">
        <?php echo $product->get_image(); ?>
    </div>
    <div class="clerk_powerstep_clear actions">
        <button class="action powerstep-cart" onclick="location.href = '<?php echo esc_attr($cart_url) ?>';" type="button" title="<?php echo __('Cart') ?>">
            <span><?php echo __('Cart', 'clerk') ?></span>
        </button>
        <button class="action clerk_powerstep_button clerk_powerstep_close">Shop videre</button>
    </div>
    <div class="clerk_powerstep_templates">
        <?php foreach (get_powerstep_templates() as $template) : ?>
            <span class="clerk"
                  data-template="@<?php echo esc_attr($template); ?>"
                  data-products="[<?php echo esc_attr($product->get_id()); ?>]"
                  data-category="<?php echo esc_attr(reset($product->get_category_ids())); ?>"
            ></span>
        <?php endforeach; ?>
    </div>
</div>