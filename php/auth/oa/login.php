<?php namespace auth\oa;

require_once __DIR__."/../mwtoken.php";
require_once __DIR__."/providers.php";
require_once __DIR__."/../../utils/utils.php";

function oa_login_main()
{
    //dbgmsg("POST:".print_r($_POST, true));
    //dbgmsg(" GET:".print_r($_GET, true));
    //dbgmsg("JSON:".print_r($GLOBALS['__JSON'], true));
    $_dct = (isset($_POST) && count($_POST)) ? $_POST : $_GET;
    $is_a_test = FALSE; //isset($_dct['tst']) && ($_dct['tst'] == 'tst');
    //dbgmsg(print_r($_dct, true));
    if (isset($_dct['login'])){
        $next_url = 0;
        $prov_name = $_dct['login'];
        dbgmsg(" *** request for login with: ".print_r($_dct, true));
        $mwt = get_mwt_cookie();
        dbgmsg("get mwt cookie: ".$mwt);
        $token = 0;
        if ($mwt){
            $prov_obj = make_provider_obj($prov_name, $mwt);
            dbgmsg("mwt to get from DB: ".$mwt);
            try{
                $token = $prov_obj->oa_db_if->get_oauth_exchange_token($mwt, $prov_name, 'access_token');
            }catch(\Throwable $e){
                if ($is_a_test){
                    dbgmsg("[test mode] Warning:".$e->getMessage());
                    $token = 0;
                }
                else{
                    dbgmsg("getting oauth exch tkn from OA table failed");
                    throw e;
                }
            }
            dbgmsg("login.php: token from DB: ".($token ? $token :'nothing found'));
        }
        if (!$token){
            //dbgmsg('COOKIE:'.print_r($_COOKIE, true));
            dbgmsg("mwt cookie missing or invalid - redirecting to the provider dialog page");
            $prov_obj = make_provider_obj($prov_name, null);
            $next_url = $prov_obj->oauth_login_url();
            dbgmsg("provider dialog page url:".$next_url);
        }
        else{
            dbgmsg("mwt cookie ok - skip provider login and jump to the home page: ".FIRST_PAGE_AFTER_LOGIN);
            $next_url = FIRST_PAGE_AFTER_LOGIN;
            exit(header("Location: /auth/oa/login2.php?oa=mwt_ok&prov=$prov_name"));
        }
        if ($next_url){
            if ($is_a_test){
                $next_url = preg_replace("/state=/", "state=tst-", $next_url);
                exit(header("Location: $next_url"));
            }
            else{
                echo $next_url;
                exit();
            }
        }
    }else{
        error_log("Login param. not set");
    }
}

error_log("\n\n Login process follows ==========================================================================================");
oa_login_main();

?>