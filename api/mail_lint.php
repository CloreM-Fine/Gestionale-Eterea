<?php
// Verifica sintassi di mail.php
$output = [];
$return = 0;
exec("php -l api/mail.php 2>&1", $output, $return);
echo implode("\n", $output);
echo "\nReturn code: " . $return;
