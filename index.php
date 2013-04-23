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
    
    //onLAN ticket check
    $user = LanWebsite_Main::getUserManager()->getActiveUser();
    if ($user->getUserId() > 0) {
        $ticket = LanWebsite_Main::getDb()->query("SELECT * FROM `tickets` WHERE assigned_forum_id = '%s' AND lan_number = '%s'", $user->getUserId(), LanWebsite_Main::getSettings()->getSetting("lan_number"))->fetch_assoc();
        if (!$ticket) {
            $res = LanWebsite_Main::getDb()->query("SELECT * FROM `tickets` WHERE lan_number = '39.5' AND seat != ''");
            $seats = explode("\n", file_get_contents("data/seats.txt"));
            while ($row = $res->fetch_assoc()) $seats = array_diff($seats, array($row["seat"]));
            LanWebsite_Main::getDb()->query("INSERT INTO `tickets` (lan_number, member_ticket, purchased_forum_id, purchased_name, assigned_forum_id, activated, seat) VALUES ('39.5', '1', '%s', '%s', '%s', 1, '%s')", $user->getUserId(), $user->getFullName(), $user->getUserId(), array_shift(array_values($seats)));
        }
        
    }
            
    LanWebsite_Main::route(array("api", "home", "tickets", "whatson", "tournaments", "info", "account", "profile", "gallery", "map", "contact", "servers", "orderfood", "presentation", "chat", "gamehub", "onlan"), "onlan");

	
?>