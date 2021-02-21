<?php namespace auth\oa;

require_once __DIR__.'/../../config.php';
require_once __DIR__.'/../../utils/utils.php';
require_once __DIR__.'/oauth_db_if.php';

class Provider{

const redirect_uri = OAUTH_REDIRECT_URI;

protected $mwt, $test_mode;

//public $conn;
public $oauth;
public $last_error;

function __construct($mwt, $test_mode = false){
    /*global $DB_SERVER, $DB_USER, $DB_PWD, $DB_NAME;
    $this->conn = new mysqli($DB_SERVER, $DB_USER, $DB_PWD, $DB_NAME);*/
    $this->mwt = $mwt;
    $this->test_mode =$test_mode;
    $this->oa_db_if = new OAuthDBIfWrap();
}

function __destruct(){}

public function set_last_error($json){
    $this->last_error = json_encode($json);
}

function oauth_login_url(){
    $state = rand_gen__token($this::tag);
    $cid = $this::client_id;
    $scope = $this::scope;
    $url = $this::oauth2_url;
    $redir = OAUTH_REDIRECT_URI_ENC;
    $oa_url = "$url?response_type=code&state=$state&client_id=$cid&redirect_uri=$redir&scope=$scope";
    //dbgmsg("OAuth2 URL:".$oa_url);
    return $oa_url;
}

public function filter_user_info($raw_dict){
    //error_log(json_encode($raw_dict));
    $keys = array(
        'eid' => $this::ID_KEY,
        'name' => $this::NAME_KEY,
        'email' => $this::EMAIL_KEY,
        'pict' => $this::PIC_KEY);
    $ret = array();
    foreach ($keys as $ck => $pk){
        if ($pk != null && isset($raw_dict[$pk]))
            $ret[$ck] = $raw_dict[$pk];
    }
    return $ret;
}

public function test(){ error_log("Not implemented!");}
public function revoke_token($tkn){ error_log("revoke_token - not implemented for ".$this::name." !");}

public function req_access_token($code){
    $return = exec_url('POST', $this::access_token_url, array(
        'client_id' => $this::client_id,
        'redirect_uri' => OAUTH_REDIRECT_URI,//$this::redirect_uri,
        'client_secret' => $this::client_secret,
        'code' => $code,
        'grant_type' => 'authorization_code',
        'prompt' => 'select_account'
    ), 0);
    //dbgmsg('req_access_token result: '.$return);
    $return = json_decode($return, true);
    if (isset ($return['error'])){
        $this->oa_db_if->delete_code($code);
        dbgmsg(print_r($return['error'], true));
        die(json_encode(['error'=>'internal_error']));
    }
    return $return;
}

function get_or_req_access_token($mwt){
    //try load it from DB;
    $this->last_error = 'None';
    $access_token = $this->oa_db_if->get_access_token($mwt, $this::name);
    if ($access_token === false){
        dbgmsg('access_token is missing or expired');
        $code = $this->oa_db_if->get_oauth_exchange_token($mwt, $this::name, 'code');
        if (!$code){
            dbgmsg("AUTH CODE IS MISSING. A FULL LOGIN REQUIRED");
            $this->last_error = 'auth_code_missing__full_login_required';
            return false;
        }
        $json = $this->req_access_token($code);
        if (isset($json['error'])){
            $this->set_last_error($json);
            //error_log(json_encode($json['error']));
            return false;
        }
        //dbgmsg('saving access token ...');
        $json['prov'] = $this::name;
        //dbgmsg(print_r($json, true));
        $this->oa_db_if->save_access_token($json, $mwt);
        return $json['access_token'];
    }
    return $access_token;
}

function get_or_req_user_info($mwt){
    dbgmsg("get logged in user info for mwt=$mwt");
    $info = $this->oa_db_if->get_user_info($mwt);
    //dbgmsg(json_encode($info));
    if ($info === false){
        dbgmsg("info not found for mwt=$mwt");
        $tkn = $this->get_or_req_access_token($mwt);
        if ($tkn === false){
            //last_error already set;
            return false; 
        }
        $remote_user_info = $this->req_user_info($tkn);
        $remote_user_info['prov'] = $this::name;
        dbgmsg("****".print_r($remote_user_info, true)."****");
        $this->oa_db_if->save_user_info($mwt, $remote_user_info);
        $info = $this->oa_db_if->get_user_info($mwt);
    }else{
        dbgmsg("user info found in OADB for mwt=$mwt");
    }
    return $info;
}

}
?>