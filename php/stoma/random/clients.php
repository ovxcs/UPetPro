<?php

require_once __DIR__."/utils.php";

class RandClients extends RandObjs {

    const target = "clients";
    static function age_to_birdate($age){ //age - array
        //dbgmsg($data['age']);
        return (new \DateTime())->sub(new \DateInterval(
                "P{$age[0]}Y{$age[1]}M"))->format('Y-m-d');
        
    }
    
    static function rand_birthdate(){
        return self::age_to_birdate([rand(5,78), rand(0,11)]);
    }

    protected static function create_entry($line){
        $ns = explode(" ", $line);
        return [
            'fullname' => "{$ns[1]}, {$ns[0]}",
            'phone' => gen_rand_tel(),
            'birthd' => self::rand_birthdate()
        ];
    }

    protected static function modify_entry(&$e){
        
    }
}


function rand_clients_main(){
    header( 'Content-type: text/html; charset=utf-8' );
    echo("<br> Inserting randoms clients ...");
    //flush_buffers();
    $rs = new RandClients();
    $rs->generate_from_file("names.txt");
    $rs->write_to_db();
    echo("done<br>");
}

if (!count(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)))
    rand_clients_main();

?>