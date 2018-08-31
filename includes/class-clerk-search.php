<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Clerk_Search {
	/**
	 * Clerk_Search constructor.
	 */
	public function __construct() {
		$this->initHooks();
	}

	/**
	 * Init hooks
	 */
	private function initHooks() {
		add_filter( 'query_vars', [ $this, 'add_search_vars' ] );
		add_shortcode( 'clerk-search', [ $this, 'handle_shortcode' ] );
	}

	/**
	 * Add query var for searchterm
	 *
	 * @param $vars
	 *
	 * @return array
	 */
	public function add_search_vars( $vars ) {
		$vars[] = 'searchterm';

		return $vars;
	}

	/**
	 * Output clerk-search shortcode
	 *
	 * @param $atts
	 */
	public function handle_shortcode( $atts ) {
		$options = get_option( 'clerk_options' );
		?>
        <span id="clerk-search"
              class="clerk"
              data-template="@<?php echo esc_attr( strtolower( str_replace( ' ', '-', $options['search_template'] ) ) ); ?>"
              data-query="<?php echo esc_attr( get_query_var( 'searchterm' ) ); ?>">
		</span>
		<?php
	}
}

new Clerk_Search();