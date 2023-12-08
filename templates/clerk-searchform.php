<?php
/**
 * Plugin Name: Clerk
 * Plugin URI: https://clerk.io/
 * Description: Clerk.io Turns More Browsers Into Buyers
 * Version: 4.1.5
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

require_once dirname( dirname( __FILE__ ) ) . '/includes/clerk-multi-lang-helpers.php';
if ( clerk_is_wpml_enabled() ) {
	do_action( 'wpml_multilingual_options', 'clerk_options' );
}

$options   = get_option( 'clerk_options' );
$unique_id = esc_attr( uniqid( 'clerk-search-form-' ) );
?>
	<form
	role="search"
	method="get"
	class="search-form"
	action="<?php echo esc_url( get_page_link( $options['search_page'] ) ); ?>">
		<label>
			<span
			class="screen-reader-text"><?php echo esc_attr_x( 'Search for:', 'label' ); ?></span>
			<input
			type="search"
			id="clerk-searchfield"
			class="search-field"
			placeholder="<?php echo esc_attr_x( 'Search &hellip;', 'placeholder' ); ?>"
			value="<?php echo esc_attr( get_search_query() ); ?>"
			name="searchterm"/>
		</label>
		<input
		type="submit"
		class="search-submit"
		value="<?php echo esc_attr_x( 'Search', 'submit button' ); ?>"/>
	</form>
<?php

if ( isset( $options['livesearch_enabled'] ) && $options['livesearch_enabled'] ) :

	?>
	<span
			class="clerk"
			data-template="@<?php echo esc_attr( strtolower( str_replace( ' ', '-', $options['livesearch_template'] ) ) ); ?>"
			data-instant-search-suggestions="<?php echo esc_attr( $options['livesearch_suggestions'] ); ?>"
			data-instant-search-categories="<?php echo esc_attr( $options['livesearch_categories'] ); ?>"
			data-instant-search-pages="<?php echo esc_attr( $options['livesearch_pages'] ); ?>"
			data-instant-search-positioning="<?php echo esc_attr( strtolower( $options['livesearch_dropdown_position'] ) ); ?>"
			<?php

			if ( isset( $options['livesearch_pages_type'] ) && 'All' !== $options['livesearch_pages_type'] ) :

				?>
			data-instant-search-pages-type="<?php echo esc_attr( $options['livesearch_pages_type'] ); ?>"
				<?php
			endif;
			if ( isset( $options['livesearch_field_selector'] ) ) :
				?>
			data-instant-search="<?php echo esc_attr( $options['livesearch_field_selector'] ); ?>">
				<?php
			else :
				?>
				data-instant-search="#clerk-searchfield">
				<?php
			endif;
			?>
</span>
	<?php
endif;
?>
