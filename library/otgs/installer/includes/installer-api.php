<?php

class MN_Installer_API{

    public static function get_product_installer_link($repository_id, $package_id = false){

        $menu_url = MN_Installer()->menu_url();

        $url = $menu_url . '#' . $repository_id;
        if($package_id){
            $url .= '/' . $package_id;
        }

        return $url;

    }

    public static function get_product_price($repository_id, $package_id, $product_id, $incl_discount = false){

        $price = MN_Installer()->get_product_price($repository_id, $package_id, $product_id, $incl_discount);

        return $price;
    }

    /**
     * Retrieve the preferred translation service.
     *
     * @since 1.6.5
     *
     * @param string The repository id (e.g. mnml)
     * @return string The translation service id
     */
    public static function get_preferred_ts($repository_id = 'mnml'){

        if(isset(MN_Installer()->settings['repositories'][$repository_id]['ts_info']['preferred'])){
            return MN_Installer()->settings['repositories'][$repository_id]['ts_info']['preferred'];
        }

        return false;

    }

    /**
     * Set the preferred translation service.
     *
     * @since 1.6.5
     *
     * @param string The translation service id
     * @param string The repository id (e.g. mnml)
     */
    public static function set_preferred_ts( $value, $repository_id = 'mnml' ){

        if( isset( MN_Installer()->settings['repositories'][$repository_id]['ts_info']['preferred'] ) ){

            MN_Installer()->settings['repositories'][$repository_id]['ts_info']['preferred'] = $value;

            MN_Installer()->save_settings();

        }

    }

    /**
     * Retrieve the referring translation service (if any)
     *
     * @since 1.6.5
     *
     * @param string The repository id (e.g. mnml)
     * @return string The translation service id or false
     */
    public static function get_ts_referal($repository_id = 'mnml'){

        if(isset(MN_Installer()->settings['repositories'][$repository_id]['ts_info']['referal'])){
            return MN_Installer()->settings['repositories'][$repository_id]['ts_info']['referal'];
        }

        return false;

    }

    /**
     * Retrieve the translation services client id for a specific repository (if any)
     *
     * @since 1.7.9
     *
     * @param string The repository id (e.g. mnml)
     * @return string The client id or false
     */
    public static function get_ts_client_id( $repository_id = 'mnml' ){

        if(isset(MN_Installer()->settings['repositories'][$repository_id]['ts_info']['client_id'])){
            return MN_Installer()->settings['repositories'][$repository_id]['ts_info']['client_id'];
        }

        return false;

    }

    /**
     * Retrieve the site key corresponding to a repository.
     * This is a wrapper of MN_Installer::get_site_key()
     * @see MN_Installer::get_site_key()
     *
     * @since 1.7.9
     *
     * @param string The repository id (e.g. mnml)
     * @return string The site key (or false)
     */
    public static function get_site_key( $repository_id = 'mnml' ){

        return MN_Installer()->get_site_key( $repository_id );

    }


}