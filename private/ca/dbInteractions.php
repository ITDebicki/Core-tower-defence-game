<?php

require_once("dbConnection.php");
require_once("errorReporting.php");
require_once("generalFunctions.php");

function create_account($jsonData){  
    //initiate variables for entire scope
    $user="";
    $hash="";
    $email="";
    $uUser="";
    $uPassword="";
    $uEmail="";

    //validate
    try{
        $uUser=$jsonData["username"];
        $uPassword= $jsonData["password"];
        $uEmail=$jsonData["email"];
    }catch(Exception $e){
        throw new Exception("Missing json values",401,$e); 
    }
    //check format of username and password
    if (login_format_check($uUser,$uPassword)){
        $user = $uUser;
        $hash = password_hash($uPassword,PASSWORD_DEFAULT);
    }

    //check format of email
    if(filter_var($uEmail, FILTER_VALIDATE_EMAIL)){
        $email = $uEmail;
    }else{
        throw new Exception("Invalid email",302);
    }
    //send request
    $conn = request_connection();
    $stmt = $conn->prepare('INSERT INTO User (username, hash, email) VALUES(:username,:hash,:email)');
    $success = $stmt->execute(array(':username' => $user, ':hash' => $hash, ':email' => $email));
    if (!$success){
         throw new Exception("Username or email already used",200);  
    }
    $affectedRows = $stmt->rowCount();

    if ($affectedRows > 0){
        return true;
    }else{
        throw new Exception("Failed to write to database",501);
    }
}

function delete_account(){
    //firstly remove avatar file
    try{
        remove_avatar();
    }catch (Exception $e){
        continue;
    }

    //delete account
    $conn = request_connection();
    $stmt = $conn->prepare('DELETE FROM User WHERE username = :username');
    $stmt->execute(array(':username' => $_SESSION["user"]));
    $affectedRows = $stmt->rowCount();

    if ($affectedRows > 0){
        //log out
        successful_logout();
        return true;
    }else{
        throw new Exception("Failed to write to database",501);
    }
    
}

function validate_user($jsonData){
    $user="";
    $password="";
    $uUser="";
    $uPassword="";
    $storedHash = null;
    
    try{
        $uUser=$jsonData["username"];
        $uPassword= $jsonData["password"];
    }catch(Exception $e){
        throw new Exception("Missing json values",401,$e); 
    }
    if (login_format_check($uUser,$uPassword)){
        $user = $uUser;
        $password= $uPassword;
    }
    
    //send request
    $conn = request_connection();
    $stmt = $conn->prepare('SELECT hash FROM User WHERE BINARY username = :username');
    $stmt->execute(array(':username' => $user));
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if(count($result)!=0){
        $storedHash = $result[0]['hash'];
    }

    return password_verify($password,$storedHash);   
}

function log_login($user){
    //send request
    $conn = request_connection();
    $stmt = $conn->prepare('INSERT INTO LastLogin (user) VALUES(:username)');
    $stmt->execute(array(':username' => $user));
    $affectedRows = $stmt->rowCount();


    if ($affectedRows > 0){
        return true;
    }else{
        throw new Exception("Failed to write to database",501);
    }    
    
}

function fetch_avatar($user){
    if ($user ==''){
        $user = $_SESSION["user"];
    }
    //send request
    $file= null;
    $conn = request_connection();
    $stmt = $conn->prepare('SELECT avatarFile FROM User WHERE username=:username');
    $stmt->execute(array(':username' => $user));
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if(count($result)!=0){
        $file = $result[0]['avatarFile'];
    }else{
        throw new Exception("No records found",503);
    }

    return $file;
    
}

function set_avatar($filename){
    //send request
    //fetch previous filename
    $prevAvatarFile=fetch_avatar();
    //remove previous file
    remove_avatar_file($prevAvatarFile);
    //set new file
    $conn = request_connection();
    $stmt = $conn->prepare('UPDATE User SET avatarFile = :file WHERE username = :username');
    //set to null if none set
    $filename = ($filename != '') ? $filename : NULL;

    $stmt->execute(array(':file' => $filename,':username' => $_SESSION['user']));
    $affectedRows = $stmt->rowCount();

    if ($affectedRows > 0){
        return true;
    }else{
        throw new Exception("Failed to write to database",501);
    }
}

function set_thumbnail($filename){
    
    
}

function remove_avatar(){ 
    //remove entry from database
    set_avatar("");
}

function get_notifications($from,$limit){
    //if from 0, set to curent timestamp
    $_POST["original"]=array($from,$limit);
    $from = (int)($from!=0) ? $from : time();
    //if number 0, set infinity
    $limit = (int)($limit>0) ? $limit : PHP_INT_MAX;
    $_POST['additional']=array($from,$limit,$_SESSION["user"]);
    //fetch records
    $conn = request_connection();
    $stmt = $conn->prepare('SELECT `idNotification`,`type`,`title`,`message`,UNIX_TIMESTAMP(`timestamp`),`opened` FROM `Notification` WHERE `user` = :username AND UNIX_TIMESTAMP(`timestamp`) < :timestamp ORDER BY `timestamp` DESC LIMIT :limit');
    $stmt->bindParam(':username',$_SESSION["user"],PDO::PARAM_STR);
    $stmt->bindParam(':timestamp',$from,PDO::PARAM_INT);
    $stmt->bindParam(':limit',$limit,PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //format to correct data style
    foreach($result as &$notification){
        $timestamp = $notification['UNIX_TIMESTAMP(`timestamp`)'];
        $notification['timestamp']=$timestamp;
        //remove unsused column
        unset($notification['UNIX_TIMESTAMP(`timestamp`)']);
    }
    return $result;
}

function mark_as_read($msgIDs){
    $where_in = implode(',', $msgIDs);
    $conn = request_connection();
    $stmt = $conn->prepare('UPDATE Notification SET `opened` = 1 WHERE `idNotification` IN (:msgIDs)');
    $stmt->bindParam(':msgIDs',$where_in,PDO::PARAM_STR);
    $stmt->execute();
    $affectedRows = $stmt->rowCount();
    if ($affectedRows >= count($msgIds)){
        return true;
    }else{
       throw new Exception("Failed to write all to database",501); 
    }
}

function create_notification($user,$type,$title,$message){
    $conn = request_connection();
    $stmt = $conn->prepare('INSERT INTO Notification (user,type,title,message) VALUES(:user,:type,:title,:message)');
    $stmt->execute(array(':user' => $_SESSION["user"],':type' => $type,':title' => $title,':message' => $message));
    $affectedRows = $stmt->rowCount();
    if ($affectedRows >= 1){
        return true;
    }else{
       throw new Exception("Failed to write to database",501); 
    }
}

function update_notification($id,$update){
    //update to be passed in as dictionary of column:updatedValue
    
    foreach($update as $column => $value){
        update_notification_column($id,$column,$value);
    }
    
}

function update_notification_column($id,$column,$value){
    $allowedColumns = ["type","title","message"];
    if (in_array($column,$allowedColumns,true)){
        $conn = request_connection();
        $sql = 'UPDATE Notification SET ' + $column + ' = :value, timestamp = CURRENT_TIMESTAMP WHERE idNotification = :id';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':value' => $value, ':id' => $id));
        $affectedRows = $stmt->rowCount();
        if ($affectedRows >= 1){
            return true;
        }else{
           throw new Exception("Failed to write to database",501); 
        }
    }else{
        throw new Exception('Invalid column value',402);
    }
}

function create_friend_request($userTo){
    $conn = request_connection();
    $stmtCheck = $conn->prepare('SELECT * FROM Friend WHERE (user_sender = :userTo AND user_reciever = :userFrom) OR (user_sender = :userFrom AND user_reciever = :userTo)');
    $stmtCheck->execute(array(':userFrom' => $_SESSION["user"],':userTo' => $userTo));
    $result = $stmtCheck->fetchAll(PDO::FETCH_ASSOC);
    foreach ($result as $entry){
        if ($entry['user_sender']==$userTo){
            switch($entry['accepted']){
                case true:
                    throw new Exception("Already friends",700);
                    break;
                case null:
                case undefined:
                    throw new Exception("Request already sent by current recciever",702);
                    break;
                case false;
                    throw new Exception("Reciever has blocked sender",701);
                    break;
                default:
                    throw new Exception("Request already sent by current reciever",703);
                    break;
            }
        }else{
           switch($entry['accepted']){
                case true:
                    throw new Exception("Already friends",700);
                    break;
                case null:
                case undefined:
                   throw new Exception("Request already sent by current sender",702);
                   break;
                case false;
                    throw new Exception("Current sender has blocked reciever",704);
                    break;
                default:
                    throw new Exception("Request already sent by current sender",702);
                    break;
            } 
        }
    }
    $stmt = $conn->prepare('INSERT INTO Friend (user_sender,user_reciever) VALUES(:userFrom,:userTo)');
    $stmt->execute(array(':userFrom' => $_SESSION["user"],':userTo' => $userTo));
    $affectedRows = $stmt->rowCount();
    if ($affectedRows >= 1){
        return true;
    }else{
       throw new Exception("Failed to write to database",501); 
    }
}

function delete_friend_request($userTo){
    $conn = request_connection();
    $stmt = $conn->prepare('DELETE FROM Friend WHERE user_sender = :userFrom AND user_reciever = :userTo AND accepted IS NULL');
    $stmt->execute(array(':userFrom' => $_SESSION["user"],':userTo' => $userTo));
    $affectedRows = $stmt->rowCount();
    if ($affectedRows >= 1){
        return true;
    }else{
       throw new Exception("Failed to write to database",501); 
    }
}

function accept_friend_request($userFrom){
    $conn = request_connection();
    $stmt = $conn->prepare('UPDATE Friend SET accepted = 1 WHERE user_sender = :userFrom AND user_reciever = :userTo AND accepted IS NULL');
    $stmt->execute(array(':userFrom' => $userFrom,':userTo' => $_SESSION["user"]));
    $affectedRows = $stmt->rowCount();
    if ($affectedRows >= 1){
        return true;
    }else{
       throw new Exception("Failed to write to database",501); 
    }
}

function refuse_friend_request($userFrom){
    $conn = request_connection();
    $stmt = $conn->prepare('DELETE FROM Friend WHERE user_sender = :userFrom AND user_reciever = :userTo AND accepted IS NULL');
    $stmt->execute(array(':userFrom' => $userFrom,':userTo' => $_SESSION["user"]));
    $affectedRows = $stmt->rowCount();
    if ($affectedRows >= 1){
        return true;
    }else{
       throw new Exception("Failed to write to database",501); 
    }
}

function delete_friend($user){
    $conn = request_connection();
    $stmt = $conn->prepare('DELETE FROM Friend WHERE ((user_sender = :user AND user_reciever = :user2) OR (user_sender = :user2 AND user_reciever = :user)) AND accepted = 1');
    $stmt->execute(array(':user' => $_SESSION["user"],':user2' => $user));
    $affectedRows = $stmt->rowCount();
    if ($affectedRows >= 1){
        return true;
    }else{
       throw new Exception("Failed to write to database",501); 
    }
}

function mark_fr_as_read($userFrom){
    $conn = request_connection();
    $stmt = $conn->prepare('UPDATE Friend SET opened = 1 WHERE user_sender = :userFrom AND user_reciever = :userTo');
    $stmt->execute(array(':userFrom' => $userFrom,':userTo' => $_SESSION["user"]));
    $affectedRows = $stmt->rowCount();
    if ($affectedRows >= 1){
        return true;
    }else{
       throw new Exception("Failed to write to database",501); 
    }
}

function block_user($user){
    $conn = request_connection();
    $stmtCheck = $conn->prepare('DELETE FROM Friend WHERE ((user_sender = :userTo AND user_reciever = :userFrom) OR (user_sender = :userFrom AND user_reciever = :userTo)) AND (accepted = 1 OR accepted IS NULL)');
    $stmtCheck->execute(array(':userFrom' => $_SESSION["user"],':userTo' => $user));
    $stmt = $conn->prepare('INSERT INTO Friend (user_sender,user_reciever,accepted,opened) VALUES(:userFrom,:userTo,0,1)');
    $stmt->execute(array(':userFrom' => $_SESSION["user"],':userTo' => $user));
    $affectedRows = $stmt->rowCount();
    if ($affectedRows >= 1){
        return true;
    }else{
       throw new Exception("Failed to write to database",501); 
    }
}

function unblock_user($user){
    $conn = request_connection();
    $stmt = $conn->prepare('DELETE FROM Friend WHERE user_sender = :userFrom AND user_reciever = :userTo AND accepted = 0');
    $stmt->execute(array(':userFrom' => $_SESSION["user"],':userTo' => $user));
    $affectedRows = $stmt->rowCount();
    if ($affectedRows >= 1){
        return true;
    }else{
       throw new Exception("Failed to write to database",501); 
    }
}

function get_friends($user){
    if (!$user){
        $user=$_SESSION["user"];
    }
    $conn = request_connection();
    $stmt = $conn->prepare('SELECT user_sender,user_reciever FROM Friend WHERE (user_sender = :user OR user_reciever = :user) AND accepted=1');
    $stmt->execute(array(':user'=>$user));
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $friendsList = [];
    //extract friends from friend links
    foreach ($result as $friendLink){
        if ($user==$friendLink['user_sender']){
            array_push($friendsList,$friendLink['user_reciever']);
        }else{
            array_push($friendsList,$friendLink['user_sender']);
        }
    }
    //sort list alphabetically
    if (count($friendsList)>0){
        sort($friendsList);
    }
    return $friendsList;
}

function get_friend_requests(){
    $conn = request_connection();
    $stmt = $conn->prepare('SELECT user_sender FROM Friend WHERE user_reciever = :user AND accepted IS NULL');
    $stmt->execute(array(':user'=>$_SESSION["user"]));
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $friendRequestList = [];
    //extract friends from friend links
    foreach ($result as $friendRequest){
        array_push($friendRequestList,$friendRequest['user_sender']);
    }
    //sort list alphabetically
    if (count($friendRequestList)>0){
        sort($friendRequestList);
    }
    return $friendRequestList;
}

function get_sent_friend_requests(){
    $conn = request_connection();
    $stmt = $conn->prepare('SELECT user_reciever FROM Friend WHERE user_sender = :user AND accepted IS NULL');
    $stmt->execute(array(':user'=>$_SESSION["user"]));
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $friendRequestSentList = [];
    
    //extract friends from friend links
    foreach ($result as $friendRequest){
        array_push($friendRequestSentList,$friendRequest['user_reciever']);
    }
    //sort list alphabetically
    if (count($friendRequestSentList)>0){
        sort($friendRequestSentList);
    }
    return $friendRequestSentList;
}

function get_blocked_users(){
    $conn = request_connection();
    $stmt = $conn->prepare('SELECT user_reciever FROM Friend WHERE user_sender = :user AND accepted = 0');
    $stmt->execute(array(':user'=>$_SESSION["user"]));
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $blockedList = [];
    //extract friends from friend links
    foreach ($result as $blockedUser){
        array_push($blockedList,$blockedUser['user_reciever']);
    }
    //sort list alphabetically
    if (count($blockedList)>0){
        sort($blockedList);
    }
    return $blockedList;
}

function get_all_users($needle){
    $conn = request_connection();
    $stmt = $conn->prepare("SELECT username FROM User WHERE username LIKE :needle AND username != :user ORDER BY username ASC");
    $stmt->execute(array(':needle'=>'%'.$needle.'%', ':user'=>$_SESSION["user"]));
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $resultList = [];
    //extract friends from friend links
    foreach ($result as $user){
        array_push($resultList,$user['username']);
    }
    return $resultList;
}

function get_exact_users($needle){
        $conn = request_connection();
    $stmt = $conn->prepare("SELECT username FROM User WHERE username LIKE :needle AND username != :user ORDER BY username ASC");
    $stmt->execute(array(':needle'=>$needle, ':user'=>$_SESSION["user"]));
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $resultList = [];
    //extract friends from friend links
    foreach ($result as $user){
        array_push($resultList,$user['username']);
    }
    return $resultList;
}
?>
