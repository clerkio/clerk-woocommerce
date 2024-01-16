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

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Clerk_Admin_Settings Class
 *
 * Clerk Module Core Class
 */
class Clerk_Admin_Settings
{

    /**
     * Error and Warning Logger
     *
     * @var $logger Clerk_Logger
     */
    protected $logger;
    /**
     * Clerk Module Version
     *
     * @var $version Version
     */
    protected $version;

    /**
     * Clerk_Admin_Settings constructor.
     */
    public function __construct()
    {
        require_once __DIR__ . '/class-clerk-logger.php';
        require_once __DIR__ . '/clerk-multi-lang-helpers.php';
        if (clerk_is_wpml_enabled()) {
            do_action('wpml_multilingual_options', 'clerk_options');
        }
        $this->init_hooks();
        $this->logger = new Clerk_Logger();
        $this->version = '4.1.6';
    }

    /**
     * Add actions
     */
    private function init_hooks()
    {

        add_action('admin_init', array($this, 'settings_init'));
        add_action('admin_menu', array($this, 'clerk_options_page'));
        add_action('admin_menu', array($this, 'load_jquery_ui'));
        add_action('admin_menu', array($this, 'load_admin_assets'));

    }

    /**
     * Load jQuery Lib and Styles
     */
    public function load_jquery_ui()
    {

        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_style('wp-jquery-ui-dialog');
    }

    /**
     * Load Custom Styles and Scripts
     */
    public function load_admin_assets()
    {

        wp_enqueue_style('clerk_admin_css', plugins_url('../assets/css/admin.css', __FILE__), array(), get_bloginfo('version'));
        wp_enqueue_script('clerk_admin_js', plugins_url('../assets/js/admin.js', __FILE__), array('jquery'), get_bloginfo('version'), true);

    }

    /**
     * Init settings
     */
    public function settings_init()
    {

        // register a new setting.
        $pll_languages = clerk_pll_languages_list();
        if ($pll_languages) {
            foreach ($pll_languages as $lang) {
                register_setting('clerk', 'clerk_options_' . $lang);
            }
        } else {
            register_setting('clerk', 'clerk_options');
        }


        $wpml_enabled = clerk_is_wpml_enabled();
        $pll_enabled = clerk_is_pll_enabled();

        if ($wpml_enabled) {
            $site_info = clerk_wpml_get_active_scope();
            $site_url = $site_info['url'];
        } elseif ($pll_enabled && false !== clerk_pll_current_language()) {
            $site_url = get_site_url() . '?lang=' . clerk_pll_current_language();
        } else {
            $site_url = get_site_url();
        }

        $options = clerk_get_options();

        // Add general section.
        add_settings_section(
            'clerk_section_general',
            __('General', 'clerk'),
            null,
            'clerk'
        );

        add_settings_field(
            'version',
            __('Plugin version', 'clerk'),
            array($this, 'add_version'),
            'clerk',
            'clerk_section_general',
            array(
                'label_for' => 'version',
            )
        );

        if ($wpml_enabled || ($pll_enabled && false !== clerk_pll_current_language() ) ) {
            add_settings_field(
                'multi_lang_info',
                __('Multi Language Scope', 'clerk'),
                array($this, 'add_wpml_info'),
                'clerk',
                'clerk_section_general',
                array(
                    'label_for' => 'multi_lang_info',
                )
            );
        }

        add_settings_field(
            'public_key',
            __('Public Key', 'clerk'),
            array($this, 'add_text_field'),
            'clerk',
            'clerk_section_general',
            array(
                'label_for' => 'public_key',
            )
        );

        add_settings_field(
            'private_key',
            __('Private Key', 'clerk'),
            array($this, 'add_text_field'),
            'clerk',
            'clerk_section_general',
            array(
                'label_for' => 'private_key',
            )
        );

        add_settings_field(
            'lang',
            __('Language', 'clerk'),
            array($this, 'add_language'),
            'clerk',
            'clerk_section_general',
            array(
                'label_for' => 'lang',
                'default' => 'auto',
            )
        );

        add_settings_field(
            'import_url',
            __('Import URL', 'clerk'),
            array($this, 'add_text_field'),
            'clerk',
            'clerk_section_general',
            array(
                'label_for' => 'import_url',
                'description' => 'Use this url to configure an importer from my.clerk.io',
                'readonly' => true,
                'value' => $site_url,
            )
        );

        // Add toggle to use legacy Authentication.
        add_settings_field(
            'legacy_auth_enabled',
            __('Legacy Authentication', 'clerk'),
            array($this, 'add_checkbox_field'),
            'clerk',
            'clerk_section_general',
            array(
                'label_for' => 'legacy_auth_enabled',
                'checked' => 0,
            )
        );

        // Add Customer sync section.
        add_settings_section(
            'clerk_section_customer_sync',
            __('Customer Sync', 'clerk'),
            null,
            'clerk'
        );

        add_settings_field(
            'customer_sync_enabled',
            __('Enabled', 'clerk'),
            array($this, 'add_checkbox_field'),
            'clerk',
            'clerk_section_customer_sync',
            array(
                'label_for' => 'customer_sync_enabled',
                'checked' => 0,
            )
        );

        add_settings_field(
            'customer_sync_customer_fields',
            __('Extra Customer Fields', 'clerk'),
            array($this, 'add_text_field'),
            'clerk',
            'clerk_section_customer_sync',
            array(
                'label_for' => 'customer_sync_customer_fields',
                'description' => 'A comma separated list of additional fields for customer to sync',
            )
        );

        // Add data sync section.
        add_settings_section(
            'clerk_section_datasync',
            __('Data Sync', 'clerk'),
            null,
            'clerk'
        );

        add_settings_field(
            'realtime_updates',
            __('Use Real-time Updates', 'clerk'),
            array($this, 'add_checkbox_field'),
            'clerk',
            'clerk_section_datasync',
            array(
                'label_for' => 'realtime_updates',
                'checked' => 0,
            )
        );

        add_settings_field(
            'include_pages',
            __('Include Pages', 'clerk'),
            array($this, 'add_checkbox_field'),
            'clerk',
            'clerk_section_datasync',
            array(
                'label_for' => 'include_pages',
                'checked' => 1,
            )
        );

        add_settings_field(
            'page_additional_fields',
            __('Page Additional Fields', 'clerk'),
            array($this, 'add_text_field'),
            'clerk',
            'clerk_section_datasync',
            array(
                'label_for' => 'page_additional_fields',
                'description' => 'A comma separated list of additional fields for pages to sync',
            )
        );

        add_settings_field(
            'page_additional_types',
            __('Page Additional Types', 'clerk'),
            array($this, 'add_text_field'),
            'clerk',
            'clerk_section_datasync',
            array(
                'label_for' => 'page_additional_types',
                'description' => 'A comma separated list of additional page types to sync',
            )
        );

        add_settings_field(
            'outofstock_products',
            __('Include Out Of Stock Products', 'clerk'),
            array($this, 'add_checkbox_field'),
            'clerk',
            'clerk_section_datasync',
            array(
                'label_for' => 'outofstock_products',
                'checked' => 0,
            )
        );

        add_settings_field(
            'collect_emails',
            __('Collect Emails', 'clerk'),
            array($this, 'add_checkbox_field'),
            'clerk',
            'clerk_section_datasync',
            array(
                'label_for' => 'collect_emails',
                'checked' => 1,
            )
        );
        add_settings_field(
            'collect_emails_signup_message',
            __('Collect Emails Signup Message', 'clerk'),
            array($this, 'add_text_field'),
            'clerk',
            'clerk_section_datasync',
            array(
                'label_for' => 'collect_emails_signup_message',
                'description' => 'Message for confirming email signup from Checkout Page',
            )
        );
        add_settings_field(
            'collect_baskets',
            __('Collect Baskets', 'clerk'),
            array($this, 'add_checkbox_field'),
            'clerk',
            'clerk_section_datasync',
            array(
                'label_for' => 'collect_baskets',
                'checked' => 1,
            )
        );

        add_settings_field(
            'additional_fields',
            __('Additional Fields', 'clerk'),
            array($this, 'add_text_field'),
            'clerk',
            'clerk_section_datasync',
            array(
                'label_for' => 'additional_fields',
                'description' => 'A comma separated list of additional fields to sync',
            )
        );

        add_settings_field(
            'additional_fields_trim',
            __('Strip/Trim Split Attributes', 'clerk'),
            array($this, 'add_checkbox_field'),
            'clerk',
            'clerk_section_datasync',
            array(
                'label_for' => 'additional_fields_trim',
                'checked' => 0,
                'description' => 'Check for Trim, uncheck for Strip',
            )
        );

        add_settings_field(
            'additional_fields_raw',
            __('Additional Fields Raw', 'clerk'),
            array($this, 'add_text_field'),
            'clerk',
            'clerk_section_datasync',
            array(
                'label_for' => 'additional_fields_raw',
                'description' => 'Attributes to exempt from sanitation and type casting',
            )
        );

        add_settings_field(
            'disable_order_synchronization',
            __('Disable Order Synchronization', 'clerk'),
            array($this, 'add_checkbox_field'),
            'clerk',
            'clerk_section_datasync',
            array(
                'label_for' => 'disable_order_synchronization',
                'checked' => 0,
            )
        );
        add_settings_field(
            'data_sync_image_size',
            __('Image Size', 'clerk'),
            array($this, 'add_image_size_dropdown'),
            'clerk',
            'clerk_section_datasync',
            array(
                'label_for' => 'data_sync_image_size',
            )
        );

        // Add livesearch section.
        add_settings_section(
            'clerk_section_livesearch',
            __('Live search Settings', 'clerk'),
            null,
            'clerk'
        );

        add_settings_field(
            'livesearch_enabled',
            __('Enabled', 'clerk'),
            array($this, 'add_checkbox_field'),
            'clerk',
            'clerk_section_livesearch',
            array(
                'label_for' => 'livesearch_enabled',
                'checked' => 0,
            )
        );

        add_settings_field(
            'livesearch_include_suggestions',
            __('Include Suggestions', 'clerk'),
            array($this, 'add_checkbox_field'),
            'clerk',
            'clerk_section_livesearch',
            array(
                'label_for' => 'livesearch_include_suggestions',
                'checked' => 0,
            )
        );

        add_settings_field(
            'livesearch_suggestions',
            __('Number of Suggestions', 'clerk'),
            array($this, 'add_1_10_dropdown'),
            'clerk',
            'clerk_section_livesearch',
            array(
                'label_for' => 'livesearch_suggestions',
                'default' => 5,
            )
        );

        add_settings_field(
            'livesearch_include_categories',
            __('Include Categories', 'clerk'),
            array($this, 'add_checkbox_field'),
            'clerk',
            'clerk_section_livesearch',
            array(
                'label_for' => 'livesearch_include_categories',
                'checked' => 0,
            )
        );

        add_settings_field(
            'livesearch_categories',
            __('Number of Categories', 'clerk'),
            array($this, 'add_1_10_dropdown'),
            'clerk',
            'clerk_section_livesearch',
            array(
                'label_for' => 'livesearch_categories',
                'default' => 5,
            )
        );

        add_settings_field(
            'livesearch_include_pages',
            __('Include Pages', 'clerk'),
            array($this, 'add_checkbox_field'),
            'clerk',
            'clerk_section_livesearch',
            array(
                'label_for' => 'livesearch_include_pages',
                'checked' => 0,
            )
        );

        add_settings_field(
            'livesearch_pages',
            __('Number of Pages', 'clerk'),
            array($this, 'add_1_10_dropdown'),
            'clerk',
            'clerk_section_livesearch',
            array(
                'label_for' => 'livesearch_pages',
                'default' => 5,
            )
        );

        add_settings_field(
            'livesearch_pages_type',
            __('Pages Type', 'clerk'),
            array($this, 'add_pages_type_dropdown'),
            'clerk',
            'clerk_section_livesearch',
            array(
                'label_for' => 'livesearch_pages_type',
            )
        );

        add_settings_field(
            'livesearch_dropdown_position',
            __('Dropdown Positioning', 'clerk'),
            array($this, 'add_dropdown_position'),
            'clerk',
            'clerk_section_livesearch',
            array(
                'label_for' => 'livesearch_dropdown_position',
            )
        );

        add_settings_field(
            'livesearch_field_selector',
            __('Live Search Input Selector', 'clerk'),
            array($this, 'add_text_field'),
            'clerk',
            'clerk_section_livesearch',
            array(
                'label_for' => 'livesearch_field_selector',
                'default' => '.search-field',
            )
        );

        add_settings_field(
            'livesearch_form_selector',
            __('Live Search Form Selector', 'clerk'),
            array($this, 'add_text_field'),
            'clerk',
            'clerk_section_livesearch',
            array(
                'label_for' => 'livesearch_form_selector',
                'default' => '[role="search"]',
            )
        );

        add_settings_field(
            'livesearch_template',
            __('Content', 'clerk'),
            array($this, 'add_text_field'),
            'clerk',
            'clerk_section_livesearch',
            array(
                'label_for' => 'livesearch_template',
            )
        );

        // Add search section.
        add_settings_section(
            'clerk_section_search',
            __('Search Settings', 'clerk'),
            null,
            'clerk'
        );

        add_settings_field(
            'search_enabled',
            __('Enabled', 'clerk'),
            array($this, 'add_checkbox_field'),
            'clerk',
            'clerk_section_search',
            array(
                'label_for' => 'search_enabled',
                'checked' => 0,
            )
        );

        add_settings_field(
            'search_page',
            __('Search Page', 'clerk'),
            array($this, 'add_page_dropdown'),
            'clerk',
            'clerk_section_search',
            array(
                'label_for' => 'search_page',
            )
        );

        add_settings_field(
            'search_include_categories',
            __('Include Categories', 'clerk'),
            array($this, 'add_checkbox_field'),
            'clerk',
            'clerk_section_search',
            array(
                'label_for' => 'search_include_categories',
                'checked' => 0,
            )
        );

        add_settings_field(
            'search_categories',
            __('Number of Categories', 'clerk'),
            array($this, 'add_1_10_dropdown'),
            'clerk',
            'clerk_section_search',
            array(
                'label_for' => 'search_categories',
                'default' => 5,
            )
        );

        add_settings_field(
            'search_include_pages',
            __('Include Pages', 'clerk'),
            array($this, 'add_checkbox_field'),
            'clerk',
            'clerk_section_search',
            array(
                'label_for' => 'search_include_pages',
                'checked' => 0,
            )
        );

        add_settings_field(
            'search_pages',
            __('Number of Pages', 'clerk'),
            array($this, 'add_1_10_dropdown'),
            'clerk',
            'clerk_section_search',
            array(
                'label_for' => 'search_pages',
                'default' => 5,
            )
        );

        add_settings_field(
            'search_pages_type',
            __('Pages Type', 'clerk'),
            array($this, 'add_pages_type_dropdown'),
            'clerk',
            'clerk_section_search',
            array(
                'label_for' => 'search_pages_type',
            )
        );

        add_settings_field(
            'search_template',
            __('Content', 'clerk'),
            array($this, 'add_text_field'),
            'clerk',
            'clerk_section_search',
            array(
                'label_for' => 'search_template',
            )
        );

        add_settings_field(
            'search_no_results_text',
            __('No results text', 'clerk'),
            array($this, 'add_text_field'),
            'clerk',
            'clerk_section_search',
            array(
                'label_for' => 'search_no_results_text',
            )
        );

        add_settings_field(
            'search_load_more_button',
            __('Load more button text', 'clerk'),
            array($this, 'add_text_field'),
            'clerk',
            'clerk_section_search',
            array(
                'label_for' => 'search_load_more_button',
            )
        );

        // Add faceted navigation.
        add_settings_section(
            'clerk_faceted_navigation',
            __('Faceted Navigation', 'clerk'),
            null,
            'clerk'
        );

        add_settings_field(
            'faceted_navigation_enabled',
            __('Enabled', 'clerk'),
            array($this, 'add_checkbox_field'),
            'clerk',
            'clerk_faceted_navigation',
            array(
                'label_for' => 'faceted_navigation_enabled',
                'checked' => 0,
            )
        );

        add_settings_field(
            'faceted_navigation_custom',
            __('Add Custom Attribute', 'clerk'),
            array($this, 'add_field_and_button'),
            'clerk',
            'clerk_faceted_navigation',
            array(
                'label_for' => 'faceted_navigation_custom',
            )
        );

        add_settings_field(
            'faceted_navigation',
            __('Facet Attributes', 'clerk'),
            array($this, 'get_facet_attributes'),
            'clerk',
            'clerk_faceted_navigation',
            array(
                'label_for' => 'faceted_navigation',
            )
        );

        add_settings_field(
            'faceted_navigation_design',
            __('Design', 'clerk'),
            array($this, 'add_text_field'),
            'clerk',
            'clerk_faceted_navigation',
            array(
                'label_for' => 'faceted_navigation_design',
            )
        );

        // Add powerstep section.
        add_settings_section(
            'clerk_section_powerstep',
            __('Powerstep Settings', 'clerk'),
            null,
            'clerk',
        );

        add_settings_field(
            'powerstep_enabled',
            __('Enabled', 'clerk'),
            array($this, 'add_checkbox_field'),
            'clerk',
            'clerk_section_powerstep',
            array(
                'label_for' => 'powerstep_enabled',
                'checked' => 0,
            )
        );

        add_settings_field(
            'powerstep_type',
            __('Powerstep Type', 'clerk'),
            array($this, 'add_powerstep_type_dropdown'),
            'clerk',
            'clerk_section_powerstep',
            array(
                'label_for' => 'powerstep_type',
            )
        );

        add_settings_field(
            'powerstep_page',
            __('Powerstep Page', 'clerk'),
            array($this, 'add_page_dropdown'),
            'clerk',
            'clerk_section_powerstep',
            array(
                'label_for' => 'powerstep_page',
            )
        );

        add_settings_field(
            'powerstep_templates',
            __('Contents', 'clerk'),
            array($this, 'add_text_field'),
            'clerk',
            'clerk_section_powerstep',
            array(
                'label_for' => 'powerstep_templates',
                'description' => 'A comma separated list of clerk templates to render',
                'value' => 'power-step-others-also-bought,power-step-visitor-complementary,power-step-popular,power-step-popular-on-sale',
            )
        );
        add_settings_field(
            'powerstep_excl_duplicates',
            __('Filter Duplicates', 'clerk'),
            array($this, 'add_checkbox_field'),
            'clerk',
            'clerk_section_powerstep',
            array(
                'label_for' => 'powerstep_excl_duplicates',
                'checked' => 0,
                'description' => 'Exclude duplicate products',
            )
        );

        add_settings_field(
            'powerstep_custom_text_enabled',
            __('Enable Custom Texts', 'clerk'),
            array($this, 'add_checkbox_field'),
            'clerk',
            'clerk_section_powerstep',
            array(
                'label_for' => 'powerstep_custom_text_enabled',
                'checked' => 0,
            )
        );

        add_settings_field(
            'powerstep_custom_text_back',
            __('Back Button', 'clerk'),
            array($this, 'add_text_field'),
            'clerk',
            'clerk_section_powerstep',
            array(
                'label_for' => 'powerstep_custom_text_back',
                'value' => 'Back to Shopping',
            )
        );

        add_settings_field(
            'powerstep_custom_text_cart',
            __('Cart Button', 'clerk'),
            array($this, 'add_text_field'),
            'clerk',
            'clerk_section_powerstep',
            array(
                'label_for' => 'powerstep_custom_text_cart',
                'value' => 'Continue to Cart',
            )
        );

        add_settings_field(
            'powerstep_custom_text_title',
            __('Product Title', 'clerk'),
            array($this, 'add_text_field'),
            'clerk',
            'clerk_section_powerstep',
            array(
                'label_for' => 'powerstep_custom_text_title',
                'value' => 'You added PRODUCT_NAME to your cart!',
                'description' => 'PRODUCT_NAME is dynamically replaced with product title.',
            )
        );

        add_settings_field(
            'powerstep_keep_atc_param',
            __('Keep Add To Cart Params', 'clerk'),
            array($this, 'add_checkbox_field'),
            'clerk',
            'clerk_section_powerstep',
            array(
                'label_for' => 'powerstep_keep_atc_param',
                'checked' => 0,
                'description' => 'Keep add to cart params when redirecting for powerstep.',
            )
        );
        // Add exit intent section.
        add_settings_section(
            'clerk_section_exit_intent',
            __('Exit Intent Settings', 'clerk'),
            null,
            'clerk'
        );

        add_settings_field(
            'exit_intent_enabled',
            __('Enabled', 'clerk'),
            array($this, 'add_checkbox_field'),
            'clerk',
            'clerk_section_exit_intent',
            array(
                'label_for' => 'exit_intent_enabled',
                'checked' => 0,
            )
        );

        add_settings_field(
            'exit_intent_template',
            __('Content', 'clerk'),
            array($this, 'add_text_field'),
            'clerk',
            'clerk_section_exit_intent',
            array(
                'label_for' => 'exit_intent_template',
                'value' => 'exit_intent',
            )
        );

        // Add category section.
        add_settings_section(
            'clerk_section_category',
            __('Category Settings', 'clerk'),
            null,
            'clerk'
        );

        add_settings_field(
            'category_enabled',
            __('Enabled', 'clerk'),
            array($this, 'add_checkbox_field'),
            'clerk',
            'clerk_section_category',
            array(
                'label_for' => 'category_enabled',
                'checked' => 0,
            )
        );

        add_settings_field(
            'category_content',
            __('Content', 'clerk'),
            array($this, 'add_text_field'),
            'clerk',
            'clerk_section_category',
            array(
                'label_for' => 'category_content',
                'value' => 'category-page-popular',
            )
        );
        add_settings_field(
            'category_excl_duplicates',
            __('Filter Duplicates', 'clerk'),
            array($this, 'add_checkbox_field'),
            'clerk',
            'clerk_section_category',
            array(
                'label_for' => 'category_excl_duplicates',
                'checked' => 0,
                'description' => 'Exclude duplicate products',
            )
        );

        add_settings_field(
            'clerk_category_shortcode',
            __('Category ID Shortcode', 'clerk'),
            array($this, 'add_text_field'),
            'clerk',
            'clerk_section_category',
            array(
                'label_for' => 'clerk_category_shortcode',
                'description' => 'Shortcode for printing the Category ID',
                'readonly' => true,
                'value' => '[clerk_category_id]',
            )
        );
        // Add product section.
        add_settings_section(
            'clerk_section_product',
            __('Product Settings', 'clerk'),
            null,
            'clerk'
        );

        add_settings_field(
            'product_enabled',
            __('Enabled', 'clerk'),
            array($this, 'add_checkbox_field'),
            'clerk',
            'clerk_section_product',
            array(
                'label_for' => 'product_enabled',
                'checked' => 0,
            )
        );

        add_settings_field(
            'product_content',
            __('Content', 'clerk'),
            array($this, 'add_text_field'),
            'clerk',
            'clerk_section_product',
            array(
                'label_for' => 'product_content',
                'value' => 'product-page-alternatives,product-page-others-also-bought',
            )
        );
        add_settings_field(
            'product_excl_duplicates',
            __('Filter Duplicates', 'clerk'),
            array($this, 'add_checkbox_field'),
            'clerk',
            'clerk_section_product',
            array(
                'label_for' => 'product_excl_duplicates',
                'checked' => 0,
                'description' => 'Exclude duplicate products',
            )
        );

        add_settings_field(
            'clerk_product_shortcode',
            __('Product ID Shortcode', 'clerk'),
            array($this, 'add_text_field'),
            'clerk',
            'clerk_section_product',
            array(
                'label_for' => 'clerk_product_shortcode',
                'description' => 'Shortcode for printing the Product ID',
                'readonly' => true,
                'value' => '[clerk_product_id]',
            )
        );

        // Add cart section.
        add_settings_section(
            'clerk_section_cart',
            __('Cart Settings', 'clerk'),
            null,
            'clerk'
        );

        add_settings_field(
            'cart_enabled',
            __('Enabled', 'clerk'),
            array($this, 'add_checkbox_field'),
            'clerk',
            'clerk_section_cart',
            array(
                'label_for' => 'cart_enabled',
                'checked' => 0,
            )
        );

        add_settings_field(
            'cart_content',
            __('Content', 'clerk'),
            array($this, 'add_text_field'),
            'clerk',
            'clerk_section_cart',
            array(
                'label_for' => 'cart_content',
                'value' => 'cart-others-also-bought',
            )
        );
        add_settings_field(
            'cart_excl_duplicates',
            __('Filter Duplicates', 'clerk'),
            array($this, 'add_checkbox_field'),
            'clerk',
            'clerk_section_cart',
            array(
                'label_for' => 'cart_excl_duplicates',
                'checked' => 0,
                'description' => 'Exclude duplicate products',
            )
        );
        add_settings_field(
            'clerk_cart_shortcode',
            __('Cart IDs Shortcode', 'clerk'),
            array($this, 'add_text_field'),
            'clerk',
            'clerk_section_cart',
            array(
                'label_for' => 'clerk_cart_shortcode',
                'description' => 'Shortcode for printing the Cart IDs',
                'readonly' => true,
                'value' => '[clerk_cart_ids]',
            )
        );

        // Add additional scripts section.
        add_settings_section(
            'clerk_section_additional_scripts',
            __('Additional Scripts', 'clerk'),
            null,
            'clerk'
        );

        add_settings_field(
            'clerk_additional_scripts_enabled',
            __('Enabled', 'clerk'),
            array($this, 'add_checkbox_field'),
            'clerk',
            'clerk_section_additional_scripts',
            array(
                'label_for' => 'clerk_additional_scripts_enabled',
                'checked' => 1,
            )
        );

        add_settings_field(
            'clerk_additional_scripts_content',
            __('JS Code', 'clerk'),
            array($this, 'add_text_area'),
            'clerk',
            'clerk_section_additional_scripts',
            array(
                'label_for' => 'clerk_additional_scripts_content',
                'description' => 'Scripts will be added to the site header.',
                'value' => '',
            )
        );

        // Add logging section.
        add_settings_section(
            'clerk_section_log',
            __('Logging', 'clerk'),
            null,
            'clerk'
        );

        add_settings_field(
            'log_enabled',
            __('Enabled', 'clerk'),
            array($this, 'add_checkbox_field'),
            'clerk',
            'clerk_section_log',
            array(
                'label_for' => 'log_enabled',
                'checked' => 1,
            )
        );

        add_settings_field(
            'log_level',
            __('Log Level', 'clerk'),
            array($this, 'add_log_level_dropdown'),
            'clerk',
            'clerk_section_log',
            array(
                'label_for' => 'log_level',
                'default' => 'Error + Warn',
            )
        );

        add_settings_field(
            'log_to',
            __('Log to', 'clerk'),
            array($this, 'add_log_to_dropdown'),
            'clerk',
            'clerk_section_log',
            array(
                'label_for' => 'log_to',
                'default' => 'my.clerk.io',
            )
        );

        if (isset($options['log_level']) && ('Error + Warn + Debug Mode' === $options['log_level'])) {

            add_settings_field(
                'log_warning',
                __('Log Debug Message', 'clerk'),
                array($this, 'add_debug_message'),
                'clerk',
                'clerk_section_log'
            );

        }

        add_settings_field(
            'extension_warning',
            __('Log Warning Message', 'clerk'),
            array($this, 'add_warning_message'),
            'clerk',
            'clerk_section_log'
        );

        add_settings_field(
            'debug_guide',
            __('Debug Guide', 'clerk'),
            array($this, 'add_debug_guide'),
            'clerk',
            'clerk_section_log'
        );

        add_settings_field(
            'debug_guide_change',
            __('Log Debug Change', 'clerk'),
            array($this, 'add_debug_change'),
            'clerk',
            'clerk_section_log',
            array(
                'label_for' => 'debug_guide_change',
                'checked' => 0,
            )
        );
    }

    /**
     * Add Clerk Plugin Version
     */
    public function add_version()
    {

        ?>
        <span>
			<p>v. <?php echo esc_textarea($this->version); ?></p>
		</span>
        <?php
    }

    /**
     * Add wpml multi language info
     */
    public function add_wpml_info()
    {
        $wpml_info = clerk_wpml_get_active_scope();

        if( clerk_is_pll_enabled() ) {
            $wpml_info['native_name'] = clerk_pll_current_language('name');
            $wpml_info['language_code'] = clerk_pll_current_language('slug');
        }
        ?>
        <span>
			<p>
				<?php echo esc_attr($wpml_info['native_name']); ?>  <code><?php echo esc_attr($wpml_info['language_code']); ?></code>
			</p>
		</span>
        <?php
    }

    /**
     * Add facet attribute input interface
     */
    public function add_field_and_button()
    {
        ?>
        <input
                class="text-box single-line"
                id="faceted_navigation_custom"
                name="faceted_navigation_custom"
                style="display: inline-block;"
                type="text"
                value="">
        <a type="button" onclick="add_facet()" title="Add" class="button button-primary">
            Add
        </a>
        <?php
    }

    /**
     * Check if string is valid JSON
     *
     * @param string $string JSON string from request.
     */
    public function is_valid_json($string)
    {
        return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() === JSON_ERROR_NONE);
    }

    /**
     * Get facet attributes
     *
     * @param array $args Array of params for request.
     */
    public function get_facet_attributes($args)
    {

        $_continue = true;
        $offset = 0;
        $page = 0;
        $public_key = '';

        $exclude_attributes = array('sku', 'list_price', 'description', 'url', 'image', 'type', 'id', 'name');
        $dynamic_attributes = array();
        $saved_attributes = array();
        $new_dynamic_attributes = array();

        $dynamic_attr_success = true;

        $options = clerk_get_options();
        $clerk_options_key = clerk_get_option_key();

        if (!empty($options['public_key'])) {
            $public_key = $options['public_key'];
        }

        if (!empty(str_replace(' ', '', $public_key))) {

            while ($_continue) {

                $check = true;

                $limit = 10;
                $orderby = 'date';
                $order = 'DESC';

                $products = clerk_get_products(
                    array(
                        'limit' => $limit,
                        'orderby' => $orderby,
                        'order' => $order,
                        'status' => array('publish'),
                        'paginate' => true,
                        'offset' => $offset,
                    )
                );

                if (!is_array($products)) {
                    $products = (array)$products;
                }

                if (is_array($products) && array_key_exists('products', $products)) {

                    foreach ($products['products'] as $product) {

                        if ($check) {

                            $id = $product->get_id();

                            $_endpoint = 'https://api.clerk.io/v2/product/attributes';

                            $data_string = wp_json_encode(
                                array(
                                    'key' => $public_key,
                                    'products' => array($id),
                                )
                            );

                            $_args = array(
                                'body' => $data_string,
                                'method' => 'POST',
                            );

                            $response = wp_remote_request($_endpoint, $_args);
                            if (is_wp_error($response)) {
                                $response = $response->get_error_message();
                            }
                            if ($this->is_valid_json($response['body'])) {

                                $response = json_decode($response['body']);

                            } else {

                                $response = $response['body'];

                            }

                            if (is_array($response)) {

                                $check = false;

                            }

                            if (is_array($response) && isset($response[0])) {

                                $response = $response[0];

                            }
                        }
                    }
                }

                if (isset($response)) {

                    foreach ($response as $attribute => $value) {

                        if ('status' === $attribute && 'error' === $value) {
                            $dynamic_attr_success = false;
                        }

                        if (!in_array($attribute, $exclude_attributes, true)) {

                            if (!empty($attribute)) {

                                $dynamic_attributes[$attribute] = $attribute;

                            }
                        }
                    }
                }

                if (0 !== count($dynamic_attributes) && 10 <= $offset) {

                    $_continue = false;

                } elseif (30 === $offset) {

                    $_continue = false;

                }

                $offset += 10;
            }

            $attributes_for_compare = array();
            $new_dynamic_attributes = array();

            if (!empty($options['faceted_navigation'])) {

                $saved_attributes = json_decode($options['faceted_navigation']);

            } else {

                $saved_attributes = array();

            }

            if (count($saved_attributes) > 0) {

                foreach ($saved_attributes as $attribute) {

                    $attributes_for_compare[] = $attribute->attribute;

                }
            }

            foreach ($dynamic_attributes as $dynamic_attribute) {

                if (!in_array($dynamic_attribute, $attributes_for_compare, true)) {

                    $new_dynamic_attributes[] = $dynamic_attribute;

                }
            }

            if (count($new_dynamic_attributes) > 0 && $dynamic_attr_success) {
                $commacounter = 0;
                $attribute_text = 'attributes';

                if (count($new_dynamic_attributes) === 1) {

                    $attribute_text = 'attribute';

                }

                ?>
                <div class="alert info">
                    <span class="closebtn">×</span>
                    <strong><?php echo esc_html(count($new_dynamic_attributes)); ?></strong>
                    new <?php echo esc_html($attribute_text); ?>

                    <?php
                    foreach ($new_dynamic_attributes as $attribute) {
                        ++$commacounter;
                        ?>
                        <strong><?php echo esc_html($attribute); ?></strong>
                        <?php
                        if ($commacounter < count($new_dynamic_attributes)) {
                            echo ', ';
                        } else {
                            echo ' detected.';
                        }
                    }
                    ?>
                </div>
                <?php
            }

            ?>
            <div class='facets_table'>

            <div class="facets_content">
            <div class="facets_content_title_wrapper">
                <div class="w-fit">Attribute</div>
                <div class="w-fit">Title</div>
                <div class="w-fit">Position</div>
                <div class="w-fit">Show</div>
            </div>
            <?php

        }

        if (is_countable($saved_attributes)) {

            $count = 0;
            foreach ($saved_attributes as $attribute) {

                ++$count;
                $checked = '';
                if ($attribute->checked) {

                    $checked = 'checked';

                }

                echo '
                <div class="facets_lines" data="' . esc_html($attribute->attribute) . '">
                    <div><input type="text" class="facets_facet" value="' . esc_html($attribute->attribute) . '" readonly></div>
                    <div><input type="text" class="facets_title" value="' . esc_html($attribute->title) . '"></div>
                    <div><input type="text" class="facets_position" value="' . esc_html($attribute->position) . '"></div>
                    <div><input class="faceted_enabled" type="checkbox" ' . esc_html($checked) . '><div class="close" onclick="remove_facet_line(\'' . esc_html($attribute->attribute) . '\')"></div></div>
                </div>
                ';

            }
        }

        if (is_countable($new_dynamic_attributes)) {
            $count = 0;
            foreach ($new_dynamic_attributes as $attribute) {

                ++$count;

                echo '
					<div class="facets_lines" data="' . esc_html($attribute) . '">
						<div><input type="text" class="facets_facet" value="' . esc_html($attribute) . '" readonly></div>
						<div><input type="text" class="facets_title" value=""></div>
						<div><input type="text" class="facets_position" value="' . esc_html($count) . '"></div>
						<div><input class="faceted_enabled" type="checkbox"><div class="close" onclick="remove_facet_line(\'' . esc_html($attribute) . '\')"></div></div>
					</div>
                    ';

            }
        }

        ?>
        <input
                name="<?php echo esc_attr($clerk_options_key); ?>[<?php echo esc_attr($args['label_for']); ?>]"
                id="faceted_navigation"
                type="hidden"/>
        </div>
        </div>
        <?php
    }

    /**
     * Get debug settings changes for UI
     *
     * @param array $args Array of params for request.
     */
    public function add_debug_change($args)
    {

        // Set defaults.
        if (1 === esc_attr($args['checked'])) {

            wp_parse_args(get_option('plugin_options'), array($args['label_for'] => ''));

        }

        // Get settings value.
        $options = clerk_get_options();
        $clerk_options_key = clerk_get_option_key();
        $label_for = (isset($options[$args['label_for']])) ? $options[$args['label_for']] : 0;
        ?>
        <input
                type="checkbox"
                style="display:none;"
                id="<?php echo esc_attr($args['label_for']); ?>"
                name="<?php echo esc_attr($clerk_options_key); ?>[<?php echo esc_attr($args['label_for']); ?>]"
                value="1" <?php checked('1', $label_for); ?>>
        <?php
    }

    /**
     * Build Search Page type selector
     *
     * @param array $args Array of params for request.
     */
    public function add_pages_type_dropdown($args)
    {
        $options = clerk_get_options();
        $clerk_options_key = clerk_get_option_key();

        $post_type_args = array('public' => true);
        $post_types = get_post_types($post_type_args);
        $types = array('All');
        if ($post_types) {
            $types = array_merge($types, $post_types);
        }
        ?>
        <select id="<?php echo esc_attr($args['label_for']); ?>"
                name="<?php echo esc_attr($clerk_options_key); ?>[<?php echo esc_attr($args['label_for']); ?>]">
            <?php foreach ($types as $type) : ?>
                <option value="<?php echo esc_attr($type); ?>"
                    <?php
                    if (isset($options[$args['label_for']]) && ($options[$args['label_for']] === $type)) :
                        ?>
                        selected<?php endif; ?>>
                    <?php
                    if (is_string($type)) {
                        echo esc_attr($type);
                    }
                    ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Build Instant Search position selector
     *
     * @param array $args Array of params for request.
     */
    public function add_dropdown_position($args)
    {

        $positions = array('Left', 'Center', 'Right', 'Below', 'Off');

        $options = clerk_get_options();
        $clerk_options_key = clerk_get_option_key();

        ?>
        <select id="<?php echo esc_attr($args['label_for']); ?>"
                name="<?php echo esc_attr($clerk_options_key); ?>[<?php echo esc_attr($args['label_for']); ?>]">
            <?php foreach ($positions as $position) : ?>
                <option value="<?php echo esc_attr($position); ?>"
                    <?php
                    if (isset($options[$args['label_for']]) && ($options[$args['label_for']] === $position)) :
                        ?>
                        selected<?php endif; ?>>
                    <?php
                    if (is_string($position)) {
                        echo esc_attr($position);
                    }
                    ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Build 1 - 10 dropdown selector
     *
     * @param array $args Array of params for request.
     */
    public function add_1_10_dropdown($args)
    {

        $numbers = array('1', '2', '3', '4', '5', '6', '7', '8', '9', '10');
        $options = clerk_get_options();
        $clerk_options_key = clerk_get_option_key();

        ?>
        <select id="<?php echo esc_attr($args['label_for']); ?>"
                name="<?php echo esc_attr($clerk_options_key); ?>[<?php echo esc_attr($args['label_for']); ?>]">
            <?php foreach ($numbers as $number) : ?>
                <option value="<?php echo esc_attr($number); ?>"
                    <?php
                    if (isset($options[$args['label_for']]) && ($options[$args['label_for']] === $number)) :
                        ?>
                        selected<?php endif; ?>>
                    <?php
                    if (is_string($number)) {
                        echo esc_attr($number);
                    }
                    ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Build Shop Language Selector
     *
     * @param array $args Array of params for request.
     */
    public function add_language($args)
    {

        $langs_auto = array(
            'da_DK' => 'Danish',
            'nl_NL' => 'Dutch',
            'en_US' => 'English',
            'en_GB' => 'English',
            'fi' => 'Finnish',
            'fr_FR' => 'French',
            'fr_BE' => 'French',
            'de_DE' => 'German',
            'hu_HU' => 'Hungarian',
            'it_IT' => 'Italian',
            'nn_NO' => 'Norwegian',
            'nb_NO' => 'Norwegian',
            'pt_PT' => 'Portuguese',
            'pt_BR' => 'Portuguese',
            'ro_RO' => 'Romanian',
            'ru_RU' => 'Russian',
            'ru_UA' => 'Russian',
            'es_ES' => 'Spanish',
            'sv_SE' => 'Swedish',
            'tr_TR' => 'Turkish',
        );

        if (clerk_is_wpml_enabled() && !clerk_wpml_all_scope_is_active()) {
            $scope_info = clerk_wpml_get_active_scope();
            $locale = $scope_info['default_locale'];
        } elseif (clerk_is_pll_enabled() && false !== clerk_pll_current_language()) {
            $locale = clerk_pll_current_language('locale');
        } else {
            $locale = get_locale();
        }

        if (isset($langs_auto[$locale])) {

            $auto_lang = array(
                'Label' => sprintf('Auto (%s)', $langs_auto[$locale]),
                'Value' => 'auto',
            );

        }

        // Get settings value.
        $langs = array(
            array(
                'Label' => 'Danish',
                'Value' => 'danish',
            ),
            array(
                'Label' => 'Dutch',
                'Value' => 'dutch',
            ),
            array(
                'Label' => 'English',
                'Value' => 'english',
            ),
            array(
                'Label' => 'Finnish',
                'Value' => 'finnish',
            ),
            array(
                'Label' => 'French',
                'Value' => 'french',
            ),
            array(
                'Label' => 'German',
                'Value' => 'german',
            ),
            array(
                'Label' => 'Hungarian',
                'Value' => 'hungarian',
            ),
            array(
                'Label' => 'Italian',
                'Value' => 'italian',
            ),
            array(
                'Label' => 'Norwegian',
                'Value' => 'norwegian',
            ),
            array(
                'Label' => 'Portuguese',
                'Value' => 'portuguese',
            ),
            array(
                'Label' => 'Romanian',
                'Value' => 'romanian',
            ),
            array(
                'Label' => 'Russian',
                'Value' => 'russian',
            ),
            array(
                'Label' => 'Spanish',
                'Value' => 'spanish',
            ),
            array(
                'Label' => 'Swedish',
                'Value' => 'swedish',
            ),
            array(
                'Label' => 'Turkish',
                'Value' => 'turkish',
            ),
        );

        if (isset($auto_lang)) {

            array_unshift($langs, $auto_lang);

        }

        $options = clerk_get_options();
        $clerk_options_key = clerk_get_option_key();

        ?>
        <select id="<?php echo esc_attr($args['label_for']); ?>"
                name="<?php echo esc_attr($clerk_options_key); ?>[<?php echo esc_attr($args['label_for']); ?>]">
            <?php foreach ($langs as $lang) : ?>
                <option value="<?php echo esc_attr($lang['Value']); ?>"
                    <?php
                    if (isset($options[$args['label_for']]) && ($options[$args['label_for']] === $lang['Value'])) :
                        ?>
                        selected<?php endif; ?>><?php echo esc_attr($lang['Label']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Build Debug Message
     */
    public function add_debug_message()
    {

        $options = clerk_get_options();

        if ('Error + Warn + Debug Mode' === $options['log_level']) {
            ?>
            <div class="notice notice-warning">
                <p><?php echo esc_attr('You are in Clerk log level all! This log level should not be enabled in production'); ?></p>
            </div>
            <?php

        }
    }

    /**
     * Build Warning Message
     */
    public function add_warning_message()
    {

        $plugin_mapping = array(
            'WPBakery Page Builder' => array(
                'Message' => 'This can cause, that our plugin have problems injecting our code on your shop .',
                'SupportLink' => 'https://clerk.io',
            ),
        );

        $plugins = get_plugins();

        foreach ($plugins as $plugin) {

            if (array_key_exists($plugin['Name'], $plugin_mapping)) {
                ?>
                <div class="notice notice-warning">
                    <p><?php echo esc_attr($plugin['Name'] . ' v' . $plugin['Version'] . ' is installed.'); ?></p>
                    <p><?php echo esc_attr(str_replace('%%PLUGIN%%', $plugin['Name'], $plugin_mapping[$plugin['Name']]['Message'])); ?></p>
                    <a href="<?php echo esc_attr($plugin_mapping[$plugin['Name']]['SupportLink']); ?>" target="_blank">
                        <p>Read about it here.</p></a>
                </div>
                <?php

            }
        }
    }

    /**
     * Build Debug Guide
     */
    public function add_debug_guide()
    {

        if (WP_DEBUG) {
            ?>
            <hr><p class="red"><strong>WordPress Debug Mode is enabled</strong></p>
            <ul>
                <li class="red">Caching is disabled.</li>
                <li class="red">Errors will be visible.</li>
                <li class="red">Clerk logger can catch all errors.</li>
                <li class="red">Remember to disable it again after use!</li>
                <li class="red">It's not best practice to have it enabled in production.</li>
                <li class="red">It's only recommended for at very short period af time for debug use.</li>
            </ul>
            <br>
            <p><strong>Step By Step Guide to disable debug mode</strong></p>
            <ol>
                <li>Please disable WordPress Debug Mode.</li>
                <li>Keep Clerk Logging enabled.</li>
                <li>Set the logging level to "Error + Warn".</li>
                <li>Keep Logging to "my.clerk.io".</li>
            </ol>
            <br><p><strong>HOW TO DISABLE DEBUG MODE:</strong></p>
            <p>Open wp_config.inc.php and usually at line 80 at the bottom of the file you will find</p>
            <p>define( 'WP_DEBUG', true );</p>
            <p>change it to:</p>
            <p>define( 'WP_DEBUG', false );</p>
            <hr>
            <?php
        } else {

            ?>
            <hr><strong>WordPress Debug Mode is disabled</strong>
            <p>When debug mode is disabled, WordPress hides a lot of errors and making it impossible for Clerk logger to
                detect and catch these errors.</p>
            <p>To make it possibel for Clerk logger to catch all errors you have to enable debug mode.</p>
            <p>Debug is not recommended in production in a longer period of time.</p>
            <br>
            <p><strong>When you store is in debug mode</strong></p>
            <ul>
                <li>Caching is disabled.</li>
                <li>Errors will be visible.</li>
                <li>Clerk logger can catch all errors.</li>
            </ul>
            <br><p><strong>Step By Step Guide to enable debug mode</strong></p>
            <ol>
                <li>Please enable WordPress Debug Mode.</li>
                <li>Enable Clerk Logging.</li>
                <li>Set the logging level to "Error + Warn + Debug Mode".</li>
                <li>Set Logging to "my.clerk.io".</li>
            </ol>
            <p>Thanks, that will make it a lot easier for our customer support to help you.</p>
            <br><p><strong>HOW TO ENABLE DEBUG MODE:</strong></p>
            <p>Open wp_config.inc.php and usually at line 80 at the bottom of the file you will find</p>
            <p>define( 'WP_DEBUG', false );</p>
            <p>change it to:</p>
            <p>define( 'WP_DEBUG', true );</p>
            <hr>
            <?php

        }
    }

    /**
     * Build Logger View
     */
    public function add_logger_view()
    {

        echo('<script>' .
            '(function () {' .
            '$.ajax({' .
            'url: "' . esc_url_raw(plugin_dir_url(__DIR__)) . 'clerk_log.log", success: function (data) {' .
            'document.getElementById("logger_view").innerHTML = data;' .
            '},' .
            '});' .
            'setTimeout(arguments.callee, 5000);' .
            '})();' .
            '</script>' .
            '<div id="logger_view"' .
            'style="background: black;color: white;padding: 20px; white-space:pre-wrap; overflow: scroll; height: 300px"></div>');
    }

    /**
     * Add text field
     *
     * @param array $args Request params for content.
     */
    public function add_text_field($args)
    {
        // Get settings value.
        $options = clerk_get_options();
        $clerk_options_key = clerk_get_option_key();

        if (isset($options[$args['label_for']])) {

            $value = $options[$args['label_for']];

        } elseif (isset($args['value'])) {

            $value = $args['value'];

        } else {

            $value = '';
        }
        ?>

        <input
                type="text"
                id="<?php echo esc_attr($args['label_for']); ?>"
                name="<?php echo esc_attr($clerk_options_key); ?>[<?php echo esc_attr($args['label_for']); ?>]"
                value="<?php echo esc_html($value); ?>"
            <?php
            if (isset($args['readonly'])) :
                ?>
                readonly<?php endif; ?>>
        <?php
        if (isset($args['description'])){
            echo '<p class="description" id="' . esc_attr($args['label_for']) . '-description">' . esc_attr($args['description']) . '</p>';
        }

    }

    /**
     * Add checkbox field
     *
     * @param array $args Request params for content.
     */
    public function add_checkbox_field($args)
    {
        // Set defaults.
        if (1 !== esc_attr($args['checked'])) {

            wp_parse_args(get_option('plugin_options'), array($args['label_for'] => ''));

        }

        // Get settings value.
        $options = clerk_get_options();

        $clerk_options_key = clerk_get_option_key();
        if (isset($options[$args['label_for']])) {

            $value = $options[$args['label_for']];

        } else {

            $value = 0;

        }

        ?>
        <input
                type="checkbox"
                id="<?php echo esc_attr($args['label_for']); ?>"
                name="<?php echo esc_attr($clerk_options_key); ?>[<?php echo esc_attr($args['label_for']); ?>]"
                value="1" <?php checked('1', $value); ?>>
        <?php
        if (isset($args['description'])) {
            echo '<small>' . esc_attr($args['description']) . '</small>';
        }
    }

    /**
     * Add text multi-line area
     *
     * @param array $args Request params for content.
     */
    public function add_text_area($args)
    {
        // Get settings value.
        $options = clerk_get_options();

        $clerk_options_key = clerk_get_option_key();
        if (isset($options[$args['label_for']])) {

            $value = $options[$args['label_for']];

        } elseif (isset($args['value'])) {

            $value = $args['value'];
        } else {

            $value = '';
        }
        ?>
        <textarea
                id="<?php echo esc_attr($args['label_for']); ?>"
                rows="5"
                cols="50"
                name="<?php echo esc_attr($clerk_options_key); ?>[<?php echo esc_attr($args['label_for']); ?>]"
                value="<?php echo esc_attr($value); ?>"><?php echo esc_attr(trim($value)); ?></textarea>
        <?php
        if (isset($args['description'])){
            echo '<p class="description" id="' . esc_attr($args['label_for']) . '-description">' . esc_attr($args['description']) . '</p>';
        }
    }

    /**
     * Add page dropdown
     *
     * @param array $args Request params for content.
     */
    public function add_page_dropdown($args)
    {
        // Get settings value.
        $options = clerk_get_options();
        $clerk_options_key = clerk_get_option_key();
        $label_for = is_string($args['label_for']) || is_array($args['label_for']) ? $args['label_for'] : array();
        $selection = array_key_exists($label_for, $options) ? $options[$label_for] : '';
        $selection = empty($selection) ? '' : $selection;
        wp_dropdown_pages(
            array(
                'selected' => esc_attr($selection),
                'name' => sprintf($clerk_options_key . '[%s]', esc_attr($label_for)),
            )
        );
    }

    /**
     * Add page dropdown
     *
     * @param array $args Request params for content.
     */
    public function add_image_size_dropdown($args)
    {
        // Get settings value.
        $options = clerk_get_options();
        $sizes = get_intermediate_image_sizes();

        $clerk_options_key = clerk_get_option_key();
        ?>
        <select id="<?php echo esc_attr($args['label_for']); ?>"
                name="<?php echo esc_attr($clerk_options_key); ?>[<?php echo esc_attr($args['label_for']); ?>]">
            <?php foreach ($sizes as $k => $size) : ?>
                <option value="<?php echo esc_attr($size); ?>"
                    <?php
                    if (isset($options['data_sync_image_size']) && ($size === $options['data_sync_image_size'])) :
                        ?>
                        selected<?php endif; ?>><?php echo esc_attr($size); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Add dropdown for powerstep type
     *
     * @param array $args Request params for content.
     */
    public function add_powerstep_type_dropdown($args)
    {
        // Get settings value.
        $options = clerk_get_options();

        $clerk_options_key = clerk_get_option_key();
        ?>
        <select id="<?php echo esc_attr($args['label_for']); ?>"
                name="<?php echo esc_attr($clerk_options_key); ?>[<?php echo esc_attr($args['label_for']); ?>]">
            <?php foreach (array(Clerk_Powerstep::TYPE_PAGE, Clerk_Powerstep::TYPE_POPUP) as $type) : ?>
                <option value="<?php echo esc_attr($type); ?>"
                    <?php
                    if (isset($options['powerstep_type']) && ($options['powerstep_type'] === $type)) :
                        ?>
                        selected<?php endif; ?>><?php echo esc_attr($type); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Add log level dropdown
     *
     * @param array $args Request params for content.
     */
    public function add_log_level_dropdown($args)
    {
        // Get settings value.
        $options = clerk_get_options();
        $clerk_options_key = clerk_get_option_key();
        wp_parse_args($options, array($args['label_for'] => $args['default']));

        ?>
        <select id="<?php echo esc_attr($args['label_for']); ?>"
                name="<?php echo esc_attr($clerk_options_key); ?>[<?php echo esc_attr($args['label_for']); ?>]">
            <?php foreach (array('Error + Warn', 'Only Error', 'Error + Warn + Debug Mode') as $level) : ?>
                <option value="<?php echo esc_attr($level); ?>"
                    <?php
                    if (isset($options['log_level']) && ($options['log_level'] === $level)) :
                        ?>
                        selected<?php endif; ?>><?php echo esc_attr($level); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Add log destination dropdown
     *
     * @param array $args Request params for content.
     */
    public function add_log_to_dropdown($args)
    {

        echo('<div id="clerk-dialog" class="hidden" style="max-width:800px">' .
            '</div>');
        // Get settings value.
        $options = clerk_get_options();
        $clerk_options_key = clerk_get_option_key();

        wp_parse_args( $options, array($args['label_for'] => $args['default']));
        ?>
        <select id="<?php echo esc_attr($args['label_for']); ?>"
                name="<?php echo esc_attr($clerk_options_key); ?>[<?php echo esc_attr($args['label_for']); ?>]">
            <?php foreach (array('my.clerk.io') as $to) : ?>
                <option value="<?php echo esc_attr($to); ?>"
                    <?php
                    if (isset($options['log_to']) && ($options['log_to'] === $to)) :
                        ?>
                        selected<?php endif; ?>><?php echo esc_attr($to); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Create options page
     */
    public function clerk_options_page()
    {
        // Add top level menu page.
        add_menu_page(
            __('Clerk', 'clerk'),
            __('Clerk', 'clerk'),
            'manage_options',
            'clerk',
            array($this, 'clerk_options_page_html'),
            plugin_dir_url(CLERK_PLUGIN_FILE) . 'assets/img/clerk.png'
        );

        add_submenu_page(
            'clerk',
            '',
            __('Clerk Settings', 'clerk'),
            'manage_options',
            'clerk',
            array(
                $this,
                'clerk_options_page_html',
            )
        );
    }

    /**
     * Create options page html
     */
    public function clerk_options_page_html()
    {
        // check user capabilities.
        if (!current_user_can('manage_options')) {
            return;
        }

        // add error/update messages.

        // check if the user have submitted the settings.
        // WordPress will add the "settings-updated" $_GET parameter to the url.
        if (null !== filter_input(INPUT_GET, 'settings-updated')) {
            delete_transient('clerk_api_contents');
            // add settings saved message with the class of "updated".
            add_settings_error('wporg_messages', 'wporg_message', __('Settings Saved', 'wporg'), 'updated');
        }

        // show error/update messages.
        settings_errors('wporg_messages');

        $language_info = wp_json_encode(clerk_wpml_get_active_scope());
        $is_pll = clerk_is_pll_enabled();
        $lang_info_json = '';

        if( $is_pll ){
            $langs = clerk_pll_languages_list();
            $cl = clerk_pll_current_language();
            $lang_settings = [
                'languages' => [],
                'current_lang' => $cl
            ];
            foreach ($langs as $lang){
                if($lang === $cl){
                    continue;
                }
                $lang_options = clerk_get_options( $lang );
                $lang_settings[$lang] = $lang_options;
                $lang_settings['languages'][] = $lang;
            }
            $lang_info_json = wp_json_encode($lang_settings);
        }
        $site_url_test = get_site_url() . '?lang=' . clerk_pll_current_language();
        ?>
        <div class="wrap">
            <div id="clerkFloatingSaveBtn"
                 onclick="clerk_submit_admin_form();"><?php echo esc_html(__('Save Settings', 'clerk')); ?></div>
            <h1>
                <img id="clerkLogoHeader"
                     src="<?php echo esc_html(plugin_dir_url(CLERK_PLUGIN_FILE) . 'assets/img/clerk.png'); ?>" alt="Clerk Logo">
                <span><?php echo esc_html(get_admin_page_title()); ?></span>
            </h1>

            <?php print_r( $site_url_test ); ?>
            <form id="clerkAdminForm" action="options.php" method="post">
                <div id="multi-lang-data"><?php echo esc_html($language_info); ?></div>
                <div id="hidden-lang-data" class="clerk_hidden" data-is-pll="<?php echo $is_pll; ?>"><?php echo esc_html($lang_info_json); ?></div>
                <?php
                // output security fields for the registered setting "wporg".
                settings_fields('clerk');
                // output setting sections and their fields.
                // (sections are registered for "wporg", each field is registered to a specific section).
                do_settings_sections('clerk');
                // output save settings button.
                submit_button('Save Settings');
                ?>
            </form>
        </div>
        <?php
    }
}

new Clerk_Admin_Settings();
