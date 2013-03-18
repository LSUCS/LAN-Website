<?php

    ini_set('display_errors','On');
    error_reporting(E_ALL);
    setlocale(LC_MONETARY, 'en_GB');

    include 'lib/LanWebsite/Autoload.php';
    
    LanWebsite_Main::initialize();
    LanWebsite_Main::routeAdmin();

?>