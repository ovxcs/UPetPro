<?php

//require_once(__DIR__."/../dbg/udp_dbg_client.php");
require_once(__DIR__."/../dbg/tcp_dbg_client.php");

//define( 'THIS_IS_MAIN', !count(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)) );
$server_script_filename_realpath;
function this_is_main(){
    global $server_script_filename_realpath;
    $caller = realpath(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file']);
    if (!$server_script_filename_realpath)
        $server_script_filename_realpath = realpath($_SERVER['SCRIPT_FILENAME']);
    if ($caller && $server_script_filename_realpath === $caller){
        return TRUE;
    }
}
//this_is_main();

function dbgmsg($msg, $ret = false, $btfi = 0){
    $btf = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $btfi + 1)[$btfi];
    $f = basename($btf['file'],'.php');
    $d = basename(dirname($btf['file']));
    $msg = print_r($msg, true);
    $m = "\n<$d/$f:{$btf['line']}> $msg";
    if ($ret) return $m;
    dbg_msg_send($m);
}

function dbgmsg_bt($depth = 0, $msg = '', $ret = false, $btfis = 0){
    $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $depth ? $depth + 1 : 0);
    $btf0 = $bt[$btfis];
    $f0 = basename($btf0['file'], '.php');
    $d0 = basename(dirname($btf0['file']));
    $str = "";
    for ($i = $btfis + 1; $i < count($bt); $i++){
        $btfx = $bt[$i];
        $fx = basename($btfx['file'], '.php');
        $dx = basename(dirname($btfx['file']));
        $str .= "$dx/$fx:{$btfx['line']}-{$btfx['function']}, ";
    }
    $m = "\nBACKTRACE FOR  <$d0/$f0:{$btf0['line']}>  IS  <$str> $msg";
    if ($ret) return $m;
    //error_log($m);
    dbg_msg_send($m);
}

$__JSON = null;
function try_grab_posted_json(){
    //Make sure that it is a POST request.
    if(isset($_SERVER['REQUEST_METHOD']) && strcasecmp($_SERVER['REQUEST_METHOD'], 'POST') != 0){
        //throw new Exception('Request method must be POST!');
        return false;
    }
    //Make sure that the content type of the POST request has been set to application/json
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
    //dbgmsg(substr($contentType, 0, 16));
    if(substr($contentType, 0, 16) !== 'application/json'){
        //throw new Exception('Content type must be: application/json');
        return false;
    }
    //Receive the RAW post data.
    $content = trim(file_get_contents("php://input"));
    //dbgmsg($content);
    //Attempt to decode the incoming RAW post data from JSON.
    $GLOBALS['__JSON'] = json_decode($content, true);
    //If json_decode failed, the JSON is invalid.
    if(!is_array($GLOBALS['__JSON'])){
        //throw new Exception('Received content contained invalid JSON!');
        return false;
    }
}
//try{
try_grab_posted_json();
/*}catch (Exception $e){
    error_log('Caught exception: '.$e->getMessage()."\n");
}*/

function get_global_dict($key){
    $_dict = 0;
    if (array_key_exists($key, $_POST)){
        $_dict = $_POST;
    }elseif ($GLOBALS['__JSON'] && array_key_exists($key, $GLOBALS['__JSON'])){
        $_dict = $GLOBALS['__JSON'];
    }elseif (array_key_exists($key, $_GET)){
        $_dict = $_GET;
    }else{
        return;
    }
    return $_dict;
}

function args_key_or_def($key, $def){
    global $__JSON;
    return isset($__JSON[$key]) ? ($__JSON[$key]) : (
            isset($_POST[$key]) ? $_POST[$key] : (isset($_GET[$key]) ? $_GET[$key] : $def)
        );
}

function exec_url($method, $url, $fields, $headers){
    $fields_string = http_build_query($fields);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($method === 'GET'){
        $url .= '?'.$fields_string;
    }
    curl_setopt($ch, CURLOPT_URL, $url);

    if ($method === 'POST'){
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    }
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    if ($headers){
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Cache-Control: no-cache"));
    curl_setopt($ch, CURLOPT_COOKIESESSION, TRUE);
    curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
    /*
    if ($usr_pwd){
        curl_setopt($ch, CURLOPT_USERPWD, "$usr_pwd");
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    }
    */
    $return = curl_exec($ch);
    curl_close($ch);
    //$return = file_get_contents($url);
    //var_dump(json_decode($return, true));
    return $return;
}

function alphaNumericRandom($length){
    $chars = "1234567890BCDFGHJKLMNPQRSTVWXYZbcdfghjklmnpqrstvwxyz";
    //$chars = "1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
    $clen = strlen($chars)-1;
    $s = '';
    for ($i = 0; $i < $length; $i++) {
        $s .= $chars[random_int(0, $clen)];
    }
    return ($s);
}

function rand_gen__token($tag, $len = 16){
    return $tag.'-'.time().'-'.alphaNumericRandom($len);
}

function rand_gen__big_int($l=0x123488881111888, $h=0xfffDfffDfffDfff){ //60 bits
    return random_int($l, $h);
}

function rand_gen__b64salt($n = 32){
    return base64_encode(random_bytes(3 * floor($n/4)));
}

function storage_path($hash, $ts = 0){
    $d0 = (!$ts) ? floor(time()/1000000) : $ts;
    $d1 = substr($hash, 0, 2);
    $d2 = substr($hash, 0, 4);
    $p = "$d0/$d1/$d2";
    return $p;
}

function utils_test(){
    $r = storage_path('123456789', 0);
    echo $r;
}

//utils_test();

?>