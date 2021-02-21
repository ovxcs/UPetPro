<?php

require_once 'auth/auth.php';
require_once 'utils/utils.php';

/*require_once 'aucts/aucts.php';*/

function ep__main(){
    global $__JSON;
    dbgmsg("###################################");
    /*
    $headers = getallheaders();
    error_log("---------------------------------------------");
    error_log( print_r($headers, true) );
    error_log( print_r($_SERVER, true) );
    error_log( print_r($_COOKIE, true) );
    */
    $_dict = get_global_dict('req');
    dbgmsg(print_r($_dict, true));
    if (!$_dict) return; //NOT ME
    $req = $_dict['req'];
    if ($req == 'usr_info'){
        dbgmsg("###################################");
        $res = \auth\get_user_info_or_die();
        dbgmsg(print_r($res, true));
        echo(json_encode($res));
        return;
        
    }
}
ep__main();




?>