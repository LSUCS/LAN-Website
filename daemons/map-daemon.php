<?php
    
    echo "### LSUCS LAN Map Daemon ###\n";
    echo "Initiating...\n";

    ini_set('display_errors','On');
    error_reporting(E_ALL);
    
    echo "Checking client...\n";
    if(!php_sapi_name() == 'cli' || !empty($_SERVER['remote_addr'])) die("Cannot execute daemon via browser");
    
    echo "Registering shutdown functions...\n";
    declare(ticks = 1);
    register_shutdown_function("shutdown");
    pcntl_signal(SIGTERM, "sigShutdown");
    pcntl_signal(SIGINT, "sigShutdown");
    
    
    echo "Including files...\n";
    
    include(dirname(__FILE__) . '/../lib/LanWebsite/Autoload.php');
    
    echo "Initialising LanWebsite framework...\n";
    LanWebsite_Main::initialize();
    
    echo "Starting loop...\n";
    
    //Start daemon
    while (1) {
    
        $sleep = LanWebsite_Main::getSettings()->getSetting("map_daemon_sleep_period");
    
        echo "WOKEN UP: Processing...\n";
    
        //Start time
        $mtime = explode(" ",microtime());
        $starttime = $mtime[1] + $mtime[0];
        
        //Get tickets with seats
        $res = LanWebsite_Main::getDb()->query("SELECT * FROM `tickets` WHERE lan_number = '%s' AND seat != ''", LanWebsite_Main::getSettings()->getSetting("lan_number"));
        
        echo "Setting up cURL multi handler...\n";
        
        //Set up cURL multi handler
        $mh = curl_multi_init();
        $handles = array();
        while ($ticket = $res->fetch_assoc()) {
            $h = curl_init();
            curl_setopt($h, CURLOPT_HEADER, 0);
            curl_setopt($h, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($h, CURLOPT_URL, LanWebsite_Main::getSettings()->getSetting("map_process_url") . $ticket["ticket_id"]);
            curl_multi_add_handle($mh, $h);
            $handles[$ticket["ticket_id"]] = $h;
        }
        
        echo "Executing handles...\n";
        
        //Execute
        $running_handles = null;
        do {
            $status_cme = curl_multi_exec($mh, $running_handles);
        } while ($running_handles > 0);
        
        echo "Retrieving data...\n";
        
        //Retrieve data
        $cache = array();
        foreach ($handles as $ticket => $handle) {
            $error = curl_error($handle);
            if (empty($error)) {
                $data = curl_multi_getcontent($handle);
                if (strpos($data, '{"seat"') !== FALSE) {
                    $cache[] = json_decode(substr($data, strpos($data, '{"seat"')), true);
                }
            }
            curl_multi_remove_handle($mh, $handle);
        }
        
        echo "Cleaning up and storing data...\n";
        
        //Close cURL
        curl_multi_close($mh);
        
        //Clear table
        LanWebsite_Main::getDb()->query("TRUNCATE TABLE `map_cache`");
        
        //Store
        foreach ($cache as $seat) {
            LanWebsite_Main::getDb()->query("INSERT INTO `map_cache` (seat, user_id, username, name, steam, avatar, ingame, game, mostplayed, favourite, game_icon)
                                      VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
                                      $seat["seat"], $seat["user_id"], $seat["username"], $seat["name"], $seat["steam"], $seat["avatar"], $seat["ingame"], $seat["game"], $seat["mostplayed"], $seat["favourite"], $seat["game_icon"]);
        }
        
        
        //End time
        $mtime = explode(" ",microtime());
        $endtime = $mtime[1] + $mtime[0];
        $exectime = $endtime - $starttime;
        
        echo "Processing done. Execution time: " . ceil($exectime) . " seconds\n";
        
        //Change browser update period
        LanWebsite_Main::getSettings()->changeSetting("map_browser_update_interval", ceil($exectime + $sleep));
        
        echo "Sleeping for " . $sleep . " seconds...\n";
        
        sleep($sleep);
        
    }
    
    
    function sigShutdown() {
        exit();
    }
    function shutdown() {
        echo "Daemon shutting down...\n";
        LanWebsite_Main::getSettings()->changeSetting('chat_daemon_online', 0);
    }
    
    
    
?>