<?php
/**
 * Plugin Name: Clerk
 * Plugin URI: https://clerk.io/
 * Description: Clerk.io Turns More Browsers Into Buyers
 * Version: 4.1.6
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
 * Check whether WPML is installed and loaded
 */
function clerk_is_wpml_enabled()
{

    if (has_action('wpml_setting', false) && has_action('wpml_loaded', false)) {
        return apply_filters('wpml_setting', false, 'setup_complete');
    }

    return false;

}

/**
 * @return bool
 */
function clerk_is_pll_enabled()
{
    if (function_exists('pll_languages_list')){
        return true;
    }
    return false;
}

/**
 * @param $return_type
 * @return false
 */
function clerk_pll_current_language($return_type=null){
    if(!$return_type){
        $return_type = 'slug';
    }
    if (function_exists('pll_current_language')){
        return pll_current_language($return_type);
    }
    return false;
}

function clerk_pll_languages_list()
{
    if ( clerk_is_pll_enabled() ) {
        return pll_languages_list();
    }
    return false;
}

function clerk_update_options($options, $lang = null){
    if($lang){
        update_option( 'clerk_options_' . $lang, $options );
    } else {
        update_option( 'clerk_options', $options );
    }
}

function clerk_get_options( $lang_iso = null )
{
    $options = get_option( clerk_get_option_key( $lang_iso ) );
    if(!is_array($options)){
        $options = array();
    }
    return $options;
}


function clerk_get_option_key( $lang_iso = null )
{
    $current_lang = clerk_pll_current_language();
    if($current_lang && ! $lang_iso){
        return 'clerk_options_' . $current_lang;
    } elseif ($lang_iso) {
        return 'clerk_options_' . $lang_iso;
    }
    return 'clerk_options';
}


/**
 * Check if all scope WPML all languages admin scope is active
 *
 * @return bool
 */
function clerk_wpml_all_scope_is_active()
{
    if (!clerk_is_wpml_enabled()) {
        return false;
    }
    $langs_active = clerk_wpml_get_languages();
    foreach ($langs_active as $language) {
        if ($language['active']) {
            return false;
        }
    }
    return true;
}

/**
 * Get WPML Active Languages
 */
function clerk_wpml_get_languages()
{

    if (!has_action('wpml_active_languages', false)) {
        return array();
    } else {
        return apply_filters('wpml_active_languages', null, array('skip_missing' => 0));
    }

}

/**
 * Get WPML Active Language
 */
function clerk_wpml_get_active_scope()
{
    $locale = get_locale();
    $lang_iso = explode('_', str_replace('-', '_', $locale))[0];
    $site_url = get_site_url();
    $result = array(
        'id' => 0,
        'active' => 1,
        'default_locale' => $locale,
        'missing' => 0,
        'translated_name' => 'Default',
        'tag' => $lang_iso,
        'native_name' => 'Default',
        'language_code' => $lang_iso,
        'country_flag_url' => '',
        'url' => $site_url,
    );
    if (!clerk_is_wpml_enabled()) {
        return $result;
    }
    $languages = clerk_wpml_get_languages();
    if (!empty($languages)) {
        foreach ($languages as $lang_iso => $lang_info) {
            if ($lang_info['active']) {
                $result = $lang_info;
            }
        }
    }
    return $result;

}

/**
 * Get Equivalent product_id from other language
 *
 * @param int $product_id Product id.
 * @param string $lang_code Language 2 letter code.
 *
 * @return int | void
 */
function clerk_wpml_get_product_id_equal($product_id, $lang_code)
{
    if (!clerk_is_wpml_enabled() || !has_action('wpml_object_id')) {
        return $product_id;
    }
    return apply_filters('wpml_object_id', $product_id, 'product', false, $lang_code);
}

/**
 * Get Equivalent product_id from other language
 *
 * @param int $product_id Product id.
 *
 * @return array | void
 */
function clerk_wpml_get_product_lang($product_id)
{
    if (!clerk_is_wpml_enabled()) {
        return;
    }
    return apply_filters('wpml_post_language_details', null, $product_id);
}

