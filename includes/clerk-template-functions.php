<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Locate template
 *
 * @param $template_name
 * @param string $template_path
 * @param string $default_path
 *
 * @return string
 */
function clerk_locate_template( $template_name, $template_path = '', $default_path = '' ) {
	// Set variable to search in clerk folder of theme.
	if ( ! $template_path ) {
		$template_path = 'clerk/';
	}

	// Set default plugin templates path.
	if ( ! $default_path ) {
		$default_path = plugin_dir_path( __FILE__ ) . '../templates/'; // Path to the template folder
	}

	// Search template file in theme folder.
	$template = locate_template(
		array(
			trailingslashit( $template_path ) . $template_name,
			$template_name,
		)
	);

	// Get plugins template file.
	if ( ! $template ) {
		$template = $default_path . $template_name;
	}

	return apply_filters( 'clerk_locate_template', $template, $template_name, $template_path, $default_path );
}

function clerk_get_template( $template_name, $args = array(), $tempate_path = '', $default_path = '' ) {
	if ( is_array( $args ) && isset( $args ) ) {
		extract( $args );
	}

	$template_file = clerk_locate_template( $template_name, $tempate_path, $default_path );

	if ( ! file_exists( $template_file ) ) {
		_doing_it_wrong( __FUNCTION__, sprintf( __('%s does not exist.', 'clerk'), $template_file ), '1.0.0' );

		return;
	}

	include $template_file;
}

if ( ! function_exists( 'get_product_search_form' ) ) {
	function get_clerk_search_form() {
		return clerk_get_template('clerk-searchform.php');
	}
}

if ( ! function_exists( 'get_clerk_powerstep' ) ) {
	function get_clerk_powerstep($product) {
		return clerk_get_template('clerk-powerstep.php', ['product' => $product]);
	}
}

if ( ! function_exists( 'get_clerk_powerstep_popup' ) ) {
    function get_clerk_powerstep_popup($product) {
        return clerk_get_template('clerk-powerstep-popup.php', ['product' => $product]);
    }
}


if ( ! function_exists( 'get_powerstep_templates' ) ) {
	function get_powerstep_templates() {
		$options = get_option( 'clerk_options' );

		if ( ! $options['powerstep_templates'] ) {
			return [];
		}

		$templates = explode( ',', $options['powerstep_templates'] );
		$templates = array_map('trim', $templates);

		return $templates;
	}
}