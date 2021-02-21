<?php

require_once "utils.php";

function generate_radio(){
    build_imgs_urs([
        'loc' => "h:/new/M/web/pro/wr/res/test_stoma/wp",
        'rem' => "https://stoma.hexbiz.eu/res/test_stoma/wp"
    ]);
    $dbif = \DBIf::getInstance();
    $r = $dbif->conn->query("SELECT * FROM clients");
    while(true){
        $row = $r->fetch_assoc();
        if (!$row) break;
        //dbgmsg($row);
        generate_radio_for_clid($row['id']);
    }
}

$IMGS_URLS = [];

function build_imgs_urs($path){
    global $IMGS_URLS;
    $rem = $path['rem'];
    foreach(scandir($path['loc']) as $f){
        if ($f == '.' || $f == '..') continue;
        echo($f);
        array_push($IMGS_URLS, "$rem/$f");
    }
}

function generate_radio_for_clid($clid){
    global $IMGS_URLS;
    $dbif = \DBIf::getInstance();
    $N = rand(1, 8);
    $uniques = [];
    for ($i = 0; $i < $N; $i++){
        $pi = -1; $k = 2*$N;
        while($k--){
            $pi = rand(0, sizeof($IMGS_URLS)-1);
            if (in_array($pi, $uniques)){
                $pi = -1;
                continue;
            }
            array_push($uniques, $pi);
            break;
        }
        if ($pi == -1) continue;
        $row = [
            'cl_id' => $clid,
            'ts' => gen_rand_ts(-100, 30),
            'path' => $IMGS_URLS[$pi]
        ];
        //dbgmsg($row);
        echo(print_r($row, 1));
        $dbif->save_valid_data('radiographs', null, $row);
    }
}

if (!count(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)))
    generate_radio();

?>