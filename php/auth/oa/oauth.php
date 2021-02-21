<?php
/*
require_once __DIR__.'/fb.php';
require_once __DIR__.'/google.php';
require_once __DIR__.'/twitter.php';
require_once __DIR__.'/amazon.php';
require_once __DIR__.'/linkedin.php';

function provider_from_state($state){
    $ss = substr($state, 0, 2);
    if ($ss === 'gg') return 'google';
    if ($ss === 'fb') return 'facebook';
    if ($ss === 'tw') return 'twitter';
    if ($ss === 'am') return 'amazon';
    if ($ss === 'ms') return 'microsoft';
    if ($ss === 'li') return 'linkedin';
}

function create_provider_interf($state){
    if ((substr($state, 0, 2) === 'fb')){
        $prov = new Facebook($state);
    }
    else if ((substr($state, 0, 3) === 'ggl')){
        $prov = new Google($state);
    }
    else if ((substr($state, 0, 2) === 'tw')){
        $prov = new Twitter($state);
    }
    else if ((substr($state, 0, 3) === 'ama')){
        $prov = new Amazon($state);
    }
    else if ((substr($state, 0, 3) === 'lin')){
        $prov = new LinkedIn($state);
    }
    return $prov;
}
*/
?>