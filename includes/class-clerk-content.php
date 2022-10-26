<?php

class Clerk_Content
{
    /**
     * Clerk_Content constructor.
     */
    protected $logger;

    public function __construct()
    {
        add_action('woocommerce_archive_description', [$this, 'clerk_woocommerce_archive_description'], 99);
        add_action('woocommerce_after_cart', [$this, 'clerk_woocommerce_after_cart_table'], 99);
        add_filter('wc_get_template', [$this, 'clerk_wc_get_template'], 99, 2);
        require_once(__DIR__ . '/class-clerk-logger.php');
        $this->logger = new ClerkLogger();
    }

    /**
     * Add content to category if enabled
     */
    public function clerk_woocommerce_archive_description()
    {

        try {

            $category = get_queried_object();
            $options = get_option('clerk_options');

            if (isset($options['category_enabled']) && $options['category_enabled'] && property_exists($category, 'term_id')) :
                
                $templates = explode(',',$options['category_content']);
                $index = 0;
                $class_string = 'clerk_';
                $filter_string = '';
                $unique_filter = (isset($options['category_excl_duplicates']) && $options['category_excl_duplicates']) ? true : false;
                foreach ($templates as $template) {

                    ?>
                    <span class="clerk <?php if($unique_filter){ echo $class_string.(string)$index; } ?>"
                        <?php if($index > 0 && $unique_filter){ echo 'data-exclude-from="'.$filter_string.'"'; }?>
                        data-template="@<?php echo str_replace(' ', '', $template); ?>"
                        data-category="<?php echo $category->term_id; ?>"></span> 
                    <?php
                    if($index > 0){
                        $filter_string .= ', ';
                    }
                    $filter_string .= '.'.$class_string.(string)$index;
                    $index++;
                }
            endif;

        } catch (Exception $e) {

            $this->logger->error('ERROR clerk_woocommerce_archive_description', ['error' => $e->getMessage()]);

        }

    }

    /**
     * Add content after cart if enabled
     */
    public function clerk_woocommerce_after_cart_table()
    {

        try {

            global $woocommerce;
            $items = $woocommerce->cart->get_cart();

            $options = get_option('clerk_options');
            $products = array();

            foreach ($items as $item => $values) {
                $products[] = $values['product_id'];
            }

            if (isset($options['cart_enabled']) && $options['cart_enabled']) {

                $templates = explode(',',$options['cart_content']);
                $index = 0;
                $class_string = 'clerk_';
                $filter_string = '';
                $unique_filter = (isset($options['cart_excl_duplicates']) && $options['cart_excl_duplicates']) ? true : false;
        
                foreach ($templates as $template) {

                    ?>
                    <span class="clerk <?php if($unique_filter){ echo $class_string.(string)$index; } ?>"
                        <?php if($index > 0 && $unique_filter){ echo 'data-exclude-from="'.$filter_string.'"'; }?>
                        data-template="@<?php echo str_replace(' ', '', $template); ?>"
                        data-products="<?php echo json_encode($products); ?>">
                    </span>
                    <?php
                    if($index > 0){
                        $filter_string .= ', ';
                    }
                    $filter_string .= '.'.$class_string.(string)$index;
                    $index++;
                }

            }

        } catch (Exception $e) {

            $this->logger->error('ERROR clerk_woocommerce_after_cart_table', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Rewrite related products template if enabled
     *
     * @param $located
     * @param $template_name
     *
     * @return string
     */
    public function clerk_wc_get_template($located, $template_name)
    {

        try {

            if ($template_name === 'single-product/related.php') {
                $options = get_option('clerk_options');

                if (isset($options['product_enabled']) && $options['product_enabled']) :
                    return clerk_locate_template('clerk-related-products.php');
                endif;
            }

            return $located;

        } catch (Exception $e) {

            $this->logger->error('ERROR clerk_wc_get_template', ['error' => $e->getMessage()]);

        }

    }
}

new Clerk_Content();