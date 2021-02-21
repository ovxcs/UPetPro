<?php
require_once ('../utils/utils.php');

error_log(json_encode($_POST));
error_log(json_encode(getallheaders()));
if (isset($_POST['profile_req'])){
    $req = $_POST['profile_req'];
    if ($req === 'win'){
        $scr = setcookie('MWTOKEN3', 'qsxdrgbvfth',
                $expires = time() + 10,
                $path= "/cdx/users",
                $domain = "",
                $secure = false,
                $httponly = true);
        error_log("******************".$scr);
        echo(file_get_contents('profile.html'));
        exit();
    }
}
//set_mwt_cookie("test");
//set_mwt_cookie("WWW");
echo(file_get_contents('profile.html'));
exit();
?>