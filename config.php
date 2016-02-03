<?php
        
    include "../password.php";
    
    /**
     * Base LanWebsite Library Directory
     */
    $config['libdir'] = $_SERVER['DOCUMENT_ROOT']. '/lib/';
    
    /**
     * Database Settings
     */

    /* Development */
    $config['database']['host'] = 'localhost';
    $config['database']['user'] = 'dev_lanwebsite';
    $config['database']['pass'] = $password;
    $config['database']['db']   = 'dev_lanwebsite';
    /**/

    /* Production 
    $config['database']['host'] = 'localhost';
    $config['database']['user'] = 'lanwebsite';
    $config['database']['pass'] = $password;
    $config['database']['db']   = 'lanwebsite';
    /**/
    
    /**
     * Default Controller location
     */
    $config['controllerdir'] = '/public/controllers/';
    
    /**
     * Auth Mechanism
     */
    $config['auth'] = 'LanWebsite_Auth_Lsucs';

    /**
     * UserManager
     */
    $config['usermanager'] = 'LanWebsite_UserManager_Lsucs';
    
    /**
     * SEO-friendly URLs
     */
    $config['seo_enabled'] = true;

?>
