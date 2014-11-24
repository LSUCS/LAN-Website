<?php
    
    //This isn't actually a daemon, just a cron task, but I'm keeping it here cos it's convenient/similar
    
    if(!php_sapi_name() == 'cli' || !empty($_SERVER['remote_addr'])) die("Cannot execute crontask via browser");

    include(dirname(__FILE__) . '/../lib/LanWebsite/Autoload.php');
    LanWebsite_Main::initialize();
    
    
    //Easier to do this in PHP than MySQL
    $today = strtolower(date("l"));
    
    $hour = date("H");
    $minute = (date('i') > 30) ? "30" : "00";
    $time = $hour . ":" . $minute;
    
    $res = LanWebsite_Main::getDb()->query("SELECT * FROM `committee_timetable` WHERE day = '%s' AND start_time <= '%s' ORDER BY day, start_time ASC LIMIT 1", $today, $time);
    
    if($res->num_rows) {
        
        $row = $res->fetch_assoc();
                
        $user = LanWebsite_Main::getUserManager()->getUserById($row['user_id_1']);
        $res = LanWebsite_Main::getDb()->query("SELECT seat FROM `tickets` WHERE lan_number = '%s' AND assigned_forum_id = '%s' AND seat != ''",
            LanWebsite_Main::getSettings()->getSetting('lan_number'), $user->getUserId());
        if($res->num_rows) {
            list($seat) = $res->fetch_row();
        } else {
            $seat = "";
        }
        
        $user2 = LanWebsite_Main::getUserManager()->getUserById($row['user_id_2']);
        $res = LanWebsite_Main::getDb()->query("SELECT seat FROM `tickets` WHERE lan_number = '%s' AND assigned_forum_id = '%s' AND seat != ''",
            LanWebsite_Main::getSettings()->getSetting('lan_number'), $user->getUserId());
        if($res->num_rows) {
            list($seat2) = $res->fetch_row();
        } else {
            $seat2 = "";
        }
        
        $committee = $user->getUsername();
        if($seat) $committee .= " (" . $seat . ")";
        $committee .= " & " . $user2->getUsername();
        if($seat2) $committee .= " (" . $seat2 . ")";
    } else {
        $committee = "(Unknown)";
    }
    
    $parts = array("Input=Duty+Committee", "SelectedIndex=0", "Value=".$committee);
        
    callApi("SetText", $parts);
    
    function callApi($function, $params) {
        $apiURL = "192.168.0.30:8088/api?Function=".$function;
        $apiURL .= implode("&", $params);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $apiURL);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    
        curl_exec($ch);
        curl_close($ch);
    }
    
?>