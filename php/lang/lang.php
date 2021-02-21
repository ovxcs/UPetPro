<?php

require_once __DIR__."/../utils/utils.php";

function lang_main(){
    if(isset($_GET['langs'])){
        $langs = $_GET['langs'];
        if(isset($_SERVER['HTTP_REFERER'])){
            dbgmsg($langs);
            dbgmsg($_SERVER['HTTP_REFERER']);
            //setcookie(MWT_KEY_PREFIX, $v, $exp_ts, MWT_CODE_ROOT, MWT_DOMAIN, MWT_SECURE, true);
            setcookie('langs', $langs, time() + 60*60*24*30, '/', '', true);
            header ("Location: ".$_SERVER['HTTP_REFERER']);
            exit;
        }
    }
    //echo("From lang.php: Something went wrong");
}
lang_main();
?>