<?php namespace auth\oa;

require_once (__DIR__.'/../mwtoken.php');
require_once (__DIR__.'/providers.php');
require_once (__DIR__.'/../db_if.php');

/*else if ((substr($state, 0, 2) === 'tw')){
            $prov = new Twitter($state);
            //$prov->req_user_info($DICT);
            dbgmsg("twitter saving verifier: ".$DICT['oauth_verifier']);
            $prov->oa_db_if->save_oauth_exchange_token($state, 'verifier', $DICT['oauth_verifier']);
}*/

function process_redirection_back($dict, $method){
    //dbgmsg(print_r($dict, true));
    $state = $dict['state'];
    $is_a_test = strpos($state, 'tst-') === 0;
    $ts_rand = set_mwt_cookie();
    $mwt = mwt_from_ts_rand($ts_rand);
    $prov = make_provider_from_state($state);
    if (!$prov){
        dbgmsg("provider creation failed!. state:  $state");
        return false;
    }
    // an new value for MWT is required;
    $dict['state'] = $mwt; //!!!!!!!!!!!!!!!!!!!!!!!! TRICK !!!!!!!!!!!!!!!!!!!!!!!!
    //dbgmsg(print_r($dict, true));
    $code = $dict['code'];
    //dbgmsg("saving code token:".$code);
    dbgmsg(print_r($dict, true));
    dbgmsg("$$$".$mwt);
    
    $r = $prov->oa_db_if->save_oauth_exchange_token($mwt, $prov::name, 'code', $dict['code']);
    dbgmsg($r);
    //obtain user info if not exist (from oauth db if found or remote)
    $ui_dict = $prov->get_or_req_user_info($mwt);
    
    //dbgmsg(">>>>>>>>>>>>>>>".print_r($ui_dict, true)."<<<<<<");
    //add user to the local auth DB if not exist
    $res = register_oauth_user_locally_if_not_exist($ui_dict);
    //create session
    dbgmsg("**** C R E A T E    S E SS I O N    for oauth ****");
    \auth\AuthDBIfWrap::getInstance()->save_session_data(
            ['ts', 'random'], [
        'usr_id' => $res['id'],
        'ts' => $ts_rand[0],
        'random' => $ts_rand[1],
        'status' => 1
    ]);
    dbgmsg("redirecting to the first(home) page: ".FIRST_PAGE_AFTER_LOGIN);
    header("Location: ".FIRST_PAGE_AFTER_LOGIN);
    exit();
}

function register_oauth_user_locally_if_not_exist($dict){
    //dbgmsg($dict);
    $dbif = \auth\AuthDBIfWrap::getInstance();
    dbgmsg("req oa usr loc if not for: {$dict['id']}");
    $res = $dbif->get_user_data('oa_db_id', $dict['id'], '*');
    dbgmsg("user already exists: ".($res ? 'yes' : 'no'));
    if ($res){
        //$msg = "Error: User with '".$dict['email']."' already exists!";
        //return $msg;
        return ['id' => $res['id'], 'info' => 'user_already_exists'];
    }
    //user does NOT exist
    //generate a string as email if this does not exist because the column must be not null
    //password must also be generated if the user does not exist
    $email = "#OA2-".$dict['id'];
    //$uemhash = hash("sha256", $email);
    $password = alphaNumericRandom(20);//random password; it can't be used anyway with the mail placeholder; - it should be a hash
    dbgmsg("adding user from oath user: $email, $password");

    $res = $dbif->save_user_data('email', array(
        'email' => $email,
        'oa_db_id' => $dict['id'],
        'eid' => $dict['prov'].'!D'.$dict['eid'],
        'random' => rand_gen__big_int(),
        'name' => $dict['name'],
        'password' => $password,
        'salt' => rand_gen__b64salt(),
        'lang' => isset($dict['lang']) ? $dict['lang'] : '!Unk',
        'status' => 0,
        'ts' => time()
    ));

    $dbif->save_user_extra('id', array(
        'id' => $res[1],
        'pict' => $dict['pict'],
        'flags' => ['|', 0x20]
                //0x10 - picture set by user;
                //0x20 - picture borrowed;
                //0x40 - picture default/internal;
                //0x80 - user does not want a picture;
    ));
    return ['id' => $res[1], 'info' => 'oauth_user_added_locally'];
}

function process_oa_skip($dict){
    $mwt = get_mwt_cookie();
    dbgmsg("!!! COOKIE FOUND: ".$mwt);
    $prov_name = $dict['prov'];
    $prov = make_provider_obj($prov_name, $mwt);
    $ui = $prov->get_or_req_user_info($mwt);
    //dbgmsg(print_r($ui, true));
    dbgmsg("redirecting to the first(home) page: ".FIRST_PAGE_AFTER_LOGIN);
    echo (FIRST_PAGE_AFTER_LOGIN); //this is a xhr request;
    //echo ("fpal");
    exit();
}

function login2_dispatcher($dict, $method){
    if (isset($dict['state']) && isset($dict['code'])){
        //request is comming after via oauth redirection
        dbgmsg("login2_disp.: req comming via oauth redirection");
        process_redirection_back($dict, $method);
    }
    elseif (isset($dict['oa']) && ($dict['oa'] === 'mwt_ok')){
        //mwt is ok - skip oauth
        dbgmsg("login2_disp.: mwt is ok skip oauth");
        process_oa_skip($dict);
    }
    else{
        dbgmsg("login2_disp.: this should not happen!!!");
    }
}

function login2__main(){
    dbgmsg("login2.php: Redirected here; Login probably succesfull. Params:");
    //dbgmsg(json_encode($_SERVER));
    //dbgmsg("GET:".json_encode($_GET));
    //dbgmsg("POST:".json_encode($_POST));
    if (empty($_GET)) login2_dispatcher($_POST, 'POST');
    else login2_dispatcher($_GET, 'GET');
    dbgmsg("login2 main done");
}

login2__main();

?>


