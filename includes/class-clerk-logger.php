<?php
/**
 * Plugin Name: Clerk
 * Plugin URI: https://clerk.io/
 * Description: Clerk.io Turns More Browsers Into Buyers
 * Version: 4.1.5
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
class Clerk_Logger
{

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
    private string $platform;

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
    private DateTime $date;

    /**
     * Time
     *
     * @var int
     */
    private int $time;

    /**
     * Clerk_Logger constructor.
     *
     * @throws Exception Init on exception.
     */
    public function __construct()
    {

        include_once __DIR__ . '/clerk-multi-lang-helpers.php';
        if (clerk_is_wpml_enabled()) {
            /**
             * Patches the clerk_options array to be language specific.
             * @since 4.1.3
             */
            do_action('wpml_multilingual_options', 'clerk_options');
        }
        $this->platform = 'WordPress';
        if (!empty($this->options)) {
            $this->key = $this->options['public_key'];
        }
        $this->date = new DateTime();
        $this->time = $this->date->getTimestamp();
        $this->options = get_option('clerk_options');
    }

    /**
     * Log Warnings and Errors
     *
     * @param string $message Status Message.
     * @param object|array $metadata Data.
     */
    public function log(string $message, object|array $metadata): void
    {
        //TODO: Remove if statement nesting.
        if (isset($_SERVER['HTTPS']) && 'on' === $_SERVER['HTTPS']) {

            $http_host = isset($_SERVER['HTTP_HOST']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_HOST'])) : '';
            $request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
            $metadata['uri'] = 'https://' . $http_host . $request_uri;

        } else {

            $request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
            $metadata['uri'] = get_site_url() . $request_uri;

        }

        if (filter_input_array(INPUT_GET)) {

            $metadata['params'] = filter_input_array(INPUT_GET);

        } elseif (filter_input_array(INPUT_POST)) {

            $metadata['params'] = filter_input_array(INPUT_POST);

        }

        $type = 'log';

        if (isset($this->options['log_enabled']) && '1' === $this->options['log_enabled']) {

            if ('Error + Warn + Debug Mode' === $this->options['log_level']) {

                if ('my.clerk.io' === $this->options['log_to']) {

                    $_endpoint = 'https://api.clerk.io/v2/log/debug';

                    $data_string = wp_json_encode(
                        array(
                            'key' => $this->key,
                            'source' => $this->platform,
                            'time' => $this->time,
                            'type' => $type,
                            'message' => $message,
                            'metadata' => $metadata,
                        )
                    );

                    $args = array(
                        'body' => $data_string,
                        'method' => 'POST',
                        'headers' => array('User-Agent' => 'ClerkExtensionBot WooCommerce/v' . get_bloginfo('version') . ' Clerk/v4.1.2 PHP/v' . phpversion()),
                    );

                    wp_remote_request($_endpoint, $args);

                }
            }
        }
    }

    /**
     * Log error
     *
     * @param string $message Status Message.
     * @param object|array $metadata Data.
     */
    public function error(string $message, object|array $metadata): void
    {

        if (isset($_SERVER['HTTPS']) && 'on' === $_SERVER['HTTPS']) {

            $http_host = isset($_SERVER['HTTP_HOST']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_HOST'])) : '';
            $request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
            $metadata['uri'] = 'https://' . $http_host . $request_uri;

        } else {

            $request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
            $metadata['uri'] = get_site_url() . $request_uri;
        }

        if (filter_input_array(INPUT_GET)) {

            $metadata['params'] = filter_input_array(INPUT_GET);

        } elseif (filter_input_array(INPUT_POST)) {

            $metadata['params'] = filter_input_array(INPUT_POST);

        }

        $type = 'error';

        if (isset($this->options['log_enabled']) && '1' === $this->options['log_enabled']) {

            if ('my.clerk.io' === $this->options['log_to']) {

                $_endpoint = 'https://api.clerk.io/v2/log/debug';

                $data_string = wp_json_encode(
                    array(
                        'debug' => '1',
                        'key' => $this->key,
                        'source' => $this->platform,
                        'time' => $this->time,
                        'type' => $type,
                        'message' => $message,
                        'metadata' => $metadata,
                    )
                );

                $args = array(
                    'body' => $data_string,
                    'method' => 'POST',
                    'headers' => array('User-Agent' => 'ClerkExtensionBot WooCommerce/v' . get_bloginfo('version') . ' Clerk/v4.1.2 PHP/v' . phpversion()),
                );

                wp_remote_request($_endpoint, $args);

            }
        }
    }

    /**
     * Log warning
     *
     * @param string $message Status Message.
     * @param object|array $metadata Data.
     */
    public function warn(string $message, object|array $metadata): void
    {

        if (isset($_SERVER['HTTPS']) && 'on' === $_SERVER['HTTPS']) {

            $http_host = isset($_SERVER['HTTP_HOST']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_HOST'])) : '';
            $request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
            $metadata['uri'] = 'https://' . $http_host . $request_uri;

        } else {

            $request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
            $metadata['uri'] = get_site_url() . $request_uri;

        }

        if (filter_input_array(INPUT_GET)) {

            $metadata['params'] = filter_input_array(INPUT_GET);

        } elseif (filter_input_array(INPUT_POST)) {

            $metadata['params'] = filter_input_array(INPUT_POST);

        }

        $type = 'warn';

        if (isset($this->options['log_enabled']) && '1' === $this->options['log_enabled']) {

            if ('Only Error' !== $this->options['log_level']) {

                if ('my.clerk.io' === $this->options['log_to']) {

                    $_endpoint = 'https://api.clerk.io/v2/log/debug';

                    $data_string = wp_json_encode(
                        array(
                            'debug' => '1',
                            'key' => $this->key,
                            'source' => $this->platform,
                            'time' => $this->time,
                            'type' => $type,
                            'message' => $message,
                            'metadata' => $metadata,
                        )
                    );

                    $args = array(
                        'body' => $data_string,
                        'method' => 'POST',
                        'headers' => array('User-Agent' => 'ClerkExtensionBot WooCommerce/v' . get_bloginfo('version') . ' Clerk/v4.1.2 PHP/v' . phpversion()),
                    );

                    wp_remote_request($_endpoint, $args);

                }
            }
        }
    }
}
