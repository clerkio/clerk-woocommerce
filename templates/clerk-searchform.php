<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

$options   = get_option( 'clerk_options' );
$unique_id = esc_attr( uniqid( 'clerk-search-form-' ) );
?>
    <form role="search" method="get" class="search-form"
          action="<?php echo esc_url( get_page_link( $options['search_page'] ) ); ?>">
        <label>
            <span class="screen-reader-text"><?php echo _x( 'Search for:', 'label' ) ?></span>
            <input type="search" id="clerk-searchfield-<?php echo $unique_id; ?>" class="search-field"
                   placeholder="<?php echo esc_attr_x( 'Search &hellip;', 'placeholder' ) ?>"
                   value="<?php echo get_search_query() ?>" name="searchterm"/>
        </label>
        <input type="submit" class="search-submit" value="<?php echo esc_attr_x( 'Search', 'submit button' ) ?>"/>
    </form>
<?php

if ( isset( $options['livesearch_enabled'] ) && $options['livesearch_enabled'] ) :

    ?>
    <span
            class="clerk"
            data-template="@<?php echo esc_attr( strtolower( str_replace( ' ', '-', $options['livesearch_template'] ) ) ); ?>"
            data-instant-search-suggestions="<?php echo $options['livesearch_suggestions']; ?>"
            data-instant-search-categories="<?php echo $options['livesearch_categories']; ?>"
            data-instant-search-pages="<?php echo $options['livesearch_pages']; ?>"
            data-instant-search-positioning="<?php echo strtolower($options['livesearch_dropdown_position']); ?>"
            <?php

            if ( isset( $options['livesearch_pages_type'] ) && $options['livesearch_pages_type'] != 'All') :

            ?>
            data-instant-search-pages-type="<?php echo $options['livesearch_pages_type']; ?>"
            <?php
            endif;
            ?>
            data-instant-search="#clerk-searchfield-<?php echo $unique_id; ?>">
</span>
<?php
endif;
?>