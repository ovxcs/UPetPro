<?php
function bigIntToPath($number, $last = 's'){
    $h = dechex($number);
    $hL = strlen($h);
    $r = 4 - (($hL+3) % 4 + 1);
    $h = str_repeat('0', $r).$h;
    $a = str_split($h, 4);
    $a[] = $last;
    return implode('/', $a);
    
}

?>