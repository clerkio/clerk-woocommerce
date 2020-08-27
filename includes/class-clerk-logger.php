<?php

/**
 * Class Logger
 */
class ClerkLogger
{
    /**
     * @var mixed|void
     */
    private $options;

    /**
     * @var string
     */
    private $Platform;

    /**
     * @var
     */
    private $Key;

    /**
     * @var DateTime
     */
    private $Date;

    /**
     * @var int
     */
    private $Time;

    /**
     * ClerkLogger constructor.
     * @throws Exception
     */
    function __construct()
    {

        $this->options = get_option('clerk_options');
        $this->Platform = 'Wordpress';
        $this->Key = $this->options['public_key'];
        $this->Date = new DateTime();
        $this->Time = $this->Date->getTimestamp();

    }

    /**
     * @param $Message
     * @param $Metadata
     */
    public function log($Message, $Metadata)
    {

        if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')

            $Metadata['uri'] = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        else {

            $Metadata['uri'] = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        }

        if ($_GET) {

            $Metadata['params'] = $_GET;

        }elseif ($_POST) {

            $Metadata['params'] = $_POST;

        }

        $Type = 'log';

        if (isset($this->options['log_enabled']) && $this->options['log_enabled'] !== '1') {

        } else {

            if ($this->options['log_level'] !== 'Error + Warn + Debug Mode') {

            } else {

                if ($this->options['log_to'] == 'my.clerk.io') {

                    $Endpoint = 'https://api.clerk.io/v2/log/debug';

                    $data_string = json_encode([
                        'key' =>$this->Key,
                        'source' => $this->Platform,
                        'time' => $this->Time,
                        'type' => $Type,
                        'message' => $Message,
                        'metadata' => $Metadata]);

                    $args = array(
                        'body'        => $data_string,
                        'method'      => 'POST',
                        'headers'     => array('User-Agent' => 'ClerkExtensionBot WooCommerce/v' .get_bloginfo('version'). ' Clerk/v3.3.2 PHP/v'.phpversion())
                    );

                    wp_remote_request( $Endpoint, $args );

                }
            }
        }
    }

    /**
     * @param $Message
     * @param $Metadata
     */
    public function error($Message, $Metadata)
    {

        if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')

            $Metadata['uri'] = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        else {

            $Metadata['uri'] = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        }

        if ($_GET) {

            $Metadata['params'] = $_GET;

        }elseif ($_POST) {

            $Metadata['params'] = $_POST;

        }

        $Type = 'error';

        if (isset($this->options['log_enabled']) && $this->options['log_enabled'] !== '1') {


        } else {

            if ($this->options['log_to'] == 'my.clerk.io') {

                $Endpoint = 'https://api.clerk.io/v2/log/debug';

                $data_string = json_encode([
                    'debug' => '1',
                    'key' => $this->Key,
                    'source' => $this->Platform,
                    'time' => $this->Time,
                    'type' => $Type,
                    'message' => $Message,
                    'metadata' => $Metadata]);

                $args = array(
                    'body' => $data_string,
                    'method' => 'POST',
                    'headers' => array('User-Agent' => 'ClerkExtensionBot WooCommerce/v' . get_bloginfo('version') . ' Clerk/v3.3.2 PHP/v' . phpversion())
                );

                wp_remote_request($Endpoint, $args);

            }
        }
    }

    /**
     * @param $Message
     * @param $Metadata
     */
    public function warn($Message, $Metadata)
    {

        if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')

            $Metadata['uri'] = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        else {

            $Metadata['uri'] = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        }

        if ($_GET) {

            $Metadata['params'] = $_GET;

        }elseif ($_POST) {

            $Metadata['params'] = $_POST;

        }

        $Type = 'warn';

        if (isset($this->options['log_enabled']) && $this->options['log_enabled'] !== '1') {


        } else {

            if ($this->options['log_level'] == 'Only Error') {


            } else {

                if ($this->options['log_to'] == 'my.clerk.io') {

                    $Endpoint = 'https://api.clerk.io/v2/log/debug';

                    $data_string = json_encode([
                        'debug' => '1',
                        'key' =>$this->Key,
                        'source' => $this->Platform,
                        'time' => $this->Time,
                        'type' => $Type,
                        'message' => $Message,
                        'metadata' => $Metadata]);

                    $args = array(
                        'body'        => $data_string,
                        'method'      => 'POST',
                        'headers'     => array('User-Agent' => 'ClerkExtensionBot WooCommerce/v' .get_bloginfo('version'). ' Clerk/v3.3.2 PHP/v'.phpversion())
                    );

                    wp_remote_request( $Endpoint, $args );

                }
            }
        }
    }

}
