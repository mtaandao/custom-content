<?php 
define('MN_INSTALLER_VERSION', '1.7.13');
  
include_once dirname(__FILE__) . '/includes/installer.class.php';

function MN_Installer() {
    return MN_Installer::instance();
}


MN_Installer();

include_once MN_Installer()->plugin_path() . '/includes/installer-api.php';
include_once MN_Installer()->plugin_path() . '/includes/translation-service-info.class.php';
include_once MN_Installer()->plugin_path() . '/includes/class-installer-dependencies.php';

// Ext function 
function MN_Installer_Show_Products($args = array()){
    
    MN_Installer()->show_products($args);
    
}