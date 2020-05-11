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

                    $Endpoint = 'api.clerk.io/v2/log/debug';

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
                        'headers'     => array('User-Agent' => 'ClerkExtensionBot WooCommerce/v' .get_bloginfo('version'). ' Clerk/v3.2.0 PHP/v'.phpversion())
                    );

                    $response = json_decode(wp_remote_request( $Endpoint, $args )['body']);

                    if ($response->status != 'ok') {

                        $this->LogToFile($Message,$Metadata);

                    }

                } elseif ($this->options['log_to'] == 'File') {

                    $this->LogToFile($Message,$Metadata);

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
                    'body'        => $data_string,
                    'method'      => 'POST',
                    'headers'     => array('User-Agent' => 'ClerkExtensionBot WooCommerce/v' .get_bloginfo('version'). ' Clerk/v3.2.0 PHP/v'.phpversion())
                );

                $response = json_decode(wp_remote_request( $Endpoint, $args )['body']);

                if ($response->status != 'ok') {

                    $this->LogToFile($Message,$Metadata);

                }

            } elseif ($this->options['log_to'] == 'File') {

                $this->LogToFile($Message,$Metadata);

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

                    $Endpoint = 'api.clerk.io/v2/log/debug';

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
                        'headers'     => array('User-Agent' => 'ClerkExtensionBot WooCommerce/v' .get_bloginfo('version'). ' Clerk/v3.2.0 PHP/v'.phpversion())
                    );

                    $response = json_decode(wp_remote_request( $Endpoint, $args )['body']);

                    if ($response->status != 'ok') {

                        $this->LogToFile($Message,$Metadata);

                    }

                } elseif ($this->options['log_to'] == 'File') {

                    $this->LogToFile($Message,$Metadata);

                }
            }
        }
    }

    public function LogToFile($Message,$Metadata)
    {

        $log = $this->Date->format('Y-m-d H:i:s') . ' MESSAGE: ' . $Message . ' METADATA: ' . json_encode($Metadata) . PHP_EOL .
            '-------------------------' . PHP_EOL;
        $path = plugin_dir_path(__DIR__) . 'clerk_log.log';

        fopen($path, "a+");
        file_put_contents($path, $log, FILE_APPEND);

    }

}
