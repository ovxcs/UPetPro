<?php

require_once (__DIR__.'/../utils.php');

const MWT_KEY_PREFIX = 'mwt2o17';
const MWT_CODE_ROOT = '/cdx';
const MWT_DOMAIN = "";
const MWT_SECURE = false;
const MWT_LENGTH = 15;
const MWT_TAG = "mwt";
define ('MWT_EXP', 3600);

function unset_mwt_cookie($provider_tag){
    dbgmsg("UNSET mwt cookie: pvt=$provider_tag");
    $past_exp_ts = time() - 3600 * 24 * 365 * 3;
    setcookie(MWT_KEY_PREFIX."-$provider_tag", '',
        $past_exp_ts, MWT_CODE_ROOT, MWT_DOMAIN, MWT_SECURE, true);
    setcookie(MWT_KEY_PREFIX."-LPV", '',
        $past_exp_ts, MWT_CODE_ROOT, MWT_DOMAIN, MWT_SECURE, true);
}

function set_mwt_cookie($provider_tag, $exp = MWT_EXP){
    dbgmsg("set mwt cookie: pvt=$provider_tag");
    $exp_ts = time() + $exp;
    $v = gen_random("$provider_tag--".MWT_TAG, MWT_LENGTH);
    dbgmsg("setting cookie for $provider_tag to $v");
    setcookie(MWT_KEY_PREFIX."-$provider_tag", $v,
            $exp_ts, MWT_CODE_ROOT, MWT_DOMAIN, MWT_SECURE, true);
    setcookie(MWT_KEY_PREFIX."-LPV", $provider_tag,
            $exp_ts, MWT_CODE_ROOT, MWT_DOMAIN, MWT_SECURE, true);
    return $v;
}

function get_mwt_cookie($provider_tag = 0){
    dbgmsg("get mwt cookie: pvt=$provider_tag");
    $lpv_k = MWT_KEY_PREFIX."-LPV";
    $lpv_v = isset($_COOKIE[$lpv_k]) ? $_COOKIE[$lpv_k] : 0;
    if (!$lpv_v) return;
    if ($provider_tag && $provider_tag != $lpv_v) return;
    if (!$provider_tag) $provider_tag = $lpv_v;
    
    $mwt_key = MWT_KEY_PREFIX."-".$provider_tag;
    if (isset($_COOKIE[$mwt_key])){
        $v = ($_COOKIE[$mwt_key]);
        $tag_mwt = "$provider_tag--".MWT_TAG;
        if (strpos($v, $tag_mwt, 0)===0)
            return $v;
    }
}
function set_mwt_cookie_if_not($provider_tag, $exp = MWT_EXP){
    dbgmsg("set mwt cookie IF NOT: pvt=$provider_tag");
    $c = get_mwt_cookie($provider_tag);
    dbgmsg("mwt cookie found: $c");
    if ($c) return $c;
    return set_mwt_cookie($provider_tag, $exp);
}
?>