<?php
/**
 * Reports Exceptions to the client
 *
 * Error codes:
 * 
 * 100 - Not all POST arguments supplied
 * 101 - Not all GET arguments supplied
 * 102 - Failed to initiate db connection
 * 103 - Failed to close db connection
 * 
 * 200 - Username or email already used
 * 
 * 300 - Bad username format
 * 301 - Bad password format
 * 302 - Bad email format
 * 303 - Passwords do not match
 * 
 * 400 - invalid json
 * 401 - missing json values
 * 402 - invalid json value
 * 
 * 500 - invalid username or password
 * 501 - Failed to write to database (unknown error)
 * 502 - Failed to retrieve data from database
 * 503 - No records found
 * 
 * 600 - File failed to upload
 * 601 - File is not an image
 * 602 - Unable to determine file type
 * 603 - File too large
 * 604 - Failed to move file
 * 605 - Unable to generate unique file name
 * 606 - Failed to scale image
 * 607 - Failed to delete file
 * 608 - Failed to write file
 * 
 * 700 - Friends already
 * 701 - Receiver has blocked sender
 * 702 - Request already sent by current sender
 * 703 - Request already sent by current receiver
 * 704 - sender has blocked receiver
 * 705 - User already blocked
 * @author Ignacy Debicki
 * @param Exception $e Exception to report
 */
function error_handler($e){
    $displayError = array("success" => false, "error" => $e->getMessage(), "errorCode" => $e->getCode(),"debug" => $_POST);
    echo json_encode($displayError);
}
?>
