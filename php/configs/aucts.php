<?php

/*
assert_options(ASSERT_ACTIVE, true);
assert_options(ASSERT_BAIL, true);
assert_options(ASSERT_WARNING,  false);
*/

//$HOST = "http://localhost:8083";
$APP_NAME = 'No name app';
//$HOST_SRC = "$HOST/cdx";
const FIRST_PAGE_AFTER_LOGIN = "/cdx/home.html";

$PICS_HOST = 'http://10.82.28.221:8085';
$UPLOAD_DIR = "h:/new/M/ED/0/uploads";
$UPLOAD_URL = "$PICS_HOST/uploads";

//const STORAGE = array(uri, usr, pwd); // USE THIS FORM ONLY IN .__secrets__.php !!!
// or
//const STORAGE = path; // MAY BE OVERWRITEN IN .__secrets__.php !!!

//const STORAGE_ACCESS_URL = http://storage.net/stor1 ...

$NO_REPLY_ADDR = 'no-reply@educks.com';


require_once __DIR__.'/../auth/oa/config.php';
require_once __DIR__.'/../../../0/secrets/.__secrets__.php';






?>
