<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Clerk_Rest_Api extends WP_REST_Server
{
    /**
     * Clerk_Rest_Api constructor.
     */
    protected $logger;

    public function __construct()
    {
        $this->initHooks();
        require_once(__DIR__ . '/class-clerk-logger.php');
        $this->logger = new ClerkLogger();

    }

    /**
     * Init hooks
     */
    private function initHooks()
    {
        add_action('rest_api_init', [$this, 'add_rest_api_routes']);
        add_filter('rest_pre_serve_request', [$this, 'rest_pre_serve_request'], 10, 3);
    }

    public function __ini()
    {
        $this->initHooks();
        $this->logger = new ClerkLogger();
    }

    /**
     * Add REST API routes
     */
    public function add_rest_api_routes()
    {
        //Product endpoint
        register_rest_route('clerk', '/product', [
            'methods' => 'GET',
            'callback' => [$this, 'product_endpoint_callback'],
        ]);

        //Product endpoint
        register_rest_route('clerk', '/page', [
            'methods' => 'GET',
            'callback' => [$this, 'page_endpoint_callback'],
        ]);

        //Category endpoint
        register_rest_route('clerk', '/category', [
            'methods' => 'GET',
            'callback' => [$this, 'category_endpoint_callback'],
        ]);

        //Order endpoint
        register_rest_route('clerk', '/order', [
            'methods' => 'GET',
            'callback' => [$this, 'order_endpoint_callback'],
        ]);

        //Customer endpoint
        register_rest_route('clerk', '/customer', [
            'methods' => 'GET',
            'callback' => [$this, 'customer_endpoint_callback'],
        ]);

        //Version endpoint
        register_rest_route('clerk', '/version', [
            'methods' => 'GET',
            'callback' => [$this, 'version_endpoint_callback'],
        ]);

        //Version endpoint
        register_rest_route('clerk', '/plugin', [
            'methods' => 'GET',
            'callback' => [$this, 'plugin_endpoint_callback'],
        ]);

        //Log endpoint
        register_rest_route('clerk', '/log', [
            'methods' => 'GET',
            'callback' => [$this, 'log_endpoint_callback'],
        ]);
    }

    /**
     * Serve request, taking into account the debug parameter
     *
     * @param $served
     * @param $result
     * @param $request
     *
     * @return bool|string
     */
    public function rest_pre_serve_request($served, $result, $request)
    {

        try {

            //Determine if this this is a clerk request
            if ($attributes = $request->get_attributes()) {
                if (is_array($attributes['callback']) && $attributes['callback'][0] instanceof $this) {
                    // Embed links inside the request.
                    $result = $this->response_to_data($result, isset($_GET['_embed']));

                    if ($request->get_param('debug') && $request->get_param('debug') == true) {
                        $result = wp_json_encode($result, JSON_PRETTY_PRINT);
                    } else {
                        $result = wp_json_encode($result);
                    }

                    $json_error_message = $this->get_json_last_error();
                    if ($json_error_message) {
                        $json_error_obj = new WP_Error('rest_encode_error', $json_error_message,
                            array('status' => 500));
                        $result = $this->error_to_response($json_error_obj);
                        $result = wp_json_encode($result->data[0]);
                    }

                    echo $result;

                    return true;
                }
            }

            return false;

        } catch (Exception $e) {

            $this->logger->error('ERROR rest_pre_serve_request', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Handle product endpoint
     *
     * @param WP_REST_Request $request
     *
     * @return array|WP_REST_Response
     */
    public function product_endpoint_callback(WP_REST_Request $request)
    {
        $options = get_option('clerk_options');

        try {

            if (!$this->validateRequest($request)) {
                return $this->getUnathorizedResponse();
            }

            $limit = $request->get_param('limit') ? $request->get_param('limit') : -1;
            $page = ($request->get_param('page') !== null) ? $request->get_param('page') : 0;
            $orderby = $request->get_param('orderby') ? $request->get_param('orderby') : 'product_id';
            $order = $request->get_param('order') ? $request->get_param('order') : 'ASC';

            $offset = ($request->get_param('page') === 0) ? 0 : $page * $limit;

            $products = clerk_get_products(array(
                'limit' => $limit,
                'page' => $page,
                'orderby' => $orderby,
                'order' => $order,
                'status' => array('publish'),
                'paginate' => true,
                'offset' => $offset
            ));

            $FinalProductsArray = [];

            foreach ($products->products as $product) {

                //Check include out of stock products
                if (!isset($options['outofstock_products'])) {

                    if (!$product->stock_status === 'instock') {

                        continue;

                    }

                }

                /** @var WC_Product $product */
                $categories = wp_get_post_terms($product->get_id(), 'product_cat');

                $on_sale = $product->is_on_sale();

                if ($product->is_type('variable')) {
                    /**
                     * Variable product sync fields
                     * Will sync the lowest price, and set the sale flag if that variant is on sale.
                     */

                    $variation = $product->get_available_variations();
                    $stock_quantity = 0;
                    $displayPrice = array();
                    $regularPrice = array();
                    foreach ($variation as $v) {
                        $vId = $v['variation_id'];
                        $displayPrice[$vId] = $v['display_price'];
                        $regularPrice[$vId] = $v['display_regular_price'];
                        $variation_obj = new WC_Product_variation($v['variation_id']);
                        $stock_quantity += $variation_obj->get_stock_quantity();
                    }

                    if (empty($displayPrice)) {

                        continue;

                    }

                    $lowestDisplayPrice = array_keys($displayPrice, min($displayPrice)); // Find the corresponding product ID
                    $price = $displayPrice[$lowestDisplayPrice[0]]; // Get the lowest price
                    $list_price = $regularPrice[$lowestDisplayPrice[0]]; // Get the corresponding list price (regular price)

                    if ($price === $list_price) $on_sale = false; // Remove the sale flag if the cheapest variant is not on sale
                } else {
                    /**
                     * Default single product sync fields
                     */
                    $price = $product->get_price();
                    $list_price = $product->get_regular_price();
                }

                if ($product->managing_stock() && !isset($options['outofstock_products']) && $product->get_stock_quantity() === 0) {

                    if (isset($stock_quantity) && $stock_quantity === 0) {

                        continue;

                    }elseif(!isset($stock_quantity)) {

                        continue;

                    }elseif(!$product->is_in_stock()) {

                        continue;

                    }
                } elseif (! $product->managing_stock() && ! $product->is_in_stock() && !isset($options['outofstock_products'])) {

                    continue;

                }

                $productArray = [
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'description' => get_post_field('post_content', $product->get_id()),
                    'price' => (float)$price,
                    'list_price' => (float)$list_price,
                    'image' => wp_get_attachment_image_src($product->get_image_id(),'medium')[0],
                    'url' => $product->get_permalink(),
                    'categories' => wp_list_pluck($categories, 'term_id'),
                    'sku' => $product->get_sku(),
                    'on_sale' => $on_sale,
                    'type' => $product->get_type(),
                ];

                $productArray['all_images'] = [];

                foreach (get_intermediate_image_sizes() as $key => $image_size) {

                    if (!in_array(wp_get_attachment_image_src($product->get_image_id(),$image_size)[0], $productArray['all_images'])) {

                        array_push($productArray['all_images'] , wp_get_attachment_image_src($product->get_image_id(), $image_size)[0]);

                    }

                }

                if (!empty($product->get_stock_quantity())) {

                    $productArray['stock'] = $product->get_stock_quantity();

                }elseif (isset($stock_quantity)) {

                    $productArray['stock'] = $stock_quantity;

                }

                //Append additional fields
                foreach ($this->getAdditionalFields() as $field) {

                    if ($field == '') {

                        continue;

                    }

                    if ($product->get_attribute($field)) {

                        $productArray[$this->clerk_friendly_attributes($field)] = str_replace(' ','',explode(',',$product->get_attribute($field)));

                    }elseif (get_post_meta( $product->get_id(), $field, true )) {

                        $productArray[$this->clerk_friendly_attributes($field)] = get_post_meta( $product->get_id(), $field, true );

                    }elseif (wp_get_post_terms( $product->get_id(), strtolower($field), array('fields'=> 'names'))) {

                        $attrubutefield = wp_get_post_terms( $product->get_id(), strtolower($field), array('fields'=> 'names'));

                        if (!array_key_exists('errors', $attrubutefield )) {

                            $productArray[strtolower($this->clerk_friendly_attributes($field))] = $attrubutefield;

                        }

                    }

                }

                $productArray = apply_filters('clerk_product_array', $productArray, $product);

                $FinalProductsArray[] = $productArray;


            }

            $this->logger->log('Successfully generated JSON with ' . count($FinalProductsArray) . ' products', ['error' => 'None']);

            header('User-Agent: ClerkExtensionBot WooCommerce/v' .get_bloginfo('version'). ' Clerk/v'.get_file_data(CLERK_PLUGIN_FILE, array('version'), 'plugin')[0]. ' PHP/v'.phpversion());
            return $FinalProductsArray;

        } catch (Exception $e) {

            $this->logger->error('ERROR product_endpoint_callback', ['error' => $e->getMessage()]);

        }
    }

    function clerk_friendly_attributes($attribute) {
        $attribute = strtolower($attribute);
        $attribute=str_replace('æ','ae',$attribute);
        $attribute=str_replace('ø','oe',$attribute);
        $attribute=str_replace('å','aa',$attribute);
        return urlencode($attribute);
    }

    public function page_endpoint_callback(WP_REST_Request $request)
    {
        $options = get_option('clerk_options');

        try {

            if (!in_array('include_pages', $options)) {
                return [];
            }

            if (!$this->validateRequest($request)) {
                return $this->getUnathorizedResponse();
            }

            $pages = get_pages();
            $FinalPageArray = [];

            foreach ($pages as $page) {

                if (!empty($page->post_content)) {

                    $page_additional_fields = explode(',',$options['page_additional_fields']);

                    switch ($page->post_type) {

                        case 'post':
                            $Type = 'Blog Post';
                            break;

                        case 'page':
                            $Type = 'CMS Page';
                            break;

                        default:
                            $Type = 'CMS Page';

                    }

                    $page_draft = [
                        'id' => $page->ID,
                        'type' => strtolower($Type),
                        'url' => $page->guid,
                        'title' => $page->post_title,
                        'text' => $page->post_content
                    ];

                    if (!$this->ValidatePage($page_draft)) {

                        continue;

                    }

                    foreach ($page_additional_fields as $page_additional_field) {
                        $page_additional_field = str_replace(' ','',$page_additional_field);
                        if (!empty($page_additional_field)) {

                            $page_draft[$page_additional_field] = $page->{$page_additional_field};

                        }

                    }

                    $FinalPageArray[] = $page_draft;

                }

            }

            $this->logger->log('Successfully generated JSON with ' . count($FinalPageArray) . ' pages', ['error' => 'None']);
            header('User-Agent: ClerkExtensionBot WooCommerce/v' .get_bloginfo('version'). ' Clerk/v'.get_file_data(CLERK_PLUGIN_FILE, array('version'), 'plugin')[0]. ' PHP/v'.phpversion());
            return $FinalPageArray;

        } catch (Exception $e) {

            $this->logger->error('ERROR page_endpoint_callback', ['error' => $e->getMessage()]);

        }
    }

    public function customer_endpoint_callback(WP_REST_Request $request)
    {
        $options = get_option('clerk_options');

        try {

            if (!in_array('customer_sync_enabled', $options)) {
                return [];
            }

            if (!$this->validateRequest($request)) {
                return $this->getUnathorizedResponse();
            }
            global $wpdb;
            $customer_ids = $wpdb->get_col("SELECT DISTINCT meta_value  FROM $wpdb->postmeta
             WHERE meta_key = '_customer_user' AND meta_value > 0");

            $FinalCustomerArray = [];

            if (isset($options['customer_sync_customer_fields'])) {

                $customer_additional_fields = explode(',', str_replace(' ', '', $options['customer_sync_customer_fields']));

            } else {

                $customer_additional_fields = [];

            }

            foreach ($customer_ids as $customer_id) {
                $customer = new WP_User($customer_id);
                $_customer = [];
                $_customer['name'] = $customer->data->display_name;
                $_customer['id'] = $customer->data->ID;
                $_customer['email'] = $customer->data->user_email;

                foreach ($customer_additional_fields as $customer_additional_field) {

                    if (isset($customer->data->{$customer_additional_field})) {

                        $_customer[$customer_additional_field] = $customer->data->{$customer_additional_field};

                    }

                }

                $FinalCustomerArray[] = $_customer;

            }

            $this->logger->log('Successfully generated JSON with ' . count($FinalCustomerArray) . ' customers', ['error' => 'None']);
            header('User-Agent: ClerkExtensionBot WooCommerce/v' .get_bloginfo('version'). ' Clerk/v'.get_file_data(CLERK_PLUGIN_FILE, array('version'), 'plugin')[0]. ' PHP/v'.phpversion());
            return $FinalCustomerArray;

        } catch (Exception $e) {

            $this->logger->error('ERROR customer_endpoint_callback', ['error' => $e->getMessage()]);

        }
    }

    public function ValidatePage($Page) {

        foreach ($Page as $key => $content) {

            if (empty($content)) {

                return false;

            }

        }

        return true;

    }

    /**
     * Validate request
     *
     * @param $request
     *
     * @return bool
     */
    private function validateRequest($request)
    {

        try {

            $options = get_option('clerk_options');

            $public_key = $request->get_param('key');
            $private_key = $request->get_param('private_key');

            if ($public_key === $options['public_key'] && $private_key === $options['private_key']) {

                return true;
            }

            $this->logger->warn('Failed to validate API Keys', ['response' => false]);

            return false;

        } catch (Exception $e) {

            $this->logger->error('ERROR validateRequest', ['error' => $e->getMessage()]);

        }

    }

    /**
     * Get unathorized response
     *
     * @return WP_REST_Response
     */
    private function getUnathorizedResponse()
    {

        try {

            $response = new WP_REST_Response([
                'error' => [
                    'code' => 403,
                    'message' => __('The supplied public or private key is invalid', 'clerk')
                ]
            ]);
            $response->set_status(403);

            $this->logger->warn('The supplied public or private key is invalid', ['status' => 403]);

            return $response;

        } catch (Exception $e) {

            $this->logger->error('ERROR getUnathorizedResponse', ['error' => $e->getMessage()]);

        }

    }

    /**
     * Get additional fields for product export
     *
     * @return array
     */
    private function getAdditionalFields()
    {

        try {

            $options = get_option('clerk_options');

            $additional_fields = $options['additional_fields'];

            $fields = explode(',', $additional_fields);

            foreach ($fields as $key => $field) {

                $fields[$key] = str_replace(' ','', $field);

            }

            if (!is_array($fields)) {
                return array();
            }

            return $fields;

        } catch (Exception $e) {

            $this->logger->error('ERROR getAdditionalFields', ['error' => $e->getMessage()]);

        }

    }

    /**
     * Handle category endpoint
     *
     * @param WP_REST_Request $request
     *
     * @return array|WP_REST_Response
     */
    public function category_endpoint_callback(WP_REST_Request $request)
    {

        try {

            if (!$this->validateRequest($request)) {
                return $this->getUnathorizedResponse();
            }

            $limit = $request->get_param('limit') ? $request->get_param('limit') : 0;
            $page = $request->get_param('page') ? $request->get_param('page') - 1 : 0;
            $offset = (int)$request->get_param('page') * $limit;
            $orderby = $request->get_param('orderby') ? $request->get_param('orderby') : 'date';
            $order = $request->get_param('order') ? $request->get_param('order') : 'DESC';

            $args = [
                'number' => $limit,
                'orderby' => $orderby,
                'order' => $order,
                'offset' => $offset,
                'hide_empty' => true,
            ];

            $product_categories = get_terms('product_cat', $args);

            $categories = [];

            foreach ($product_categories as $product_category) {
                $category = [
                    'id' => $product_category->term_id,
                    'name' => $product_category->name,
                    'url' => get_term_link($product_category),
                ];

                if ($product_category->parent > 0) {
                    $category['parent'] = $product_category->parent;
                }

                $subcategories = get_term_children($product_category->term_id, 'product_cat');
                $category['subcategories'] = $subcategories;

                $category = apply_filters('clerk_category_array', $category, $product_category);

                $categories[] = $category;
            }

            $this->logger->log('Successfully generated category JSON with ' . count($categories) . ' categories', ['error' => 'None']);

        } catch (Exception $e) {

            $this->logger->error('ERROR category_endpoint_callback', ['error' => $e->getMessage()]);

        }
        header('User-Agent: ClerkExtensionBot WooCommerce/v' .get_bloginfo('version'). ' Clerk/v'.get_file_data(CLERK_PLUGIN_FILE, array('version'), 'plugin')[0]. ' PHP/v'.phpversion());
        return $categories;
    }

    /**
     * Handle order endpoint
     *
     * @param WP_REST_Request $request
     *
     * @return array|WP_REST_Response
     */
    public function order_endpoint_callback(WP_REST_Request $request)
    {

        try {

            if (!$this->validateRequest($request)) {
                return $this->getUnathorizedResponse();
            }

            $options = get_option('clerk_options');

            if (in_array('disable_order_synchronization', $options) && $options['disable_order_synchronization']) {
                return [];
            }

            $limit = $request->get_param('limit') ? $request->get_param('limit') : -1;
            $page = $request->get_param('page') ? $request->get_param('page') + 1 : 1;
            $start_date = $request->get_param('start_date') ? $request->get_param('start_date') : 'today - 200 years';
            $end_date = $request->get_param('end_date') ? $request->get_param('end_date') : 'today + 1 day';

            $orders = wc_get_orders([
                'limit' => $limit,
                'offset' => ($page - 1) * $limit,
                'type' => 'shop_order',
                'status' => 'completed',
                'date_query' => array(
                    'after' => date('Y-m-d', strtotime($start_date)),
                    'before' => date('Y-m-d', strtotime($end_date))
                )
            ]);

            $order_array = [];

            foreach ($orders as $order) {
                /** @var WC_Order $order */
                $order_items = [];
                $valid = true;

                //Get order products
                foreach ($order->get_items() as $item) {
                    if ($item['qty'] > 0) {
                        if ($item['line_subtotal'] > 0) {
                            $order_items[] = array(
                                'id' => $item['product_id'],
                                'quantity' => $item['qty'],
                                'price' => ($item['line_subtotal'] / $item['qty']),
                            );
                        }
                    }
                }

                if (empty($order_items)) {
                    $valid = false;
                }

                $order_object = [
                    'products' => $order_items,
                    'time' => strtotime($order->order_date),
                    'class' => get_class($order)
                ];

                //Include email if defined
                if (in_array('collect_emails', $options) && $options['collect_emails']) {
                    $order_object['email'] = $order->billing_email;
                }

                //id is a protected property in 3.0
                if (clerk_check_version()) {
                    $order_object['id'] = $order->get_id();
                } else {
                    $order_object['id'] = $order->id;
                }

                if ($order->customer_id > 0) {
                    $order_object['customer'] = $order->customer_id;
                }

                if ($valid) {
                    $order_object = apply_filters('clerk_order_array', $order_object, $order);
                    $order_array[] = $order_object;
                }
            }

            $this->logger->log('Successfully generated order JSON with ' . count($order_array) . ' orders', ['error' => 'None']);

        } catch (Exception $e) {

            $this->logger->error('ERROR order_endpoint_callback', ['error' => $e->getMessage()]);

        }
        header('User-Agent: ClerkExtensionBot WooCommerce/v' .get_bloginfo('version'). ' Clerk/v'.get_file_data(CLERK_PLUGIN_FILE, array('version'), 'plugin')[0]. ' PHP/v'.phpversion());
        return $order_array;
    }

    /**
     * Handle version endpoint
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     */
    public function version_endpoint_callback(WP_REST_Request $request)
    {

        try {

            if (!$this->validateRequest($request)) {
                return $this->getUnathorizedResponse();
            }

            $response =  new WP_REST_Response([
                'platform' => 'WooCommerce',
                'platform_version' => get_bloginfo('version'),
                'clerk_version' => get_file_data(CLERK_PLUGIN_FILE, array('version'), 'plugin')[0],
                'php_version' => phpversion()
            ]);
            $response->header( 'User-Agent', 'ClerkExtensionBot WooCommerce/v' .get_bloginfo('version'). ' Clerk/v'.get_file_data(CLERK_PLUGIN_FILE, array('version'), 'plugin')[0]. ' PHP/v'.phpversion() );

            $this->logger->log('Successfully generated Version JSON', ['response' => $response]);

            return $response;

        } catch (Exception $e) {

            $this->logger->error('ERROR version_endpoint_callback', ['error' => $e->getMessage()]);

        }

    }

    public function plugin_endpoint_callback(WP_REST_Request $request)
    {

        try {

            if (!$this->validateRequest($request)) {
                return $this->getUnathorizedResponse();
            }

            $plugins = get_plugins();



            $response =  new WP_REST_Response($plugins);
            $response->header( 'User-Agent', 'ClerkExtensionBot WooCommerce/v' .get_bloginfo('version'). ' Clerk/v'.get_file_data(CLERK_PLUGIN_FILE, array('version'), 'plugin')[0]. ' PHP/v'.phpversion() );

            $this->logger->log('Successfully generated Plugin JSON', ['response' => $response]);

            return $response;

        } catch (Exception $e) {

            $this->logger->error('ERROR plugin_endpoint_callback', ['error' => $e->getMessage()]);

        }

    }

}

new Clerk_Rest_Api();