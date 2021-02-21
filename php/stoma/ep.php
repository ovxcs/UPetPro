<?php namespace stoma;

require_once __DIR__.'/../config.php';
require_once __DIR__.'/../utils/utils.php';
require_once __DIR__.'/../db/db_if.php';
require_once __DIR__.'/../mailer/mailer.php';

require_once "accounts.php";
require_once "stock.php";
require_once "agenda.php";

//$dict['in']
//$dict['out']

class StomaObject{

    public function __construct(&$data){
        $this->data = &$data;
        if(isset($data['id'])){
            if ($data['id']){
                $this->id = $data['id'];
            }else{ //remove if zero
                $this->id = null;
                unset($this->data['id']);
            }
        }else{
            $this->id = null;
        }
    }

    function prepare_for_DB(){}
    function prepare_for_JSON(){}
    function after_write(...$args){}
    function after_read(...$args){}
}


class Client extends StomaObject{

    static function __age_to_birdate(&$data){
        if (!isset($data['age'])) return;
        //dbgmsg($data['age']);
        $y = intval($data['age'][0]);
        $m = intval($data['age'][1]);
        if ($y === 0 && $m === 0){
            dbgmsg("invalid age");
        }else{
            $data['birthd'] = (new \DateTime())->sub(new \DateInterval(
                "P{$y}Y{$m}M"))->format('Y-m-d');
            unset($data['age']);
        }
    }

    static function __birdate_to_age(&$data){
        if (isset($data['birthd']) and $data['birthd']){
            $interval = (new \DateTime())->diff((new \DateTime($data['birthd'])));
            $data['age'] = [$interval->y, $interval->m];
            unset($data['birthd']);
        }else{
            //$data['age'] = 0;
        }
    }

    static function __fullname_join(&$data){
        if (!isset($data['fullname'])) return;
        $data['fullname'] = $data['fullname'][0].','.$data['fullname'][1];
        //unset($data['name']);
    }

    static function __fullname_split(&$data){
        //dbgmsg(gettype($data['fullname']));
        $data['fullname'] = explode(',', $data['fullname']);
        //unset($data['fullname']);
    }

    static function __contacts(&$data){
        if (isset($data['phone']) && !$data['phone']) unset($data['phone']);
        if (isset($data['email']) && !$data['email']) unset($data['email']);
    }

    function prepare_for_DB(){
        self::__contacts($this->data);
        self::__age_to_birdate($this->data);
        self::__fullname_join($this->data);
    }

    function prepare_for_JSON(){
        //dbgmsg($this->data);
        self::__fullname_split($this->data);
        self::__birdate_to_age($this->data);
    }

    function after_write(...$args){ //create a fake visit
        $dbif = $args[0];
        $dbif->save_valid_data('visits', null, [
            'kind' => VisitKind::FAKE,
            'cl_id' => $this->data['id'],
            'md_id' => 87
        ]);
    }

    function after_read(...$args){
        $dbif = $args[0];
        $cl_id = $args[1];
        //$array = \DBUt::get_rows($key, $val, $table, $conn, $cols, $limit = 0, $order = '')
        $array = \DBUt::get_rows('cl_id', $cl_id, 'visits',
            $dbif->conn, $dbif->columns('visits'), $limit = 0, $order = 'ORDER BY sched_ts DESC');
        $this->data['visits'] = $array;
    }
}
//-------------------------------------

abstract class VisitKind{
    const SCHED = 0x1;
    const DONE = 0x2;
    const CANCELED = 0x3;
    const POSTPONED = 0x4;
    const ONGOING = 0x5;
    const FAKE = 0x7;
}

class Visit extends StomaObject{

    function after_write(...$args){
        $dbif = $args[0];
        $clid = $this->data['cl_id'];
        $result = $dbif->conn->query("SELECT count(id) FROM visits WHERE cl_id=$clid");
        $c = $result->fetch_row();
        if ($c && $c[0] > 1){
            $r = $dbif->conn->query("DELETE FROM visits WHERE cl_id=$clid AND kind=7");
        }
    }

    static function __sched_ts(&$data){
        if (!isset($data['day_tsms'])) return;
        $t = $data['hhmm'];
        $h = intval(substr($t, 0, 2));
        $matches = [];
        $m = 0;
        preg_match('/[0-9]{2}/', substr($t, 2), $matches);
        if ($matches){
            $m = intval($matches[0]);
        }
        $tz_diff = $data['dlsavings']; #$data['tzone'] + $data['dlsavings'];
        $ts = intdiv($data['day_tsms'] + $tz_diff, A_DAY * 1000) * A_DAY;
        //dbgmsg("SAVING: $ts, h:$h, m:$m");
        $ts += ($h * 3600 + $m * 60);//UTC
        $ts -= intdiv($tz_diff, 1000);
        $data['sched_ts'] = $ts;
        //dbgmsg("SAVING TS: $ts");
        unset($data['hhmm']);
        unset($data['day_tsms']);
    }

    function prepare_for_DB(){
        self::__sched_ts($this->data);
    }
}

//----------------------------------------------------------------------------------
const Scopes = [
    'clients' => [
        'class' => 'Client'
    ],
    
    'visits' => [
        'class' => 'Visit'
    ]
];
//----------------------------------------------------------------------------------


class Gallery extends StomaObject{

function process(){
    if ($this->data['op'] === 'load') $this->load();
}

function load(){
    $dbif = \DBIf::getInstance();
    $clid = $this->data['cl_id'];
    $this->data['data'] = $dbif->conn->query(
        "SELECT * FROM radiographs WHERE cl_id=$clid ORDER BY ts DESC")->fetch_all(MYSQLI_ASSOC);
    dbgmsg($this->data);
}

function fake_load(){
    $IMGS = "http://10.73.37.221:8085/res/test_stoma/wp";
    $data = array(
        [
            'path' => "$IMGS/wp0015.jfif",
            'info' => "Descrierie descriere ",
            'ts' => 19895765433,
            'id' => 145
        ],
        [
            'path' => "$IMGS/wp0003.jfif",
            'info' => "Descrierie descriere ",
            'ts' => 198765433,
            'id' => 19
        ],
        [
            'path' => "$IMGS/wp0006.jfif",
            'info' => "Descrierie descriere ",
            'ts' => 198765433,
            'id' => 188
        ],
        [
            'path' => "$IMGS/wp0012.jfif",
            'info' => "Descrierie descriere ",
            'ts' => 19855765433,
            'id' => 10
        ],
        [
            'path' => "$IMGS/wp0010.jfif",
            'info' => "Descrierie descriere ",
            'ts' => 19895765433,
            'id' => 177
        ],
    );
    $this->data['data'] = $data;
}


}


//----------------------------------------------------------------------------------
function normalize_errors($err, $ret_original_on_fail = false){
    $tag = "";
    if (is_array($err)){ $tag = $err[0]; $err= $err[1]; }
    if (strpos($err, 'Duplicate') !== false){ return 'duplicate_entry'; }
    return $ret_original_on_fail ? $err : 0;
}

function process(&$dict, $scope){
    $dbif = \DBIf::getInstance();
    $table = $scope;
    $CLS = '\\stoma\\'.Scopes[$scope]['class'];
    if ($dict['op'] === 'save'){
        dbgmsg("Saving into $table ......");
        $data = is_string($dict['data']) ? json_decode($dict['data'], true) : $dict['data'];
        $obj = new $CLS($data);
        if ($obj->id === null){ //NEW object insert
            //dbgmsg($data);
            $obj->prepare_for_DB();
            //dbgmsg("DATA for INS:");
            //dbgmsg($data);
            $id = $dbif->save_valid_data($table, null, $data);
            if (!$id){
                dbgmsg(\DBUt::last_error());
                $err = normalize_errors(\DBUt::last_error());
                unset($data);
                $dict['error'] = $err ? $err : \DBUt::last_error()[1];
                return false;
            }else{
                //dbgmsg("Saving into $table result: {$id[1]}");
                $data['id'] = $id[1];
                $obj->after_write($dbif);
                $obj->prepare_for_JSON();
                $dict['data'][0] = $data;
            }
        }else{  //id present -> update
            $obj->prepare_for_DB();
            $r = $dbif->save_valid_data($table, 'id', $data);
            //dbgmsg($r);
            $obj->prepare_for_JSON();
            $dict['data'][0] = $data;
            $resp_obj = new $CLS($dict['data']);
            $resp_obj->after_read($dbif, $obj->id);
            //dbgmsg($data);
        }
        return true;
    }
    if ($dict['op'] === 'load'){ //by ID;
        dbgmsg("Loading from $table .......");
        if (!isset($dict['data']) || $dict['data'] === 0){
            dbgmsg("unset id or is zero");
            $dict['error'] = "invalid_id";
            return false;
        }else{
            //$data = is_string($dict['data']) ? json_decode($dict['data'], true) : $dict['data'];
            $id = $dict['data']['id'];
            $obj = new $CLS($dict['data']);
            //dbgmsg($dbif->columns($table));
            //$map = \DBUt::get_row_data($column, $val, $table, $conn, $aliases);
            $map = \DBUt::get_row_data('id', $id, $table, $dbif->conn, $dbif->columns($table));
            if ($map === false){
                dbgmsg(\DBUt::last_error());
                $err = normalize_errors(\DBUt::last_error());
                unset($data);
                $dict['error'] = $err ? $err : "Loading data for id:$id failed";
                return false;
            }else{
                $resp_obj = new $CLS($map);
                $resp_obj->prepare_for_JSON();
                $dict['data'][0] = $map;//zero - primary information
                $obj->after_read($dbif, $id);
                dbgmsg($map);
            }
        }
        return true;
    }
}


//**************************************************************************************************


//**************************************************************************************************

function ep_stoma($arg){
    global $__JSON;
    $in = $__JSON ? $__JSON : ($arg ? $arg : $_GET);
    dbgmsg($in);
    $scope = $in['trg']; //scope
    $out = $in; $out['warns'] = [];
    if ($scope === 'login'){
        Account::login($out);
    }else if($scope === 'logout'){
        Account::logout($out);
    }else{
        $d = Account::get_user_or_die();
        dbgmsg($d);

        if(isset(Scopes[$scope])){
            process($out, $scope);
        }
        else if($scope === 'agenda'){
            dbgmsg("go for agenda");
            select_agenda($out);
        }
        else if($scope === 'stock'){
            Stock::select($out);
        }
        else if($scope === 'gallery'){
            (new Gallery($out))->process();
        }
        else{
            dbgmsg("Unknown arguments");
        }
    }
    $out_str = json_encode($out, JSON_NUMERIC_CHECK); 
    //dbgmsg($out_str);
    echo($out_str);
}

if (!count(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS))
            or constant(___MAIN_SCRIPT___) == __FILE__)
{
    //$dbif = \DBIf::getInstance();
    //echo(print_r($dbif->tables(), true));
    dbgmsg("Hello Stoma EP");
    ep_stoma(null);
    //echo("DONE");
}

?>