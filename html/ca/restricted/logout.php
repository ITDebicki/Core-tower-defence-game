<?php
require_once("../../../private/ca/sessionFunctions.php");
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');
successful_logout();
echo '{"success":true}';
exit(0);
?>