<?php

/**
 * Class Logger
 */
class ClerkLogger
{
    public function SendLog ($Message,$Type,$Metadata) {

        $options = get_option( 'clerk_options' );

        if ( $options['disable_debug_mode'] ) {
            return [];
        }
        
        //Customize $Platform and the function for getting the public key.
        $Key = $options['public_key'];
        $Platform = 'Wordpress';
        $JSON_Metadata = json_encode($Metadata);
        $date = new DateTime();
        $Time = $date->getTimestamp();

        $Endpoint = 'api.clerk.io/v2/log/debug?key='.$Key.'&source='.$Platform.'&time='.$Time.'&type='.$Type.'&message='.$Message.'&metadata='.$JSON_Metadata;
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL,$Endpoint);

        curl_exec($curl);

        curl_close($curl);

    }

}
