<?php
/**
 * Plugin Name: Clerk
 * Plugin URI: https://clerk.io/
 * Description: Clerk.io Turns More Browsers Into Buyers
 * Version: 4.1.2
 * Author: Clerk.io
 * Author URI: https://clerk.io
 *
 * Text Domain: clerk
 * Domain Path: /i18n/languages/
 * License: MIT
 *
 * @package clerkio/clerk-woocommerce
 */

if ( ! function_exists( 'clerk_is_wpml_enabled' ) ) {
	/**
	 * Check whether WPML is installed and loaded
	 */
	function clerk_is_wpml_enabled() {

		if ( has_action('wpml_setting', false) && has_action('wpml_loaded', false) ) {
			return apply_filters( 'wpml_setting', false, 'setup_complete' );
		}
		return false;

	}
}

if ( ! function_exists( 'clerk_wpml_get_languages' ) ) {
	/**
	 * Get WPML Active Languages
	 */
	function clerk_wpml_get_languages() {

		if ( ! has_action( 'wpml_active_languages', false ) ) {
			return array();
		} else {
			return apply_filters( 'wpml_active_languages', NULL, array ('skip_missing' => 0 ) );
		}

	}
}


if ( ! function_exists( 'clerk_wpml_get_active_scope' ) ) {
	/**
	 * Get WPML Active Language
	 */
	function clerk_wpml_get_active_scope() {
		$scope_found = false;
		if( clerk_is_wpml_enabled() ) {
			$languages = clerk_wpml_get_languages();
			if ( ! empty( $languages ) ) {
				foreach ( $languages as $lang_iso => $lang_info ) {
					if ( 1 == $lang_info['active']) {
						$scope_found = true;
						return $lang_info;
					}
				}
			}
		} else {
			$locale = get_locale();
			$lang_iso = explode('_', str_replace('-', '_', $locale))[0];
			$site_url = get_site_url();
			return [
				'id' => 0,
				'active' => 1,
				'default_locale' => $locale,
				'missing' => 0,
				'translated_name' => 'Default',
				'native_name' => 'Default',
				'language_code' => $lang_iso,
				'country_flag_url' => '',
				'url' => $site_url
			];
		}
		if(!$scope_found){
			$locale = get_locale();
			$lang_iso = explode('_', str_replace('-', '_', $locale))[0];
			$site_url = get_site_url();
			return [
				'id' => 0,
				'active' => 1,
				'default_locale' => $locale,
				'missing' => 0,
				'translated_name' => 'All Languages',
				'native_name' => 'All Languages',
				'language_code' => $lang_iso,
				'country_flag_url' => '',
				'url' => $site_url
			];
		}

	}
}