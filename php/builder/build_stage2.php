<?php

/*
create HTMLs and 
add .js, .css, res., fonts to build
*/

require_once(__DIR__."/build.php");

class Processador{

public function __construct($target){
    $this->target = $target;
    $this->target_path = realpath(__DIR__."/../../builds/$target");
    $this->srcs_root = realpath(__DIR__."/../../wr");
    $this->scripts = array();
    $this->fonts = array();
}
function grab_scripts($obj, $crt_build_path){
    $this->crt_build_path = $crt_build_path;
    $r = $obj->root();
    foreach(['scripts', 'links'] as $group){
        foreach($obj->$group as $k=>$v){
            if (!array_key_exists($k, $this->scripts)){
                $this->scripts[$k] = realpath("$r/$k");
            }
        }
    }
    foreach($obj->dictionaries as $k=>$v){
        $p = realpath("$r/$k");
        foreach(scandir($p) as $f){
            if (substr($f, -5) === '.json'){
                $f = "$k/$f";
                if (!array_key_exists($f, $this->scripts)){
                    $this->scripts[$f] = realpath("$r/$f");
                }
            }
        }
    }
}
function export(){
    $newRoot = $this->crt_build_path;
    foreach(['3fe', 'cdx', 'res', 'fonts'] as $d){
        $dp = "$newRoot/$d";
        rc_mkdir($dp);
    }
    foreach($this->scripts as $rp=>$ap){
        if (substr($rp, 0, 4) === "/3fe"){
            $lib = substr($rp, 0, strpos($rp, '/', 5));
            $dest = "$newRoot/$lib";
            $src = realpath($this->srcs_root.$lib);
            if (!is_link($dest))
                symlink($src, $dest);
        }
    }
    foreach(['res', 'fonts'] as $d){
        foreach ( scandir(realpath($this->srcs_root."/$d")) as $f){
            if ($f === '.' || $f === '..') continue;
            $x = "$newRoot/$d/$f";
            $src = realpath($this->srcs_root."/$d/$f");
            if (!is_link($x))
                symlink($src, $x);
        }
    }
    foreach($this->scripts as $rp=>$ap){
        if (!$ap){
            print_r("\nWARNING: $rp is MISSING!");
            continue;
        }
        if (substr($rp, 0, 4) === '/cdx'){
            $dest = "$newRoot/$rp";
            rc_mkdir(dirname($dest));
            copy($ap, $dest);
        }
    }
}

}//end class

function build2($target){
    $files = array();
    $proc = new Processador($target);
    $root = build($target, [$proc, 'grab_scripts']);
    $proc->export();
    print("\n");
    $vwr = $proc->target_path."/wr";
    if (is_link($vwr)) rmdir($vwr);
    symlink($proc->crt_build_path, $vwr);
    /*
    foreach(scandir($proc->crt_build_path) as $dn){
        if ($dn[0] === '.') continue;
        symlink($proc->crt_build_path."/$dn", "$vwr/$dn");
    }
    */
    return $root;
}

if (!count(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)))
{
    $build_root = build2($argv[1]);
    error_log("\nbuild root: ".$build_root);
}


?>