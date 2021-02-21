<?php

$dbg_udp_server_address = $_SERVER["REMOTE_ADDR"];//"127.0.0.1";
const dbg_udp_server_port = 7070;

if(!($__g_dbg_udp_sock = socket_create(AF_INET, SOCK_DGRAM, 0)))
{
    $errorcode = socket_last_error();
    $errormsg = socket_strerror($errorcode);
    die("Couldn't create socket: [$errorcode] $errormsg");
}

function dbg_msg_send($input){
    global $__g_dbg_udp_sock, $dbg_udp_server_address;
    if( !socket_sendto($__g_dbg_udp_sock, $input, strlen($input), 0,
                    $dbg_udp_server_address, dbg_udp_server_port))
    {
        $errorcode = socket_last_error();
        $errormsg = socket_strerror($errorcode);
        die("Could not send data: [$errorcode] $errormsg \n");
    }
}

?>