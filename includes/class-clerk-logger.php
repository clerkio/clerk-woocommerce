<?php
/**
 * Plugin Name: Clerk
 * Plugin URI: https://clerk.io/
 * Description: Clerk.io Turns More Browsers Into Buyers
 * Version: 4.1.1
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
 * Clerk_Logger Class
 *
 * Clerk Module Core Class
 */
class Clerk_Logger {

	/**
	 * Options
	 *
	 * @var mixed|void
	 */
	private $options;

	/**
	 * Platform
	 *
	 * @var string
	 */
	private $platform;

	/**
	 * Key
	 *
	 * @var string|void
	 */
	private $key;

	/**
	 * Date Time
	 *
	 * @var DateTime
	 */
	private $date;

	/**
	 * Time
	 *
	 * @var int
	 */
	private $time;

	/**
	 * Clerk_Logger constructor.
	 *
	 * @throws Exception Init on exception.
	 */
	public function __construct() {

		$this->options  = get_option( 'clerk_options' );
		$this->platform = 'WordPress';
		if ( ! empty( $this->options ) ) {
			$this->key = $this->options['public_key'];
		}
		$this->date = new DateTime();
		$this->time = $this->date->getTimestamp();

	}

	/**
	 * Log Warnings and Erros
	 *
	 * @param string|void       $message Status Message.
	 * @param array|object|void $metadata Data.
	 */
	public function log( $message, $metadata ) {

		if ( isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ) {

			$http_host       = isset( $_SERVER['HTTP_HOST'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
			$request_uri     = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$metadata['uri'] = 'https://' . $http_host . $request_uri;

		} else {

			$request_uri     = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$metadata['uri'] = get_site_url() . $request_uri;

		}

		if ( filter_input_array( INPUT_GET, FILTER_SANITIZE_STRING ) ) {

			$metadata['params'] = filter_input_array( INPUT_GET, FILTER_SANITIZE_STRING );

		} elseif ( filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING ) ) {

			$metadata['params'] = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );

		}

		$type = 'log';

		if ( isset( $this->options['log_enabled'] ) && '1' === $this->options['log_enabled'] ) {

			if ( 'Error + Warn + Debug Mode' === $this->options['log_level'] ) {

				if ( 'my.clerk.io' === $this->options['log_to'] ) {

					$_endpoint = 'https://api.clerk.io/v2/log/debug';

					$data_string = wp_json_encode(
						array(
							'key'      => $this->key,
							'source'   => $this->platform,
							'time'     => $this->time,
							'type'     => $type,
							'message'  => $message,
							'metadata' => $metadata,
						)
					);

					$args = array(
						'body'    => $data_string,
						'method'  => 'POST',
						'headers' => array( 'User-Agent' => 'ClerkExtensionBot WooCommerce/v' . get_bloginfo( 'version' ) . ' Clerk/v4.1.1 PHP/v' . phpversion() ),
					);

					wp_remote_request( $_endpoint, $args );

				}
			}
		}
	}

	/**
	 * Log error
	 *
	 * @param string|void       $message Status Message.
	 * @param array|object|void $metadata Data.
	 */
	public function error( $message, $metadata ) {

		if ( isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ) {

			$http_host       = isset( $_SERVER['HTTP_HOST'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
			$request_uri     = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$metadata['uri'] = 'https://' . $http_host . $request_uri;

		} else {

			$request_uri     = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$metadata['uri'] = get_site_url() . $request_uri;
		}

		if ( filter_input_array( INPUT_GET, FILTER_SANITIZE_STRING ) ) {

			$metadata['params'] = filter_input_array( INPUT_GET, FILTER_SANITIZE_STRING );

		} elseif ( filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING ) ) {

			$metadata['params'] = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );

		}

		$type = 'error';

		if ( isset( $this->options['log_enabled'] ) && '1' === $this->options['log_enabled'] ) {

			if ( 'my.clerk.io' === $this->options['log_to'] ) {

				$_endpoint = 'https://api.clerk.io/v2/log/debug';

				$data_string = wp_json_encode(
					array(
						'debug'    => '1',
						'key'      => $this->key,
						'source'   => $this->platform,
						'time'     => $this->time,
						'type'     => $type,
						'message'  => $message,
						'metadata' => $metadata,
					)
				);

				$args = array(
					'body'    => $data_string,
					'method'  => 'POST',
					'headers' => array( 'User-Agent' => 'ClerkExtensionBot WooCommerce/v' . get_bloginfo( 'version' ) . ' Clerk/v4.1.1 PHP/v' . phpversion() ),
				);

				wp_remote_request( $_endpoint, $args );

			}
		}
	}

	/**
	 * Log warning
	 *
	 * @param string|void       $message Status Message.
	 * @param array|object|void $metadata Data.
	 */
	public function warn( $message, $metadata ) {

		if ( isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ) {

			$http_host       = isset( $_SERVER['HTTP_HOST'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
			$request_uri     = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$metadata['uri'] = 'https://' . $http_host . $request_uri;

		} else {

			$request_uri     = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$metadata['uri'] = get_site_url() . $request_uri;

		}

		if ( filter_input_array( INPUT_GET, FILTER_SANITIZE_STRING ) ) {

			$metadata['params'] = filter_input_array( INPUT_GET, FILTER_SANITIZE_STRING );

		} elseif ( filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING ) ) {

			$metadata['params'] = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );

		}

		$type = 'warn';

		if ( isset( $this->options['log_enabled'] ) && '1' === $this->options['log_enabled'] ) {

			if ( 'Only Error' !== $this->options['log_level'] ) {

				if ( 'my.clerk.io' === $this->options['log_to'] ) {

					$_endpoint = 'https://api.clerk.io/v2/log/debug';

					$data_string = wp_json_encode(
						array(
							'debug'    => '1',
							'key'      => $this->key,
							'source'   => $this->platform,
							'time'     => $this->time,
							'type'     => $type,
							'message'  => $message,
							'metadata' => $metadata,
						)
					);

					$args = array(
						'body'    => $data_string,
						'method'  => 'POST',
						'headers' => array( 'User-Agent' => 'ClerkExtensionBot WooCommerce/v' . get_bloginfo( 'version' ) . ' Clerk/v4.1.1 PHP/v' . phpversion() ),
					);

					wp_remote_request( $_endpoint, $args );

				}
			}
		}
	}

}
