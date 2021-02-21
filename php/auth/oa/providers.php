<?php namespace auth\oa;

require_once __DIR__."/../../config.php";

require_once __DIR__."/provider_class.php";

require_once __DIR__."/providers/fb.php";
require_once __DIR__."/providers/google.php";
require_once __DIR__."/providers/linkedin.php";
require_once __DIR__."/providers/amazon.php";

//require_once __DIR__."/providers/twitter.php"; not working with oauth2

require_once __DIR__."/oauth_db_if.php";

const OAUTH_PROVIDERS = array("Google", "Facebook", "LinkedIn", "Amazon");

function provider_class_from_string($provider_name){
    foreach(OAUTH_PROVIDERS as $class_name){
        if (strcasecmp($provider_name, $class_name) === 0)
            return $class_name;
    }
}

function make_provider_obj($provider_name, $mwt){
    //called in login.php and logout
    $cn = "auth\\oa\\".provider_class_from_string($provider_name);
    return new $cn($mwt);
}

function make_provider_from_state($state){
    //called in login2.php after redirection
    $is_a_test = strpos($state, 'tst-') === 0;
    if ($is_a_test) $state = substr($state, 4);
    foreach(OAUTH_PROVIDERS as $p){
        $Pv_class = "auth\\oa\\".$p;
        if (strpos($state, $Pv_class::tag) === 0){
            return new $Pv_class($state, $is_a_test);
        }
    }
}

/*
function make_provider_from_mwt($mwt){
    //$oadbif = OAuthDBIfWrap::getInstance();
    $oaui = OAuthDBIfWrap::getInstance()->get_user_info($mwt);
    if($oaui){
        if (isset($oaui['prov']))
        return make_provider_obj($oaui['prov'], $mwt);
    }
}
*/

?>