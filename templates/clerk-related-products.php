<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

$options = get_option( 'clerk_options' );

if ($options['product_enabled']) :
?>
<span class="clerk" data-template="@<?php echo $options['product_content']; ?>" data-products="[<?php echo get_the_ID(); ?>]"></span>
<?php
endif;
?>