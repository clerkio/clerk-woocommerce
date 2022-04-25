<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Clerk_Basket {
    protected $logger;

	/**
	 * Clerk_Powerstep constructor.
	 */
	public function __construct() {
		$this->initHooks();
        require_once( __DIR__ . '/class-clerk-logger.php' );
        $this->logger = new ClerkLogger();
	}

	/**
	 * Init hooks
	 */
	private function initHooks() {

        $options = get_option('clerk_options');
        if ( !isset($options['collect_baskets']) ) {
            return;
        }

		add_filter( 'woocommerce_add_to_cart_redirect', [ $this, 'update_basket' ] );
        add_filter( 'template_redirect', [ $this, 'update_basket' ] );

	}

	/**
	 * If collect basket is enabled, track baskets for abandoned cart support.
	 */
	public function update_basket( $url ) {

        try {

            $options = get_option('clerk_options');

            global $current_user;
            global $woocommerce;

            $items = $woocommerce->cart->get_cart();
            $email = (string) $current_user->user_email;

            if (empty($_REQUEST['add-to-cart']) || !is_numeric($_REQUEST['add-to-cart'])) {
                if (empty($_REQUEST['removed_item']) || !is_numeric($_REQUEST['removed_item'])) {
                    if (empty($_REQUEST['product_id']) || !is_numeric($_REQUEST['product_id'])) {
                        return $url;
                    }
                }
            }

            $_product_ids = [];

            foreach($items as $item => $values) { 
                if (!in_array($values['data']->get_id(), $_product_ids)) {
                    array_push($_product_ids, $values['data']->get_id());
                }
            }


            if (count($_product_ids) > 0) {

                if (!empty($email)) {

                    $Endpoint = 'https://api.clerk.io/v2/log/basket/set';

                    $data_string = json_encode([
                        'key' => $options['public_key'],
                        'products' => $_product_ids,
                        'email' => $email]);

                    $args = array(
                        'body'        => $data_string,
                        'method'      => 'POST'
                    );

                    wp_remote_request( $Endpoint, $args );                

                } else {
                    
                          
                }
            }

        } catch (Exception $e) {

            $this->logger->error('ERROR update_basket', ['error' => $e->getMessage()]);

        }

	}
   
}

new Clerk_Basket();