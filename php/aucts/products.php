<?php

require_once __DIR__."/../utils/utils.php";
require_once __DIR__."/../config.php";
require_once __DIR__."/../db/db_if.php";
require_once __DIR__."/../utils/big_strings.php";

function register_product($dbif, $dct){
    $main_key = array_key_exists('id', $dct) ? 'id' : 0;
    if ($main_key !== 0){
        $row = DBUt::get_row_data('id', $dct['id'], 'goods', $dbif->conn, $dbif->columns('goods'));
        //!!! this isn't anough
        if ($row['owner_id'] !== $dct['crt_user_id']){
            dbgmsg("User action denied: crt={$dct['crt_user_id']}, actual owner={$row['owner_id']}");
            return ['error' => 'action_denied'];
        }
        $main_key = ['id', 'owner_id'];
    }
    $dct['owner_id'] = $dct['crt_user_id'];
    if (array_key_exists('year', $dct) &&
        !is_numeric($dct['year'])) $dct['year'] = -1;
    $id = $dbif->save_valid_data('goods', $main_key, $dct);
    $goodId = $id[0] === 1 ? $id[1] : $dct['id'];
    save_product_pictures($dbif, $dct, $goodId);
    return $id;
}

function save_product_pictures($dbif, $dct, $goodId){
    global $PICS_HOST;
    $common = $PICS_HOST;
    if (count($dct['files']) === 0){
        return;
    }
    $bsi = BigString::getInstance();
    $pics = $dct['files'];
    foreach($pics as $ix=>$v){
        $pics[$ix] = $bsi->reduce($pics[$ix], $common);
    }
    $txt = implode(',', $pics);
    $dct2 = [
        'goodId' => $goodId,
        'pictures' => $txt
    ];
    if ($dct['def_pic'])
        $dp = $dct['def_pic'];
    else{
        $dp = $dct['files'][0];
    }
    $dct2['default_pic'] = $bsi->reduce($dp, $common);
    $s = array_search($dct['def_pic'], $pics);
    $dct2['default_pic_ix'] = $s === FALSE ? 0 : $s;
    $dbif->save_valid_data('goods_pics', 'goodId', $dct2);
}

function place_offer($dbif, $dct){
    $dct['goodId'] = $dct['id']; unset($dct['id']);
    $cols = $dbif->columns('aucts');
    $offers = DBUt::get_rows('goodId', $dct['goodId'], 'aucts', $dbif->conn,
        $cols, 2, $order = 'ORDER BY end_ts DESC');
    if ($offers){
        if (time() < $offers[0]['end_ts']){
            return (['error'=>'offer already placed']);
        }
    }
    if (array_key_exists('auct_ends', $dct) &&
                is_numeric($dct['auct_ends'])){
        $unit = ($dct['auct_ends_unit'] === 'd' ? 24 : 1) * 3600;
        $ts = time();
        $dct['placed_ts'] = $ts;
        $dct['start_ts'] = $ts;
        $dct['end_ts'] = $dct['start_ts'] + intval(
            $dct['auct_ends']) * $unit;
    }else{
        return ['error' => 'invalid offer interval'];
    }
    //dbgmsg("svd: ".json_encode($dct));
    $id = $dbif->save_valid_data('aucts','goodId', $dct);
    $dct['id'] = $dct['goodId'];//setting it back;
    return ['ok'=>true];
}

function load_product($dbif, $id){
    $id = intval($id);
    if (!$id){
        return ['error' => 'invalid prod. id'];
    }
    //dbgmsg($id);
    $cols = $dbif->columns('goods');
    $dct = DBUt::get_row_data('id', $id, 'goods', $dbif->conn, $cols);
    //dbgmsg($dct);
    if (array_key_exists('year', $dct) && $dct['year'] < 0)
        $dct['year'] = '';
    $offers = DBUt::get_rows('goodId', $dct['id'],
            'aucts', $dbif->conn, ['end_ts', 'start_ts', 'placed_ts'],
                $limit = 1, $order = 'ORDER BY end_ts DESC');
    if ($offers){
        $dct['offer'] = $offers[0];
        $dct['sts'] = time();
    }
    $cols = $dbif->columns('goods_pics');
    $picts = DBUt::get_row_data('goodId', $id, 'goods_pics', $dbif->conn, $cols);
    if ($picts){
        $bsi = BigString::getInstance();
        $dct['files'] = [];
        foreach(explode(',', $picts['pictures']) as $k=>$v){
            if ($v)
                $dct['files'][] = $bsi->restore($v);
        }
        if ($picts['default_pic']){
            $dct['def'] = $bsi->restore($picts['default_pic']);
            $dct['def_ix'] = $picts['default_pic_ix'];
        }
    }
    //dbgmsg("product loading result: ".print_r($dct, true));
    return $dct;
}

function my_products_list($dbif, $owner_id){
    $lst = DBUt::get_rows('owner_id', $owner_id, 'goods', $dbif->conn,
        ['owner_id', 'id', 'name', 'fabrication_h'],
        150, 'ORDER BY id DESC');
    $goods_picts_cols = $dbif->columns('goods_pics');
    foreach($lst as $ix =>$_row){
        $offers = DBUt::get_rows('goodId', $_row['id'],
            'aucts', $dbif->conn, ['end_ts', 'start_ts', 'placed_ts'],
                $limit = 1, $order = 'ORDER BY end_ts DESC');
        //dbgmsg($g_row['id']."-".($offers !== false ? json_encode($offers): 'FALSE'));
        if ($offers){
            $lst[$ix]['offer'] = $offers[0];
        }else{
            if ($dbif->conn->error)
                dbgmsg("warn.:".$dbif->conn->error);
        }
        $picts = DBUt::get_row_data('goodId', $_row['id'],
                'goods_pics', $dbif->conn, $goods_picts_cols);
        if ($picts['default_pic']){
            $bsi = BigString::getInstance();
            $lst[$ix]['def'] = $bsi->restore($picts['default_pic']);
        }
    }
    return ['lst' => $lst];
}

function offers_list($dbif, $user_id, $query, $limit = 10, $offset = 0, $order = NULL){
    $query = '*';
    $table = 'aucts';
    $table_favs = 'aucts_watchlists';
    $ts_past = 3600000;//30;
    $q = "SELECT * FROM $table ";
    if ($query == '*'){
        $ts = time(); $ts_l = $ts - $ts_past;
        $q .= "WHERE end_ts > $ts_l ";
    }
    else{
        $ts = time(); $ts_l = $ts + 3600;
        $q .= "WHERE end_ts > $ts_l ";
    }
    if (!$order) $order = 'end_ts';
    $q .= "  ORDER BY $order ASC";
    $total = 1020;
    $q .= " LIMIT ".intval($limit).($offset ? " OFFSET ".intval($offset) : "");
    dbgmsg($q);
    $result = $dbif->conn->query($q);
    if (!($result)){
        //dbgmsg("Selection from $table failed: ".($dbif->conn->error));
        return ['error'=>$dbif->conn->error];
    }
    $all = $result->fetch_all(MYSQLI_ASSOC);
    //dbgmsg(print_r($all, true));
    $goods_cols = $dbif->columns('goods');
    $goods_picts_cols = $dbif->columns('goods_pics');
    $bsi = BigString::getInstance();
    foreach($all as $ix => $row){
        /*error_log("----------------------------------------".$row['goodId']);*/
        $goods_row = DBUt::get_row_data('id', $row['goodId'], 'goods', $dbif->conn, $goods_cols);
        $all[$ix]['good'] = $goods_row;
        $all[$ix]['ixx'] = $ix;
        $picts = DBUt::get_row_data(
                    'goodId', $row['goodId'], 'goods_pics', $dbif->conn, $goods_picts_cols);
        //dbgmsg($picts);
        if ($picts['default_pic']){
            $all[$ix]['def'] = $bsi->restore($picts['default_pic']);
        }
        $reviews = 0;
        $q = "SELECT * FROM reviews_and_comments WHERE gKind=1 AND gId={$row['goodId']}";
        $result = $dbif->conn->query($q);
        if ($result){
            $rez = 0; $cnt = 0;
            foreach($result->fetch_all(MYSQLI_ASSOC) as $rev){
                $rez += $rev['note'];
                $cnt += 1;
            }
            if($cnt){
                $all[$ix]['note'] = $rez;
                $all[$ix]['revs_cnt'] = $cnt;
            }
        }
    }

    $favs = 0;
    if ($user_id){
        $result = $dbif->conn->query("SELECT list FROM $table_favs WHERE user_id='$user_id'");
        if($result){
            $row = $result->fetch_row();
            if($row) $favs = $row[0];
        }else{
            return ['error'=>$dbif->conn->error];
        }
    }

    //$total = 120; $limit = 5; $offset = 110;
    $uri = $_SERVER['REQUEST_URI'];
    return ['lst' => $all, 'favs' => $favs, 'pages' =>[$total, $limit, $offset, $query, $uri]];
}

function offers_list_wrap($dbif, $user_id){
    $query = '';
    $crt_pp = isset($_GET['cpp']) ? $_GET['cpp']: (
                    isset($_POST['cpp']) ? $_POST['cpp']: 10); //current perPage
    $crt_start = isset($_GET['cos']) ? $_GET['cos']: (
                    isset($_POST['cos']) ? $_POST['cos']: -1); //current offset
    $limit = isset($_GET['pp']) ? $_GET['pp']: (
                    isset($_POST['pp'])  ? $_POST['pp'] : $crt_pp);
    $page = isset($_GET['pg']) ? $_GET['pg']: (
                    isset($_POST['pg']) ? $_POST['pg'] : 0);
    $offset = $crt_start === -1 ? 0 : $crt_start;
    if(!$limit) $limit = $crt_pp;
    if ($page === 'n') $offset += $crt_pp;
    else if($page === 'p') $offset -= $crt_pp;
    else if($page === 'c') $offset = $crt_start; //remains the same (used for page size modifiactions)
    else $offset = intval($page) * $crt_pp;
    //if ($offset < 0) $offset = 0;
    return offers_list($dbif, $user_id, $query, $limit, $offset);
}

function offers_updates($dbif, $list){
    $table = 'aucts';
    $str_lst = array_map(function ($e) {
        return intval($e);
    }, $list);
    $q = "SELECT id, status, flags, crt_bid FROM $table WHERE id IN(".implode(',',$str_lst).")";
    //dbgmsg($q);
    $result = $dbif->conn->query($q);
    if (!($result)){
        dbgmsg("Selection from $table failed: ".($dbif->conn->error));
        return ['error'=>$dbif->conn->error];
    }
    return ['updates' => $result->fetch_all(MYSQLI_ASSOC)];
}

function get_var(&$var, $default = null){
    return isset($var) ? $var : $default;
}

function watchlist($dbif, $user_id){
    $table_watchlists = 'aucts_watchlists';
    $table = 'aucts';
    $q = "SELECT list FROM $table_watchlists WHERE user_id='$user_id'";
    $result = $dbif->conn->query($q);
    if (!($result)){
        dbgmsg("Selection from $table_watchlists failed: ".($dbif->conn->error));
        return ['error'=>$dbif->conn->error];
    }
    $row = $result->fetch_row();
    //dbgmsg($row);
    if (count($row) != 0){
        $favs = trim($row[0], ',');
        //dbgmsg($favs);
        $q2 = "SELECT * from $table WHERE id IN ($favs) ORDER BY end_ts DESC";
        $result2 = $dbif->conn->query($q2);
        if (!($result2)){
            dbgmsg("Selection from $table failed: ".($dbif->conn->error));
            return ['error'=>$dbif->conn->error];
        }
        $all = $result2->fetch_all(MYSQLI_ASSOC);
        //dbgmsg(print_r($all, true));
        $goods_cols = $dbif->columns('goods');
        $goods_picts_cols = $dbif->columns('goods_pics');
        $bsi = BigString::getInstance();
        foreach($all as $ix => $row2){
            /*error_log("----------------------------------------".$row['goodId']);*/
            $goods_row = DBUt::get_row_data('id', $row2['goodId'], 'goods', $dbif->conn, $goods_cols);
            $all[$ix]['good'] = $goods_row;
            $all[$ix]['ixx'] = $ix;
            $picts = DBUt::get_row_data(
                    'goodId', $row2['goodId'], 'goods_pics', $dbif->conn, $goods_picts_cols);
            //dbgmsg($picts);
            if ($picts['default_pic']){
                $all[$ix]['def'] = $bsi->restore($picts['default_pic']);
            }
        }
        $total = count($all);
        $limit = 100;
        $offset = 0;
        $uri = '';
        return ['lst' => $all, 'favs' => $favs, 'pages' => 0];
    }
    return ['lst' => 0];
}

function aucts__toggle_bookmark($dbif, $auct_id, $user_id){
    $table= 'aucts_watchlists';
    $needle_id = intval($auct_id);
    if (!$needle_id) return ['error' => 'invalid_needle_id'];
    $q = "SELECT list FROM $table WHERE user_id = $user_id";
    $result = $dbif->conn->query($q);
    if (!($result)){
        dbgmsg("Selection from $table failed: ".($dbif->conn->error));
        return ['error'=>$dbif->conn->error];
    }
    $row = $result->fetch_row();
    if (count($row) == 0){
        $arr = ",$needle_id,";
        $dbif->conn->query("INSERT INTO $table(user_id, list) VALUES ('$user_id', '$arr')");
        $stat = 1;
    }else{
        $arr = $row[0];
        $count = -1;
        $v = ",$needle_id,";
        //dbgmsg("actual list: $arr; to_replace: $v");
        $new_arr = str_replace($v, ",", $arr, $count);
        //dbgmsg("COUNT for $needle_id: $count");
        if ($count === 0){
            $new_arr = "$arr$needle_id,"; //$arr should already have a comma at end
        }
        if ($new_arr[0] !== ',') $new_arr = ",$arr";
        //dbgmsg("UPDATE: $new_arr");
        $dbif->conn->query("UPDATE $table SET list='$new_arr' WHERE user_id='$user_id'");
        $stat = $count ? 0 : 1;
    }
    return ['stat'=> $stat]; 
}

$pp_ops_NOT_req_auth = [
    'updates' => true
];

function products_main(){
    global $pp_ops_NOT_req_auth;
    dbgmsg("POST: ".json_encode($_POST));
    dbgmsg(" GET: ".json_encode($_GET));
    dbgmsg("JSON: ".json_encode($GLOBALS['__JSON']));
    if (array_key_exists('products_op', $_POST))
        $_dict = $_POST;
    elseif ($GLOBALS['__JSON'] && array_key_exists('products_op', $GLOBALS['__JSON']))
        $_dict = $GLOBALS['__JSON'];
    else
        return; //NOT ME;
    $op = $_dict['products_op'];
    if (isset($pp_ops_NOT_req_auth[$op]) && $pp_ops_NOT_req_auth[$op] === true){
        //auth not required
        //dbgmsg("AUTH NOT REQUIRED for $op");
    }else{
        require_once __DIR__."/../auth/auth.php";
        $user = auth\get_user_info_or_die();
        $_dict['crt_user_id'] = $user['usr_info']['aid'];
        //dbgmsg("USER: ".json_encode($user));
    }
    $dbif = DBIf::getInstance();
    $l = 0;
    if ($op === 'set'){
        //dbgmsg("register product: ".print_r($_dict, true));
        $r = register_product($dbif, $_dict);
        if (isset($r['error'])){
            $l = $r;
        }else{
            $id = array_key_exists('id', $_dict) ? $_dict['id'] : $r[1];
            $l = load_product($dbif, $id,  $_dict);
            //dbgmsg("reg. prod result: ".print_r($l, true));
        }
    }elseif($op === 'placeIt'){
        if (!array_key_exists('flags',$_dict)) $_dict['flags'] = 1;
        $r = register_product($dbif, $_dict);
        $id = array_key_exists('id', $_dict) ? $_dict['id'] : $r[1];
        $_dict['id'] = $id;
        $po = place_offer($dbif, $_dict);
        if (array_key_exists('error', $po)){
            ob_end_clean();
            echo(json_encode($po));
            return;
        }
        $l = load_product($dbif, $id,  $_dict);
    }elseif($op === 'product_details'){
        //dbgmsg($_dict);
        $l = load_product($dbif, $_dict['id'], $_dict);
        //dbgmsg($l);
    }elseif($op === 'my_list'){
        $l = my_products_list($dbif, $user['usr_info']['aid']);
    }elseif($op === 'all_offers'){
        $usr = auth\get_user_info_or_die(false);
        $usr_id = $usr ? $usr['usr_info']['aid'] : 0;
        if (isset($_dict['wls']) && strpos($_dict['wls'], "mode=watchlist")){
            $l = watchlist($dbif, $usr_id);
        }else{
            //dbgmsg("requesting ALL offers");
            //$l = offers_list($dbif, $usr_id, $query, $limit, $offset);
            
            $l = offers_list_wrap($dbif, $usr_id);
        }
        //dbgmsg($l);
    }elseif($op === 'updates'){
        if ($_dict['list'])
            $l = offers_updates($dbif, $_dict['list']);
        else
            $l = ['count' => 0];
    }elseif($op === 'toggle_bookmark'){
        //dbgmsg("toggle_bookmark for ". $_dict['id']);
        $l = aucts__toggle_bookmark($dbif, $_dict['id'], $user['usr_info']['aid']);
    }
    if ($l){
        ob_end_clean();
        $l['sts'] = time();
        //dbgmsg($l);
        echo(json_encode($l));
        return;
    }
    dbgmsg('-----------------------------------------------');
    $str = json_encode(['page' => 'products.php', 'error' => 'invalid request']);
    echo($str);
    die();
}

if (!count(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1))){
//if (this_is_main()){
    //error_log(__FILE__." IS MAIN");
    products_main();
}

?>