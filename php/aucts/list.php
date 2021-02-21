<?php

require_once(__DIR__.'/products.php');

define( 'REQLST_DEFAULT_PARAMS', array(
    'ordby' => ['cst', 'my_cents'],
    'ordir' => ['DESC', 'ASC'],
    'limit' => [20]
));

function list__sql_from_params($table, $params){
    foreach(REQLST_DEFAULT_PARAMS as $k => $v){
        if (!isset($params[$k]) || !in_array($params[$k], $v, true)){
            $params[$k] = $v[0];
        }
    }
    $part2 = "ORDER BY ".$params['ordby']." ".$params['ordir']. " LIMIT ".$params['limit'];
    $columns = DBIf::getInstance()->columns($table);
    $where = "";
    foreach($params as $k => $v){
        if(!$v) continue;
        if ($k === 'limit' || $k==='ordby' || $k==='ordir') continue;
        if (!in_array($k, $columns)){
            dbgmsg("!!!!! $k !!!!! KEY NOT FOUND IN COLUMNS");
            die ("Invalid request (1): ".print_r($params, true));
            //return FALSE;
        }
        if (is_string($v)) {
            if (strlen($v) > 100){
                die("Invalid request (2): ".print_r($params, true));
            }
            if ($v[0] === '['){
                $v = json_decode($v);
                foreach($v as $s){
                    if (strlen($s) > 25){
                        die ("Invalid request (3): ".print_r($params, true));
                    }
                }
            }
        }
        if (is_array($v) && $v){
            if (is_string($v[0])){
                $where .= " $k in ('".join("','", $v)."') AND";
            }else{
                if(count($v) === 2){
                    $where .= " $k BETWEEN $v[0] AND $v[1] AND";
                }
            }
        }else{
            $where .= " $k='".$v."' AND";
        }
    }
    if ($where) $where= "WHERE ".substr($where, 0, -3); //remove the last and after for;
    $stmnt = "SELECT * FROM $table ".$where." ".$part2;
    error_log("\n>>".$stmnt);
    return $stmnt;
}

/*
function products_list($dbif, $params){
    $dbif = DBIf::getInstance();
    if($op === 'all_offers'){
        dbgmsg("requesting ALL offers");
        $stat = 0;
        $l = offers_list($dbif, $stat, $limit = 20);
        //dbgmsg(print_r($l, true));
    }
}
*/

if (!count(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1))){
    error_log(__FILE__." IS MAIN");
    sql_from_params("goods", array(
                "sn" => '["test","varza"]',
                "flags" => '[0, 123456]'
        ));
}
?>