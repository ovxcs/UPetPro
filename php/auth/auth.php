<?php namespace auth;

require_once __DIR__.'/oa/oauth_db_if.php';
require_once __DIR__.'/l_auth.php';
require_once __DIR__.'/mwtoken.php';

function user_info__oauth($mwt){
    $oa_dbif = oa\OAuthDBIfWrap::getInstance();
    $usr_info = $oa_dbif->get_user_info($mwt);
    if (!$usr_info){
        return false;
    }
    $usr_info['aid'] = $usr_info['eid'];
    return (array("usr_info" => $usr_info));
}

function user_info__local($mwt){
    $dbif = AuthDBIfWrap::getInstance();
    $sinfo = $dbif->get_session_data('MWT', $mwt);
    if (!$sinfo){
        dbgmsg("user_info__local: no session for mwt: $mwt");
        return ["error" => "invalid session: $mwt"];
    }
    $uinfo = $dbif->get_user_data('id', $sinfo['usr_id'], 'mid', TRUE);
    if (!$uinfo){
        dbgmsg("user_info__local: no user for session: ".$sinfo['id']." (mwt: $mwt)");
        return ['error' => 'user not found'];
    }
    $r = array("usr_info" => array(
        'name' => $uinfo['name'],
        'status' => $uinfo['status'],
        'flags' => $uinfo['flags'],
        'lang' => $uinfo['lang'],
        'aid' => $uinfo['id'],
        'pict' => isset($uinfo['pict']) ? $uinfo['pict'] : null,
        'prov' => $uinfo['prov  ']
    ));
    /*
        $uinfo_extra = DBUt::get_row_data('id', $sinfo['usr_id'], 'users_extra',
            $dbif->conn, $dbif->columns('users_extra'));
        $r['pict'] = $uinfo_extra['pict'];
    */
    return $r;
}

function auth__user_info($mwtoken){ //local or 3p.
    $ui = user_info__local($mwtoken);
    if (isset($ui['error'])){
        $ui = user_info__oauth($mwtoken);
        if ($ui && !isset($ui['error']))
            return $ui;
    }else{
        return $ui;
    }
    return ['error' => 'user not found at all'];
}

function get_user_info_or_die($really = true){
    $mwtoken = get_mwt_cookie();
    dbgmsg("get usr_info or die: MWT obtained: $mwtoken");
    if (!$mwtoken){
        if (!$really) return false;
        echo(json_encode(['error' => 'mwtoken not set']));
        die();
    }
    $result = auth__user_info($mwtoken);
    if (array_key_exists('error', $result) && $result['error']){
        if (!$really) return false;
        dbgmsg(print_r($result, true));
        echo(json_encode($result));
        die();
    }
    dbgmsg("get ui or die: AUTH USER INFO obtained for: ".$result['usr_info']['name']);
    return $result;
}

function auth__main(){
    //dbgmsg(print_r($_POST, true));
    //dbgmsg(print_r($_GET, true));
    l_auth_main();
}

auth__main();
?>