<?php

require_once __DIR__.'/../config.php';

if (MAILER === 'PHPMailer'){

require_once __XENO__.'/github/PHPMailer/src/PHPMailer.php';
require_once __XENO__.'/github/PHPMailer/src/Exception.php';
require_once __XENO__.'/github/PHPMailer/src/SMTP.php';

function __send_mail($to, $subj, $body){
    //error_log(extension_loaded('openssl')?'    SSL loaded':'    SSL not loaded')."\n";
    $m = new PHPMailer\PHPMailer\PHPMailer(TRUE); //true - enables exceptions
    $m->IsSMTP();
    $m->Host = explode('@', g_SMTP_USER, 2)[1];
    $m->SMTPAuth = true;
    $m->Username = g_SMTP_USER;
    $m->Password = g_SMTP_PASSWORD;
    //$m->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
    //$m->Port       = 587;                                    // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS
    $m->setFrom(g_SMTP_USER, 'No Reply');
    /*$m->addReplyTo('noreply@thissite.org', 'First Last');*/
    $m->addAddress($to);
    $m->Subject = $subj;//'PHPMailer mail() test';
    $m->Body = $body;
    //Replace the plain text body with one created manually
    //$mail->AltBody = 'This is a plain-text message body';
    $m->send();
}

$_g_wellknown_smtp_hosts = [
    'gmail.com' => ['smtp.gmail.com', 587, 'tls'],
    'outlook.com' => ['smtp-mail.outlook.com', 587, 'tls'] 
];

function guess_host($email_address){
    global $_g_wellknown_smtp_hosts;
    $host = explode('@', $email_address)[1];
    if (!isset($_g_wellknown_smtp_hosts[$host]))
        throw new Exception ("The mail server host wasn't guessed!");
        //error_log ("The mail server host wasn't guessed!");
        //return false;
    return $_g_wellknown_smtp_hosts[$host];
}

function __send_mail_with_extern($to, $subj, $body, $our_email, $our_pw, $our_name = null){
    //error_log(extension_loaded('openssl')?'    SSL loaded':'    SSL not loaded')."\n";
    $host = ''; $secu = ''; $port = '';
    if ($host_details = guess_host($our_email)){
        $host = $host_details[0];
        $port = $host_details[1];
        $secu = $host_details[2];
    }
    if (!$host) return false;
    $m = new PHPMailer\PHPMailer\PHPMailer(TRUE); //true - enables exceptions
    $m->IsSMTP();
    $m->Host = $host;
    $m->SMTPAuth = true;
    $m->Username = $our_email;
    $m->Password = $our_pw;
    
    $m->SMTPSecure = $secu;//PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
    $m->Port       = $port;                                    // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS
    
    $m->setFrom($our_email, $our_name === null ? explode('@', $our_email)[0] : $our_name);
    /*$m->addReplyTo('noreply@thissite.org', 'First Last');*/
    $m->addAddress($to);
    $m->Subject = $subj;//'PHPMailer mail() test';
    $m->Body = $body;
    //Replace the plain text body with one created manually
    //$mail->AltBody = 'This is a plain-text message body';
    //print_r($m);
    $m->send();
}

}

function send_mail($to, $subj, $body){
    __send_mail($to, $subj, $body);
}



function test(){
    //echo(json_encode($_GET, true));
    if (isset($_GET['mtst'])&& $_GET['mtst']=='27ian2021'){
        echo("Hello");
        __send_mail_with_extern();
    }
}


if (!count(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1))){
    echo("IS MAIN");
    test($_GET);
}

?>