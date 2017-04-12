<?php

class Clerk_Widget_Search extends WP_Widget {
	/**
	 * Clerk_Widget_Search constructor.
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'                   => 'clerk widget_search',
			'description'                 => __( 'Clerk powered search form', 'clerk' ),
			'customize_selective_refresh' => true,
		);
		parent::__construct( 'clerk_search', __( 'Clerk Search', 'clerk' ), $widget_ops );
	}

	/**
	 * Render clerk search form
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance,
			$this->id_base );

		echo $args['before_widget'];
		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		// Use current theme search form if it exists
		get_clerk_search_form();

		echo $args['after_widget'];
	}
}