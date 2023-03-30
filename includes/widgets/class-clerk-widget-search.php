<?php
/**
 * Plugin Name: Clerk
 * Plugin URI: https://clerk.io/
 * Description: Clerk.io Turns More Browsers Into Buyers
 * Version: 4.0.7
 * Author: Clerk.io
 * Author URI: https://clerk.io
 *
 * Text Domain: clerk
 * Domain Path: /i18n/languages/
 * License: MIT
 *
 * @package clerkio/clerk-woocommerce
 */

/**
 * Clerk_Sales_Tracking Class
 *
 * Clerk Module Core Class
 */
class Clerk_Widget_Search extends WP_Widget {

	/**
	 * Clerk_Widget_Search constructor.
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'                   => 'widget_search widget_clerk',
			'description'                 => __( 'Clerk powered search form', 'clerk' ),
			'customize_selective_refresh' => true,
		);
		parent::__construct( 'clerk_search', __( 'Clerk Search', 'clerk' ), $widget_ops );
	}

	/**
	 * Render clerk search form
	 *
	 * @param array $args Arguments.
	 * @param array $instance Instance.
	 */
	public function widget( $args, $instance ) {
		$title = apply_filters(
			'widget_title',
			empty( $instance['title'] ) ? '' : wp_kses_post( $instance['title'] ),
			$instance,
			$this->id_base
		);

		echo wp_kses_post( $args['before_widget'] );
		if ( $title ) {
			echo wp_kses_post( $args['before_title'] . $title . $args['after_title'] );
		}

		// Use current theme search form if it exists.
		get_clerk_search_form();

		echo wp_kses_post( $args['after_widget'] );
	}
}
