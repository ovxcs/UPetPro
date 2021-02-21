<?php namespace auth\oa;

require_once __DIR__.'/../provider_class.php';

class Facebook extends Provider{

const name = 'facebook';
const tag='fb';

const facebook_v = 'v6.0';

const oauth2_url = "https://www.facebook.com/".self::facebook_v."/dialog/oauth";
const scope = 'email';

const client_id = FB_APP_ID;
const client_secret = FB_APP_SECRET;

const access_token_url = "https://graph.facebook.com/".self::facebook_v."/oauth/access_token";

const user_info_url = 'https://graph.facebook.com/me';
const ID_KEY = 'id';
const NAME_KEY = 'name';
const EMAIL_KEY= 'email';
const PIC_KEY = null;



public function req_user_info($access_token){
    $return = exec_url('GET', $this::user_info_url, array(
        'fields' => 'id,name,email',
        'access_token' => $access_token), 0);
    return $this->filter_user_info(json_decode($return, true));
}

}

function test(){
    $fb = new Facebook("fake_mwt");
    $fb->request_access_token("");
}


?>