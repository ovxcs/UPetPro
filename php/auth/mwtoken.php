<?php

require_once(__DIR__.'/../utils/utils.php');

const MWT_KEY_PREFIX = 'mwt2o17';
const MWT_CODE_ROOT = '/';
const MWT_DOMAIN = "";
const MWT_SECURE = false;
const MWT_LENGTH = 15;
const MWT_TAG = "mwt";
const MWT_EXP = 3600 * 24 * 30;

function unset_mwt_cookie(){
    dbgmsg("UNSET mwt cookie");
    $past_exp_ts = time() - 3600 * 24 * 365 * 3;
    setcookie(MWT_KEY_PREFIX, '',
        $past_exp_ts, MWT_CODE_ROOT, MWT_DOMAIN, MWT_SECURE, true);
}

/*
function set_mwt_cookie($exp = MWT_EXP){
    dbgmsg("set mwt cookie");
    $exp_ts = time() + $exp;
    $v = rand_gen__token(MWT_TAG, MWT_LENGTH);
    dbgmsg("setting mwt cookie to $v");
    setcookie(MWT_KEY_PREFIX, $v,
            $exp_ts, MWT_CODE_ROOT, MWT_DOMAIN, MWT_SECURE, true);
    return $v;
}*/

function mwt_from_ts_rand($ts_rand){
    return MWT_TAG.'-'.dechex($ts_rand[0]).'-'.dechex($ts_rand[1]);
}

function ts_rand_from_mwt($mwt){
    $a = explode('-', $mwt);
    return [hexdec($a[1]), hexdec($a[2])];
}

function set_mwt_cookie($exp = MWT_EXP){
    $exp_ts = time() + $exp;
    $bi = rand_gen__big_int();
    $a = [$exp_ts, $bi];
    $v = mwt_from_ts_rand($a);
    //$dt = new DateTime('@' . $exp_ts);
    //dbgmsg("setting mwt cookie to $v (exp.: {$dt->format('Y-m-d H:i:s')})");
    //setlocale(LC_TIME, "ro_RO");
    //date_default_timezone_set('Europe/Bucharest');
    dbgmsg("setting mwt cookie to $v (exp.: ".strftime('%A, %T', $exp_ts).' utc)');
    setcookie(MWT_KEY_PREFIX, $v,
            $exp_ts, MWT_CODE_ROOT, MWT_DOMAIN, MWT_SECURE, true);
    return [$v, $exp_ts, $bi];
}

function get_mwt_cookie(){
    //dbgmsg($_COOKIE);
    $mwt_key = MWT_KEY_PREFIX;
    if (isset($_COOKIE[$mwt_key])){
        $v = ($_COOKIE[$mwt_key]);
        return $v;
    }
}

function set_mwt_cookie_if_not($exp = MWT_EXP){
    dbgmsg("set mwt cookie IF NOT");
    $c = get_mwt_cookie();
    dbgmsg("mwt cookie found: $c");
    if ($c) return $c;
    return set_mwt_cookie($exp);
}

?>