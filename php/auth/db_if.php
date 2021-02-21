<?php namespace auth;
require_once __DIR__.'/../db/db_if.php';
use DBUt;

final class AuthDBIfWrap{

const FLAG_OAUTH_USER = 0x8;
const FLAG_ACTIVATED = 0x10;
const FLAG_ADMIN = 0x20;

private $dbif;

private function __construct(){
    $this->dbif = \DBIf::getInstance();
}

static function __kv__($key, $val){
    if ($key === 'MWT'){
        $key = ['ts', 'random'];
        $a = explode('-', $val);
        $val = [hexdec($a[1]), hexdec($a[2])];
    }
    return [$key, $val];
}

function save_session_data($key, $dict){
    //dbgmsg_bt();
    return DBUt::save_data($this->dbif->conn, 'sessions', $key, $dict);
}

function save_user_data($key, $dict){
    return DBUt::save_data($this->dbif->conn, 'users', $key, $dict);
}

function save_user_extra($key, $dict){
    //$key should always be user id from the user table
    return DBUt::save_data($this->dbif->conn, 'users_extra', $key, $dict);
}

function get_session_data($key, $val){
    $kv = self::__kv__($key, $val);
    $res = DBUt::get_row_data($kv[0], $kv[1], 'sessions', $this->dbif->conn,
            $this->dbif->columns('sessions'));
            //['sid', 'addr', 'status', 'uid', 'ts']);
    return $res;
}

function get_user_data($key, $val, $mode, $extra = FALSE){
    $res = DBUt::get_row_data($key, $val, 'users', $this->dbif->conn,
            $this->dbif->columns('users'));
    if ($res){
        //$res['flags'] |= (is_null($res['oa_db_id']) ? 0 : FLAG_OAUTH_USER);
        if (!is_null($res['oa_db_id'])){
            $res['flags'] |= self::FLAG_OAUTH_USER;
            $res['prov'] = explode('!', $res['eid'], 2)[0];
        }
        if ($extra){
            $res_ex = DBUt::get_row_data('id', $res['id'], 'users_extra', $this->dbif->conn,
                $this->dbif->columns('users_extra'));
            if ($res_ex){
                $res_ex['eflags'] = $res_ex['flags'];
                unset($res_ex['flags']);
                $res = array_merge($res, $res_ex);
            }
        }
    }
    return $res;
}
/*
function is_admin($mix){
    if ($mix === 0) return False; //$mix => get current user id;
    if ($mix contains '@') return False; //$mix => get user id for email;
    $mix is considered to be user id;
    return False;
}
*/

private static $_inst = null;
final static function getInstance(){
    if (!self::$_inst) self::$_inst = new AuthDBIfWrap();
    return self::$_inst;
}

}



?>