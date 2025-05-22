<?php
/**
 * Clerk.io Elementor Compatibility
 *
 * This file adds compatibility between Clerk.io and Elementor by ensuring
 * that shortcodes in data attributes are properly processed.
 *
 * @package clerk-woocommerce
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Clerk_Elementor_Compatibility Class
 */
class Clerk_Elementor_Compatibility {

    /**
     * Error and Warning Logger
     *
     * @var $logger Clerk_Logger
     */
    protected $logger;

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
        include_once __DIR__ . '/class-clerk-logger.php';
        $this->logger = new Clerk_Logger();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Add AJAX handler for evaluating shortcodes
        add_action('wp_ajax_clerk_evaluate_shortcode', array($this, 'evaluate_shortcode'));
        add_action('wp_ajax_nopriv_clerk_evaluate_shortcode', array($this, 'evaluate_shortcode'));
        
        // Enqueue the compatibility script
        add_action('wp_enqueue_scripts', array($this, 'enqueue_compatibility_script'));
        
        // Add support for Elementor widgets
        add_action('elementor/frontend/after_enqueue_scripts', array($this, 'enqueue_compatibility_script'));
        
        // Add filter to process shortcodes in Elementor HTML widget
        add_filter('elementor/widget/render_content', array($this, 'process_elementor_widget_content'), 10, 2);
    }

    /**
     * Enqueue the compatibility script
     */
    public function enqueue_compatibility_script() {
        // Only enqueue if Elementor is active
        if (!did_action('elementor/loaded')) {
            return;
        }
        
        $options = clerk_get_options();
        
        // Only enqueue if Clerk.io is enabled
        if (!isset($options['public_key']) || !$options['public_key']) {
            return;
        }
        
        // Enqueue the script
        wp_enqueue_script(
            'clerk-elementor-compatibility',
            plugins_url('assets/js/clerk-elementor-fix.js', dirname(__FILE__)),
            array('jquery'),
            '1.0.0',
            true
        );
        
        // Localize the script with the AJAX URL
        wp_localize_script(
            'clerk-elementor-compatibility',
            'clerk_elementor_compat',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
            )
        );
    }

    /**
     * AJAX handler for evaluating shortcodes
     */
    public function evaluate_shortcode() {
        try {
            // Get the shortcode from the request
            $shortcode = isset($_POST['shortcode']) ? sanitize_text_field(wp_unslash($_POST['shortcode'])) : '';
            
            if (empty($shortcode)) {
                wp_send_json_error('No shortcode provided');
                return;
            }
            
            // Process the shortcode
            $result = do_shortcode($shortcode);
            
            // Send the result
            wp_send_json_success($result);
        } catch (Exception $e) {
            $this->logger->error('ERROR evaluate_shortcode', array('error' => $e->getMessage()));
            wp_send_json_error($e->getMessage());
        }
        
        wp_die();
    }

    /**
     * Process Elementor widget content to handle Clerk.io shortcodes
     *
     * @param string $widget_content The widget content.
     * @param object $widget The widget instance.
     * @return string The processed widget content.
     */
    public function process_elementor_widget_content($widget_content, $widget) {
        // Only process HTML widgets
        if ('html' !== $widget->get_name()) {
            return $widget_content;
        }
        
        // Process shortcodes in the widget content
        $widget_content = do_shortcode($widget_content);
        
        // Process data attributes with PHP code
        $widget_content = $this->process_php_in_attributes($widget_content);
        
        return $widget_content;
    }

    /**
     * Process PHP code in HTML attributes
     *
     * @param string $content The HTML content.
     * @return string The processed HTML content.
     */
    private function process_php_in_attributes($content) {
        // Use a regular expression to find data attributes with PHP code
        $pattern = '/data-(products|categories)=["\'](.*?)\[\s*clerk_(?:product|category)_id\s*\](.*?)["\']/i';
        
        // Replace the PHP code with the evaluated result
        $content = preg_replace_callback($pattern, function($matches) {
            $attribute = $matches[1];
            $prefix = $matches[2];
            $suffix = $matches[3];
            
            // Get the appropriate ID based on the attribute
            if ($attribute === 'products') {
                $id = $this->get_product_id();
            } else {
                $id = $this->get_category_id();
            }
            
            // Return the attribute with the evaluated ID
            return 'data-' . $attribute . '="' . $prefix . $id . $suffix . '"';
        }, $content);
        
        return $content;
    }

    /**
     * Get the current product ID
     *
     * @return int|null The product ID or null if not on a product page.
     */
    private function get_product_id() {
        if (is_product()) {
            return get_the_ID();
        }
        
        return null;
    }

    /**
     * Get the current category ID
     *
     * @return int|null The category ID or null if not on a category page.
     */
    private function get_category_id() {
        if (is_product_category()) {
            $category = get_queried_object();
            return $category->term_id;
        }
        
        return null;
    }
}

// Initialize the compatibility class
new Clerk_Elementor_Compatibility();

