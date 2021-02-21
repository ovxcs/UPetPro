<?php

$__XENO__ = __DIR__.'/'.(is_dir(__DIR__.'/../3') ? '/../3' : '/../../3');
define ('__XENO__', $__XENO__);
assert(is_dir(__XENO__), "Directory not found: '".__XENO__."'");

const MAILER = "PHPMailer";

?>