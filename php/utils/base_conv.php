<?php
const __g_DEFAULT_ALPHABET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz_-';
function dec2base($dec, $alphabet=63)
{
    if (is_int($alphabet) and $alphabet <= 64){
        $base = $alphabet;
        $alphabet = __g_DEFAULT_ALPHABET;
    }else{
        $base = strlen($alphabet);
    }
    $res = '';
    while($dec) {
        $dec = ($dec-($r=$dec%$base))/$base;
        $res = $alphabet[$r] . $res;
    };
    return $res;
}

?>