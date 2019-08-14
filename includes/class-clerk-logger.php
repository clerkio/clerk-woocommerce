<?php

/**
 * Class Logger
 */
class ClerkLogger
{

    private $options;
    private $Platform;
    private $Key;
    private $Date;
    private $Time;


    function __construct() {

        $this->options = get_option( 'clerk_options' );
        $this->Platform = 'Wordpress';
        $this->Key = $this->options['public_key'];
        $this->Date = new DateTime();
        $this->Time = $this->Date->getTimestamp();
    }

    public function log ($Message, $Metadata) {

        //Customize $Platform and the function for getting the public key.
        $JSON_Metadata_Encode = json_encode($Metadata);
        $Type = 'log';

        if ($this->options['log_level'] !== 'all') {


        }else {

            if ($this->options['log_to'] == 'Collect') {

                if ($this->options['log_level'] == 'all') {

                    $Endpoint = 'api.clerk.io/v2/log/debug?debug=1&key=' . $this->Key . '&source=' . $this->Platform . '&time=' . $this->Time . '&type=' . $Type . '&message=' . $Message . '&metadata=' . urlencode($JSON_Metadata_Encode);

                } else {

                    $Endpoint = 'api.clerk.io/v2/log/debug?key=' . $this->Key . '&source=' . $this->Platform . '&time=' . $this->Time . '&type=' . $Type . '&message=' . $Message . '&metadata=' . urlencode($JSON_Metadata_Encode);

                }

                $curl = curl_init();

                curl_setopt($curl, CURLOPT_URL, $Endpoint);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_exec($curl);
                curl_close($curl);

            } elseif ($this->options['log_to'] == 'Local') {

                $log = $this->Date->format('Y-m-d H:i:s') . ' MESSAGE: ' . $Message . ' METADATA: ' . $JSON_Metadata_Encode . PHP_EOL .
                    '-------------------------' . PHP_EOL;
                $path = plugin_dir_path(__DIR__) . 'clerk_log.log';

                fopen($path, "a+");
                file_put_contents($path, $log, FILE_APPEND);

            }
        }

    }

    public function error ($Message, $Metadata) {

        //Customize $Platform and the function for getting the public key.
        $JSON_Metadata_Encode = json_encode($Metadata);
        $Type = 'error';

        if ($this->options['log_to'] == 'Collect') {

            if ($this->options['log_level'] == 'all') {

                $Endpoint = 'api.clerk.io/v2/log/debug?debug=1&key=' . $this->Key . '&source=' . $this->Platform . '&time=' . $this->Time . '&type=' . $Type . '&message=' . $Message . '&metadata=' . urlencode($JSON_Metadata_Encode);

            } else {

                $Endpoint = 'api.clerk.io/v2/log/debug?key=' . $this->Key . '&source=' . $this->Platform . '&time=' . $this->Time . '&type=' . $Type . '&message=' . $Message . '&metadata=' . urlencode($JSON_Metadata_Encode);

            }

            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, $Endpoint);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_exec($curl);
            curl_close($curl);

        }
        elseif ($this->options['log_to'] == 'local') {

            $log = $this->Date->format('Y-m-d H:i:s').' MESSAGE: '.$Message. ' METADATA: '. $JSON_Metadata_Encode.PHP_EOL.
                '-------------------------'.PHP_EOL;
            $path = plugin_dir_path(__DIR__) . 'clerk_log.log';

            fopen($path, "a+");
            file_put_contents($path, $log, FILE_APPEND);

        }

    }

    public function warn ($Message, $Metadata) {

        //Customize $Platform and the function for getting the public key.
        $JSON_Metadata_Encode = json_encode($Metadata);
        $Type = 'warn';

        if ($this->options['log_level'] == 'error') {


        }else {

            if ($this->options['log_to'] == 'hosted') {

                if ($this->options['log_level'] == 'all') {

                    $Endpoint = 'api.clerk.io/v2/log/debug?debug=1&key=' . $this->Key . '&source=' . $this->Platform . '&time=' . $this->Time . '&type=' . $Type . '&message=' . $Message . '&metadata=' . urlencode($JSON_Metadata_Encode);

                } else {

                    $Endpoint = 'api.clerk.io/v2/log/debug?key=' . $this->Key . '&source=' . $this->Platform . '&time=' . $this->Time . '&type=' . $Type . '&message=' . $Message . '&metadata=' . urlencode($JSON_Metadata_Encode);

                }

                $curl = curl_init();

                curl_setopt($curl, CURLOPT_URL, $Endpoint);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_exec($curl);
                curl_close($curl);

            } elseif ($this->options['log_to'] == 'local') {

                $log = $this->Date->format('Y-m-d H:i:s') . ' MESSAGE: ' . $Message . ' METADATA: ' . $JSON_Metadata_Encode . PHP_EOL .
                    '-------------------------' . PHP_EOL;
                $path = plugin_dir_path(__DIR__) . 'clerk_log.log';

                fopen($path, "a+");
                file_put_contents($path, $log, FILE_APPEND);

            }
        }

    }

}
