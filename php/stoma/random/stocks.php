<?php

require_once __DIR__."/utils.php";

class RandStocks extends RandObjs {

    const target = "stock";

    protected static function create_entry($line){
        return [
            'name' => $line,
            'code' => genRndStr(13, 'ABCDEFGHIJKLMNOP01234567'),
            'um' => getRandFrom(['l', 'kg', 'pks', 'st']),
            'qty' => rand(10, 100),
            'adm_um' => getRandFrom(['ml', 'g', 'buc', 'tab']),
            'adm_qty' => rand(3, 20),
            'in_ts' => gen_rand_ts(-600, -3),
            'exp_ts' => gen_rand_ts(-10, 600)
        ];
    }

    protected static function modify_entry(&$e){
        $e['in_ts'] = gen_rand_ts(-600, -3);
        $e['exp_ts'] = gen_rand_ts(-10, 600);
        $e['code'] = genRndStr(13, 'ABCDEFGHIJKLMNOP01234567');
        $e['qty'] = rand(10, 100);
    }
}


function rand_stocks_main(){
    header( 'Content-type: text/html; charset=utf-8' );
    echo("<br> Inserting randoms stocks ...");
    //flush_buffers();
    $rs = new RandStocks();
    $rs->generate_from_file("medicines.txt", 10); //and ten alike each
    $rs->write_to_db();
    echo("done<br>");
}


if (!count(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)))
    rand_stocks_main();

?>