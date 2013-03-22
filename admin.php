<?php

    ini_set('display_errors','On');
    error_reporting(E_ALL);
    setlocale(LC_MONETARY, 'en_GB');

    include 'lib/LanWebsite/Autoload.php';
    
    define("LANWEBSITE_ADMIN", true);
    
    LanWebsite_Main::initialize();
    LanWebsite_Main::setControllerDir("/admin/controllers/");
    LanWebsite_Main::getTemplateManager()->setBaseDir('/admin/');
    LanWebsite_Main::getAuth()->requireAdmin();
    
    LanWebsite_Main::route(array("settings", "tournaments", "whatson", "blog", "gallery", "tickets", "lanvan", "tf2", "hungergames", "food"), "settings");

?>