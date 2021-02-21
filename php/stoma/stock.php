<?php namespace stoma;

require_once __DIR__.'/../config.php';
require_once __DIR__.'/../utils/utils.php';
require_once __DIR__.'/../db/db_if.php';

class Stock{

const Q_INTERVAL = '/[><=][\-0-9]/';
const A_DAY = 60*60*24;
const Q_PAGE = '/[0-9]+?x[0-9]+/';
const ARP = 'ackReqPrms'; // acknowledgedRequestedParameters
const LOW_LIMIT_REACHED = 'low_limit_reached';
const HIGH_LIMIT_REACHED = 'high_limit_reached';

static function query_to_dict(&$dict){
    $q = $dict['query'];
    $matches = [];
    $result = preg_match(self::Q_INTERVAL, $q, $matches);
    if ($result){
        //dbgmsg($matches);
        $cmp = $matches[0][0];
        $day = intval($matches[0][1]);
        $today_ts = intdiv(time(), self::A_DAY);
        $ref = ($day + $today_ts) * self::A_DAY;
        $date = [$cmp, $ref];
        $dict['date'] = $date;
        //dbgmsg($date);
    }
    $result = preg_match(self::Q_PAGE, $q, $matches);
    if ($result){
        $ops = explode('x', $matches[0]);
        $bounds = [$ops[0], $ops[1]];
        //dbgmsg($page);
        $dict['limit'] = $bounds;
    }
    $dict['name'] = $q;
}

static function select(&$dict){
    //DBUt::get_rows($key, $val, $table, $conn, $cols, $limit = 0, $order = '')
    //dbgmsg($dict);
    $dbif = \DBIf::getInstance();
    $conn = $dbif->conn;
    $table = "stock";
    $dict[self::ARP] = [];
    if(isset($dict['query']) && $dict['query']){
        self::query_to_dict($dict);
    }

    $bounds = [0, 0];
    if (isset($dict['limit'])){
        $bounds = is_string($dict['limit']) ? explode(',', trim($dict['limit'],"[]\"\'"), 2) : $dict['limit'];
        if (count($bounds) === 2){
            $bounds[0] = intval($bounds[0]);
            $bounds[1] = intval($bounds[1]);
            if (isset($dict['dir'])){
                $bounds[0] += $bounds[1] * $dict['dir'];
                if ($bounds[0] < 0) $bounds[0] = 0;
            }
            $dict[self::ARP]['limit'] = $bounds;
        }
    }

    if (!$bounds[1]){
        $dict['error'] = 'malformed_query';
        return false;
    }

    $date_query = "";
    if (isset($dict['date'])){
        $dt = is_string($dict['date']) ? explode(',', $dict['date'], 2) : $dict['date'];
        $cmp = "";
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
            $dict[self::ARP]['date'] = [$cmp, $val];
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
            $name_query = "name REGEXP '$match_or'";
            $namesAllMatchOrder = "ORDER BY(CASE WHEN name REGEXP '$match_and' THEN 0 ELSE 1 END)";
            $dict[self::ARP]['name'] = [$match_or, $match_and];
        }
    }
    //dbgmsg($dict);
    $where = "";
    if ($date_query) $where .= " $date_query AND";
    if ($name_query) $where .= " $name_query AND";
    if ($where) $where = "WHERE ".substr($where, 0, -3); //remove the last AND

    $x = "SELECT * FROM $table $where $namesAllMatchOrder LIMIT {$bounds[0]},{$bounds[1]}";
    dbgmsg($x);
    $result = $conn->query($x);
    if (!($result)){
        dbgmsg("Selection from $table failed: ".$conn->error);
        dbgmsg("Q: $x");
        dbgmsg_bt();
        return false;
    }
    $ret = $result->fetch_all(MYSQLI_ASSOC);
    $dict['data'] = ['list' => $ret];
    if ($bounds[0] == 0){
        array_push($dict['warns'], self::LOW_LIMIT_REACHED);
    }
    if (count($ret) < $bounds[1]){
        array_push($dict['warns'], self::HIGH_LIMIT_REACHED);
    }
    return true;
}


}
?>