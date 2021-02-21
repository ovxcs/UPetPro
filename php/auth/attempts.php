<?php

abstract class Attempts{
    const LogIn = array(1, 'users', 'email');
    const SignUp = array(2, 'users', 'email');
}

abstract class AttemptResult{
    const success = 1;
    const failed = 2;
    const rejected = 3;
}

const attempts__warning_delta_time = 6 * 10; //seconds
const attempts__warning_number = 5;
const attempts__too_soon = 3;

function attempts__analyze($conn, $source, $kind, $target){ //kind, target
    if ($source === 'IP'){
        if (isset($_SERVER['REMOTE_ADDR'])){
            $source = $_SERVER['REMOTE_ADDR'];
        }
    }
    if (is_array($kind)) $kind = $kind[0];
    if($source){
        $stmnt = "SELECT * FROM attempts WHERE address='$source' AND kind=$kind AND target='$target' ORDER BY ts DESC";
        $result = $conn->query($stmnt);
        if (!$result){
            dbgmsg("sql error:".$conn->error);
        }
        else if ($result->num_rows > 0){
            $row = $result->fetch_assoc();
            //dbgmsg(print_r($row, true));
            if (time() - $row['ts'] < attempts__too_soon && $row['number'] >= attempts__warning_number){
                return false;
            }
        }
    }
    return true;
}

function attempts__insert_data($conn, $data){
    $ts = time(); $data['ts'] = $ts;
    $next_number = 1;
    $where = "";
    $target = $data['target'];
    $code = $data['code'];
    $kind = $data['kind'];
    $row_id = 0;
    $source = $data['address'];
    if ($source){
        $min_ts = $ts - attempts__warning_delta_time;
        /*
        $target_id = isset($data['target_id']) ? $data['target_id'] : FALSE;
        $kind = $data['kind'];
        $where = "ts>=$min_ts AND kind=$kind ".($target_id !== FALSE ? " AND target_id=$target_id": "")." AND address='$source'";
        */
        $where = "ts>=$min_ts AND kind=$kind AND target='$target' AND address='$source'";
    }
    if ($where){
        $stmnt = "SELECT * FROM attempts WHERE $where ORDER BY ts DESC FOR UPDATE";
        $result = $conn->query($stmnt);
        if (!$result){
            dbgmsg("sql error:".$conn->error);
        }
        else if ($result->num_rows > 0){
            $row = $result->fetch_assoc();
            $next_number = $row['number'] + 1;
            $row_id = $row['id'];
        }
    }
    if ($next_number === 1){
        $data['number'] = $next_number;
        $data_len = count($data);
        $columns = array_fill(0, $data_len, 'x');
        $format = str_repeat('s', $data_len);
        $values = array_fill(0, $data_len + 1, 0); //format
        $wild_values = array_fill(0, $data_len, '?');
        $i = -1;
        foreach ($data as $k => $v){
            $i++;
            $columns[$i] = $k;
            $values[$i+1] = $v;
            if (is_int($v)) $format[$i] = 'i';
        }
        $columns_str = join(',', $columns);
        $wild_values_str = join(',', $wild_values);
        $values[0] = $format;
        $sqlstr = "INSERT INTO attempts ($columns_str) VALUES ($wild_values_str)";
        dbgmsg($sqlstr);
        if ($stmnt = $conn->prepare($sqlstr)){
            $stmnt->bind_param(...$values); //format + values
            //call_user_method_array('bind_param', $stmnt, $values);
            $stmnt->execute();
            $stmnt->close();
        }else{
            dbgmsg("sql error:".$conn->error);
        }
    }else{
        $sqlstr = "UPDATE attempts SET ts=$ts, number=$next_number, code=$code WHERE id=$row_id";
        $result = $conn->query($sqlstr);
        if (!$result){
            dbgmsg("sql error:".$conn->error);
        }
    }
}

function register_attempt($attempt, $target, $source, $result, $code){
    //$target: AttemptKind::LogIn, AttemptKind::SignUp
    $conn = DBIf::getInstance()->conn;
    $data = array('kind' => $attempt[0],
            'number' => 0, 'code' => $code);
    if (is_int($source)){
        //source should be an id from another (heuristic?) table, not implemented
        $data['source_id'] = $source;
    }elseif (is_string($source)){ //should be an IPv4/6
        if ($source === 'IP'){
            if (isset($_SERVER['REMOTE_ADDR'])){
                $source = $_SERVER['REMOTE_ADDR'];
            }
        }
        $data['address'] = $source;
    }else{//presumed array
        //bouth
    }
    /*
    if (is_string($target)){
        $table = $attempt[1];
        $col = $attempt[2];
        $stmnt = "SELECT id FROM $table where $col = '$target'";
        $result = $conn->query($stmnt);
        if (!$result){
            dbgmsg("sql error:".$conn->error);
        }
        else if ($result->num_rows > 0){
            $data['target_id'] = $result->fetch()[0];
        }
    }elseif(is_int($target)){
        $data['target_id'] = $target;
    }else{
        //???
    }
    */
    $data['target'] = $target;
    $data['code'] = $code;
    //if(count($data)){
        attempts__insert_data($conn, $data);
    //}
}

?>