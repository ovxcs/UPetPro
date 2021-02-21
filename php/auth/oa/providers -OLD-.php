<?php 

require_once __DIR__."/../../config.php";
require_once __DIR__."/provider.php";

require_once __DIR__."/twitteroauth/autoload.php";
require_once __DIR__."/twitteroauth/src/TwitterOAuth.php";
//use \Abraham\TwitterOAuth;

$PROVIDERS = [
    'google' => [
        'tag' => 'ggl',
        'app_id' => GOOGLE_APP_ID,
        'url' => "https://accounts.google.com/o/oauth2/v2/auth?",
        'scope' => 'profile%20email'
    ],
    'facebook' => [
        'tag' => 'fb',
        'app_id' => FB_APP_ID,
        'url' => "https://www.facebook.com/".FB_VERSION."/dialog/oauth?",
        'scope' => 'email',
    ],
    'amazon' => [
        'tag' => 'ama',
        'app_id' => AMAZON_CLIENT_ID,
        'url' => "https://www.amazon.com/ap/oa?",
        'scope' => 'profile'
    ],
    'linkedin' => [
        'tag' => 'lin',
        'app_id' => LINKEDIN_CLIENT_ID,
        'url' => "https://www.linkedin.com/oauth/v2/authorization?",
        'scope' => 'r_basicprofile%20r_emailaddress'
    ],
];

?>