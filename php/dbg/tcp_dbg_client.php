<?php

$dbg_server_address = "127.0.0.1";//isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : 0;//"127.0.0.1";
$dbg_server_port = 2122;

$__g_dbg_tcp_sock = -1;
const __g_dbg_tcp_sock_open_timeout = 0.2;

function dbg_socket_close($sock){
    fclose($sock);
}

function dbg_socket_open(){
    global $__g_dbg_tcp_sock, $dbg_server_address, $dbg_server_port;
    if ($dbg_server_address === 0){
        //probably running from cmd
        $__g_dbg_tcp_sock = -2; return;
    }
    $errorcode = 0;
    $errormsg = "";
    $rc = @fsockopen($dbg_server_address, $dbg_server_port, 
            $errorcode, $errormsg, __g_dbg_tcp_sock_open_timeout);
    if(is_resource($rc))
    {
        $__g_dbg_tcp_sock = $rc;
        register_shutdown_function ('dbg_socket_close', $__g_dbg_tcp_sock);
    }
    else{
        if(__g_dbg_tcp_sock_open_timeout == null)
            die("Couldn't create socket: [$errorcode] $errormsg");
        else{
            //server not running - ignore
            $__g_dbg_tcp_sock = -3;
        }
    }
}

function fwrite_with_retry($sock, $data)
{
    $bytes_to_write = strlen($data);
    $bytes_written = 0;
    while ( $bytes_written < $bytes_to_write )
    {
        if ( $bytes_written == 0 ) {
            $rv = fwrite($sock, $data);
        } else {
            $rv = fwrite($sock, substr($data, $bytes_written));
        }
        if ( $rv === false || $rv == 0 )
            return( $bytes_written == 0 ? false : $bytes_written );
        $bytes_written += $rv;
    }
    return $bytes_written;
}

$__g_local_log_path = null;
function dbg_local_log_path(){
    global $__g_local_log_path;
    if ($__g_local_log_path == null){
        $server_name = $_SERVER['SERVER_NAME'];
        $server_port = $_SERVER['SERVER_PORT'];
        $host = $_SERVER['SERVER_NAME'].'.'.$_SERVER['SERVER_PORT'];
        $__g_local_log_path = __DIR__."/../../logs/$host.log";
        $date = date("H:i:s");
        $__new = "\n\n----------------------------------- [$date] ---------------";
        error_log(print_r($__new, true), 3, dbg_local_log_path());
    }
    return $__g_local_log_path;
}

function dbg_msg_send($input){
    global $__g_dbg_tcp_sock, $dbg_udp_server_address;
    if ($__g_dbg_tcp_sock === -1) dbg_socket_open();
    if ($__g_dbg_tcp_sock === -2){
        print_r($input); return;
    }
    if ($__g_dbg_tcp_sock === -3){
        //server probably not running 
        error_log(print_r($input, true), 3, dbg_local_log_path());
        return;
    }
    $rv = fwrite_with_retry($__g_dbg_tcp_sock, $input);
    if (!$rv)
        die("Unable to write to socket");
    if ($rv != strlen($input))
        die("Incomplete write to socket");
    //error_log("receiving ack");
    //fread($__g_dbg_tcp_sock, 4);
}

?>