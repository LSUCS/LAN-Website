<?php
    
    echo "### LSUCS LAN Website Lobby Daemon ###\n";
    echo "Initiating...\n";

    ini_set('display_errors','On');
    error_reporting(E_ALL);
    
    echo "Checking client...\n";
    if(!php_sapi_name() == 'cli' || !empty($_SERVER['remove_addr'])) die("Cannot execute daemon via browser");
    
    echo "Registering shutdown functions...\n";
    declare(ticks = 1);
    register_shutdown_function("shutdown");
    pcntl_signal(SIGTERM, "sigShutdown");
    pcntl_signal(SIGINT, "sigShutdown");
    
    
    echo "Including files...\n";
    
    include(dirname(__FILE__) . '/../lib/LanWebsite/Autoload.php');
    include(dirname(__FILE__) . "/../lib/WebSockets/websockets.php");
	include(dirname(__FILE__) . "/lobby/lobbyserver.php");
	include(dirname(__FILE__) . "/lobby/lobbyuser.php");
    
    echo "Initialising LanWebsite framework...\n";
    LanWebsite_Main::initialize();
    
    echo "Instantiating websocket lobby server...\n";
    LanWebsite_Main::getSettings()->changeSetting('lobby_daemon_online', true);
    new LobbyServer(LanWebsite_Main::getSettings()->getSetting("lobby_address"), LanWebsite_Main::getSettings()->getSetting("lobby_port"), LanWebsite_Main::getSettings()->getSetting("lobby_buffer_size"));
    
    
    function sigShutdown() {
        exit();
    }
    function shutdown() {
        echo "Daemon shutting down...\n";
        LanWebsite_Main::getSettings()->changeSetting('lobby_daemon_online', 0);
    }
    
    
    
?>