<?php
/*
Error codes:

100 - Not all POST arguments supplied
101 - Not all GET arguments supplied
102 - Failed to initiate connection
103 - Failed to close connection

200 - Username or email already used

300 - Bad username format
301 - Bad password format
302 - Bad email format

400 - invalid json
401 - missing json values

500 - invalid username or password
501 - Failed to write to database (unknown error)
502 - Failed to retrieve data from database
503 - No records found

600 - File failed to upload
601 - File is not an image
602 - Unable to determine file type
603 - File too large
604 - Failed to move file
605 - Unable to generate unique file name
606 - failed to scale image
*/
function error_handler($e){
    $displayError = array("success" => false, "error" => $e->getMessage(), "errorCode" => $e->getCode(),"post" => $_POST);
    echo json_encode($displayError);
    //var_dump($e);
}
?>