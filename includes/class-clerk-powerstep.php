<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Clerk_Powerstep {
    const TYPE_POPUP = 'popup';
    const TYPE_PAGE = 'page';

	/**
	 * Clerk_Powerstep constructor.
	 */
	public function __construct() {
		$this->initHooks();
	}

	/**
	 * Init hooks
	 */
	private function initHooks() {
		add_filter('woocommerce_add_to_cart_redirect', [$this, 'redirect_to_powerstep']);
		add_filter( 'query_vars', [$this, 'add_powerstep_vars'] );
		add_shortcode( 'clerk-powerstep', [$this, 'handle_shortcode'] );
		add_action( 'wp_enqueue_scripts', [$this, 'add_powerstep_files'] );
        add_action( 'wp_ajax_clerk_powerstep', [$this, 'powerstep_ajax'] );
        add_action( 'wp_ajax_nopriv_clerk_powerstep', [$this, 'powerstep_ajax'] );
    }

	/**
	 * If powerstep is enabled, either redirect user to powerstep page or redirect with popup param
	 */
	public function redirect_to_powerstep($url) {
		if ( empty( $_REQUEST['add-to-cart'] ) || ! is_numeric( $_REQUEST['add-to-cart'] ) ) {
			return $url;
		}

		$options = get_option( 'clerk_options' );

		if ( !$options['powerstep_enabled'] || $options['powerstep_type'] !== self::TYPE_PAGE) {
			return $url;
		}

		$product_id = absint( $_REQUEST['add-to-cart'] );

		$adding_to_cart = wc_get_product( $product_id );

		if ( ! $adding_to_cart ) {
			return $url;
		}

		$url = esc_url( get_page_link( $options['powerstep_page'] ) . '?product_id=' . $product_id ) ;

		return $url;
	}

	/**
	 * Add query var for searchterm
	 *
	 * @param $vars
	 *
	 * @return array
	 */
	public function add_powerstep_vars( $vars ) {
		$vars[] = 'show_powerstep';
		$vars[] = 'product_id';

		return $vars;
	}

	/**
	 * Output clerk-powerstep shortcode
	 * @param $atts
	 */
	public function handle_shortcode( $atts ) {
		$options = get_option( 'clerk_options' );

		if ( !$options['powerstep_enabled']) {
			return;
		}

		$product_id = absint( get_query_var('product_id') );

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return;
		}

		return get_clerk_powerstep($product);
	}

	/**
	 * Add powerstep css
	 */
	public function add_powerstep_files()
	{
		$options = get_option( 'clerk_options' );

		if ( !$options['powerstep_enabled']) {
			return;
		}

		if ( is_page( $options['powerstep_page'] ) ) {
			wp_enqueue_style( 'clerk_powerstep_css', plugins_url('../assets/css/powerstep.css', __FILE__) );
		}

        wp_enqueue_script( 'clerk_powerstep_js', plugins_url('../assets/js/powerstep.js', __FILE__), array('jquery') );
        wp_localize_script( 'clerk_powerstep_js', 'variables',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'type' => $options['powerstep_type'],
                'powerstep_url' => esc_url( get_page_link( $options['powerstep_page'] ) )
            )
        );
	}

    /**
     * Get powerstep popup content
     */
    public function powerstep_ajax()
    {
        $product_id = absint( $_POST['product_id'] );

        $product = wc_get_product( $product_id );

        if ( ! $product ) {
            return;
        }

        echo get_clerk_powerstep_popup($product);
        wp_die();
    }
}

new Clerk_Powerstep();