<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Clerk_Admin_Settings
{

    protected $logger;

    protected $version;

    /**
     * Clerk_Admin_Settings constructor.
     */
    public function __construct()
    {

        $this->initHooks();
        require_once(__DIR__ . '/class-clerk-logger.php');
        $this->logger = new ClerkLogger();
        $this->version = '3.3.0';

        $this->InitializeSettings();

    }

    /**
     * Add actions
     */
    private function initHooks()
    {

        add_action('admin_init', [$this, 'settings_init']);
        add_action('admin_menu', [$this, 'clerk_options_page']);
        add_action('admin_menu', [$this, 'loadJqueryUI']);

    }

    public function loadJqueryUI() {

        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_style('wp-jquery-ui-dialog');

    }

    public function InitializeSettings()
    {

        $options = get_option('clerk_options');

        if ($options['log_to'] !== false) {

        } else {

            // The option hasn't been added yet. We'll add it with $autoload set to 'no'.
            $deprecated = null;
            $autoload = 'no';
            add_option($options, ['log_to' => 'my.clerk.io'], $deprecated, $autoload);
        }

        if ($options['log_level'] !== false) {

        } else {

            // The option hasn't been added yet. We'll add it with $autoload set to 'no'.
            $deprecated = null;
            $autoload = 'no';
            add_option($options, ['log_level' => 'Error + Warn'], $deprecated, $autoload);
        }
        if (isset($options['log_enabled'])) {
            if ($options['log_enabled'] !== false) {

            } else {

                // The option hasn't been added yet. We'll add it with $autoload set to 'no'.
                $deprecated = null;
                $autoload = 'no';
                add_option($options, ['log_enabled' => '1'], $deprecated, $autoload);
            }
        }
        if (get_option('livesearch_initiated') !== false) {

        } else {

            // The option hasn't been added yet. We'll add it with $autoload set to 'no'.
            $deprecated = null;
            $autoload = 'no';
            add_option('livesearch_initiated', 0, $deprecated, $autoload);
        }

        if (get_option('powerstep_initiated') !== false) {

        } else {

            // The option hasn't been added yet. We'll add it with $autoload set to 'no'.
            $deprecated = null;
            $autoload = 'no';
            add_option('powerstep_initiated', 0, $deprecated, $autoload);
        }

        if (get_option('search_initiated') !== false) {

        } else {

            // The option hasn't been added yet. We'll add it with $autoload set to 'no'.
            $deprecated = null;
            $autoload = 'no';
            add_option('search_initiated', 0, $deprecated, $autoload);
        }

        if (get_option('exit_intent_initiated') !== false) {

        } else {

            // The option hasn't been added yet. We'll add it with $autoload set to 'no'.
            $deprecated = null;
            $autoload = 'no';
            add_option('exit_intent_initiated', 0, $deprecated, $autoload);
        }

        if (get_option('category_initiated') !== false) {

        } else {

            // The option hasn't been added yet. We'll add it with $autoload set to 'no'.
            $deprecated = null;
            $autoload = 'no';
            add_option('category_initiated', 0, $deprecated, $autoload);
        }

        if (get_option('product_initiated') !== false) {

        } else {

            // The option hasn't been added yet. We'll add it with $autoload set to 'no'.
            $deprecated = null;
            $autoload = 'no';
            add_option('product_initiated', 0, $deprecated, $autoload);
        }

        if (get_option('cart_initiated') !== false) {

        } else {

            // The option hasn't been added yet. We'll add it with $autoload set to 'no'.
            $deprecated = null;
            $autoload = 'no';
            add_option('cart_initiated', 0, $deprecated, $autoload);
        }

        if (get_option('sync_mails_initiated') !== false) {

        } else {

            // The option hasn't been added yet. We'll add it with $autoload set to 'no'.
            $deprecated = null;
            $autoload = 'no';
            add_option('sync_mails_initiated', 0, $deprecated, $autoload);
        }

        if (get_option('disable_order_sync_initiated') !== false) {

        } else {

            // The option hasn't been added yet. We'll add it with $autoload set to 'no'.
            $deprecated = null;
            $autoload = 'no';
            add_option('disable_order_sync_initiated', 0, $deprecated, $autoload);
        }

        $livesearch_initiated = get_option('livesearch_initiated');
        $search_initiated = get_option('search_initiated');
        $powerstep_initiated = get_option('powerstep_initiated');
        $exit_intent_initiated = get_option('exit_intent_initiated');
        $category_initiated = get_option('category_initiated');
        $product_initiated = get_option('product_initiated');
        $cart_initiated = get_option('cart_initiated');
        $sync_mails_initiated_initiated = get_option('sync_mails_initiated');
        $disable_order_sync_initiated_initiated = get_option('disable_order_sync_initiated');

        if (isset($options['collect_emails']) && !$sync_mails_initiated_initiated == 1) {

            update_option('sync_mails_initiated', 1);
            $this->logger->log('Sync Mails initiated', ['' => '']);

        }

        if (!isset($options['collect_emails']) && $sync_mails_initiated_initiated == 1) {

            update_option('sync_mails_initiated', 0);
            $this->logger->log('Sync Mails uninitiated', ['' => '']);

        }

        if (isset($options['cart_enabled']) && !$cart_initiated) {

            update_option('cart_initiated', 1);
            $this->logger->log('Cart Settings initiated', ['' => '']);

        }

        if (!isset($options['cart_enabled']) && $cart_initiated) {

            update_option('cart_initiated', 0);
            $this->logger->log('Cart Settings uninitiated', ['' => '']);

        }

        if (isset($options['disable_order_synchronization']) && !$disable_order_sync_initiated_initiated) {

            update_option('disable_order_sync_initiated', 1);
            $this->logger->log('Disable Order Sync initiated', ['' => '']);

        }

        if (!isset($options['disable_order_synchronization']) && $disable_order_sync_initiated_initiated) {

            update_option('disable_order_sync_initiated', 0);
            $this->logger->log('Disable Order Sync uninitiated', ['' => '']);

        }

        if (isset($options['product_enabled']) && !$product_initiated) {

            update_option('product_initiated', 1);
            $this->logger->log('Product Settings initiated', ['' => '']);

        }

        if (!isset($options['product_enabled']) && $product_initiated) {

            update_option('product_initiated', 0);
            $this->logger->log('Product Settings uninitiated', ['' => '']);

        }

        if (isset($options['category_enabled']) && !$category_initiated) {

            update_option('category_initiated', 1);
            $this->logger->log('Category Settings initiated', ['' => '']);

        }

        if (!isset($options['category_enabled']) && $category_initiated) {

            update_option('category_initiated', 0);
            $this->logger->log('Category Settings uninitiated', ['' => '']);

        }

        if (isset($options['exit_intent_enabled']) && !$exit_intent_initiated) {

            update_option('exit_intent_initiated', 1);
            $this->logger->log('Exit Intent initiated', ['' => '']);

        }

        if (!isset($options['exit_intent_enabled']) && $exit_intent_initiated) {

            update_option('exit_intent_initiated', 0);
            $this->logger->log('Exit Intent uninitiated', ['' => '']);

        }

        if (isset($options['search_enabled']) && !$search_initiated) {

            update_option('search_initiated', 1);
            $this->logger->log('Search initiated', ['' => '']);

        }

        if (!isset($options['search_enabled']) && $search_initiated) {

            update_option('search_initiated', 0);
            $this->logger->log('Search uninitiated', ['' => '']);

        }

        if (isset($options['livesearch_enabled']) && !$livesearch_initiated) {

            update_option('livesearch_initiated', 1);
            $this->logger->log('Live Search initiated', ['' => '']);

        }

        if (!isset($options['livesearch_enabled']) && $livesearch_initiated) {

            update_option('livesearch_initiated', 0);
            $this->logger->log('Live Search uninitiated', ['' => '']);

        }

        if (isset($options['powerstep_enabled']) && !$powerstep_initiated) {

            update_option('powerstep_initiated', 1);
            $this->logger->log('Powerstep initiated', ['' => '']);

        }

        if (!isset($options['powerstep_enabled']) && $powerstep_initiated) {

            update_option('powerstep_initiated', 0);
            $this->logger->log('Powerstep uninitiated', ['' => '']);

        }

    }

    /**
     * Init settings
     */
    public function settings_init()
    {

        // register a new setting
        register_setting('clerk', 'clerk_options');
        $options = get_option('clerk_options');

        //Add general section
        add_settings_section(
            'clerk_section_general',
            __('General', 'clerk'),
            null,
            'clerk');

        add_settings_field('version',
            __('Plugin version', 'clerk'),
            [$this, 'addVersion'],
            'clerk',
            'clerk_section_general',
            [
                'label_for' => 'version',
            ]
        );

        add_settings_field('public_key',
            __('Public Key', 'clerk'),
            [$this, 'addTextField'],
            'clerk',
            'clerk_section_general',
            [
                'label_for' => 'public_key',
            ]
        );

        add_settings_field('private_key',
            __('Private Key', 'clerk'),
            [$this, 'addTextField'],
            'clerk',
            'clerk_section_general',
            [
                'label_for' => 'private_key',
            ]
        );

        add_settings_field('lang',
            __('Language', 'clerk'),
            [$this, 'addLang'],
            'clerk',
            'clerk_section_general',
            [
                'label_for' => 'lang',
                'default' => 'auto'
            ]
        );

        add_settings_field('import_url',
            __('Import URL', 'clerk'),
            [$this, 'addTextField'],
            'clerk',
            'clerk_section_general',
            [
                'label_for' => 'import_url',
                'description' => 'Use this url to configure an importer from my.clerk.io',
                'readonly' => true,
                'value' => get_site_url(),
            ]
        );

        //Add Customer sync section
        add_settings_section(
            'clerk_section_customer_sync',
            __('Customer Sync', 'clerk'),
            null,
            'clerk');

        add_settings_field('customer_sync_enabled',
            __('Enabled', 'clerk'),
            [$this, 'addCheckboxField'],
            'clerk',
            'clerk_section_customer_sync',
            [
                'label_for' => 'customer_sync_enabled',
                'checked' => 0
            ]
        );

        add_settings_field('customer_sync_customer_fields',
            __('Extra Customer Fields', 'clerk'),
            [$this, 'addTextField'],
            'clerk',
            'clerk_section_customer_sync',
            [
                'label_for' => 'customer_sync_customer_fields',
                'description' => 'A comma separated list of additional fields for customer to sync'
            ]
        );

        //Add data sync section
        add_settings_section(
            'clerk_section_datasync',
            __('Data Sync', 'clerk'),
            null,
            'clerk');

        add_settings_field('realtime_updates',
            __('Use Real-time Updates', 'clerk'),
            [$this, 'addCheckboxField'],
            'clerk',
            'clerk_section_datasync',
            [
                'label_for' => 'realtime_updates',
                'checked' => 0
            ]
        );

        add_settings_field('include_pages',
            __('Include Pages', 'clerk'),
            [$this, 'addCheckboxField'],
            'clerk',
            'clerk_section_datasync',
            [
                'label_for' => 'include_pages',
                'checked' => 1
            ]
        );

        add_settings_field('page_additional_fields',
            __('Page Additional Fields', 'clerk'),
            [$this, 'addTextField'],
            'clerk',
            'clerk_section_datasync',
            [
                'label_for' => 'page_additional_fields',
                'description' => 'A comma separated list of additional fields for pages to sync'
            ]
        );

        add_settings_field('outofstock_products',
            __('Include Out Of Stock Products', 'clerk'),
            [$this, 'addCheckboxField'],
            'clerk',
            'clerk_section_datasync',
            [
                'label_for' => 'outofstock_products',
                'checked' => 0
            ]
        );

        add_settings_field('collect_emails',
            __('Collect Emails', 'clerk'),
            [$this, 'addCheckboxField'],
            'clerk',
            'clerk_section_datasync',
            [
                'label_for' => 'collect_emails',
                'checked' => 1
            ]
        );

        add_settings_field('additional_fields',
            __('Additional Fields', 'clerk'),
            [$this, 'addTextField'],
            'clerk',
            'clerk_section_datasync',
            [
                'label_for' => 'additional_fields',
                'description' => 'A comma separated list of additional fields to sync'
            ]
        );

        add_settings_field('disable_order_synchronization',
            __('Disable Order Synchronization', 'clerk'),
            [$this, 'addCheckboxField'],
            'clerk',
            'clerk_section_datasync',
            [
                'label_for' => 'disable_order_synchronization',
                'checked' => 0
            ]
        );

        //Add search section
        add_settings_section(
            'clerk_section_search',
            __('Search Settings', 'clerk'),
            null,
            'clerk');

        add_settings_field('search_enabled',
            __('Enabled', 'clerk'),
            [$this, 'addCheckboxField'],
            'clerk',
            'clerk_section_search',
            [
                'label_for' => 'search_enabled',
                'checked' => 0            ]
        );

        add_settings_field('search_page',
            __('Search Page', 'clerk'),
            [$this, 'addPageDropdown'],
            'clerk',
            'clerk_section_search',
            [
                'label_for' => 'search_page',
            ]
        );

        add_settings_field('search_template',
            __('Content', 'clerk'),
            [$this, 'addTextField'],
            'clerk',
            'clerk_section_search',
            [
                'label_for' => 'search_template',
            ]
        );

        add_settings_field('search_no_results_text',
            __('No results text', 'clerk'),
            [$this, 'addTextField'],
            'clerk',
            'clerk_section_search',
            [
                'label_for' => 'search_no_results_text',
            ]
        );

        add_settings_field('search_load_more_button',
            __('Load more button text', 'clerk'),
            [$this, 'addTextField'],
            'clerk',
            'clerk_section_search',
            [
                'label_for' => 'search_load_more_button',
            ]
        );

        //Add livesearch section
        add_settings_section(
            'clerk_section_livesearch',
            __('Live search Settings', 'clerk'),
            null,
            'clerk');

        add_settings_field('livesearch_enabled',
            __('Enabled', 'clerk'),
            [$this, 'addCheckboxField'],
            'clerk',
            'clerk_section_livesearch',
            [
                'label_for' => 'livesearch_enabled',
                'checked' => 0
            ]
        );

        add_settings_field('livesearch_include_categories',
            __('Include Categories', 'clerk'),
            [$this, 'addCheckboxField'],
            'clerk',
            'clerk_section_livesearch',
            [
                'label_for' => 'livesearch_include_categories',
                'checked' => 0
            ]
        );

        add_settings_field('livesearch_suggestions',
            __('Number of Suggestions', 'clerk'),
            [$this, 'add1_10Dropdown'],
            'clerk',
            'clerk_section_livesearch',
            [
                'label_for' => 'livesearch_suggestions',
                'default' => 5
            ]
        );

        add_settings_field('livesearch_categories',
            __('Number of Categories', 'clerk'),
            [$this, 'add1_10Dropdown'],
            'clerk',
            'clerk_section_livesearch',
            [
                'label_for' => 'livesearch_categories',
                'default' => 5
            ]
        );

        add_settings_field('livesearch_pages',
            __('Number of Pages', 'clerk'),
            [$this, 'add1_10Dropdown'],
            'clerk',
            'clerk_section_livesearch',
            [
                'label_for' => 'livesearch_pages',
                'default' => 5
            ]
        );

        add_settings_field('livesearch_pages_type',
            __('Pages Type', 'clerk'),
            [$this, 'addPagesTypeDropdown'],
            'clerk',
            'clerk_section_livesearch',
            [
                'label_for' => 'livesearch_pages_type',
            ]
        );

        add_settings_field('livesearch_dropdown_position',
            __('Dropdown Positioning', 'clerk'),
            [$this, 'addDropdownPosition'],
            'clerk',
            'clerk_section_livesearch',
            [
                'label_for' => 'livesearch_dropdown_position',
            ]
        );

        add_settings_field('livesearch_field_selector',
            __('Live Search Input Selector', 'clerk'),
            [$this, 'addTextField'],
            'clerk',
            'clerk_section_livesearch',
            [
                'label_for' => 'livesearch_field_selector',
                'default' => '.search-field'
            ]
        );

        add_settings_field('livesearch_form_selector',
            __('Live Search Form Selector', 'clerk'),
            [$this, 'addTextField'],
            'clerk',
            'clerk_section_livesearch',
            [
                'label_for' => 'livesearch_form_selector',
                'default' => '[role="search"]'
            ]
        );

        add_settings_field('livesearch_template',
            __('Content', 'clerk'),
            [$this, 'addTextField'],
            'clerk',
            'clerk_section_livesearch',
            [
                'label_for' => 'livesearch_template',
            ]
        );


        //Add faceted navigation
        add_settings_section(
            'clerk_faceted_navigation',
            __('Faceted Navigation', 'clerk'),
            null,
            'clerk');

        add_settings_field('faceted_navigation_enabled',
            __('Enabled', 'clerk'),
            [$this, 'addCheckboxField'],
            'clerk',
            'clerk_faceted_navigation',
            [
                'label_for' => 'faceted_navigation_enabled',
                'checked' => 0
            ]
        );

        add_settings_field('faceted_navigation_custom',
            __('Add Custom Attribute', 'clerk'),
            [$this, 'addFildAndButton'],
            'clerk',
            'clerk_faceted_navigation',
            [
                'label_for' => 'faceted_navigation_custom'
            ]
        );

        add_settings_field('faceted_navigation',
            __('Facet Attributes', 'clerk'),
            [$this, 'getAttributes'],
            'clerk',
            'clerk_faceted_navigation',
            [
                'label_for' => 'faceted_navigation'
            ]
        );

        //Add powerstep section
        add_settings_section(
            'clerk_section_powerstep',
            __('Powerstep Settings', 'clerk'),
            null,
            'clerk');

        add_settings_field('powerstep_enabled',
            __('Enabled', 'clerk'),
            [$this, 'addCheckboxField'],
            'clerk',
            'clerk_section_powerstep',
            [
                'label_for' => 'powerstep_enabled',
                'checked' => 0
            ]
        );

        add_settings_field('powerstep_type',
            __('Powerstep Type', 'clerk'),
            [$this, 'addPowerstepTypeDropdown'],
            'clerk',
            'clerk_section_powerstep',
            [
                'label_for' => 'powerstep_type',
            ]
        );


        add_settings_field('powerstep_page',
            __('Powerstep Page', 'clerk'),
            [$this, 'addPageDropdown'],
            'clerk',
            'clerk_section_powerstep',
            [
                'label_for' => 'powerstep_page',
            ]
        );

        add_settings_field('powerstep_templates',
            __('Contents', 'clerk'),
            [$this, 'addTextField'],
            'clerk',
            'clerk_section_powerstep',
            [
                'label_for' => 'powerstep_templates',
                'description' => 'A comma separated list of clerk templates to render'
            ]
        );

        //Add exit intent section
        add_settings_section(
            'clerk_section_exit_intent',
            __('Exit Intent Settings', 'clerk'),
            null,
            'clerk');

        add_settings_field('exit_intent_enabled',
            __('Enabled', 'clerk'),
            [$this, 'addCheckboxField'],
            'clerk',
            'clerk_section_exit_intent',
            [
                'label_for' => 'exit_intent_enabled',
                'checked' => 0
            ]
        );

        add_settings_field('exit_intent_template',
            __('Content', 'clerk'),
            [$this, 'addTextField'],
            'clerk',
            'clerk_section_exit_intent',
            [
                'label_for' => 'exit_intent_template'
            ]
        );

        //Add category section
        add_settings_section(
            'clerk_section_category',
            __('Category Settings', 'clerk'),
            null,
            'clerk');

        add_settings_field('category_enabled',
            __('Enabled', 'clerk'),
            [$this, 'addCheckboxField'],
            'clerk',
            'clerk_section_category',
            [
                'label_for' => 'category_enabled',
                'checked' => 0
            ]
        );

        add_settings_field('category_content',
            __('Content', 'clerk'),
            [$this, 'addTextField'],
            'clerk',
            'clerk_section_category',
            [
                'label_for' => 'category_content',
            ]
        );

        //Add product section
        add_settings_section(
            'clerk_section_product',
            __('Product Settings', 'clerk'),
            null,
            'clerk');

        add_settings_field('product_enabled',
            __('Enabled', 'clerk'),
            [$this, 'addCheckboxField'],
            'clerk',
            'clerk_section_product',
            [
                'label_for' => 'product_enabled',
                'checked' => 0
            ]
        );

        add_settings_field('product_content',
            __('Content', 'clerk'),
            [$this, 'addTextField'],
            'clerk',
            'clerk_section_product',
            [
                'label_for' => 'product_content',
            ]
        );

        //Add cart section
        add_settings_section(
            'clerk_section_cart',
            __('Cart Settings', 'clerk'),
            null,
            'clerk');

        add_settings_field('cart_enabled',
            __('Enabled', 'clerk'),
            [$this, 'addCheckboxField'],
            'clerk',
            'clerk_section_cart',
            [
                'label_for' => 'cart_enabled',
                'checked'  => 0
            ]
        );

        add_settings_field('cart_content',
            __('Content', 'clerk'),
            [$this, 'addTextField'],
            'clerk',
            'clerk_section_cart',
            [
                'label_for' => 'cart_content',
            ]
        );

        //Add logging section
        add_settings_section(
            'clerk_section_log',
            __('Logging', 'clerk'),
            null,
            'clerk');

        add_settings_field('log_enabled',
            __('Enabled', 'clerk'),
            [$this, 'addCheckboxField'],
            'clerk',
            'clerk_section_log',
            [
                'label_for' => 'log_enabled',
                'checked' => 1
            ]
        );

        add_settings_field('log_level',
            __('Log Level', 'clerk'),
            [$this, 'addLogLevelDropdown'],
            'clerk',
            'clerk_section_log',
            [
                'label_for' => 'log_level',
                'default' => "Error + Warn"
            ]
        );

        add_settings_field('log_to',
            __('Log to', 'clerk'),
            [$this, 'addLogToDropdown'],
            'clerk',
            'clerk_section_log',
            [
                'label_for' => 'log_to',
                'default' => "my.clerk.io"
            ]
        );

        if ($options['log_level'] === 'Error + Warn + Debug Mode') {

            add_settings_field('log_warning',
                __('', 'clerk'),
                [$this, 'addDebugMessage'],
                'clerk',
                'clerk_section_log'
            );

        }

        add_settings_field('extension_warning',
            __('', 'clerk'),
            [$this, 'addWarningMessage'],
            'clerk',
            'clerk_section_log'
        );

        add_settings_field('debug_guide',
            __('Debug Guide', 'clerk'),
            [$this, 'addDebugGuide'],
            'clerk',
            'clerk_section_log'
        );

        add_settings_field('debug_guide_change',
            __('', 'clerk'),
            [$this, 'addDebugChange'],
            'clerk',
            'clerk_section_log',
            [
                'label_for' => 'debug_guide_change',
                'checked' => 0
            ]
        );
    }
    /**
     *
     */
    public function addVersion()
    {

        ?>
        <span>
                <p>v. <?php echo $this->version; ?></p>
            </span>
        <?php

    }

    public function addFildAndButton() {
        ?>
        <input class="text-box single-line" id="faceted_navigation_custom"
               name="faceted_navigation_custom" style="display: inline-block;" type="text"
               value="">
        <a type="button" onclick="add_facet()" title="Add" class="button button-primary">
            Add
        </a>
        <?php
    }

    function isJSON($string){
        return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }

    public function getAttributes($args) {

        $_continue = true;
        $offset = 0;
        $page = 0;

        $exclude_attributes = ['sku','list_price','description','url','image','type','id','name'];
        $DynamicAttributes = [];

        $options = get_option('clerk_options');
        $public_key = $options['public_key'];

        if (!empty(str_replace(' ','', $public_key))) {

            while ($_continue) {

                $check = true;


                $limit = 10;
                $orderby = 'date';
                $order = 'DESC';

                $products = clerk_get_products(array(
                    'limit' => $limit,
                    'orderby' => $orderby,
                    'order' => $order,
                    'status' => array('publish'),
                    'paginate' => true,
                    'offset' => $offset
                ));

                foreach ($products->products as $product) {

                    if ($check) {

                        $id = $product->get_id();

                        $Endpoint = 'https://api.clerk.io/v2/product/attributes';

                        $data_string = json_encode([
                            'key' => $public_key,
                            'products' => [$id]]);

                        $_args = array(
                            'body'        => $data_string,
                            'method'      => 'POST',
                        );

                        $response = wp_remote_request( $Endpoint, $_args );

                        if ($this->isJSON($response['body'])) {

                            $response = json_decode($response['body']);

                        }else {

                            $response = $response['body'];

                        }

                        if (is_array($response)) {

                            $check = false;

                        }

                        if (isset($response[0]) && is_array($response)) {

                            $response = $response[0];

                        }

                    }

                }

                if (isset($response)) {

                    foreach ($response as $attribute => $value) {

                        if (!in_array($attribute, $exclude_attributes)) {

                            if (!empty($attribute)) {

                                $DynamicAttributes[$attribute] = $attribute;

                            }

                        }

                    }

                }

                if (count($DynamicAttributes) != 0 && $offset >= 10) {

                    $_continue = false;

                } elseif ($offset == 30) {

                    $_continue = false;

                }

                $offset += 10;
            }

            $AttributesCorCompare = [];
            $NewDynamicAttributes = [];

            if (!empty($options['faceted_navigation'])) {

                $savedAttributes = json_decode($options['faceted_navigation']);

            } else {

                $savedAttributes = [];

            }

            if (count($savedAttributes) > 0) {

                foreach ($savedAttributes as $Attribute) {

                    $AttributesCorCompare[] = $Attribute->attribute;

                }

            }

            foreach ($DynamicAttributes as $DynamicAttribute) {

                if (in_array($DynamicAttribute, $AttributesCorCompare)) {


                } else {

                    $NewDynamicAttributes[] = $DynamicAttribute;

                }

            }

            if (count($NewDynamicAttributes) > 0) {
                $commacounter = 0;
                $attribute_text = 'attributes';


                if (count($NewDynamicAttributes) === 1) {


                    $attribute_text = 'attribute';


                }

                ?>
                <div class="alert info">
                    <span class="closebtn">×</span>
                    <strong><?php echo count($NewDynamicAttributes) ?></strong> new <?php echo $attribute_text ?>

                    <?php
                    foreach ($NewDynamicAttributes as $Attribute) {
                        $commacounter++;
                        ?>
                        <strong><?php echo $Attribute ?></strong><?php if ($commacounter < count($NewDynamicAttributes)) {
                            echo ', ';
                        } else {
                            echo ' detected.';
                        } ?>
                        <?php
                    }
                    ?>

                </div>
                <?php
            }

        if (count($NewDynamicAttributes) > 0 || count($savedAttributes) > 0) {

            ?>
            <table>

                <tbody id="facets_content">
                <th>Attribute</th>
                <th>Title</th>
                <th>Position</th>
                <th>Show</th>
                <?php

                }

                if (count($savedAttributes) > 0) {

                    $count = 0;
                    foreach ($savedAttributes as $Attribute) {

                        $count++;
                        $checked = '';
                        if ($Attribute->checked) {

                            $checked = 'checked';

                        }

                        echo '

                <tr id="facets_lines">

                    <td><input type="text" id="facets_facet" value="' . $Attribute->attribute . '" readonly></td>
                    <td><input type="text" id="facets_title" value="' . $Attribute->title . '"></td>
                    <td><input type="text" id="facets_position" value="' . $Attribute->position . '"></td>
                    <td><input id="faceted_enabled" type="checkbox" ' . $checked . '></td>
                    
                </tr>
                ';

                    }
                }

                if (count($NewDynamicAttributes) > 0) {
                    $count = 0;
                    foreach ($NewDynamicAttributes as $Attribute) {

                        $count++;

                        echo '

                    <tr id="facets_lines">

                        <td><input type="text" id="facets_facet" value="' . $Attribute . '" readonly></td>
                        <td><input type="text" id="facets_title" value=""></td>
                        <td><input type="text" id="facets_position" value="' . $count . '"></td>
                        <td><input id="faceted_enabled" type="checkbox"></td>
                        
                    </tr>
                    ';

                    }
                }

                if (count($NewDynamicAttributes) > 0 || count($savedAttributes) > 0) {


                ?>

                <input name="clerk_options[<?php echo esc_attr($args['label_for']); ?>]" id="faceted_navigation"
                       type="hidden">
                </tbody>
            </table>
            <script>
                jQuery('.wrap form').submit(function () {

                    CollectAttributes();

                });

                function remove_facet_line(data_value) {

                    jQuery("[data=" + data_value + "]").remove();

                }

                function add_facet() {

                    if (jQuery("#facets_content #facets_lines").length === 0) {
                        jQuery('#facets_content').html('');
                    }

                    var linescount = jQuery('#facets_content #facets_lines').length;

                    facets_lines = document.createElement("tr");
                    facets_lines.setAttribute("id", "facets_lines");
                    facets_lines.setAttribute("data", jQuery('#faceted_navigation_custom').val());

                    facet_td = document.createElement("td");

                    facet = document.createElement("input");
                    facet.setAttribute("id", "facets_facet");
                    facet.setAttribute("type", "text");
                    facet.setAttribute("value", jQuery('#faceted_navigation_custom').val());
                    facet.setAttribute("readonly", '');

                    title_td = document.createElement("td");
                    title = document.createElement("input");
                    title.setAttribute("id", "facets_title");
                    title.setAttribute("type", "text");
                    title.setAttribute("value", '');


                    position_td = document.createElement("td");
                    position = document.createElement("input");
                    position.setAttribute("id", "facets_position");
                    position.setAttribute("type", "text");
                    position.setAttribute("value", linescount + 1);

                    checkbox_td = document.createElement("td");

                    checkbox = document.createElement("input");
                    checkbox.setAttribute("type", "checkbox");
                    checkbox.setAttribute("id", "faceted_enabled");
                    checkbox.setAttribute("value", "1");


                    remove = document.createElement("a");
                    remove.setAttribute("class", "close");
                    remove.setAttribute("onclick", 'remove_facet_line("' + jQuery("#faceted_navigation_custom").val() + '");');

                    facet_td.append(facet)
                    facets_lines.append(facet_td);
                    title_td.append(title);
                    facets_lines.append(title_td);
                    position_td.append(position);
                    facets_lines.append(position_td);
                    checkbox_td.append(checkbox);
                    checkbox_td.append(remove);
                    facets_lines.append(checkbox_td);

                    jQuery('#facets_content').append(facets_lines);

                    jQuery('#faceted_navigation_custom').val('')

                }


                function CollectAttributes() {

                    Attributes = [];

                    count = 0;
                    countFacets = jQuery('input[id^=facets_facet]').length;

                    while ((count + 1) <= countFacets) {

                        var data = {

                            attribute: jQuery('input[id^=facets_facet]:eq(' + count + ')').val(),
                            title: jQuery('input[id^=facets_title]:eq(' + count + ')').val(),
                            position: jQuery('input[id^=facets_position]:eq(' + count + ')').val(),
                            checked: jQuery('input[id^=faceted_enabled]:eq(' + count + ')').is(':checked')

                        };

                        Attributes.push(data);

                        count = count + 1;

                    }

                    jQuery('#faceted_navigation').val(JSON.stringify(Attributes));

                }

                jQuery(".closebtn").click(function () {
                    jQuery(".alert").remove();
                });
            </script>
            <style>
                .alert.info {
                    background-color: #2196F3;
                    border-radius: 6px;
                }

                .alert {
                    padding: 20px;
                    background-color: #f44336;
                    color: white;
                    opacity: 0.83;
                    transition: opacity 0.6s;
                    margin-bottom: 15px;
                }

                .closebtn {
                    padding-left: 15px;
                    color: white;
                    font-weight: bold;
                    float: right;
                    font-size: 20px;
                    line-height: 18px;
                    cursor: pointer;
                    transition: 0.3s;
                }

                .close {
                    position: absolute;
                    width: 32px;
                    height: 32px;
                    opacity: 0.4;
                }

                .close:hover {
                    opacity: 1;
                }

                .close:before, .close:after {
                    position: absolute;
                    left: 15px;
                    content: ' ';
                    height: 20px;
                    width: 2px;
                    background-color: #f44336;
                }

                .close:before {
                    transform: rotate(45deg);
                }

                .close:after {
                    transform: rotate(-45deg);
                }
            </style>

            <?php

        }

        }

    }

    public function addDebugChange($args)
    {

        //Set defaults
        if (esc_attr($args['checked']) == 1) {

            wp_parse_args(get_option('plugin_options'), [$args['label_for'] => '']);

        }

        //Get settings value
        $options = get_option('clerk_options');
        ?>
        <input type="checkbox" style="display:none;" id="<?php echo esc_attr($args['label_for']); ?>"
               name="clerk_options[<?php echo esc_attr($args['label_for']); ?>]"
               value="1" <?php checked('1', $options[$args['label_for']]); ?>>
        <?php

    }

    public function addPagesTypeDropdown($args)
    {

        $Types = ['All', 'CMS Page', 'Blog Post'];
        $options = get_option('clerk_options');

        ?>
        <select id="<?php echo esc_attr($args['label_for']); ?>"
                name="clerk_options[<?php echo esc_attr($args['label_for']); ?>]">
            <?php foreach ($Types as $Type) : ?>
                <option value="<?php echo $Type; ?>"
                        <?php if ($options[$args['label_for']] === $Type) : ?>selected<?php endif; ?>><?php echo __($Type, 'clerk'); ?></option>
            <?php endforeach; ?>
        </select>
        <?php

    }

    public function addDropdownPosition($args)
    {

        $Positions = ['Left', 'Center', 'Right', 'Below', 'Off'];
        $options = get_option('clerk_options');

        ?>
        <select id="<?php echo esc_attr($args['label_for']); ?>"
                name="clerk_options[<?php echo esc_attr($args['label_for']); ?>]">
            <?php foreach ($Positions as $Position) : ?>
                <option value="<?php echo $Position; ?>"
                        <?php if ($options[$args['label_for']] === $Position) : ?>selected<?php endif; ?>><?php echo __($Position, 'clerk'); ?></option>
            <?php endforeach; ?>
        </select>
        <?php

    }

    public function add1_10Dropdown($args)
    {

        $Numbers = ['1','2','3','4','5','6','7','8','9','10'];
        $options = get_option('clerk_options');

        ?>
        <select id="<?php echo esc_attr($args['label_for']); ?>"
                name="clerk_options[<?php echo esc_attr($args['label_for']); ?>]">
            <?php foreach ($Numbers as $Number) : ?>
                <option value="<?php echo $Number; ?>"
                        <?php if ($options[$args['label_for']] === $Number) : ?>selected<?php endif; ?>><?php echo __($Number, 'clerk'); ?></option>
            <?php endforeach; ?>
        </select>
        <?php

    }

    public function addLang($args)
    {

        $LangsAuto = [
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
            'tr_TR' => 'Turkish'
        ];

        if (isset($LangsAuto[get_locale()])) {

            $AutoLang = ['Label' => sprintf( 'Auto (%s)', $LangsAuto[get_locale()]), 'Value' => 'auto'];

        }

        //Get settings value
        $Langs = [
            ['Label' => 'Danish','Value' => 'danish'],
            ['Label' => 'Dutch','Value' => 'dutch'],
            ['Label' => 'English','Value' => 'english'],
            ['Label' => 'Finnish','Value' => 'finnish'],
            ['Label' => 'French','Value' => 'french'],
            ['Label' => 'German','Value' => 'german'],
            ['Label' => 'Hungarian','Value' => 'hungarian'],
            ['Label' => 'Italian','Value' => 'italian'],
            ['Label' => 'Norwegian','Value' => 'norwegian'],
            ['Label' => 'Portuguese','Value' => 'portuguese'],
            ['Label' => 'Romanian','Value' => 'romanian'],
            ['Label' => 'Russian','Value' => 'russian'],
            ['Label' => 'Spanish','Value' => 'spanish'],
            ['Label' => 'Swedish','Value' => 'swedish'],
            ['Label' => 'Turkish','Value' => 'turkish']
        ];

        if (isset($AutoLang)) {

            array_unshift($Langs, $AutoLang);

        }

        $options = get_option('clerk_options');

        ?>
        <select id="<?php echo esc_attr($args['label_for']); ?>"
                name="clerk_options[<?php echo esc_attr($args['label_for']); ?>]">
            <?php foreach ($Langs as $Lang) : ?>
                <option value="<?php echo $Lang['Value']; ?>"
                        <?php if ($options[$args['label_for']] === $Lang['Value']) : ?>selected<?php endif; ?>><?php echo __($Lang['Label'], 'clerk'); ?></option>
            <?php endforeach; ?>
        </select>
        <?php

    }

    /**
     *
     */
    public function addDebugMessage()
    {

        $options = get_option('clerk_options');

        if ($options['log_level'] === 'Error + Warn + Debug Mode') {

            ?>
            <div class="notice notice-warning">
                <p><?php echo esc_attr('You are in Clerk log level all! This log level should not be enabled in production'); ?></p>
            </div>
            <?php

        }

    }

    public function addWarningMessage()
    {

        $PluginMapping = [
            'WPBakery Page Builder' => ['Message' => 'This can cause, that our plugin have problems injecting our code on your shop .', 'SupportLink' => 'https://clerk.io']
        ];

        $plugins = get_plugins();

        foreach ($plugins as $plugin) {

            if (array_key_exists($plugin['Name'], $PluginMapping)) {
                ?>
                <div class="notice notice-warning">
                    <p><?php echo esc_attr($plugin['Name'].' v' .$plugin['Version'].' is installed.'); ?></p>
                    <p><?php echo esc_attr(str_replace('%%PLUGIN%%', $plugin['Name'], $PluginMapping[$plugin['Name']]['Message'])); ?></p>
                    <a href="<?php echo esc_attr($PluginMapping[$plugin['Name']]['SupportLink']) ?>" target="_blank"><p>Read about it here.</p></a>
                </div>
                <?php

            }

        }

    }

    public function addDebugGuide() {

        if (WP_DEBUG) {
            ?>
            <hr><p style="color: red;"><strong>Wordpress Debug Mode is enabled</strong></p>
            <ul>
                <li style="color: red;">Caching is disabled.</li>
                <li style="color: red;">Errors will be visible.</li>
                <li style="color: red;">Clerk logger can catch all errors.</li>
                <li style="color: red;">Remember to disable it again after use!</li>
                <li style="color: red;">It's not best practice to have it enabled in production.</li>
                <li style="color: red;">It's only recommended for at very short period af time for debug use.</li>
            </ul>
            <br>
            <p><strong>Step By Step Guide to disable debug mode</strong></p>
            <ol>
                <li>Please disable Wordpress Debug Mode.</li>
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
            <hr><strong>Wordpress Debug Mode is disabled</strong>
            <p>When debug mode is disabled, Wordpress hides a lot of errors and making it impossible for Clerk logger to detect and catch these errors.</p>
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
                <li>Please enable Wordpress Debug Mode.</li>
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
     *
     */
    public function addLoggerView()
    {

        echo('<script type="text/javascript">' .
            '(function () {' .
            '$.ajax({' .
            'url: "' . plugin_dir_url(__DIR__) . 'clerk_log.log", success: function (data) {' .
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
     * @param $args
     */
    public function addTextField($args)
    {
        //Get settings value
        $options = get_option('clerk_options');

        if (isset($options[$args['label_for']])) {

            $value = $options[$args['label_for']];

        }else {

            $value = '';

        }

        if (isset($args['value'])) {
            $value = $args['value'];
        }
        ?>

        <input type="text" id="<?php echo esc_attr($args['label_for']); ?>"
               name="clerk_options[<?php echo esc_attr($args['label_for']); ?>]"
               value="<?php echo $value; ?>"<?php if (isset($args['readonly'])): ?> readonly<?php endif; ?>>
        <?php
        if (isset($args['description'])) :
            ?>
            <p class="description"
               id="<?php echo $args['label_for']; ?>-description"><?php echo $args['description']; ?></p>
        <?php
        endif;
    }

    /**
     * Add text field
     *
     * @param $args
     */
    public function addCheckboxField($args)
    {
        //Set defaults
        if (esc_attr($args['checked']) == 1) {

            wp_parse_args(get_option('plugin_options'), [$args['label_for'] => '']);

        }

        //Get settings value
        $options = get_option('clerk_options');
        if (isset($options[$args['label_for']])) {

            $value = $options[$args['label_for']];

        }else {

            $value = 0;

        }

        ?>
        <input type="checkbox" id="<?php echo esc_attr($args['label_for']); ?>"
               name="clerk_options[<?php echo esc_attr($args['label_for']); ?>]"
               value="1" <?php checked('1',  $value ); ?>>
        <?php
    }

    /**
     * Add page dropdown
     *
     * @param $args
     */
    public function addPageDropdown($args)
    {
        //Get settings value
        $options = get_option('clerk_options');
        wp_dropdown_pages([
            'selected' => $options[$args['label_for']],
            'name' => sprintf('clerk_options[%s]', $args['label_for'])
        ]);
    }

    /**
     * Add dropdown for powerstep type
     *
     * @param $args
     */
    public function addPowerstepTypeDropdown($args)
    {
        //Get settings value
        $options = get_option('clerk_options');
        ?>
        <select id="<?php echo esc_attr($args['label_for']); ?>"
                name="clerk_options[<?php echo esc_attr($args['label_for']); ?>]">
            <?php foreach (array(Clerk_Powerstep::TYPE_PAGE, Clerk_Powerstep::TYPE_POPUP) as $type) : ?>
                <option value="<?php echo $type; ?>"
                        <?php if ($options['powerstep_type'] === $type) : ?>selected<?php endif; ?>><?php echo __($type, 'clerk'); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * @param $args
     */
    public function addLogLevelDropdown($args)
    {
        //Get settings value
        $options = get_option('clerk_options');
        wp_parse_args(get_option('clerk_options'), [$args['label_for'] => $args['default']]);

        ?>
        <select id="<?php echo esc_attr($args['label_for']); ?>"
                name="clerk_options[<?php echo esc_attr($args['label_for']); ?>]">
            <?php foreach (array('Error + Warn', 'Only Error', 'Error + Warn + Debug Mode') as $level) : ?>
                <option value="<?php echo $level; ?>"
                        <?php if ($options['log_level'] === $level) : ?>selected<?php endif; ?>><?php echo __($level, 'clerk'); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * @param $args
     */
    public function addLogToDropdown($args)
    {


        echo('<div id="clerk-dialog" class="hidden" style="max-width:800px">'.
            '</div>');
        //Get settings value
        $options = get_option('clerk_options');
        wp_parse_args(get_option('clerk_options'), [$args['label_for'] => $args['default']]);
        ?>
        <select id="<?php echo esc_attr($args['label_for']); ?>"
                name="clerk_options[<?php echo esc_attr($args['label_for']); ?>]">
            <?php foreach (array('my.clerk.io') as $to) : ?>
                <option value="<?php echo $to; ?>"
                        <?php if ($options['log_to'] === $to) : ?>selected<?php endif; ?>><?php echo __($to, 'clerk'); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Create options page
     */
    public function clerk_options_page()
    {
        //Add top level menu page
        add_menu_page(
            __('Clerk', 'clerk'),
            __('Clerk', 'clerk'),
            'manage_options',
            'clerk',
            [$this, 'clerk_options_page_html'],
            plugin_dir_url(CLERK_PLUGIN_FILE) . 'assets/img/clerk.png'
        );

        add_submenu_page('clerk', '', __('Clerk Settings', 'clerk'), 'manage_options', 'clerk', [
            $this,
            'clerk_options_page_html'
        ]);

    }

    public function clerk_options_page_html()
    {
        // check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // add error/update messages

        // check if the user have submitted the settings
        // wordpress will add the "settings-updated" $_GET parameter to the url
        if (isset($_GET['settings-updated'])) {
            delete_transient('clerk_api_contents');
            // add settings saved message with the class of "updated"
            add_settings_error('wporg_messages', 'wporg_message', __('Settings Saved', 'wporg'), 'updated');
        }

        // show error/update messages
        settings_errors('wporg_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                // output security fields for the registered setting "wporg"
                settings_fields('clerk');
                // output setting sections and their fields
                // (sections are registered for "wporg", each field is registered to a specific section)
                do_settings_sections('clerk');
                // output save settings button
                submit_button('Save Settings');
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * @param $type
     * @return string
     */
    private function getEmbedUrl($type)
    {
        $options = get_option('clerk_options');

        $publicKey = $options['public_key'];
        $privateKey = $options['private_key'];
        $storePart = $this->getStorePart($publicKey);

        return sprintf('https://my.clerk.io/#/store/%s/analytics/%s?key=%s&private_key=%s&embed=yes', $storePart, $type, $publicKey, $privateKey);
    }

    /**
     * Get first 8 characters of public key
     *
     * @param $publicKey
     *
     * @return string
     */
    private function getStorePart($publicKey)
    {
        return substr($publicKey, 0, 8);
    }

}

new Clerk_Admin_Settings();