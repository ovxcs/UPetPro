<?php

require_once __DIR__."/_defs.php";

function load_guessed_config(){

    global $CONFIGS_DEFS;
    error_log(print_r($CONFIGS_DEFS, true));
    error_log(" ==================== ");
    $server_name = $_SERVER['SERVER_NAME'];
    $server_port = $_SERVER['SERVER_PORT'];
    $host = $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'];
    
    foreach($CONFIGS_DEFS as $cfg){
        foreach($cfg['regex'] as $regex){
            //echo($regex);
            //echo($host);
            if (preg_match($regex, $host)){
                require_once($cfg['script']);
                return $cfg;
            }
        }
    }
    //throw new Exception("Host not identified");
    error_log("WARNING: Host not identified!");
    return false;
    #$document_root = $_SERVER['DOCUMENT_ROOT'];
    #$http_host = $_SERVER['HTTP_HOST'];
    #$server_admin = $_SERVER['SERVER_ADMIN'];
    #echo("$server_name, $document_root, $http_host, $server_admin, $server_port");

}

load_guessed_config()

?>