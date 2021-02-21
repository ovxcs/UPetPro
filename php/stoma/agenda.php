<?php namespace stoma;


/*
exchange jsons:
scope = agenda, clients, visits ...
op = insert, load, update ...
mwt = mwt (in cookie)
params = dict //requested parms
data = dict //resolved data
info = "..."
warns = []
error = "..."
ackPrms = dict
*/


require_once __DIR__.'/../config.php';
require_once __DIR__.'/../utils/utils.php';
require_once __DIR__.'/../db/db_if.php';


const Q_INTERVAL = '/[><=][\-0-9]+/';
const A_DAY = 60*60*24;
const Q_PAGE = '/[0-9]+?x[0-9]+/';
const ARP = 'ackReqPrms'; // acknowledgedRequestedParameters
const LOW_LIMIT_REACHED = 'low_limit_reached';
const HIGH_LIMIT_REACHED = 'high_limit_reached';


function query_to_dict(&$dict){
    $q = $dict['query'];
    $matches = [];
    $result = preg_match(Q_INTERVAL, $q, $matches);
    if ($result){
        //dbgmsg($matches);
        $cmp = $matches[0][0];
        $day = intval(substr($matches[0], 1));
        //dbgmsg($day);
        $today_ts = intdiv(time(), A_DAY);
        $ref = ($day + $today_ts) * A_DAY;
        $date = [$cmp, $ref];
        $dict['date'] = $date;
        //dbgmsg($date);
    }
    $result = preg_match(Q_PAGE, $q, $matches);
    if ($result){
        $ops = explode('x', $matches[0]);
        $bounds = [$ops[0], $ops[1]];
        //dbgmsg($page);
        $dict['limit'] = $bounds;
    }
    $dict['name'] = $q;
}


function select_agenda(&$dict){ //select agenda
    //DBUt::get_rows($key, $val, $table, $conn, $cols, $limit = 0, $order = '')
    dbgmsg($dict);
    $dbif = \DBIf::getInstance();
    $conn = $dbif->conn;
    $table = "agenda";
    $dict[ARP] = [];
    if(isset($dict['query']) && $dict['query']){
        query_to_dict($dict);
    }

    $bounds = [0, 0];
    if (isset($dict['limit'])){
        $numbers = is_string($dict['limit']) ? explode(',', trim($dict['limit'],"[]\"\'"), 3) : $dict['limit'];
        if (count($numbers) === 3){
            $start = intval($numbers[0]);
            $crtLen = intval($numbers[1]);
            $perPage = intval($numbers[2]);
            if (isset($dict['dir'])){
                $dir = $dict['dir'];
                if ($dir > 0){
                    $bounds[0] = $start + $crtLen;
                }else if($dir < 0){
                    $bounds[0] = $start - $perPage;
                }else{// no direction read exactcly what is required
                    $bounds[0] = $start;
                }
                if ($bounds[0] < 0) $bounds[0] = 0;
            }
            $bounds[1] = $perPage; //always
            $dict[ARP]['limit'] = $bounds;
        }
    }
    
    if (!$bounds[1]){
        $dict['error'] = 'malformed_query';
        dbgmsg("ERROR: malformed_query");
        return false;
    }

    $cmp = "";
    $date_query = "";
    if (isset($dict['date'])){
        $dt = $dict['date'];
        if (is_string($dt)){
            if(strlen($dt) > 0){
                if ($dt[0] === '[')
                    $dt = json_decode($dt);
                else
                    $dt = explode(',', $dict['date'], 2);
            }else{
                $dt = [];
            }
        }

        if (count($dt) === 2){
            if (is_string($dt[0])){
                $cmp = $dt[0][0];
                if ($cmp !== '<' && $cmp !== '>') $cmp = '=';
            }else{
                $x = intval($dt[0][0]);
                $cmp =  $x == 0 ? "=" : ($x == -1 ? "<" : ">");
            }
            $val = intval($dt[1]);
            $date_query = "sched_ts $cmp $val";
            $dict[ARP]['date'] = [$cmp, $val];
        }
    }

    //name(s) param is considered to be the rest of incoming query if param name is missing!!!	
    $name_query = ""; $namesAllMatchOrder = "";
    $names = (isset($dict['name']) && $dict['name']) ? $dict['name'] : (isset($dict['query']) ? $dict['query'] : null);
    if ($names){
        $valid = false;
        if (is_string($names)){
            if (strlen($names) > 50){
                $names = substr($dict['name'], 0, 50);
            }
            if ($names[0] == '['){
                $names = json_decode($names);
            }else{
                $alphas = [];
                $count = preg_match_all("/[a-zA-Z\.]{2,20}/", $names, $alphas);
                if($count){
                    $match_or = implode('|', $alphas[0]);
                    $match_and = implode('.+?', $alphas[0]);
                    $valid = true;
                }
            }
        }
        if(is_array($names)){ //DON't add 'else' here because $names may become array from string 
            $match_or = $names[0];
            $match_and = $names[1];
            $valid = true;
        }
        if ($valid){
            $name_query = "fullname REGEXP '$match_or'";
            $namesAllMatchOrder = "(CASE WHEN fullname REGEXP '$match_and' THEN 0 ELSE 1 END)";
            $dict[ARP]['name'] = [$match_or, $match_and];
        }
    }

    //dbgmsg($dict);
    $where = "";
    if ($date_query) $where .= " $date_query AND";
    if ($name_query) $where .= " $name_query AND";
    if ($where) $where = "WHERE ".substr($where, 0, -3); //remove the last AND

    $order = "";
    if ($namesAllMatchOrder) $order = $namesAllMatchOrder;
    if ($cmp === '>' ) $order .= ($order ?  ', ' : '').'sched_ts';
    if ($order) $order = "ORDER BY $order"; 
    //

    $x = "SELECT * FROM $table $where $order LIMIT {$bounds[0]},{$bounds[1]}";
    dbgmsg($x);
    $result = $conn->query($x);
    if (!($result)){
        dbgmsg("Selection from $table failed: ".$conn->error);
        dbgmsg("Q: $x");
        dbgmsg_bt();
        return false;
    }

    $ret = $result->fetch_all(MYSQLI_ASSOC);
    $dict['data'] = ['agenda' => $ret];
    if ($bounds[0] == 0){
        array_push($dict['warns'], LOW_LIMIT_REACHED);
    }
    if (count($ret) < $bounds[1]){
        array_push($dict['warns'], HIGH_LIMIT_REACHED);
    }
    return true;

}

?>