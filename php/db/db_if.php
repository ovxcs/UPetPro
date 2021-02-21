<?php

require_once __DIR__.'/../config.php';
require_once __DIR__.'/db_utils.php';

abstract class ColAttr{
const Name     = 0;
const Type     = 1;
const Default  = 2;
const Def      = 2; //alias
const Nullable = 3;
const Nlb      = 3; //alias
const Key      = 4;
const Extra    = 5;
}

class DBIf{

public $conn;
private $__columns = []; //static?
private $__tabels = []; //static?
private $db_name; //static?

public $COLS = []; //static?

function __construct($conn){
    $this->conn = $conn;
}

function __destruct(){
    $this->conn->close();
}

public static function withCredentials($server, $user, $pwd, $db){
    //parent::__construct($server, $user, $pwd, $db);
    $cls = get_called_class();
    $inst = new $cls(new mysqli($server, $user, $pwd, $db));
    $inst->db_name = $db;
    return $inst;
}

public static function withDefaults(){
    return static::withCredentials(g_DB_SERVER, g_DB_USER, g_DB_PWD, g_DB_NAME);
}

public function columns($table_name){
    $db_name = $this->db_name;
    if (!array_key_exists($table_name, $this->__columns)){
        $q = "SELECT    column_name,
                        column_type,
                        column_default,
                        is_nullable,
                        column_key,
                        extra
                    FROM information_schema.columns
                    WHERE table_schema = '$db_name'
                    AND table_name = '$table_name'
                    ORDER BY ordinal_position";
        $rs = $this->conn->query($q);
        if (!$rs){
            throw new Exception("Getting columns failed: ".$this->conn->error);
        }
        $this->__columns[$table_name] = [];
        $this->COLS[$table_name] = [];
        while ($row = $rs->fetch_row()){
            //dbgmsg($row);
            array_push($this->__columns[$table_name], $row[0]);
            array_push($this->COLS[$table_name], $row);
        }
    }
    return $this->__columns[$table_name];
}

public function columns_info($table_name){
    $db_name = $this->db_name;
    $q = "SELECT * FROM information_schema.columns
                    WHERE table_schema = '$db_name'
                    AND table_name = '$table_name'
                    ORDER BY ordinal_position";
    $rs = $this->conn->query($q);
    if (!$rs){
        throw new Exception("Getting columns failed: ".$this->conn->error);
    }
    while ($row = $rs->fetch_assoc()){
        dbgmsg($row);
    }
}

public function tables(){
    $db_name = $this->db_name;
    if (!$this->__tabels){
        $q = "SELECT table_name FROM information_schema.tables
                    WHERE table_schema = '$db_name'";
        $rs = $this->conn->query($q);
        if (!$rs){
            throw new Exception("Getting tables failed: ".$this->conn->error);
        }
        $this->__tables = [];
        while ($row = $rs->fetch_row()){
            array_push($this->__tables, $row[0]);
        }
    }
    return $this->__tables;
}

public function add_tables(...$tables){
    foreach($tables as $tb) $this->__tables[$tb[0]] = $tb[1];
}

public function save_valid_data(string $table, ?string $main_key, array $dct)
{
    $this->columns($table);
    $valid = [];
    foreach($this->__columns[$table] as $k){
        if (array_key_exists($k, $dct))
            $valid[$k] = $dct[$k];
    }
    $id = DBUt::save_data($this->conn, $table, $main_key, $valid);
    return $id;
}

public function save_eligible(string $table, ?string $main_key, array $dct){
    //difference from `save_valid_data` - it insertion a full row is constructed with defaults
    if ($main_key !== null){
        return save_valid_data($table, $main_key, $dct);
    }
    $valid = [];
    $this->columns($table);
    foreach($this->COLS[$table] as $col){
        if ($col[ColAttr::Extra] === 'auto_increment')
            continue;
        $name = $col[ColAttr::Name];
        $type = $col[ColAttr::Type];
        $def = $col[ColAttr::Def];
        $nlb = $col[ColAttr::Nlb];
        if (array_key_exists($name, $dct))
            $valid[$name] = $dct[$name];
        else if($nlb === 'NO'){
            //dbgmsg("extra $name of $type");
            if (strpos($type, 'int') !== false)
                $valid[$name] = intval($def);
            else if($type === 'datetime'){
                $valid[$name] = '0000-00-00';
            }
            else
                $valid[$name] = $def;
        }
    }
    $r = DBUt::save_data($this->conn, $table, null, $valid);//insert
    //dbgmsg($id);
    return $r;
}


public $creation_statements = array();
public function create_tables(...$tables_defs_files){
    foreach($tables_defs_files as $rp){
        $p = __DIR__."/../$rp";
        $f = fopen($p, "r"); if (!$f) throw new Exception("Unable to open '$p' file!");
        $c = fread($f, filesize($p));
        fclose($f);
        error_log("Processing $rp ...");
        foreach(explode(';', $c) as $s){
            $ts = trim($s);
            if (!$ts) continue;
            $r = $this->conn->query($ts);
            $x = 'error';
            if (!$r){
                if (strpos($this->conn->error, 'already exists') !== -1)
                    $x = 'already exists';
                error_log('Query failed:'.$this->conn->error);
                //error_log($ts);
            }else{
                $x = 'created';
                error_log(substr($ts, 0, 40).' ...');
            }
            $this->creation_statements[] = [$ts, $x];
        }
    }
}

private static $__instances = [];
public static function getInstance(){
    $_cls = get_called_class();
    if (!isset(static::$__instances[$_cls]))
        $__instances[$_cls] = static::withDefaults();
    return $__instances[$_cls];
}

}



?>