function twitter__redirect_to_oauth(){
    global $TWITTER_CONSUMER_KEY, $TWITTER_CONSUMER_SECRET,
        $OAUTH_REDIRECT_URI;
    $state = gen_state('tw');
    $toa = new \Abraham\TwitterOAuth\TwitterOAuth(
        $TWITTER_CONSUMER_KEY, $TWITTER_CONSUMER_SECRET);
    $request_token = $toa->oauth('oauth/request_token', 
        array('oauth_callback' => $OAUTH_REDIRECT_URI."?state=".$state));
    
    $url = $toa->url('oauth/authorize', array(
        'oauth_token' => $request_token['oauth_token'],
    ));
    $prov = new Provider($state);
    $prov->oa_db_if->save_oauth_exchange_token($state, 'access_token', $request_token['oauth_token']);
    $prov->oa_db_if->save_oauth_exchange_token($state, 'code', $request_token['oauth_token_secret']);
    echo $url;
}


if ($provider === 'twitter'){
            //allways redirect to oauth provider login page for Twitter
            twitter__redirect_to_oauth($state);
            return;
        }
 