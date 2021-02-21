<?php
$hrtsnss = hrtime(true);
$hrtsns = hrtime(true);
$hrdiffns = hrtime(true) - $hrtsnss;
echo ("PHP is OK! vers.: ".phpversion()." hrts:$hrtsns, diff:$hrdiffns");
require ('utils/utils.php');
dbgmsg("debug message");
dbgmsg("debug message 2");
?>