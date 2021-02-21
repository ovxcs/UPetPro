<?php

require_once __DIR__."/../utils/utils.php";

class DBUt{

private static $__errors__ = array();

public static function register_error($tag, $error){
    array_push(self::$__errors__, [$tag, $error]);
    dbgmsg("$tag: $error", false, 1);
    dbgmsg_bt(0, '', false, 1);
}

public static function last_error($idx = -1){
    return self::$__errors__[$idx < 0 ? count(self::$__errors__) + $idx : $idx];
}

public static function prepare_and_exec($conn, $stmnt, $values, &$results){
    //dbgmsg("p&e stmnt:".$stmnt);
    //dbgmsg($values);
    //dbgmsg($results);
    $stmnt = $conn->prepare($stmnt);
    if ($stmnt === false){
        self::register_error("p&e ERROR", $conn->error);
        return false;
    }
    $stmnt->bind_param(...$values);
    //call_user_func_array(array($stmnt, 'bind_param'), refValues($values));
    $stmnt->execute();
    if ($stmnt->error){
        self::register_error("p&e ERROR", $conn->error);
        return false;
    }
    if (!$results){
        return $stmnt;
    }
    if (!$stmnt->bind_result(...$results)){
        self::register_error("p&e ERROR", $conn->error);
        $stmnt->close();
        return false;
    }
    //call_user_func_array(array($stmnt,'bind_result'), refValues($results));
    return $stmnt;
}

public static function get_rows($key, $val, $table, $conn, $cols, $limit = 0, $order = ''){
    //!!!!!!!!!!!!!!!!! use prepare, bind_param and exec or prepare_and_exec
    $y = implode(',', $cols);
    $x = "SELECT $y FROM $table WHERE $key = $val ".(
        $order ? $order : '').($limit ? " LIMIT $limit" : '');
    $result = $conn->query($x);
    if (!($result)){
        dbgmsg("Selection from $table failed: ".$conn->error);
        dbgmsg("Q: $x");
        dbgmsg_bt();
        return false;
    }
    $ret = $result->fetch_all(MYSQLI_ASSOC);
    /*
    $x = "SELECT ? FROM $table WHERE ? = ?".(
        $order ? "BY ?" : "").($limit ? " LIMIT ".$limit : '');
    $values = //cols + key + val + order_if_order
    $stmnt = prepare_and_exec($conn, $x, )
    */
    //dbgmsg($x);
    return $ret;
}

/* returns a map or false*/

public static function get_row_data($column, $val, $table, $conn, $aliases){
    //aliases - array with all columns aliases in the order from db
	//usually/lazy $conn = $dbif->conn, $aliases = $dbif->columns($table)
    //column and val may also be arrays
    //dbgmsg_bt();
    if (is_string($column)){
        $cols = array_fill(0, count($aliases), 0);
        $stmnt = self::prepare_and_exec($conn, "SELECT * FROM $table WHERE $column = ? ",
                array(is_int($val) ? 'i' : 's', $val), $cols);
    }
    else{ //presumed array (and also $val)
        $where = "";
        foreach($column as $c){
            $where .= "$c=? AND ";
        }
        $where = substr($where, 0, -4); // rm last 'AND '
        $values = array_fill(0, count($val)+1, 0);
        $format = str_repeat('s', count($val));
        foreach($val as $i=>$v){
            $format[$i] = is_int($v) ? 'i' : 's';
            $values[$i+1] = $v;
        }
        $values[0] = $format;
        $cols = array_fill(0, count($aliases), 0);
        $stmnt = self::prepare_and_exec($conn, "SELECT * FROM $table WHERE $where ",
                $values, $cols);
    }
    if (!($stmnt)){
        dbgmsg("Selection from $table failed: ".$conn->error);
        dbgmsg_bt();
        return false;
    }
    $fetch_res = $stmnt->fetch();
    if (!$fetch_res){
        //dbgmsg("nothing found");
        return false;
    }
    $stmnt->close();
    $ret = array();
    foreach ($aliases as $ix => $c){
        $ret[$c] = $cols[$ix];
    }
    return $ret;
}

public static function remap_and_save($conn, $table, $main_key, $data, $k2c_map){
    //translate keys to columns and save data
    $dict = [];
    foreach($data as $k => $v){
        if (($v !== null) && isset($k2c_map[$k]))
            $dict[$k2c_map[$k]] = $v;
    }
    return self::save_data($conn, $table, $main_key, $dict);
    //return $dict;
}

public static function save_data($conn, $table, $main_key, $dict){
    //$main_key = $main_key ? $main_key : ''; - wrong may be null
    $row_found = false;
    if (!is_null($main_key)){
        if (is_string($main_key)){
            $main_val = $dict[$main_key];
            $where = "$main_key = ? ";
            $format = is_int($main_val) ? 'i' : 's';
            $ar = array($format, $main_val);
        }else{//array
            $where = "";
            $format = str_repeat("", count($main_key));
            $ar = array_fill(0, count($main_key) + 1, 'x');
            $i = -1;
            foreach($main_key as $mk){
                $i++;
                $mv = $dict[$mk];
                $where .= "$mk = ? AND ";
                $format[$i] = is_int($mv) ? 'i' : 's';
                $ar[$i+1] = $mv;
            }
            $where = substr($where, 0, -4);//remove last 'AND '
            $ar[0] = $format;
        }
        $x = "SELECT * FROM $table WHERE $where FOR UPDATE";
        //dbgmsg($dict);
        //$cols = array_fill(0, 1, 0);
        $cols = false;
        $stmnt = self::prepare_and_exec($conn, $x, $ar, $cols);
        if ($stmnt){
            $f = $stmnt->fetch();
            //dbgmsg($f);
            $stmnt->close();
            $row_found =  $f ? true : false;
        }else{
            $row_found = false;
        }

        $zero = 0;
    }
    if (!$row_found){ //INSERT
        //dbgmsg("db_utils/save_data - INSERT");
        $l = count($dict);
        $cols = array_fill(0, $l, 'x');
        $wilds = array_fill(0, $l, '?');
        $format = str_repeat("", $l);
        $ar = array_fill(0, $l + 1, 'x');
        //$i = $main_key ? 2 : 1;
        $i = -1;
        foreach ($dict as $k => $v){
            //if ($k === $main_key) continue;
            $i++;
            if ($v === null) $v = '';
            if (is_array($v)) $v = $v[1]; //$v[0] is op but at insert isn't required
            $cols[$i] = $k;
            $format[$i] = is_int($v) ? 'i' : 's';
            $ar[$i+1] = $v;
        }
        $ar[0] = $format;
        $cols_str = join(', ', $cols);
        $wilds_str = join(', ', $wilds);
        $x = "INSERT INTO $table ( $cols_str ) VALUES ( $wilds_str )";
        //dbgmsg($x);
        //dbgmsg($ar);
        $stmnt = self::prepare_and_exec($conn, $x, $ar, $zero);
        if (!$stmnt){
            dbgmsg("ERROR: INS into $table failed: ".$conn->error);
            dbgmsg_bt();
            return false;
        }
        $id = mysqli_insert_id($conn);
        //dbgmsg("insert_id: $id");
        return [1, $id];
    }else{ //UPDATE
        //dbgmsg("db_utils/save_data - UPDATE");
        if (is_string($main_key)) $main_key = array($main_key);
        $ar= array_fill(0, count($dict), 'x');
        $format = str_repeat('x', count($dict));
        $what = "";
        $i = -1;
        foreach ($dict as $k => $v){
            if (in_array($k, $main_key)) continue;
            $i++;
            $op = 0;
            if ($v === null) $v = '';
            elseif (is_array($v)){
                if ($k === 'flags'){
                    //ex.: ['&',0x28]
                    $op = $v[0];
                    $v = $v[1];
                }
            }
            $ar[$i+1] = $v;
            $format[$i] = is_int($v) ? 'i' : 's';
            if ($op)
                $what .= "$k=$k$op?, ";
            else
                $what .= "$k=?, ";
        }
        $what = substr($what, 0, -2);//remove last ', '
        $where = '';
        foreach ($main_key as $mk){
            $i++;
            $v = $dict[$mk];
            if ($v === null) $v = '';
            $ar[$i+1] = $v;
            $format[$i] = is_int($v) ? 'i' : 's';
            $where .= "$mk=? AND ";
        }
        $where = substr($where, 0, -4);//remove last 'AND '
        $ar[0] = $format;
        $stmnt = self::prepare_and_exec($conn, "UPDATE $table SET $what WHERE $where ",
                $ar,
                $zero);
        if (!$stmnt){
            dbgmsg("ERROR: update $table failed: ".$conn->error);
            dbgmsg_bt();
            return false;
        }
        $id = mysqli_insert_id($conn);
        return [2, $id];
    }
}

}

?>

