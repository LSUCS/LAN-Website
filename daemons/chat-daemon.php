<?php
    
    echo "### LSUCS LAN Website Chat Daemon ###\n";
    echo "Initiating...\n";

    ini_set('display_errors','On');
    error_reporting(E_ALL);
    
    echo "Checking client...\n";
    
    //This can't run via the browser
    if(!php_sapi_name() == 'cli' || !empty($_SERVER['remove_addr'])) die("Cannot execute daemon via browser");
    
    echo "Including required files...\n";
    
    //Include necessaries
	include(dirname(__FILE__) . "/../config.php");
	include(dirname(__FILE__) . "/../db.php");
    include(dirname(__FILE__) . "/../settings.php");
	include(dirname(__FILE__) . "/../auth.php");
	include(dirname(__FILE__) . "/../lib/WebSockets/websockets.php");
	include(dirname(__FILE__) . "/chat/main.php");
	include(dirname(__FILE__) . "/chat/chatserver.php");
	include(dirname(__FILE__) . "/chat/user.php");
	
    //Main
    echo "Launching main class...\n";
    $_MAIN = new Main();
	$_MAIN->init();

?>