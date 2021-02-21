<?php namespace auth;

//fallbacks en, esp, deutsch, fr, port

require_once __DIR__.'/../config.php';
require_once __DIR__.'/../utils/utils.php';
require_once __DIR__.'/db_if.php';
require_once __DIR__.'/../mailer/mailer.php';

require_once __DIR__.'/attempts.php';

use DBIf;
use Attempts, AttemptResult;

/*$included_files = get_included_files();

foreach ($included_files as $filename) {
    error_log("$filename\n");
}*/

const attempt__too_soon__seconds_to_sleep = 10;

class LocalAuth {

static function send_activation_mail($dict, $dbif){
    global $APP_NAME, $FIRST_PAGE_AFTER_LOGIN;
    $code = rand(101234, 999888);
    $uehash = md5($dict['email']);
    $res = $dbif->save_user_data('email', array(
        'email' => $dict['email'],
        'verification' => $code));
    if (!$res){
        dbgmsg('ERROR: failed to save verification code in DB');
        return ['error' => 'activation_mail_sending_failed'];
    }
    $host = self::get_host();
    $to = 'ovvisoiu@yahoo.com';
    $headers = 'From: noreply@localhost.com' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

    $subj = 'Activation code';
    
    $msg = "Hello! \n    Please follow the next link in order to complete the registration for your '$APP_NAME' account"
            ." \n   $host/auth/auth.php?act=verif&code=$code&ueh=$uehash";
    //$res = mail($to, $subj, $msg, $headers);
    $res = send_mail($to, $subj, $msg);
    if ($res === false){
        dbgmsg('ERROR: send_mail failed');
        return ['error' => 'activation_mail_sending_failed'];
    }
    $login_res = self::login(['email' => $dict['email'], 'pwh' => $dict['pwh']]);
    $uinfo = $dbif->get_user_data('email', $dict['email'], '*');
    $ses = $dbif->get_session_data('usr_id', $uinfo['id']);
    /*
    return "<div> An e-mail containing the activation code for <span style='color:yellow'>$APP_NAME</span> will be sent to "
            ."<span style='color:yellow'>".$dict['email']."</span>. Please also check your spam folder."
            ."\n<br> <a href='".$FIRST_PAGE_AFTER_LOGIN.'?'.http_build_query(array(
                        'session' => $ses['sid'],
                        'provider' => 'lh'
                ))."'>Continue</a></div>";
    */
    return [
        'act_mail_sent' => true,
        'email' => $dict['email'],
        'session' => $ses['id'],
        'first_page' => $FIRST_PAGE_AFTER_LOGIN,
        'app_name' => 'this_app'
    ];
}

static function send_pw_reset_mail($dict, $dbif){
    global $HOST_SRC, $APP_NAME;
    $code = rand(10128456, 99988832);
    $uehash = md5($dict['email']);
    $res = $dbif->save_user_data('email', array(
            'email' => $dict['email'],
            'pwrc' => $code,
        ));
    $host = self::get_host();
    $to = 'ovvisoiu@yahoo.com';
    $headers = 'From: noreply@localhost' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();
    //translate
    $subj = 'Password reset code';
    $msg = "Hello! \n    Please follow the next link in order to reset the password for your <span style='color:yellow'>$APP_NAME</span> account"
            ." \n   $host/auth/auth.php?act=rpw&code=$code&ueh=$uehash";
    //$res = mail($to, $subj, $msg, $headers);
    $res = send_mail($to, $subj, $msg);
    if ($res === false){
        dbgmsg('ERROR: send_mail failed');
        return ['error' => 'pwr_mail_sending_failed'];
    }
    /*$login_res = login(['email' => $dict['email'], 'pwh' => $dict['pwh']]);
    $uinfo = $dbif->get_users_info('email', $dict['email'], '*');
    $ses = $dbif->get_sessions_info('uid', $uinfo['id']);*/
    /*
    return "<div> An e-mail containing the password reset code for <span style='color:yellow'>$APP_NAME</span> will be sent to "
            ."<span style='color:yellow'>".$dict['email']."</span>. Please also check your spam folder."
            ."\n<br> <a href='".$HOST_SRC.'/login.html?'.http_build_query(array(
                        'email' => $dict['email'],
                        'mode' => 'pwr',
                ))."'>Continue</a></div>";
    */
    return [
        'reset_code_sent' => true,
        'email' => $dict['email']
    ];
}

static function login($dict){
    if(!attempts__analyze(DBIf::getInstance()->conn, 'IP',
                    Attempts::LogIn, $dict['email'])){
        $resp['error'] = 'too_soon';
        sleep(attempt__too_soon__seconds_to_sleep);
        //return $resp;
    }
    $dbif = AuthDBIfWrap::getInstance();
    $uinfo = $dbif->get_user_data('email', $dict['email'], '*');
    $err = 0; $err_code = 0;
    if (!$uinfo){
        $err = ["error" => 'mail_not_found'];
        $err_code = 1;
    } else {
        if (isset($dict['pwh'])){
            if ($uinfo['password'] != $dict['pwh']){
                //attempts
                $err = ["error" => "login_password_incorrect"];
                $err_code = 2;
            } else {
                $mwt_array = set_mwt_cookie();
                $random = $mwt_array[1];
                $ts = $mwt_array[0];
                //setcookie("logintest", "2017", time() + 120, "/cdx", "", false, true);
                $dbif->save_session_data(
                        ['ts', 'random'], [
                    'usr_id' => $uinfo['id'],
                    'ts' => $ts,
                    'random' => $random,
                    'status' => 1
                ]);
            }
        } else {
            $mwt = get_mwt_cookie();
            if (!$mwt){
                $err = ["error"=>'incorrect_request_params'];
                $err_code = 3;
            } else {
                $ses = $dbif->get_session_data('sid', $mwt);
                if (!$ses){
                    $err = ['error' => 'invalid_session_id'];
                    $err_code = 4;
                }
            }
        }
    }
    if($err){
        $source = $_SERVER['REMOTE_ADDR'];
        $target = $dict['email'];
        register_attempt(Attempts::LogIn, $target, $source, AttemptResult::failed, $err_code);
        return $err;
    }
    $url = FIRST_PAGE_AFTER_LOGIN."?provider=lh";
    return array('location' => $url);
}

static function sign_up($dict){
    if(!attempts__analyze(DBIf::getInstance()->conn, 'IP',
                    Attempts::SignUp, $dict['email'])){
        $resp['error'] = 'too_soon';
        sleep(attempt__too_soon__seconds_to_sleep);
        //return $resp;
    }
    $email = $dict['email'];
    $dbif = AuthDBIfWrap::getInstance();
    $res = $dbif->get_user_data('email', $email, '*');
    dbgmsg("signup: user already exists: ".($res ? 'yes' : 'no'));
    $err = 0;
    $err_code = 0;
    if ($res){
        //$msg = "Error: User with '".$dict['email']."' already exists!";
        //return $msg;
        $err = ['error' => 'user_already_exists'];
        $err_code = 1;
    }else{
        $res = $dbif->save_user_data('email', array(
            'email' => $email,
            'random' => rand_gen__big_int(),
            'name' => $dict['name'],
            'password' => $dict['pwh'],
            'salt' => rand_gen__b64salt(),
            'lang' => $dict['lang'],
            'status' => 0,
            'ts' => time()
        ));
        dbgmsg(print_r($res, true));
        $res = self::send_activation_mail($dict, $dbif);
    }
    if($err){
        register_attempt(Attempts::SignUp, $dict['email'], 'IP', AttemptResult::failed, $err_code);
        return $err;
    }
    return $res;
}

static function init_pw_reset($dict){
    $dbif = AuthDBIfWrap::getInstance();
    $res = $dbif->get_user_data('email', $dict['email'], '*');
    if (!$res){
        return ['error' => 'mail_not_found'];
    }
    $res = self::send_pw_reset_mail($dict, $dbif);
    return $res;
}

static function activate($dict){
    $dbif = \AuthDBIf::getInstance();
    $ret = ['error' => false];
    $res1 = $dbif->get_user_data('verification', $dict['code'], '*');
    if (!$res1) { $ret['error'] = 'activation_code_not_found'; return ($ret); }
    $res2 = $dbif->get_user_data('emh', $dict['ueh'], '*');
    if (!$res2) { $ret['error'] = 'email_hash_not_found'; return ($ret); }
    if ($res1['verif'] === $res2['verif'] && $res1['emh'] === $res2['emh']){
        //if ($res1['status'] !== 0){
        if($res1['flags'] & AuthDBIfWrap::FLAG_ACTIVTED){
            $ret['error'] = 'already_activated';
        } else {
            $dbif->save_user_data('email', [
                'email' => $res1['email'],
                'status' => 1
            ]);
        }
    } else {
        $ret['error'] = 'invalid_activation_code';
    }
    return $ret;
}

private static $_host = null;
static function get_host(){
    global $G_HOST;
    if (!self::$_host){
        self::$_host = $G_HOST;
    }
    return self::$_host;
}

} //END LocalAuth




define ('LAUTH_REQ_TYPE', 'lauth_act');

function l_auth_main(){
    $resp = ['error' => 'unknown req.'];
    $act = 0;
    if (isset($_POST[LAUTH_REQ_TYPE])){
        {
            $act = $_POST[LAUTH_REQ_TYPE];
            if ($act == 'su'){
                $resp = LocalAuth::sign_up($_POST);
            } else if ($act == 'li'){
                $resp = LocalAuth::login($_POST);
                //redirect to the home page and exit in this case;
                //request should be made with submit
            } else if ($act == 'ipwr'){
                $resp = LocalAuth::init_pw_reset($_POST);
            }
        }
    } else if (isset ($_GET[LAUTH_REQ_TYPE])){
        {
            $act = $_GET[LAUTH_REQ_TYPE];
            if ($act == 'verif'){
                $resp = LocalAuth::activate($_GET);
            }
        }
    } else {
        //NOT ME;
        return;
    }
    if (gettype($resp) === 'array'){
        $str = json_encode($resp);
    } else {
        $str = "<div style='font-size:25px'> $resp </div>";
    }
    dbgmsg($str);
    echo($str);
    exit();
}

//l_auth_main();

/*
try{
    auth_main();
}catch (Exception $e){
    dbgmsg($e->getMessage());
    echo(['error' => "Internal error"]);
}
*/
?>