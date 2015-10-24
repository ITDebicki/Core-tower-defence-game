<?php

require_once("../../../private/ca/dbInteractions.php");
require_once("../../../private/ca/sessionFunctions.php");
require_once("../../../private/ca/generalFunctions.php");
require_once("../../../private/ca/fileManipulation.php");
protected_page_check_session_credentials();
header('Content-Type: application/json');
try{
    $jsonData = null;
    $action = null;
    $file=null;
    
    $action = $_POST['action'];
    try{
        $jsonString = $_POST['json'];
        $jsonData = (array)json_decode($jsonString);
    }catch(Exception $e){
        continue;
    }
    $_POST["jsonData"]=$jsonData;
    
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
        case "fetchNotifications":
            $result = get_notifications($jsonData["fromDate"],$jsonData["limit"]);
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