<?php
        
    include "/home/soc_lsucs/lan.lsucs.org.uk/password.php";
    
    /**
     * Base LanWebsite Library Directory
     */
    $config['libdir'] = "/home/soc_lsucs/dev.lan.lsucs.org.uk/htdocs/lib/";
    
    /**
     * Database Settings
     */
    $config['database']["host"] = "localhost";
    $config['database']["user"] = "lanwebsite";
    $config['database']["pass"] = $password;
    $config['database']["db"]   = "dev-lanwebsite";
    
    /**
     * Default Controller location
     */
    $config['controllerdir'] = '/public/controllers/';
    
    /**
     * Auth Mechanism
     */
    $config['auth'] = "LanWebsite_Auth_Lsucs";

    /**
     * UserManager
     */
    $config['usermanager'] = "LanWebsite_UserManager_Lsucs";
    
    /**
     * SEO-friendly URLs
     */
    $config['seo_enabled'] = true;

?>
