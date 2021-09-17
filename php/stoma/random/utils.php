<?php

require_once __DIR__."/../../utils/utils.php";
require_once __DIR__."/../../db/db_if.php";


function gen_rand_ts($mD, $MD){
    return (time() + rand(60 * 60 * 24 * $mD, 60 * 60 * 24 * $MD));
}

function genRndStr($length = 10, $characters = '0123456789') {
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function getRandFrom($array){
    return $array[rand(0, sizeof($array) - 1)];
}

function gen_rand_tel(){
    return "07".genRndStr(2,'689').genRndStr(7);
}

function flush_buffers(){
    ob_end_flush();
    ob_flush();
    flush();
    ob_start();
}

abstract class RandObjs{

    function __construct(){
        $this->vect = array();
    }

    abstract protected static function create_entry(string $string);
    abstract protected static function modify_entry(array &$entry);

    function generate_from_file($infile, $alike = 10){
        $handle = fopen($infile, "r");
        if ($handle){
            while(($line = fgets($handle)) !== false) {
                //process the line read.
                array_push($this->vect, static::create_entry(trim($line)));
            }
            fclose($handle);
        }else{
            die("Error opening $infile");
            //dbgmsg("error opening the file");
            //error opening the file.
        }
        $sz = sizeof($this->vect);
        for($i=0; $i < $alike * $sz; $i++){
            $m = $this->vect[rand(0, $sz-1)];
            static::modify_entry($m);
            array_push($this->vect, $m);
        }
        dbgmsg(sizeof($this->vect));
    }

    function write_to_db(){
        $dbif = \DBIf::getInstance();
        $table = static::target;
        dbgmsg($table);
        foreach($this->vect as $m){
            $dbif->save_valid_data($table, null, $m);
            //echo(json_encode($m));
        }
    }
}


?>