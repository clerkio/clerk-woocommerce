<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$options = get_option( 'clerk_options' );

if ( isset( $options['product_enabled'] ) && $options['product_enabled'] ) :
    $contents = explode(',', $options['product_content']);

    foreach ($contents as $content) :
?>
    <span class="clerk" data-template="@<?php echo $content; ?>"
          data-products="[<?php echo get_the_ID(); ?>]"></span>
<?php
    endforeach;
endif;
?>