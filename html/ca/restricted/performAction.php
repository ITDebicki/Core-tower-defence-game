<?php

require_once("../../../private/ca/dbInteractions.php");
require_once("../../../private/ca/sessionFunctions.php");
require_once("../../../private/ca/generalFunctions.php");
require_once("../../../private/ca/fileManipulation.php");
if ( !in_array($_POST['action'],["getMapList","getHighScoreList","fetchAvatar"])){
    protected_page_check_session_credentials();
}
header('Content-Type: application/json');
try{
    $jsonData = null;
    $action = null;
    $file = null;
    
    $action = $_POST['action'];
    try{
        $jsonString = $_POST['json'];
        $jsonData = (array)json_decode($jsonString);
    }catch(Exception $e){
        continue;
    }
    $_POST["jsonData"]=$jsonData;
    
    $result = false;
    $user="";
    if (isset($jsonData["user"])){
        $user = $jsonData["user"];
    }
    switch($action){
        case "fetchAvatar":
            $result = fetch_Avatar($user);
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
        case "markAsRead":
            $result = mark_as_read($jsonData["msgIds"]);
            break;
        case "createNotification":
            $result = create_notification($user,$jsonData["type"],$jsonData["title"],$jsonData["message"]);
            break;
        case "updateNotification":
            $result = update_notification($jsonData["id"],$jsonData["update"]);
        case "acceptFriendRequest":
            $result = accept_friend_request($jsonData["userFrom"]);
            break;
        case "refuseFriendRequest":
            $result = refuse_friend_request($jsonData["userFrom"]);
            break;
        case "deleteFriendRequest":
            $result = delete_friend_request($jsonData["userTo"]);
            break;
        case "createFriendRequest":
            $result = create_friend_request($jsonData["userTo"]);
            break;
        case "markFRAsRead":
            $result = mark_fr_as_read($jsonData["userFrom"]);
            break;
        case "blockUser":
            $result = block_user($user);
            break;
        case "getFriends":
            $result = get_friends($user);
            break;
        case "unblockUser":
            $result = unblock_user($user);
            break;
        case "deleteFriend":
            delete_friend($user);
            break;
        case "getBlockedUsers":
            $result = get_blocked_users();
            break;
        case "getFriendRequests":
            $result = get_friend_requests();
            break;
        case "getSentFriendRequests":
            $result = get_sent_friend_requests();
            break;
        case "getAllUsers":
            $result = get_all_users($jsonData["needle"]);
            break;
        case "getExactUsers":
            $result = get_exact_users($jsonData["needle"]);
            break;
        case "addScore":
            $result = add_score($jsonData["map"],$jsonData["score"]);
            break;
        case "isHighScore":
            $result = is_high_score($jsonData["map"],$jsonData["score"]);
            break;
        case "userRank":
            $result = user_rank($user,$jsonData["map"],$jsonData["timespan"]);
            break;
        case "getUserHighScores":
            $result = get_user_high_scores($user);
            break;
        case "getMapList":
            $result = get_map_list();
            break;
        case "getHighScoreList":
            $result = get_highscore_list($jsonData["map"],$jsonData["limit"],$jsonData["from"],$json["to"]);
            break;
        case "getUserHighScoreForMap":
            $result = get_user_high_score_for_map($user,$jsonData["map"],$jsonData["from"],$json["to"]);
            break;
        case "getSaveData":
            $result = get_save_data($jsonData["save"]);
            break;
        case "getSaves":
            $result = get_saves();
            break;
        case "updateSave":
            $result = update_save($jsonData["save"],$jsonData["saveData"],$jsonData["map"],$jsonData["thumbnail"]);
            break;
        case "createSave":
            $result = create_save($jsonData["saveData"],$jsonData["name"],$jsonData["thumbnail"],$jsonData["map"]);
            break;
        case "updateName":
            $result = update_name($jsonData["save"],$jsonData["name"]);
            break;
        case "deleteSave":
            $reuslt = delete_save($jsonData["save"]);
            break;
        case "getExperience":
            $result = get_experience();
            break;
        case "addExperience":
            $result = add_experience($jsonData["experience"]);
            break;
        case "getXPMultiplier":
            $result = get_XP_multiplier();
            break;
        default:
            throw new Exception("POST parameter 'action' not correct",100);
    }
    
    $resultDict = array("success" => true, "data" =>$result, "debug" => $_POST);
    echo json_encode($resultDict);
}catch(Exception $e){
    error_handler($e);
}
?>