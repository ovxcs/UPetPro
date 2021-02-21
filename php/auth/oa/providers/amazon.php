<?php namespace auth\oa;

require_once __DIR__.'/../provider_class.php';

class Amazon extends Provider{

const name = 'amazon';
const tag = 'ama';

const oauth2_url = "https://www.amazon.com/ap/oa";
const scope = 'profile';

const client_id = AMAZON_CLIENT_ID;
const client_secret = AMAZON_CLIENT_SECRET;

const access_token_url = "https://api.amazon.com/auth/O2/token";

const user_info_url = 'https://api.amazon.com/user/profile';
const ID_KEY = 'user_id';
const NAME_KEY = 'name';
const EMAIL_KEY = 'email';
const PIC_KEY = null;

public function req_user_info($access_token){
    dbgmsg("amazon.req_user_info");
    $ama_headers = array('Authorization: bearer ' . $access_token);
    $return = exec_url('GET', $this::user_info_url, array(
        'access_token' => $access_token),
        $ama_headers);
    return $this->filter_user_info(json_decode($return, true));
}

}

?>