<?php namespace stoma;

require_once __DIR__.'/../auth/mwtoken.php';

class Session{

    const ENABLED = 8;

    static function get_mwt(){
        $r = get_mwt_cookie();
        return $r;
    }

    static function addNewMWTForAccountId($account_id){
        $dbif = \DBIf::getInstance();
        $ca = set_mwt_cookie();
        $can = $ca[0];
        $ctsns = time();//hrtime();
        $atsns = $ctsns;
        $flags = self::ENABLED; 
        $res = $dbif->conn->query("INSERT INTO sessions(mwt, ctsns, atsns, aid, flags)
            VALUES('$can', $ctsns, $atsns, $account_id, $flags)");
        if (!$res){
            dbgmsg($dbif->conn->error);
            return false;
        }
        return $ca;
    }

    static function getAccountIdForCurrentMWT(){
        $mwt = self::get_mwt();
        dbgmsg("MWT: $mwt");
        if ($mwt){
            $dbif = \DBIf::getInstance();
            //dbgmsg("SELECT * FROM account where mwt='$mwt'");
            $res = $dbif->conn->query("SELECT * FROM sessions where mwt='$mwt'");
            if ($res){
                $row = $res->fetch_assoc();
                if ($row){
                    if ($row['flags'] & self::ENABLED){
                        return $row['aid'];
                    }else{
                        dbgmsg("This MWT was disabled, probably by logout");
                    }
                }else{
                    dbgmsg("This MWT was not registered here");
                }
            }else{
                dbgmsg($dbif->conn->error);
            }
        }else{
            dbgmsg("MWT not set");
        }
        return false;
    }

    static function removeCurrentMWT(){
        $dbif = \DBIf::getInstance();
        $mwt = self::get_mwt();
        //dbgmsg($mwt);
        $res = $dbif->conn->query("UPDATE sessions set flags=0 WHERE mwt='$mwt'");
        if (!$res){
            dbgmsg($dbif->conn->error);
            return false;
        }
        return true;
    }
}


class Account{

    const tables = array('accounts', 'personal', 'clients');
    const def_salt = "qwertyuiop";

    //test account id=187;pw=azor2020
    //43ee7b87f50214ec6b5f469e38237be1180a3f1acbf7c25109b98446462f9b4f
    static function login(&$dict){
        $dbif = \DBIf::getInstance();
        //$fid = intval($dict['data']['fid']);
        //$tid = intval($dict['data']['tid']);
        $uid = $dict['data']['uid'];
        dbgmsg($uid);
        //$tid = 0; $fid = 0;
        if (strpos($uid, '@') !== false){
            
        }else{
            $fid = intval(substr($uid, 1));
            $tid = intval($uid[0]);
            dbgmsg($tid);
        }
        //dbgmsg("SELECT * FROM accounts WHERE fid=$fid AND tid=$tid");
        $where = $tid === 0 ? "id=$fid" : "fid=$fid AND tid=$tid";
        dbgmsg($where);
        $result = $dbif->conn->query("SELECT * FROM accounts WHERE $where");
        //dbgmsg($result);
        if (!$result){
            dbgmsg("Login failed");
            dbgmsg($dbif->conn->error);
            $dict['error'] = "login_failed_internal_error";
            return false;
        }
        $row = $result->fetch_assoc();
        if (!$row){
            dbgmsg("Login failed");
            dbgmsg($dbif->conn->error);
            $dict['error'] = "login_failed_account_not_found";
            return false;
        }
        $salt = (isset($row['salt']) && $row['salt']) ? $row['salt'] : self::def_salt;
        $pwh = self::calc_pwh(substr($dict['data']['pw'], 0, 64), $salt);
        dbgmsg($pwh);
        dbgmsg($row);
        if ($pwh !== $row['pwh']){
            dbgmsg("Login failed");
            $dict['error'] = "login_failed_pw_mismatch";
            return false;
        }
        //add new mwt for account
        if (Session::addNewMWTForAccountId($row['id'])){
            $dict['info'] = 'login_success';
            $dict['extra'] = self::get_user_extra_info($tid, $fid);
            return true;
        }
        else {
            $dict['info'] = 'login_failed';
            $dict['error'] = 'creating_session_failed';
        } 
    }

    static function calc_pwh($str, $salt){
        //dbgmsg($str.$salt);
        return hash("sha256", $str.$salt);
    }

    static function get_user_extra_info($tid, $fid){
        $dbif = \DBIf::getInstance();
        $table = self::tables[$tid];
        $row = $dbif->conn->query("SELECT * from $table where id=$fid")->fetch_assoc();
        return $row;
    }

    static function get_user_or_die(){
        $aid = Session::getAccountIdForCurrentMWT();
        $error = "unknown";
        if ($aid){
            $dbif = \DBIf::getInstance();
            $res = $dbif->conn->query("SELECT * FROM accounts where id='$aid'");
            if ($res){
                $row = $res->fetch_assoc();
                //dbgmsg("HELLO");
                //dbgmsg($row);
                $e = self::get_user_extra_info($row['tid'], $row['fid']);
                $row['extra'] = $e;
                if ($row)
                    return $row;
            }else{
                dbgmsg($dbif->conn->error);
                $error = 'internal_error';
            }
        }else{
            $error = 'invalid_or_missing_mwt';
        }
        $out = [
            'error' => $error
        ];
        echo(json_encode($out));
        die();
    }

    static function logout($dict){
        //$dbif = \DBIf::getInstance();
        //$mwt = $dict['data']; //!!!
        //dbgmsg($mwt);
        if (!Session::removeCurrentMWT()){
            //dbgmsg("mwt not found");
            //$dict['error'] = $dbif->conn->error;
            $dict['error'] = 'internal_error';
        }else{
            $dict['info'] = 'signed_out';
        }
        echo(json_encode($dict));
        die();
    }
}
?>