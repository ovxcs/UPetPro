<?php namespace auth\oa;

require_once __DIR__.'/../provider_class.php';

class Twitter extends Provider{

const name = 'twitter';
const tag = 'tw';

const oauth2_url = "https://api.twitter.com/oauth2/token";
const scope = 'r_basicprofile%20r_emailaddress';
//grant_type=client_credentials

const client_id = TWITTER_CONSUMER_KEY;
const client_secret = TWITTER_CONSUMER_SECRET;

const access_token_url = 'https://www.linkedin.com/oauth/v2/accessToken';

const user_info_url = 'https://api.linkedin.com/v1/people/~';
const ID_KEY = 'id';
const NAME_KEY = 'name';
const EMAIL_KEY = 'email';
const PIC_KEY = 'picture';


public function req_access_token($code){
    $authorization = $this::client_id.':'.$this::client_secret;
    //error_log(">>>>>>".$authorization);
    $return = exec_url('POST', $this::oauth2_url,
        array(
            'grant_type' => 'client_credentials',
        ), 
        array(
            "Content-Type: application/x-www-form-urlencoded;charset=UTF-8",
            "Authorization : Basic ". base64_encode($authorization)
        )
    );
    //dbgmsg('req_access_token: '.$return);
    var_dump($return);
    $return = json_decode($return, true);
    error_log("************************************".$return);
    /*
    if (isset ($return['error'])){
        $this->oa_db_if->delete_code($code);
        dbgmsg(print_r($return['error'], true));
        die(json_encode(['error'=>'internal_error']));
    }
    return $return;
    */
}


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

}//END TWITTER CLASS

function test(){
    $o = new Twitter("fake_mwt");
    $r =$o->req_access_token(0);
    echo($r);
    echo "OK2";
}

test();



?>