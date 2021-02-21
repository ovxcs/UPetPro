<?php

error_log("\n\n================ ".__FILE__." ================");

const __TABLES__ = array();

function print_a($a){
    error_log(str_replace(['&','='], [', ',':'], http_build_query($a)));
}

function analyze_table($cs){
    $matches;
    $r = preg_match('/(?<=CREATE[\s\t]TABLE[\s\t])[\s\ta-zA-Z0-9_]+/', $cs, $matches);
    if ($r !== 1) return;
    $name = trim($matches[0]);
    $r = preg_match_all('/(?<=[(,])[\n\r\t\s]+[a-zA-Z0-9_+]+[\t\s]/', $cs, $matches);
    array_walk($matches[0], function(&$v){
        $v = trim($v);
    });
    $columns = array_values(array_filter($matches[0], function($v){
        if ($v === 'CONSTRAINT') return false;
        return true;
    }));

    $inst = DBIf::getInstance();
    
    if ($columns != $inst->columns($name)){
        error_log("$name columns mismatch");
        print_a($columns);
        print_a($inst->columns($name));
    }
}


function analyze(){
    $inst = DBIf::getInstance();
    foreach($inst->creation_statements as $cs){
        if ($cs[1] === "already exists"){
            //error_log("...");
            analyze_table($cs[0]);
        }
    }
}

function __main(){
    error_log("\nadmin: create tables - IF YOU WANT TO REMOVE OR CHANGE THEM, ANOTHER APP MUST BE USED (e.g. phpMyAdmin)\n");
    require_once __DIR__.'/../db/db_if.php';
    $inst = DBIf::getInstance();
    DBIf::getInstance()->create_tables(
        'utils/big_strings.sql',
        'auth/db.sql',
        'auth/oa/db.sql',
        'aucts/db.sql'
    );
    error_log("creation_statements: ".count($inst->creation_statements));
    analyze();
}




__main();

?>
