<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Clerk_Powerstep {
	const TYPE_POPUP = 'popup';
	const TYPE_PAGE = 'page';
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
		$options = get_option( 'clerk_options' );

		// if powerstep disabled, there's no need to init hooks
		if ( ! isset( $options['powerstep_enabled'] ) ) {
			return false;
		}

		add_filter( 'woocommerce_add_to_cart_redirect', [ $this, 'redirect_to_powerstep' ] );
		add_filter( 'query_vars', [ $this, 'add_powerstep_vars' ] );
		add_shortcode( 'clerk-powerstep', [ $this, 'handle_shortcode' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'add_powerstep_files' ] );
		add_action( 'wp_ajax_clerk_powerstep', [ $this, 'powerstep_ajax' ] );
		add_action( 'wp_ajax_nopriv_clerk_powerstep', [ $this, 'powerstep_ajax' ] );
	}

	/**
	 * If powerstep is enabled, either redirect user to powerstep page or redirect with popup param
	 */
	public function redirect_to_powerstep( $url ) {

        try {

            if (empty($_REQUEST['add-to-cart']) || !is_numeric($_REQUEST['add-to-cart'])) {
                return $url;
            }

            $options = get_option('clerk_options');

            if (!$options['powerstep_enabled'] || $options['powerstep_type'] !== self::TYPE_PAGE) {
                return $url;
            }

            $product_id = absint($_REQUEST['add-to-cart']);

            $adding_to_cart = wc_get_product($product_id);

            if (!$adding_to_cart) {
                return $url;
            }

            $url = esc_url(get_page_link($options['powerstep_page']) . '?product_id=' . $product_id);

            return $url;

        } catch (Exception $e) {

            $this->logger->error('ERROR redirect_to_powerstep', ['error' => $e->getMessage()]);

        }

	}

	/**
	 * Add query var for searchterm
	 *
	 * @param $vars
	 *
	 * @return array
	 */
	public function add_powerstep_vars( $vars ) {

        try {

            $vars[] = 'show_powerstep';
            $vars[] = 'product_id';

            return $vars;

        } catch (Exception $e) {

            $this->logger->error('ERROR add_powerstep_vars', ['error' => $e->getMessage()]);

        }

	}

	/**
	 * Output clerk-powerstep shortcode
	 *
	 * @param $atts
	 */
	public function handle_shortcode( $atts ) {

        try {

            $options = get_option('clerk_options');

            if (!$options['powerstep_enabled']) {
                return;
            }

            $product_id = absint(get_query_var('product_id'));

            $product = wc_get_product($product_id);

            if (!$product) {
                return;
            }

            return get_clerk_powerstep($product);

        } catch (Exception $e) {

            $this->logger->error('ERROR handle_shortcode', ['error' => $e->getMessage()]);

        }

	}

	/**
	 * Add powerstep css
	 */
	public function add_powerstep_files() {

        try {

            require_once(__DIR__ . '../includes/class-clerk-logger.php');
            $logger = new ClerkLogger();
            $options = get_option('clerk_options');

            if (!$options['powerstep_enabled']) {
                return;
            }

            if (is_page($options['powerstep_page'])) {
                wp_enqueue_style('clerk_powerstep_css', plugins_url('../assets/css/powerstep.css', __FILE__));
            }

            wp_enqueue_script('clerk_powerstep_js', plugins_url('../assets/js/powerstep.js', __FILE__), array('jquery'));
            wp_localize_script('clerk_powerstep_js', 'variables',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'type' => $options['powerstep_type'],
                    'powerstep_url' => esc_url(get_page_link($options['powerstep_page']))
                )
            );

        } catch (Exception $e) {

            $this->logger->error('ERROR add_powerstep_files', ['error' => $e->getMessage()]);

        }

	}

	/**
	 * Get powerstep popup content
	 */
	public function powerstep_ajax() {

        try {

            $product_id = absint($_POST['product_id']);

            $product = wc_get_product($product_id);

            if (!$product) {
                return;
            }

            echo get_clerk_powerstep_popup($product);
            wp_die();

        } catch (Exception $e) {

            $this->logger->error('ERROR powerstep_ajax', ['error' => $e->getMessage()]);

        }

	}
}

new Clerk_Powerstep();