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
                type="button" title="<?php echo __( 'Cart' ) ?>">
            <span><?php echo __( 'Cart', 'clerk' ) ?></span>
        </button>
        <button class="action" onclick="location.href = '<?php echo esc_attr( $checkout_url ); ?>';" type="button"
                title="<?php echo __( 'Proceed to Checkout' ); ?>">
            <span><?php echo __( 'Proceed to Checkout' ); ?></span>
        </button>
    </div>
</div>
<div class="powerstep-templates">
	<?php
    $Issetdataexclude = false;
    $dataexcludestring = '';
    $count = 0;
    foreach ( get_powerstep_templates() as $template ) :
        $count++;
        $id = 'clerk_'.time();
        ?>
        <span class="clerk"
              id="<?php echo $id ?>"
              <?php if($Issetdataexclude) {
                  echo 'data-exclude-from="'.$dataexcludestring.'"';
              } ?>
              data-template="@<?php echo esc_attr( $template ); ?>"
              data-products="[<?php echo esc_attr( $product->get_id() ); ?>]"
              data-category="<?php echo esc_attr( reset( $product->get_category_ids() ) ); ?>"
        ></span>
        <?php
        if ($count == 1) {

            $dataexcludestring .= '#'.$id.':limit(4)';

        }else {

            $dataexcludestring .= ',#'.$id.':limit(4)';

        }
        $Issetdataexclude = true;
	 endforeach; ?>
</div>