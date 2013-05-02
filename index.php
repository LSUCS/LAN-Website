<?php
    ini_set('display_errors','On');
    error_reporting(E_ALL);
    setlocale(LC_MONETARY, 'en_GB');

    include 'lib/LanWebsite/Autoload.php';
    
    LanWebsite_Main::initialize();
    
    //Force data requirement if at LAN
    if (LanWebsite_Main::isAtLan() && (!isset($_GET["page"]) || $_GET["page"] != "account")) {

        $user = LanWebsite_Main::getUserManager()->getActiveUser();
        
        //Ticket checks
        $ticket = LanWebsite_Main::getDb()->query("SELECT * FROM `tickets` WHERE assigned_forum_id = '%s' AND lan_number = '%s'", $user->getUserId(), LanWebsite_Main::getSettings()->getSetting("lan_number"))->fetch_assoc();
        if (!$ticket || $ticket["activated"] == 0 || $user->getFullName() == "" || $user->getEmergencyContact() == "" || $user->getEmergencyNumber() == "") {
            header(LanWebsite_Main::buildUrl(false, "account"));
        }
        
    }
            
    LanWebsite_Main::route(array("api", "home", "tickets", "whatson", "tournaments", "info", "account", "profile", "gallery", "map", "contact", "servers", "orderfood", "presentation", "chat", "gamehub"), "home");

	
?>