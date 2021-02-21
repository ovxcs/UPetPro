<?php namespace auth;

require_once("mwtoken.php");
require_once("oa/oauth_db_if.php");
require_once("oa/providers.php");

$mwt = get_mwt_cookie();
dbgmsg("MWT: $mwt");

$oaui = oa\OAuthDBIfWrap::getInstance()->get_user_info($mwt);
if($oaui){
    if (isset($oaui['prov']))
    $provider = oa\make_provider_obj($oaui['prov'], $mwt);
    //$res = $provider->revoke_token(0);
    //echo($res);
    //exit();
}

unset_mwt_cookie(explode('-', $mwt, 1)[0]);

header("Location: /cdx/login.html");
die();

?>