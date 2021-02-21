<?php

require_once "utils.php";

function generate_visits(){
    $dbif = \DBIf::getInstance();
    $r = $dbif->conn->query("SELECT * FROM clients");
    while(true){
        $row = $r->fetch_assoc();
        if (!$row) break;
        //dbgmsg($row);
        generate_visits_for_clid($row['id']);
    }
}

function generate_visits_for_clid($clid){
    $dbif = \DBIf::getInstance();
    $N = rand(1, 8);
    for ($i = 0; $i < $N; $i++){
        $v = [
            'cl_id' => $clid,
            'sched_ts' => gen_rand_ts(-100, 30),
            'md_id' => 87
        ];
        dbgmsg($v);
        $dbif->save_valid_data('visits', null, $v);
    }
}

if (!count(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)))
    generate_visits();

?>