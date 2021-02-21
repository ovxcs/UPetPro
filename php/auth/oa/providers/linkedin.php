<?php namespace auth\oa;

require_once __DIR__.'/../provider_class.php';

class LinkedIn extends Provider{

const name = 'linkedin';
const tag = 'lin';

const oauth2_url = "https://www.linkedin.com/oauth/v2/authorization";
const scope = 'r_basicprofile%20r_emailaddress';

const client_id = LINKEDIN_CLIENT_ID;
const client_secret = LINKEDIN_CLIENT_SECRET;

const access_token_url = 'https://www.linkedin.com/oauth/v2/accessToken';

const user_info_url = 'https://api.linkedin.com/v1/people/~';
const ID_KEY = 'id';
const NAME_KEY = 'name';
const EMAIL_KEY = 'email';
const PIC_KEY = 'picture';

public function req_user_info($access_token){
    $result = exec_url('GET', $this::access_token_url,
        array(
            'format' => 'json'), 
        array(
            "Authorization: Bearer $access_token")
        );
    $dct = json_decode($result, true);
    $dct['name'] = $dct['firstName'].' '.$dct['lastName'];
    dbgmsg(json_encode($dct));
    return $this->filter_user_info($dct);
}

}



?>