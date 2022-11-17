<?php
/**
 * Plugin Name: Clerk
 * Plugin URI: https://clerk.io/
 * Description: Clerk.io Turns More Browsers Into Buyers
 * Version: 3.8.3
 * Author: Clerk.io
 * Author URI: https://clerk.io
 *
 * Text Domain: clerk
 * Domain Path: /i18n/languages/
 * License: MIT
 *
 * @package clerkio/clerk-woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Clerk_Powerstep Class
 *
 * Clerk Module Core Class
 */
class Clerk_Powerstep {

	const TYPE_POPUP = 'popup';
	const TYPE_PAGE  = 'page';

	/**
	 * Error and Warning Logger
	 *
	 * @var $logger Clerk_Logger
	 */
	protected $logger;

	/**
	 * Clerk_Powerstep constructor.
	 */
	public function __construct() {
		$this->init_hooks();
		include_once __DIR__ . '/class-clerk-logger.php';
		$this->logger = new Clerk_Logger();
	}

	/**
	 * Init hooks
	 */
	private function init_hooks() {
		$options = get_option( 'clerk_options' );

		// if powerstep disabled, there's no need to init hooks.
		if ( ! isset( $options['powerstep_enabled'] ) ) {
			return false;
		}

		add_filter( 'woocommerce_add_to_cart_redirect', array( $this, 'redirect_to_powerstep' ) );
		add_filter( 'template_redirect', array( $this, 'redirect_to_powerstep_no_ajax' ) );
		add_filter( 'query_vars', array( $this, 'add_powerstep_vars' ) );
		add_shortcode( 'clerk-powerstep', array( $this, 'handle_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'add_powerstep_files' ) );
		add_action( 'wp_ajax_clerk_powerstep', array( $this, 'powerstep_ajax' ) );
		add_action( 'wp_ajax_nopriv_clerk_powerstep', array( $this, 'powerstep_ajax' ) );

	}

	/**
	 * If powerstep is enabled, either redirect user to powerstep page or redirect with popup param
	 *
	 * @param string $url Powerstep Url.
	 */
	public function redirect_to_powerstep( $url ) {

		try {
			$add_to_cart_param = false;
			$add_to_cart_param = ( null !== filter_input( INPUT_POST, 'add-to-cart', FILTER_SANITIZE_STRING ) ) ? filter_input( INPUT_POST, 'add-to-cart', FILTER_SANITIZE_STRING ) : $add_to_cart_param;
			$add_to_cart_param = ( null !== filter_input( INPUT_GET, 'add-to-cart', FILTER_SANITIZE_STRING ) ) ? filter_input( INPUT_GET, 'add-to-cart', FILTER_SANITIZE_STRING ) : $add_to_cart_param;
			if ( $add_to_cart_param ) {
				if ( ! is_numeric( $add_to_cart_param ) ) {
					return $url;
				}
			} else {
				return $url;
			}

			$options = get_option( 'clerk_options' );

			if ( ! $options['powerstep_enabled'] || self::TYPE_PAGE !== $options['powerstep_type'] ) {
				return $url;
			}

			$product_id = absint( $add_to_cart_param );

			$adding_to_cart = wc_get_product( $product_id );

			if ( ! $adding_to_cart ) {
				return $url;
			}

			$url = esc_url_raw( get_page_link( $options['powerstep_page'] ) . '?product_id=' . $product_id );

			return $url;

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR redirect_to_powerstep', array( 'error' => $e->getMessage() ) );

		}

	}

	/**
	 * If powerstep is enabled, either redirect user to powerstep page or redirect with popup param.
	 *
	 * @param string $url Powerstep Url.
	 */
	public function redirect_to_powerstep_no_ajax( $url ) {

		try {

			$add_to_cart_param = false;
			$add_to_cart_param = ( null !== filter_input( INPUT_POST, 'add-to-cart', FILTER_SANITIZE_STRING ) ) ? filter_input( INPUT_POST, 'add-to-cart', FILTER_SANITIZE_STRING ) : $add_to_cart_param;
			$add_to_cart_param = ( null !== filter_input( INPUT_GET, 'add-to-cart', FILTER_SANITIZE_STRING ) ) ? filter_input( INPUT_GET, 'add-to-cart', FILTER_SANITIZE_STRING ) : $add_to_cart_param;
			if ( $add_to_cart_param ) {
				if ( ! is_numeric( $add_to_cart_param ) ) {
					return $url;
				}
			} else {
				return $url;
			}
			$options = get_option( 'clerk_options' );

			$product_id = absint( $add_to_cart_param );

			if ( ! $options['powerstep_enabled'] || self::TYPE_PAGE !== $options['powerstep_type'] ) {

				if ( null === filter_input( INPUT_GET, 'clerk_powerstep', FILTER_SANITIZE_STRING ) ) {
					$_uri        = isset( $_SERVER[ REQUEST_URI ] ) ? sanitize_text_field( wp_unslash( $_SERVER[ REQUEST_URI ] ) ) : '';
					$_host       = isset( $_SERVER[ HTTP_HOST ] ) ? sanitize_text_field( wp_unslash( $_SERVER[ HTTP_HOST ] ) ) : '';
					$actual_link = ( isset( $_SERVER['HTTPS'] ) && 'on' === sanitize_text_field( wp_unslash( $_SERVER['HTTPS'] ) ) ? 'https' : 'http' ) . "://$_host$_uri";
					$_url        = str_replace( '?add-to-cart=' . $product_id, '', $actual_link ) . '?clerk_powerstep=true&product_id=' . $product_id;
					header( 'Location: ' . $_url );
					return $url;

				} else {
					return $url;
				}
			}

			$adding_to_cart = wc_get_product( $product_id );

			if ( ! $adding_to_cart ) {
				return $url;
			}

			$url = esc_url_raw( get_page_link( $options['powerstep_page'] ) . '?product_id=' . $product_id );

			header( 'Location: ' . $url );

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR redirect_to_powerstep_no_ajax', array( 'error' => $e->getMessage() ) );

		}

	}

	/**
	 * Add powerstep variables
	 *
	 * @param array $vars Product Info and Display data array.
	 *
	 * @return array
	 */
	public function add_powerstep_vars( $vars ) {

		try {

			$vars[] = 'show_powerstep';
			$vars[] = 'product_id';

			return $vars;

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR add_powerstep_vars', array( 'error' => $e->getMessage() ) );

		}

	}

	/**
	 * Output clerk-powerstep shortcode
	 *
	 * @return html
	 */
	public function handle_shortcode() {

		try {

			$options = get_option( 'clerk_options' );

			if ( ! $options['powerstep_enabled'] ) {
				return;
			}

			$product_id = absint( get_query_var( 'product_id' ) );

			$product = wc_get_product( $product_id );

			if ( ! $product ) {
				return;
			}

			return get_clerk_powerstep( $product );

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR handle_shortcode', array( 'error' => $e->getMessage() ) );

		}

	}

	/**
	 * Add powerstep css
	 */
	public function add_powerstep_files() {

		try {

			$options = get_option( 'clerk_options' );

			if ( ! $options['powerstep_enabled'] ) {
				return;
			}

			if ( is_page( $options['powerstep_page'] ) ) {
				wp_enqueue_style( 'clerk_powerstep_css', plugins_url( '../assets/css/powerstep.css', __FILE__ ), array(), get_bloginfo( 'version' ) );
			}

			wp_enqueue_script( 'clerk_powerstep_js', plugins_url( '../assets/js/powerstep.js', __FILE__ ), array( 'jquery' ), get_bloginfo( 'version' ), true );
			wp_localize_script(
				'clerk_powerstep_js',
				'variables',
				array(
					'ajax_url'      => admin_url( 'admin-ajax.php' ),
					'type'          => $options['powerstep_type'],
					'powerstep_url' => esc_url_raw( get_page_link( $options['powerstep_page'] ) ),
				)
			);

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR add_powerstep_files', array( 'error' => $e->getMessage() ) );

		}

	}

	/**
	 * Get powerstep popup content
	 */
	public function powerstep_ajax() {

		try {

			$add_to_cart_param = false;
			$add_to_cart_param = ( null !== filter_input( INPUT_POST, 'product_id', FILTER_SANITIZE_STRING ) ) ? filter_input( INPUT_POST, 'product_id', FILTER_SANITIZE_STRING ) : $add_to_cart_param;

			if ( ! $add_to_cart_param ) {
				return;
			}
			$product = wc_get_product( absint( $add_to_cart_param ) );

			if ( ! $product ) {
				return;
			}

			echo wp_kses_post( get_clerk_powerstep_popup( $product ) );
			wp_die();

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR powerstep_ajax', array( 'error' => $e->getMessage() ) );

		}

	}
}

new Clerk_Powerstep();
