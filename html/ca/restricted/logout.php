<?php
require_once("../../../private/ca/sessionFunctions.php");
header("Access-Control-Allow-Origin: *");
protected_page_check_session_credentials();
successful_logout();
?>