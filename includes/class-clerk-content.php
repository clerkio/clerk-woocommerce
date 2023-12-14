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

/**
 * Clerk_Content Class
 *
 * Clerk Module Core Class
 */
class Clerk_Content
{

    /**
     * Error and Warning Logger
     *
     * @var $logger Clerk_Logger
     */
    protected Clerk_Logger $logger;

    /**
     * Clerk_Content constructor.
     */
    public function __construct()
    {
        add_action('woocommerce_archive_description', array($this, 'clerk_woocommerce_archive_description'), 99);
        add_action('woocommerce_after_cart', array($this, 'clerk_woocommerce_after_cart_table'), 99);
        add_filter('wc_get_template', array($this, 'clerk_wc_get_template'), 99, 2);
        include_once __DIR__ . '/class-clerk-logger.php';
        include_once __DIR__ . '/clerk-multi-lang-helpers.php';
        if (clerk_is_wpml_enabled()) {
            /**
             * Patches the clerk_options array to be language specific.
             * @since 4.1.3
             */
            do_action('wpml_multilingual_options', 'clerk_options');
        }
        $this->logger = new Clerk_Logger();
    }

    /**
     * Add content to category if enabled
     */
    public function clerk_woocommerce_archive_description(): void
    {

        try {
            $options = get_option('clerk_options');
            $category = get_queried_object();
            $enabled = isset($options['category_enabled']) && $options['category_enabled'] && property_exists($category, 'term_id');

            if (!$enabled) {
                return;
            }

            $templates = explode(',', $options['category_content']);
            $index = 0;
            $class_string = 'clerk_';
            $filter_string = '';
            $unique_filter = isset($options['category_excl_duplicates']) && $options['category_excl_duplicates'];

            foreach ($templates as $template) {
                $class_value = $unique_filter ? ' class="clerk ' . $class_string . $index . '"' : ' class="clerk"';
                $exclude_value = $unique_filter && $index > 0 ? ' data-exclude-from="' . $filter_string . '"' : '';
                $template_value = ' data-template="@' . str_replace(' ', '', $template) . '"';
                $category_value = ' data-category="' . $category->term_id . '"';
                $tag_attributes = $class_value . $template_value . $category_value . $exclude_value;
                $anchor_string = '<span' . esc_attr($tag_attributes) . '></span>';
                // Print Span Element.
                echo $anchor_string;

                $filter_string .= $index > 0 ? ', ' : '';
                $filter_string .= '.' . $class_string . $index;
                ++$index;
            }

        } catch (Exception $e) {

            $this->logger->error('ERROR clerk_woocommerce_archive_description', array('error' => $e->getMessage()));

        }
    }

    /**
     * Add content after cart if enabled
     */
    public function clerk_woocommerce_after_cart_table(): void
    {

        try {
            $options = get_option('clerk_options');
            $enabled = isset($options['cart_enabled']) && $options['cart_enabled'];
            if (!$enabled) {
                return;
            }

            global $woocommerce;
            $items = $woocommerce->cart->get_cart();
            $products = array();

            foreach ($items as $item => $values) {
                $products[] = $values['product_id'];
            }


            $templates = explode(',', $options['cart_content']);
            $index = 0;
            $class_string = 'clerk_';
            $filter_string = '';
            $unique_filter = isset($options['cart_excl_duplicates']) && $options['cart_excl_duplicates'];

            foreach ($templates as $template) {
                $class_value = $unique_filter ? ' class="clerk ' . $class_string . $index . '"' : ' class="clerk"';
                $exclude_value = $unique_filter && $index > 0 ? ' data-exclude-from="' . $filter_string . '"' : '';
                $template_value = ' data-template="@' . str_replace(' ', '', $template) . '"';
                $products_value = ' data-products="' . wp_json_encode($products) . '"';
                $tag_attributes = $class_value . $template_value . $products_value . $exclude_value;
                $anchor_string = '<span' . esc_attr($tag_attributes) . '></span>';
                // Print Span Element.
                echo $anchor_string;

                $filter_string .= $index > 0 ? ', ' : '';
                $filter_string .= '.' . $class_string . $index;
                ++$index;
            }
        } catch (Exception $e) {

            $this->logger->error('ERROR clerk_woocommerce_after_cart_table', array('error' => $e->getMessage()));

        }
    }

    /**
     * Rewrite related products template if enabled
     *
     * @param mixed $located Template found.
     * @param string $template_name Template name.
     *
     * @return string
     */
    public function clerk_wc_get_template(mixed $located, string $template_name): string
    {

        try {

            if ('single-product/related.php' === $template_name) {
                $options = get_option('clerk_options');
                if (isset($options['product_enabled']) && $options['product_enabled']) {
                    return clerk_locate_template('clerk-related-products.php');
                }
            }

        } catch (Exception $e) {
            $this->logger->error('ERROR clerk_wc_get_template', array('error' => $e->getMessage()));
        }
        return $located;
    }
}

new Clerk_Content();
