<?php

$OAUTH_HTTP_HOST = isset($_SERVER['HTTP_HOST']) ? $_SERVER[
    'HTTP_HOST'] : (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER[
    'HTTP_ORIGIN'] : null);

assert($OAUTH_HTTP_HOST !== null, "Invalid host");

//$OAUTH_PROTOCOL = !isset($_SERVER['SERVER_PROTOCOL']) ? 'file' : ($_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.1') ? 'http' : 'https';
$OAUTH_PROTOCOL = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] === 443)) ? 'https' : 'http';
$OAUTH_SOURCES = "$OAUTH_PROTOCOL://$OAUTH_HTTP_HOST/auth/oa";

$_OAUTH_REDIRECT_URI = "$OAUTH_SOURCES/login2.php";
$_OAUTH_REDIRECT_URI_ENC = urlencode($_OAUTH_REDIRECT_URI);

define("OAUTH_REDIRECT_URI", $_OAUTH_REDIRECT_URI);
define("OAUTH_REDIRECT_URI_ENC", $_OAUTH_REDIRECT_URI_ENC);

//------------------------------------------------------------------------------------------------------

const FB_VERSION = 'v3.2';

?>