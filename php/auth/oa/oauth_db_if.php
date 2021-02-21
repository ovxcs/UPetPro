<?php namespace auth\oa;

require_once (__DIR__.'/../../db/db_if.php');
use DBUt;

class OAuthDBIfWrap{

private $dbif;
function __construct(){
    $this->dbif = \DBIf::getInstance();
}

function save_user_info($mwt, $dict){
    if (!array_key_exists('mwt', $dict)){
        $dict['mwt'] = $mwt;
    }
    if (isset($dict['prov']) && isset($dict['eid'])){
        $data = DBUt::get_row_data(
            array('prov', 'eid'),
            array($dict['prov'], $dict['eid']),
            'oauth',
            $this->dbif->conn,
            $this->dbif->columns('oauth')
        );
        //dbgmsg("============================================");
        //dbgmsg(print_r($data, true));
        //dbgmsg(print_r($dict, true));
        //dbgmsg("============================================");
        if ($data){
            $dict['id'] = $data['id'];
            return DBUt::save_data($this->dbif->conn, 'oauth', 'id', $dict);
        }
    }
    return DBUt::save_data($this->dbif->conn, 'oauth', 'mwt', $dict);
}

function get_user_info($mwt){
    $cols = array_fill(0, 8, 0); //!!!HARDCODED!!!//
    //dbgmsg("MWT: $mwt");
    $stmnt = DBUt::prepare_and_exec($this->dbif->conn, "SELECT * FROM oauth WHERE mwt = ? ",
        array('s', $mwt), $cols);
    if (!$stmnt){
        //dbgmsg("sel from oauth failed: ".($this->dbif->conn->error));
        return false;
    }
    $fetch_res = $stmnt->fetch();
    if (!$fetch_res){
        //dbgmsg("nothing found");
        return false;
    }
    $stmnt->close();
    return array(
        'id' => $cols[0],
        /*'mwt' => $cols[1], DO NOT RETURN THIS - DBG ONLY*/
        'prov' => $cols[2],
        'name' => $cols[3],
        'eid' => $cols[4],
        'email' => $cols[5],
        'tel' => $cols[6],
        'pict' => $cols[7]
    );
}

function save_oauth_exchange_token($mwt, $provider, $key, $value){
    dbgmsg("saving xchg tkn: $key=$value for $mwt, $provider");
    $ar = array($key => $value, 'mwt' => $mwt, 'prov' => $provider);
    $r = DBUt::save_data($this->dbif->conn, 'oa_exchanges', 'mwt', $ar);
    //dbgmsg("saving xchg tkn result: ".print_r($r, true));
    return $r;
}

function get_oauth_exchange_token($mwt, $provider, $key)
{
    $cols = array_fill(0, 2, 0);
    $stmnt = DBUt::prepare_and_exec(
        $this->dbif->conn, "SELECT $key, prov FROM oa_exchanges WHERE mwt = ? ",
        array('s', $mwt), $cols);
    if (!$stmnt){
        dbgmsg("sel from oa_exchanges failed: ".$this->dbif->conn->error);
        return false;
    }
    $fetch_res = $stmnt->fetch();
    $stmnt->close();
    if ($cols[1] === $provider){
        return $cols[0];
    }
    dbgmsg("sel from oa_exchanges failed: PROVIDER DOES NOT MATCH!");
    return false;
}

function save_access_token($json, $mwt){
    dbgmsg("SAVING ACCESS TOKEN for ".$mwt);
    $ar = array(
        'mwt' => $mwt,
        'token_expires_at' => time() + intval($json['expires_in']),
        'access_token' => $json['access_token'],
        'prov' => $json['prov'],
    );
    //dbgmsg("saving access token: size: ".strlen($json['access_token']));
    $r = DBUt::save_data($this->dbif->conn, 'oa_exchanges', 'mwt', $ar);
    //error_log($r);
    return $r;
}

function get_access_token($mwt, $provider_name){
    $cols = array_fill(0, 3, -7);
    $stmnt = DBUt::prepare_and_exec($this->dbif->conn,
        "SELECT access_token, token_expires_at, prov FROM oa_exchanges WHERE mwt = ? ",
        array('s', $mwt),
        $cols);
    if (!$stmnt){
        dbgmsg("access_token sel from oa_exchanges failed: ".$this->dbif->conn->error);
        return false;
    }
    $fetch_res = $stmnt->fetch();
    dbgmsg(print_r($cols, true));
    dbgmsg($fetch_res);
    $stmnt->close();
    if ($provider_name !== 0 && $provider_name !== $cols[2]){
        dbgmsg("provider mismatch for this mwt: $provider_name != {$cols[2]}");
        return false;
    }
    if (time() > ($cols[1] - 3600)){
        dbgmsg("ACCESS TOKEN considered EXPIRED for ".$mwt. " > tsNow:"
                .time().", tsStored:".$cols[1].", diff:".(time()-$cols[1]));
        return false;
    }
    return $cols[0];
}

function delete_code($code){
    $stmnt = $this->dbif->conn->prepare("DELETE FROM oa_exchanges WHERE code=?");
    $stmnt->bind_param('s', $code);
    $stmnt->execute();
}

private static $_inst = null;
final static function getInstance(){
    if (!self::$_inst) self::$_inst = new OAuthDBIfWrap();
    return self::$_inst;
}


}
?>
