<?php namespace auth\oa;

require_once __DIR__.'/../provider_class.php';

class Google extends Provider{

const name = 'google';
const tag = 'ggl';

const oauth2_url = "https://accounts.google.com/o/oauth2/v2/auth";
const scope = 'profile%20email';

const client_id = GOOGLE_APP_ID;
const client_secret = GOOGLE_APP_SECRET;

const access_token_url = 'https://www.googleapis.com/oauth2/v4/token';

//const revoke_uri = 'https://www.googleapis.com/oauth2/revoke';
const revoke_uri = 'https://oauth2.googleapis.com/revoke';

const user_info_url = 'https://www.googleapis.com/oauth2/v1/userinfo';
const ID_KEY = 'id';
const NAME_KEY = 'name';
const EMAIL_KEY = 'email';
const PIC_KEY = 'picture';

public function req_user_info($access_token){
    $return = exec_url('GET', $this::user_info_url, array(
        'alt' => 'json',
        'access_token' => $access_token), 0);
    //dbgmsg($return);
    return $this->filter_user_info(json_decode($return, true));
}

public function revoke_token($access_token){
    if (!$access_token){
        //$mwt = $this->$mwt; //get_mwt_cookie();
        $access_token = $this->oa_db_if->get_access_token($this->mwt, $this::name);
    }
    //dbgmsg("REVOKE AT:".$access_token);
    $return = exec_url('POST', $this::revoke_uri, array(
            'token' => $access_token
        ), 0);
    //dbgmsg(print_r($return, true));
    return $return;
}

}

?>