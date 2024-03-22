<?php
/**
 * Plugin Name: Clerk
 * Plugin URI: https://clerk.io/
 * Description: Clerk.io Turns More Browsers Into Buyers
 * Version: 4.1.7
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
class Clerk_Content {

	/**
	 * Error and Warning Logger
	 *
	 * @var $logger Clerk_Logger
	 */
	protected $logger;

	/**
	 * Clerk_Content constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_archive_description', array( $this, 'clerk_woocommerce_archive_description' ), 99 );
		add_action( 'woocommerce_after_cart', array( $this, 'clerk_woocommerce_after_cart_table' ), 99 );
    add_action( 'woocommerce_after_single_product', array( $this, 'clerk_woocommerce_after_single_product' ), 99 );
		add_filter( 'wc_get_template', array( $this, 'clerk_wc_get_template' ), 99, 2 );
		include_once __DIR__ . '/class-clerk-logger.php';
		include_once __DIR__ . '/clerk-multi-lang-helpers.php';
		if ( clerk_is_wpml_enabled() ) {
			do_action( 'wpml_multilingual_options', 'clerk_options' );
		}
		$this->logger = new Clerk_Logger();
	}

	/**
	 * Add content to category if enabled
	 */
	public function clerk_woocommerce_archive_description() {

		try {

			$category = get_queried_object();

			$options = clerk_get_options();

			if ( isset( $options['category_enabled'] ) && $options['category_enabled'] && property_exists( $category, 'term_id' ) ) :

				$templates     = explode( ',', $options['category_content'] );
				$index         = 0;
				$class_string  = 'clerk_';
				$filter_string = '';
				$unique_filter = ( isset( $options['category_excl_duplicates'] ) && $options['category_excl_duplicates'] ) ? true : false;
				foreach ( $templates as $template ) {

					?>
					<span class="clerk
					<?php
					if ( $unique_filter ) {
						echo esc_attr( $class_string . (string) $index );
					}
					?>
					"
					<?php
					if ( $index > 0 && $unique_filter ) {
						echo esc_attr( 'data-exclude-from="' . $filter_string . '"' );
					}
					?>
						data-template="@<?php echo esc_attr( str_replace( ' ', '', $template ) ); ?>"
						data-category="<?php echo esc_attr( $category->term_id ); ?>"></span>
					<?php
					if ( $index > 0 ) {
						$filter_string .= ', ';
					}
					$filter_string .= '.' . $class_string . (string) $index;
					++$index;
				}
			endif;

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR clerk_woocommerce_archive_description', array( 'error' => $e->getMessage() ) );

		}
	}

	/**
	 * Add content after cart if enabled
	 */
	public function clerk_woocommerce_after_cart_table() {

		try {

			global $woocommerce;
			$items = $woocommerce->cart->get_cart();

			$options  = clerk_get_options();
			$products = array();

			foreach ( $items as $item => $values ) {
				$products[] = $values['product_id'];
			}

			if ( isset( $options['cart_enabled'] ) && $options['cart_enabled'] ) {

				$templates     = explode( ',', $options['cart_content'] );
				$index         = 0;
				$class_string  = 'clerk_';
				$filter_string = '';
				$unique_filter = ( isset( $options['cart_excl_duplicates'] ) && $options['cart_excl_duplicates'] ) ? true : false;

				foreach ( $templates as $template ) {

					?>
					<span class="clerk
					<?php
					if ( $unique_filter ) {
						echo esc_attr( $class_string . (string) $index );
					}
					?>
					"
					<?php
					if ( $index > 0 && $unique_filter ) {
						echo esc_attr( 'data-exclude-from="' . $filter_string . '"' );
					}
					?>
						data-template="@<?php echo esc_attr( str_replace( ' ', '', $template ) ); ?>"
						data-products="<?php echo esc_attr( wp_json_encode( $products ) ); ?>">
					</span>
					<?php
					if ( $index > 0 ) {
						$filter_string .= ', ';
					}
					$filter_string .= '.' . $class_string . (string) $index;
					++$index;
				}
			}
		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR clerk_woocommerce_after_cart_table', array( 'error' => $e->getMessage() ) );

		}
	}

	/**
	 * Rewrite related products template if enabled
	 *
	 * @param mixed  $located Template found.
	 * @param string $template_name Template name.
	 *
	 * @return string
	 */
	public function clerk_wc_get_template( $located, $template_name ) {

		try {

			if ( 'single-product/related.php' === $template_name ) {

				$options = clerk_get_options();

				if ( isset( $options['product_enabled'] ) && $options['product_enabled'] ) :
					return clerk_locate_template( 'clerk-related-products.php' );
				endif;
			}

			return $located;

		} catch ( Exception $e ) {

			$this->logger->error( 'ERROR clerk_wc_get_template', array( 'error' => $e->getMessage() ) );

		}
	}

	/**
	 * Rewrite related products template if enabled
	 *
	 * @param mixed  $located Template found.
	 * @param string $template_name Template name.
	 *
	 * @return string
	 */
	public function clerk_woocommerce_after_single_product() {

		try {
      $options = clerk_get_options();

      if ( isset( $options['product_enabled'] ) && $options['product_enabled'] && $options['product_injection_after'] ) {
        $contents      = explode( ',', $options['product_content'] );
        $index         = 0;
        $class_string  = 'clerk_';
        $filter_string = '';
        $unique_filter = ( isset( $options['product_excl_duplicates'] ) && $options['product_excl_duplicates'] ) ? true : false;
        foreach ( $contents as $content ) {
          ?>
          <span class="clerk
          <?php
          if ( $unique_filter ) {
            echo esc_attr( $class_string . (string) $index );
          }
          ?>
          "
          <?php
          if ( $index > 0 && $unique_filter ) {
            echo 'data-exclude-from="' . esc_attr( $filter_string ) . '"';
          }
          ?>
            data-template="@<?php echo esc_attr( str_replace( ' ', '', $content ) ); ?>"
            data-products="[<?php echo get_the_ID(); ?>]"></span>
          <?php
          if ( $index > 0 ) {
            $filter_string .= ', ';
          }
          $filter_string .= '.' . $class_string . (string) $index;
          ++$index;
        }
      }
    } catch ( Exception $e ) {

			$this->logger->error( 'ERROR clerk_woocommerce_after_single_product', array( 'error' => $e->getMessage() ) );

		}
	}

}

new Clerk_Content();
