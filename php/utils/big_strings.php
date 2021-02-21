<?php
require_once __DIR__.'/../db/db_if.php';

class BigString{

static $cache = array();
static $revs_cache = array();

function __construct(){
    $this->conn = DBIf::getInstance()->conn;
}

private static $_inst = null;
static function getInstance(){
    if (self::$_inst === null) self::$_inst = new BigString();
    return self::$_inst;
}

function reduce(string $str, string $common, $may_recall = true){
    if (strpos($str, $common) !== 0)
        throw new InvalidArgumentException("'$str' does not start with '$common'");
    $id = isset(self::$cache[$common]) ? self::$cache[$common] : -1;
    if ($id === -1){
        $res = $this->conn->query("SELECT id FROM big_strings WHERE str='$common'");
        if ($res === false){
            dbgmsg("ERROR: Selection from big_strings failed: ".($this->conn->error));
            return false;
        }
        $row = $res->fetch_row();
        if ($row === NULL){
            $res = $this->conn->query("INSERT INTO big_strings(str) VALUES ('$common')");
            if ($res === false){
                dbgmsg("ERROR: Insertion into big_strings failed: ".($this->conn->error));
                return false;
            }
            if ($may_recall)
                return $this->reduce($str, $common, false);
            dbgmsg("ERROR: BigString::reduce - recursive call not allowed");
            return false;
        }
        $id = $row[0];
        self::$cache[$common] = $id;
    }
    else{
        //dbgmsg("found in common");
    }
    return "#".dechex($id).'.'.substr($str, strlen($common));
}

function restore(string $str){
    //should start with #
    $a = explode('.', substr($str, 1), 2);
    if (count($a) !== 2)
        throw new InvalidArgumentException("'$str' must contain at least one '.'");
    $id = hexdec($a[0]);
    $common = isset(self::$revs_cache[$id]) ? self::$revs_cache[$id] : -1;
    if ($common === -1){
        $res = $this->conn->query("SELECT str FROM big_strings WHERE id=$id");
        if ($res === false){
            dbgmsg("Selection from big_strings failed: ".($this->conn->error));
            return false;
        }
        $row = $res->fetch_row();
        if ($row === NULL){
            dbgmsg("ERROR: String id 0x{$a[0]}($id) not found in big_strings");
            return false;
        }
        $common = $row[0];
        self::$revs_cache[$id] = $common;
    }
    else{
        //dbgmsg("id found in common");
    }
    return $common.$a[1];
}

function reduce_strings(array &$array){
    if (count($array) < 2)
        throw new InvalidArgumentException("An array with at least two elements must be passed");
    $mincl = 0xFFFF;
    for ($j=0; $j < count($array); $j++){
        $cl = 0; $L = min(strlen($array[0]), strlen($array[$j]));
        for(; $cl < $L; ++$cl){
            if ($array[0][$cl] != $array[$j][$cl]) break;
        }
        if ($cl < 8){
            throw new InvalidArgumentException("A pair of strings shares too little in common");
        }
        if ($mincl > $cl) $mincl = $cl;
    }

    $common = substr($array[0], 0, $mincl);
    foreach($array as $ix=>$v){
        $array[$ix] = $this->reduce($array[$ix], $common);
    }
}


}

?>