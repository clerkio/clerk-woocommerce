<?php
/**
 * Plugin Name: Clerk
 * Plugin URI: https://clerk.io/
 * Description: Clerk.io Turns More Browsers Into Buyers
 * Version: 4.1.9
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

/**
 * Clerk_Search Class
 *
 * Clerk Module Core Class
 */
class Clerk_Search {

	/**
	 * Error and Warning Logger
	 *
	 * @var $logger Clerk_Logger
	 */
	protected $logger;

	/**
	 * Clerk_Search constructor.
	 */
	public function __construct() {
		$this->init_hooks();
		include_once __DIR__ . '/class-clerk-logger.php';
		include_once __DIR__ . '/clerk-multi-lang-helpers.php';
		if ( clerk_is_wpml_enabled() ) {
			do_action( 'wpml_multilingual_options', 'clerk_options' );
		}
		$this->logger = new Clerk_Logger();
	}

	/**
	 * Init hooks
	 */
	private function init_hooks() {
		add_filter( 'query_vars', array( $this, 'add_search_vars' ) );
		add_shortcode( 'clerk-search', array( $this, 'handle_shortcode' ) );
	}

	/**
	 * Add query var for searchterm
	 *
	 * @param mixed $vars Url arguments.
	 *
	 * @return array
	 */
	public function add_search_vars( $vars ) {

		try {

			$vars[] = 'searchterm';

			return $vars;

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR add_search_vars', array( 'error' => $e->getMessage() ) );

		}
	}

	/**
	 * Output clerk-search shortcode
	 *
	 * @param array|mix|void $atts Void attribute.
	 */
	public function handle_shortcode( $atts ) {

		$facets_attributes = '[';
		$facets_titles     = '{';
		$attributes        = array();

		$options = clerk_get_options();

		if ( $options['faceted_navigation_enabled'] ) {

			$facets_design             = isset( $options['faceted_navigation_design'] ) ? $options['faceted_navigation_design'] : false;
			$search_include_categories = isset( $options['search_include_categories'] ) ? $options['search_include_categories'] : false;
			$search_categories         = isset( $options['search_categories'] ) ? $options['search_categories'] : false;
			$search_include_pages      = isset( $options['search_include_pages'] ) ? $options['search_include_pages'] : false;
			$search_pages              = isset( $options['search_pages'] ) ? $options['search_pages'] : false;
			$search_pages_type         = isset( $options['search_pages_type'] ) ? $options['search_pages_type'] : false;

			$_attributes = json_decode( $options['faceted_navigation'] );
			$count       = 0;

			foreach ( $_attributes as $key => $_attribute ) {
				if ( $_attribute->checked ) {
					$attributes[] = $_attribute;
				}
			}

			/**
			 * Changed to use usort instead to fix sorting bug 22-07-2021 KKY
			 *
			 * @example
			 * foreach ($attributes as $key => $Sorted_Attribute) {
			 *
			 *      $sorted_attributes[$Sorted_Attribute->position] = $Sorted_Attribute;
			 *
			 * }
			 */

			$sorted_attributes = $attributes;

			usort(
				$sorted_attributes,
				function ( $a, $b ) {
					return $a->position <=> $b->position;
				}
			);

			foreach ( $sorted_attributes as $key => $attribute ) {

				++$count;

				if ( count( $attributes ) === $count ) {

					$facets_attributes .= '"' . $attribute->attribute . '"';
					$facets_titles     .= '"' . $attribute->attribute . '": "' . $attribute->title . '"';

				} else {

					$facets_attributes .= '"' . $attribute->attribute . '", ';
					$facets_titles     .= '"' . $attribute->attribute . '": "' . $attribute->title . '",';

				}
			}
		}

		$facets_attributes .= ']';
		$facets_titles     .= '}';

		try {

			$options = clerk_get_options();
			?>
			<span
			id="clerk-search"
			class="clerk"
			data-template="@<?php echo esc_attr( strtolower( str_replace( ' ', '-', $options['search_template'] ) ) ); ?>"
			data-target="#clerk-search-results"
			<?php
			if ( count( $attributes ) > 0 ) {
				echo 'data-facets-target="#clerk-search-filters"';
				echo "data-facets-attributes='" . esc_attr( $facets_attributes ) . "'";
				echo "data-facets-titles='" . esc_attr( $facets_titles ) . "'";
				echo "data-facets-design='" . esc_attr( $facets_design ) . "'";
			}
			if ( isset( $search_include_categories ) && $search_include_categories ) {
				echo "data-search-categories='" . esc_attr( $search_categories ) . "'";
			}
			if ( isset( $search_include_pages ) && $search_include_pages ) {
				echo "data-search-pages='" . esc_attr( $search_pages ) . "'";
				if ( 'All' !== $search_pages_type ) {
					echo "data-search-pages-type='" . esc_attr( $search_pages_type ) . "'";
				}
			}
			?>
			data-query="<?php echo esc_attr( get_query_var( 'searchterm' ) ); ?>">
			</span>
			<?php
			if ( count( $attributes ) > 0 ) {
				echo '<div id="clerk-search-page-wrap" style="display: flex;">';
				echo '<div id="clerk-search-filters"></div>';
			}
			?>
			<ul style="width: 100%;" id="clerk-search-results"></ul>
			<?php
			if ( count( $attributes ) > 0 ) {
				echo ' </div>';
			}
			?>
			</div>
			<div id="clerk-search-no-results" style="display: none; margin-left: 3em;"><h2><?php echo esc_attr( $options['search_no_results_text'] ); ?></h2></div>

			<script>

				var clerk_results = false;

				document.addEventListener('DOMContentLoaded', function(){
					Clerk('on', 'response', '#clerk-search', function(content, data){
						clerk_results = (data.product_data.length > 0) ? true : false;
						if(!clerk_results){
							document.querySelector('#clerk-search-no-results').style.display = 'initial';
						}
					});
				});

			</script>
			<?php

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR handle_shortcode', array( 'error' => $e->getMessage() ) );

		}
	}
}

new Clerk_Search();
