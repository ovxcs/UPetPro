<?php

require_once(__DIR__."/../utils/utils.php");
require_once(__DIR__."/../config.php");
require_once(__DIR__."/../auth/auth.php");

class Uploader{

public $dct;
public $stor_root_path;
public $stor_root_url;

function __construct($srp, $sru, $stor_kind, $ownerId){
    $this->stor_root_path = $srp;
    $this->stor_root_url = $sru;
    $this->stor_kind = $stor_kind;
    $this->ownerId = $ownerId;
    $this->dct = array('files' => array(),'errors' => 0);
}

function __destruct(){}

static function startsWith($haystack, $needle)
{
     $length = strlen($needle);
     return (substr($haystack, 0, $length) === $needle);
}

static function endsWith($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}

static function bigIntToPath($number, $last = 's'){
    $h = dechex($number);
    $hL = strlen($h);
    $r = 4 - (($hL+3) % 4 + 1);
    $h = str_repeat('0', $r).$h;
    $a = str_split($h, 4);
    $a[] = $last;
    return implode('/', $a);
    
}

public function save_files($files_list){
    foreach($files_list as $f){
        $tf = $f['tmp_name'];
        $h = hash_file('md5', $tf);
        $rel_pth = $this->stor_kind.'/'.self::bigIntToPath(
                $this->ownerId);
        $lp = $this->stor_root_path."/$rel_pth";
        if (!file_exists($lp)) mkdir($lp, 0777, true);
        if (!file_exists("$lp/$h")) move_uploaded_file($tf, "$lp/$h");
        else unlink($tf);
        $url = $this->stor_root_url."/$rel_pth/$h";
        array_push($this->dct['files'], $url);
    }
}

}

function uploader_main(){
    global $UPLOAD_DIR, $UPLOAD_URL;
    if (array_key_exists('SOME_CONTENT__ULF', $_POST)){
        $s = auth\get_user_info_or_die();
        try
        {
            $upld = new Uploader($UPLOAD_DIR, $UPLOAD_URL, 'pp', $s['usr_info']['aid']);
            $upld->save_files($_FILES);
            dbgmsg(json_encode($upld->dct));
            echo(json_encode($upld->dct));
            return;
        }
        catch(Exception $exc){
            dbgmsg($exc->getMessage());
            echo(['error'=>'some error occured']);
            return;
        }
    }
}

uploader_main();

?>












