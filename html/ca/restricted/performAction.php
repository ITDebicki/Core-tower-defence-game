<?php

require_once("../../../private/ca/dbInteractions.php");
require_once("../../../private/ca/sessionFunctions.php");
require_once("../../../private/ca/generalFunctions.php");
require_once("../../../private/ca/fileManipulation.php");
protected_page_check_session_credentials();
header('Content-Type: application/json');
try{
    $jsonString = null;
    $action = null;
    $file=null;
    
    $action = $_POST['action'];
    //$jsonString = $_POST['json'];
    //$jsonData = decode_JSON($jsonString);
    $result = false;
    switch($action){
        case "fetchAvatar":
            $result = fetch_Avatar();
            break;
        case "uploadAvatar":
            $result = upload_file($_FILES['userfile'],"avatars");
            break;
        case "uploadThumbnail":
            $result = upload_file($_FILES['userfile'],"mapThumbnails");
            break;
        case "removeAvatar":
            $result = remove_avatar();
            break;
        case "deleteAccount":
            $result = delete_account();
            break;
        default:
            throw new Exception("POST parameter 'action' not supplied",100);
    }
    
    $resultDict = array("success" => true, "data" =>$result, "debug" => $_POST);
    echo json_encode($resultDict);
}catch(Exception $e){
    error_handler($e);
}
?>