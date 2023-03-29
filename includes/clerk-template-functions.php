<?php
/**
 * Plugin Name: Clerk
 * Plugin URI: https://clerk.io/
 * Description: Clerk.io Turns More Browsers Into Buyers
 * Version: 4.0.6
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
 * Locate template file
 *
 * @param string $template_name Name.
 * @param string $template_path Path.
 * @param string $default_path Fallback.
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
		$default_path = plugin_dir_path( __FILE__ ) . '../templates/'; // Path to the template folder.
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

/**
 * Locate template
 *
 * @param string $template_name Name.
 * @param array  $args Arguments.
 * @param string $template_path Path.
 * @param string $default_path Fallback.
 *
 * @return object
 */
function clerk_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	if ( is_array( $args ) && isset( $args ) ) {
		foreach ( $args as $key => $value ) {
			$$key = $value;
		}
	}

	$template_file = clerk_locate_template( $template_name, $template_path, $default_path );

	if ( ! file_exists( $template_file ) ) {
		/* translators: %s file name */
		_doing_it_wrong( __FUNCTION__, sprintf( esc_html( __( '%s does not exist.', 'clerk' ) ), esc_url_raw( $template_file ) ), '1.0.0' );

		return;
	}

	include $template_file;
}

if ( ! function_exists( 'get_product_search_form' ) ) {
	/**
	 * Return searchform template
	 *
	 * @return html
	 */
	function get_clerk_search_form() {
		return clerk_get_template( 'clerk-searchform.php' );
	}
}

if ( ! function_exists( 'get_clerk_powerstep' ) ) {
	/**
	 * Return powerstep page template
	 *
	 * @param array $product Product object.
	 *
	 * @return html
	 */
	function get_clerk_powerstep( $product ) {
		return clerk_get_template( 'clerk-powerstep.php', array( 'product' => $product ) );
	}
}

if ( ! function_exists( 'get_clerk_powerstep_popup' ) ) {
	/**
	 * Return powerstep popup template
	 *
	 * @param array $product Product object.
	 *
	 * @return html
	 */
	function get_clerk_powerstep_popup( $product ) {
		return clerk_get_template( 'clerk-powerstep-popup.php', array( 'product' => $product ) );
	}
}


if ( ! function_exists( 'get_powerstep_templates' ) ) {
	/**
	 * Return powerstep popup clerk content strings
	 *
	 * @return string
	 */
	function get_powerstep_templates() {
		$options = get_option( 'clerk_options' );

		if ( ! $options['powerstep_templates'] ) {
			return array();
		}

		$templates = explode( ',', $options['powerstep_templates'] );

		foreach ( $templates as $key => $template ) {

			$templates[ $key ] = str_replace( ' ', '', $template );

		}

		$templates = array_map( 'trim', $templates );

		return $templates;
	}
}
