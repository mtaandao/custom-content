<?php

class TranslationServiceInfo{

    function __construct(){

        add_action('installer_fetched_subscription_data',array($this, 'save_info'), 10, 2);

    }

    function save_info($data, $repository_id) {

        $ts_info = isset( MN_Installer()->settings['repositories'][$repository_id]['ts_info'] ) ?
                        MN_Installer()->settings['repositories'][$repository_id]['ts_info'] : false;

        $save_settings = false;
        if(isset($data->ts_info['preferred']) && empty($ts_info['preferred'])){
            MN_Installer()->settings['repositories'][$repository_id]['ts_info']['preferred'] = $data->ts_info['preferred'];
            $save_settings = true;
        }

        if(isset($data->ts_info['referal']) && empty($ts_info['referal'])){
            MN_Installer()->settings['repositories'][$repository_id]['ts_info']['referal'] = $data->ts_info['referal'];
            $save_settings = true;
        }

        if ( !empty( $data->ts_info['client_id'] ) ) { // can be updated
            MN_Installer()->settings['repositories'][$repository_id]['ts_info']['client_id'] = $data->ts_info['client_id'];
            $save_settings = true;
        }

        if($save_settings){
            MN_Installer()->save_settings();
        }

    }

}

new TranslationServiceInfo();