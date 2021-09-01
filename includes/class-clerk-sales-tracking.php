<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Clerk_Sales_Tracking
{
    /**
     * Clerk_Sales_Tracking constructor.
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
        add_action('woocommerce_thankyou', [$this, 'add_sales_tracking']);
    }

    /**
     * Include sales tracking
     */
    public function add_sales_tracking($order_id)
    {

        try {

            $order = wc_get_order($order_id);

            $products = [];
            $items = $order->get_items();

            //Iterate products, adding to products array
            foreach ($items as $item) {
                $products[] = [
                    'id' => $item['product_id'],
                    'quantity' => $item['qty'],
                    'price' => $item['line_subtotal'] / $item['qty'],
                ];
            }

            $order_array = [
                'id' => $order_id,
                'email' => $order->billing_email,
                'products' => $products,
            ];

            $order_array = apply_filters('clerk_tracking_order_array', $order_array, $order);
            ?>
            <span
                    class="clerk"
                    data-api="log/sale"
                    data-sale="<?php echo $order_array['id']; ?>"
                    data-email="<?php echo $order_array['email']; ?>"
                    data-products='<?php echo json_encode($order_array['products']); ?>'>
            </span>
            <script type="text/javascript">
            (function () {
                var clerk_no_productids = [];
                Clerk('cart', 'set', clerk_no_productids);
            })();
            </script>
            <?php

        } catch (Exception $e) {

            $this->logger->error('ERROR add_sales_tracking', ['error' => $e->getMessage()]);

        }

	}
}

new Clerk_Sales_Tracking();