<?php

class MyPHPTree{

public function __construct($list){
    foreach ($list as $p){
        require_once($p);
    }
    $this->php_srcs = realpath((dirname(__DIR__)));
    $this->pro_dir = dirname($this->php_srcs); //pro dir
    $this->wr_dir = realpath($this->pro_dir.'/wr'); //webroot
    $this->sr_dir = dirname($this->pro_dir); //superroot
    error_log("Pro dir:    ".$this->pro_dir.",\n"
             ."PHP srcs:   ".$this->php_srcs.",\n"
             ."Web root:   ".$this->wr_dir.",\n"
             ."Super root: ".$this->sr_dir."\n");
             
    $this->cdx = realpath($this->wr_dir.'/cdx');
    $this->zero_dir = realpath($this->sr_dir.'/0'); //zero dir
    $this->trd_parts = realpath($this->sr_dir.'/3'); //3rd-party dir
    error_log("cdx dir:       ".$this->cdx.",\n"
             ."zero dir:      ".$this->zero_dir.",\n"
             ."3rd-party dir: ".$this->trd_parts."\n");
}

public function export($destination){

    $includes = get_included_files();
    foreach($includes as $fn){
        error_log("# ".$fn);
    }
}

}

$LST = [
    "h:\\new\\M\\ED\\pro\\php\\auth\\auth.php",
    "h:\\new\\M\\ED\\pro\\php\\auth\\mwtoken.php"
];

$pt = new MyPHPTree($LST);
$pt->export(null);

?>