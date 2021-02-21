<?php

require_once __DIR__."/../utils/utils.php";
require_once __DIR__."/../config.php";
require_once __DIR__."/../db/db_if.php";
require_once __DIR__."/../auth/auth.php";

class RevAndComs{

const TN = 'reviews_and_comments';

function __construct(){
    $this->dbif = DBIf::getInstance();
}

function save_review($dict){
    $user = auth\get_user_info_or_die();
    $dict['usrId'] = $user['usr_info']['aid'];
    dbgmsg($dict);
    $dict['gKind'] = 1;
    $dict['edTs'] = time();
    $r = $this->dbif->save_valid_data(self::TN, null, $dict);
    dbgmsg($r);
}

function save_comment($dict){
    $user = auth\get_user_info_or_die();
    $dict['usrId'] = $user['usr_info']['aid'];
    $dict['gKind'] = 0x8001;
    $dict['edTs'] = time();
    $r = $this->dbif->save_valid_data(self::TN, null, $dict);
}

function load_reviews($good_id){
    $table = self::TN;
    //DBUt::get_rows('gId', $good_id, TN, $this->dbif->conn, $cols, $limit = 0, $order = '')
    $good_id = intval($good_id);
    //$q = "SELECT * FROM $table WHERE gId=$good_id";
    $q = "SELECT * FROM $table WHERE (gKind=1 AND gId=$good_id) OR (gKind=0x8001 AND gId IN (SELECT id FROM $table WHERE (gKind=1 AND gId=$good_id)))";
    $result = $this->dbif->conn->query($q);
    if (!($result)){
        dbgmsg("Selection from $table failed: ".$this->dbif->conn->error);
        dbgmsg("Q: $q");
        dbgmsg_bt();
        return false;
    }
    $revs = $result->fetch_all(MYSQLI_ASSOC);
    //dbgmsg("...");
    dbgmsg($revs);
    $usrs = 0;
    $usrsIds = implode(',', array_unique(array_map(function ($e){ return $e['usrId'];}, $revs)));
    $q = "SELECT id, name FROM users WHERE id in ($usrsIds)";
    $result = $this->dbif->conn->query($q);
    if (!($result)){
        dbgmsg("Selection from $table failed: ".$this->dbif->conn->error);
        dbgmsg("Q: $q");
        dbgmsg_bt();
        return false;
    }
    $usrs = $result->fetch_all(MYSQLI_ASSOC);
    return [$revs, $usrs];
}

private static $_inst = null;
final static function getInstance(){
    if (!self::$_inst) self::$_inst = new RevAndComs();
    return self::$_inst;
}

}

function reviews__main(){
    global $__JSON;
    dbgmsg($__JSON);
    $l = 0;
    if(array_key_exists('reviews_op', $__JSON)){
        if ($__JSON['reviews_op'] === 'save_rev'){
            $l = RevAndComs::getInstance()->save_review($__JSON);
        }
        if ($__JSON['reviews_op'] === 'save_com'){
            $l = RevAndComs::getInstance()->save_comment($__JSON);
        }
        else if($__JSON['reviews_op'] === 'load_revs'){
            $l = RevAndComs::getInstance()->load_reviews($__JSON['gId']);
        }
    }
    if ($l){
        ob_end_clean();
        //$l['sts'] = time();
        //dbgmsg($l);
        echo(json_encode($l));
        return;
    }else{
        $str = json_encode(['page' => 'reviews.php', 'error' => 'invalid request']);
        echo($str);
        die();
    }
}


if (!count(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1))){
    //error_log(__FILE__." IS MAIN");
    reviews__main();
}

?>