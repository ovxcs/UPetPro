<?php

require_once __DIR__.'/../config.php';
require_once __DIR__.'/../utils/utils.php';
require_once __DIR__.'/../db/db_if.php';

function test($ts, $dir, $offset, $limit){
    $q = "
        SELECT * FROM visits
                WHERE (CASE
                        WHEN $dir=-1 THEN sched_ts<=$ts
                        WHEN $dir=1 THEN sched_ts>=$ts
                        ELSE 1 
                END)
                ORDER BY (CASE WHEN $dir!=1 THEN sched_ts END) DESC,
                         (CASE WHEN $dir=1 THEN sched_ts END) ASC
                LIMIT $offset,$limit
            ";
    //$q = "SELECT * FROM visits";
    $dbif = DBIf::getInstance();
    $r = $dbif->conn->query($q);
    if (!$r){
        dbgmsg($dbif->conn->error);
        dbgmsg("error");
    }else{
        dbgmsg($r);
        dbgmsg("DIR:$dir");
        while($row = $r->fetch_assoc()){
            dbgmsg($row['sched_ts']."    ".$row['id']);
        }
    }
}

dbgmsg("zzzzzz");
test(1612456700, 0, 1, 4);
test(1612456700, 0, 1, 4);

?>