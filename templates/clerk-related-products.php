<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$options = get_option( 'clerk_options' );

if ( isset( $options['product_enabled'] ) && $options['product_enabled'] ) :
    $contents = explode(',', $options['product_content']);
    $index = 0;
    $class_string = 'clerk_';
    $filter_string = '';
    $unique_filter = (isset($options['product_excl_duplicates']) && $options['product_excl_duplicates']) ? true : false;
    foreach ($contents as $content) :

        ?>
        <span class="clerk <?php if($unique_filter){ echo $class_string.(string)$index; } ?>" 
            <?php if($index > 0 && $unique_filter){ echo 'data-exclude-from="'.$filter_string.'"'; }?>
            data-template="@<?php echo str_replace(' ','', $content); ?>"
            data-products="[<?php echo get_the_ID(); ?>]"></span>
        <?php
        if($index > 0){
            $filter_string .= ', ';
        }
        $filter_string .= '.'.$class_string.(string)$index;
        $index++;
    endforeach;
endif;
?>