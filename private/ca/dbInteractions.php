<?php

require_once("dbConnection.php");
require_once("errorReporting.php");
require_once("generalFunctions.php");
require_once("fileManipulation.php");
/**
 * Create a new user account
 * @author Ignacy Debicki
 * @param  object  $jsonData Dictionary containing values for keys: username,password,email
 * @return boolean True if creation was succesfull
 */
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
/**
 * Deletes the current session user's account
 * @author Ignacy Debicki
 * @return boolean If deletion was succesfull
 */
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
/**
 * Returns if the password for the username is correct
 * @author Ignacy Debicki
 * @param  object  $jsonData Dictionary containing values for keys: username,password
 * @return boolean If password is correct
 */
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
/**
 * Logs the login of the user in the database
 * @author Ignacy Debicki
 * @param  string  $user User to log the login for
 * @return boolean If logging was succesfull
 */
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
/**
 * Gets the avatar file name for th euser
 * @author Ignacy Debicki
 * @param  string $user Username of user
 * @return string Name of avatar file
 */
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
        $file="";
    }

    return $file;
    
}
/**
 * Sets a new avatar and deletes the previous avatar
 * @author Ignacy Debicki
 * @param  string  $filename filename of new avatar
 * @return boolean If update was succesfull
 */
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

/**
 * Removes the user's avatar and sets the default avatar
 * @author Ignacy Debicki
 * @return boolean If update was succesfull
 */
function remove_avatar(){ 
    //remove entry from database
    return set_avatar("");
}
/**
 * Gets notifications for the current session user
 * @author Ignacy Debicki
 * @param  integer $to    Timestamp to fetch notifications up to
 * @param  integer $limit Maximum number of records to return
 * @return array   Array of notification objects with keys: idNotification, type, title, message, timestamp, opened
 */
function get_notifications($to,$limit){
    //if from 0, set to curent timestamp
    $_POST["original"]=array($to,$limit);
    $to = (int)($to!=0) ? $to : time();
    //if number 0, set infinity
    $limit = (int)($limit>0) ? $limit : PHP_INT_MAX;
    $_POST['additional']=array($to,$limit,$_SESSION["user"]);
    //fetch records
    $conn = request_connection();
    $stmt = $conn->prepare('SELECT `idNotification`,`type`,`title`,`message`,UNIX_TIMESTAMP(`timestamp`) AS timestamp,`opened` FROM `Notification` WHERE `user` = :username AND UNIX_TIMESTAMP(`timestamp`) < :timestamp ORDER BY `timestamp` DESC LIMIT :limit');
    $stmt->bindParam(':username',$_SESSION["user"],PDO::PARAM_STR);
    $stmt->bindParam(':timestamp',$to,PDO::PARAM_INT);
    $stmt->bindParam(':limit',$limit,PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $result;
}
/**
 * Marks notification as read
 * @author Ignacy Debicki
 * @param  array   $msgIDs Array of notification ids to set as read
 * @return boolean If update was succesfull
 */
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
/**
 * Creates and sends a new notification to a user
 * @author Ignacy Debicki
 * @param  string  $user    Username of user
 * @param  string  $type    Type of notification
 * @param  string  $title   Title of message
 * @param  string  $message Message content of notification
 * @return boolean If creation and sending was succesfull
 */
function create_notification($user,$type,$title,$message){
    $conn = request_connection();
    $stmt = $conn->prepare('INSERT INTO Notification (user,type,title,message) VALUES(:user,:type,:title,:message)');
    $stmt->execute(array(':user' => $user,':type' => $type,':title' => $title,':message' => $message));
    $affectedRows = $stmt->rowCount();
    if ($affectedRows >= 1){
        return true;
    }else{
       throw new Exception("Failed to write to database",501); 
    }
}
/**
 * Updates a value of a notification
 * @author Ignacy Debicki
 * @param integer $id     Identifier of notification to update
 * @param object  $update Dictionary of columns and values to update in format {field => $value}
 * @return boolean If updates were succesfull
 */
function update_notification($id,$update){
    //update to be passed in as dictionary of field:updatedValue
    
    foreach($update as $column => $value){
        update_notification_column($id,$column,$value);
    }
    return true;
}
/**
 * Updates a field for the notification
 * @author Ignacy Debicki
 * @param  integer $id     ID of notification to update
 * @param  string  $column Name of field to update. Can only have values of: type,title or message
 * @param  string  $value  Value to chenge the field to
 * @return boolean If update was succesfull
 */
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
/**
 * Creates and sends a new friend request
 * @author Ignacy Debicki
 * @param  string  $userTo Username of user
 * @return boolean If sending was succesfull
 */
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
        $msg = $_SESSION["user"] . " has sent you a friend request. Go to manage friends to view the request";
        $title = $_SESSION["user"] . " has sent you a friend request!";
        return  create_notification($userTo,"friendRequest",$title,$msg);
    }else{
       throw new Exception("Failed to write to database",501); 
    }
}
/**
 * Deletes an unaccepted friend request
 * @author Ignacy Debicki
 * @param  string  $userTo User the invite ws originally sent to
 * @return boolean If deletion ws succesfull
 */
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
/**
 * Accepts a friend request
 * @author Ignacy Debicki
 * @param  string  $userFrom Who the friend request was from
 * @return boolean If acceptation was succesfull
 */
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
/**
 * Refuses a freind request from a user
 * @author Ignacy Debicki
 * @param  string  $userFrom User the request is from
 * @return boolean IF refusal was succesfull
 */
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
/**
 * Deletes a friend
 * @author Ignacy Debicki
 * @param  string  $user Username of friend to delete
 * @return boolean If deletion was succesfull
 */
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
/**
 * Marks a friend request as read
 * @author Ignacy Debicki
 * @param  string  $userFrom The user the request is from
 * @return boolean if update was succesfull
 */
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
/**
 * Blocks the specified user from interacting with the curent user
 * @author Ignacy Debicki
 * @param  string  $user Username of user to block
 * @return boolean If block was succesfull
 */
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
/**
 * Unblocks a user
 * @author Ignacy Debicki
 * @param  string  $user Username of user to unblock
 * @return boolean If succesfull
 */
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
/**
 * Fetches friends of a user
 * @author Ignacy Debicki
 * @param  string $user User to fetch friends for. Pass "" for the current session's user
 * @return array  Array of friend's usernames
 */
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
/**
 * Gets all friend requests that have not been replied to for the current user
 * @author Ignacy Debicki
 * @return array Array of usernames from whom the user has recieved friend requests from and not replied
 */
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
/**
 * Gets all friend requests that have been sent by the user and have not been replied to
 * @author Ignacy Debicki
 * @return array Array of usernames requests have been sent to
 */
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
/**
 * Gets a list of all users blocked by the current user
 * @author Ignacy Debicki
 * @return array Array of usernames blocked by the current user
 */
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
/**
 * Search through the username database with a rough search using a search phrase. Returns all usernames containing the search stirng as a substring
 * @author Ignacy Debicki
 * @param  string $needle The search string
 * @return array  Array of usernames matched by the search
 */
function get_all_users($needle){
    $conn = request_connection();
    $stmt = $conn->prepare("SELECT username FROM User WHERE CONVERT(username USING latin1) COLLATE latin1_swedish_ci LIKE :needle AND username != :user ORDER BY username ASC");
    $stmt->execute(array(':needle'=>'%'.$needle.'%', ':user'=>$_SESSION["user"]));
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $resultList = [];
    //extract friends from friend links
    foreach ($result as $user){
        array_push($resultList,$user['username']);
    }
    return $resultList;
}
/**
 * Searches through the username database o find users which match exactly (case insensitive) to the search string
 * @author Ignacy Debicki
 * @param  string $needle The search string
 * @return array  Array of usernames which have matched the search
 */
function get_exact_users($needle){
    $conn = request_connection();
    $stmt = $conn->prepare("SELECT username FROM User WHERE CONVERT(username USING latin1) COLLATE latin1_swedish_ci = :needle AND username != :user ORDER BY username ASC");
    $stmt->execute(array(':needle'=>$needle, ':user'=>$_SESSION["user"]));
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $resultList = [];
    //extract friends from friend links
    foreach ($result as $user){
        array_push($resultList,$user['username']);
    }
    return $resultList;
}
/**
 * Adds high score to the database
 * @author Ignacy Debicki
 * @param string  $map   Identifier of map that the score was achieved on
 * @param integer $score Score achieved
 * @return integer 0 If not new high score. Returns new all time rank if is new highscore
 */
function add_score($map,$score){
    $isNewHighscore = is_high_score($map,$score);
    $_POST["isNew"]=$isNewHighscore;
    if ($isNewHighscore==1){
        $conn = request_connection();
        $stmt = $conn->prepare('UPDATE Score SET scoreVal=:score WHERE user=:user AND map=:map');
        $stmt->execute(array(':user' => $_SESSION["user"],':map' => $map,':score' => $score));
        $affectedRows = $stmt->rowCount();
        if ($affectedRows >= 1){
            return user_rank($_SESSION["user"],$map);
        }else{
           throw new Exception("Failed to write to database",501); 
        }
    }elseif($isNewHighscore == 2){
        $conn = request_connection();
        $stmt = $conn->prepare('INSERT INTO Score (user,map,scoreVal) VALUES(:user,:map, :score)');
        $stmt->execute(array(':user' => $_SESSION["user"],':map' => $map,':score' => $score));
        $affectedRows = $stmt->rowCount();
        if ($affectedRows >= 1){
            $_POST["ad4"]=[$_SESSION["user"],$map,array("from"=>0,"to"=>0)];
            return user_rank($_SESSION["user"],$map,array("from"=>0,"to"=>0));
        }else{
           throw new Exception("Failed to write to database",501); 
        }
    }else{
        return false;
    }
    
}
/**
 * Checks if the score for that map for that user is a new highscore for the user
 * @author Ignacy Debicki
 * @param  integer $map   Map identifier of map fow which to check
 * @param  integer $score Score to check against
 * @return integer returns 0 if false, 1 if higher than previous score and 2 if no previous score exists
 */
function is_high_score($map,$score){
    $conn = request_connection();
    $stmt = $conn->prepare("SELECT scoreVal FROM Score WHERE user = :user AND map = :map");
    $stmt->execute(array(':map'=>$map, ':user'=>$_SESSION["user"]));
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if(isset($result[0]["scoreVal"])){
        if ($result[0]["scoreVal"]<$score){
            return 1;
        }else{
            return 0;
        }
    }else{
        return 2;
    }
}
/**
 * Returns the rank of the user for the specified map in the specified time period.
 * @author Ignacy Debicki
 * @param  string  $user    Username of user to rank
 * @param  integer $map     Map id of which map to rank user on
 * @param object $timespan  Dictionary containing from and to timespan in format ["from" => $from, "to" => $to]. Set $to to 0 if current date is desired. Both ranges are inclusive
 * @return integer Rank of the user. 0 If user does not have high score on the map
 */
function user_rank($user,$map,$timespan){
    $sql = "SELECT 
                                IF((SELECT idScore FROM Score WHERE BINARY user=:user AND map=:map)>0,count(*)+1,0)
                            AS rank FROM 
                                (SELECT count(*) FROM Score WHERE map=:map 
                                AND (scoreVal > (SELECT scoreVal AS val FROM Score WHERE BINARY user=:user AND map=:map)
                                OR
                                (
                                scoreVal = (SELECT scoreVal FROM Score WHERE BINARY user=:user AND map=:map)
                                AND 
                                timestamp <= (SELECT timestamp FROM Score WHERE BINARY user=:user AND map=:map)
                                AND user != :user
                                )
                                ) AND timestamp >= :from AND timestamp <= :to GROUP BY idScore)
                            AS groupHigher";
    if ($timespan["to"]==0 || $timespan["to"]==null){
        $sql = str_replace(":to",'CURRENT_TIMESTAMP()',$sql);
    }
    if (!$timespan["from"]){
        $timespan["from"]=0;
    }
    $_POST["a"]=[$user,$map,$timespan];
    $conn = request_connection();
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':map',$map,PDO::PARAM_INT);
    $stmt->bindValue(':from',$timespan["from"],PDO::PARAM_INT);
    if (strpos($sql,":to")){
        $stmt->bindValue(':to',$timespan["to"],PDO::PARAM_INT);
    }
    $stmt->bindValue(':user',$user,PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if(isset($result[0]["rank"])){
        return $result[0]["rank"];
    }else{
        throw new Exception("Failed to retrieve data from database",502);
    }
}

/**
 * Gets all of the highscores and all time rank for each map for a specific user
 * @author Ignacy Debicki
 * @param  string $user Username of user to get highscores
 * @return object Dictionary of $map => [score => $score, rank => $rank, timestamp => $timestamp] for each map
 */
function get_user_high_scores($user){
    $conn = request_connection();
    $stmt = $conn->prepare("SELECT map,scoreVal,UNIX_TIMESTAMP(timestamp) AS timestamp FROM Score WHERE BINARY user=:user");
    $stmt->execute(array(':user'=>$user));
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $highScoreList = [];
    
    foreach($result as $highscore){
        $score = $highscore["scoreVal"];
        $map = $highscore["map"];
        $rank = user_rank($user,$map,array("from"=>1,"to"=>0));
        $highScoreList[$map]=array("score"=>$score,"rank"=>$rank,"timestamp"=>$highscore["timestamp"]);
    }
    
    return $highScoreList;
}

/**
 * Gets a user's highscore for a specific map in the specific timespan
 * @author Ignacy Debicki
 * @param  string  $user Username of user to fetch
 * @param  integer $map  Id of map
 * @param  integer $from UNIX timestamp from (inclusive)
 * @param  integer $to   Unix timestamp to (inclusive) - Set to 0 for current timestamp
 * @return array   Array of values ["score"=>score,"rank"=>rank,"timestamp"=>timestamp]
 */
function get_user_high_score_for_map($user,$map,$from,$to){
    $sql='SELECT scoreVal AS score, UNIX_TIMESTAMP(timestamp) AS timestamp FROM Score WHERE user = :user AND map = :map AND timestamp >= :from AND timestamp <= :to';
    if ($to==0 || !$to){
        $_POST["toReplace"]=true;
        $sql = str_replace(":to",'CURRENT_TIMESTAMP()',$sql);
    }
    $conn = request_connection();
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':map',$map,PDO::PARAM_INT);
    $stmt->bindValue(':from',$from,PDO::PARAM_INT);
    if (strpos($sql,":to")){
        $stmt->bindValue(':to',$to,PDO::PARAM_INT);
    }
    $stmt->bindValue(':user',$user,PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($result)==0){
        return [];
    }
    if (!$to){
        $to=0;
    }
    if(!$from){
        $from=0;
    }
    $rank = user_rank($user,$map,["from"=>$from,"to"=>$to]);
    try{
        $result[0]["rank"]=$rank;
        return $result[0];
    }catch(Exception $e){
        return [];
    }
    
}
/**
 * Gets all maps and returns them in an array acording to their levelNo
 * @author Ignacy Debicki
 * @return array Array ordered by levelNo with values of format:
 ["id"=>$id,"name"=>$name,"description"=>$description,"file"=>$file,"image"=>$image,"levelNo" => $levelNo]
 */
function get_map_list(){
    $conn = request_connection();
    $stmt = $conn->prepare("SELECT idMap AS id, mapName AS name, mapDescription AS description, mapFile AS file, mapImage AS image, levelNo FROM Map ORDER BY levelNo ASC");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}
/**
 * Gets the highscore list for the map in that timespan
 * @author Ignacy Debicki
 * @param  integer $map   Id of map for which to fetch the highscore
 * @param  integer $limit Maximum no. of records to fetch
 * @param  integer $from  Timestamp from when to find rank
 * @param  integer $to    Timestamp up to when to find rank
 * @return array   Array of highscores arranged by rank for the map in the format [{"user"=>$user,"score"=>score,"timestamp" => timestamp}]
 */
function get_highscore_list($map,$limit,$from,$to){
    $sql='SELECT user, scoreVal AS score, UNIX_TIMESTAMP(timestamp) AS timestamp FROM Score WHERE map = :map AND timestamp >= :from AND timestamp <= :to ORDER BY scoreVal DESC, timestamp ASC LIMIT :limit';
    if ($to==0){
        $sql = str_replace(":to",'CURRENT_TIMESTAMP()',$sql);
    }
    $conn = request_connection();
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':map',$map,PDO::PARAM_INT);
    $stmt->bindValue(':from',$from,PDO::PARAM_INT);
    if (strpos($sql,":to")){
        $stmt->bindValue(':to',$to,PDO::PARAM_INT);
    }
    $stmt->bindValue(':limit',$limit,PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}
/**
 * Fetches a specific save file for the current user
 * @author Ignacy Debicki
 * @param  integer $saveId Identifier of the save file to fetch
 * @return object  Dicitonary of save file
 */
function get_save_data($saveID){
    $conn = request_connection();
    $stmt = $conn->prepare('SELECT fileLocation FROM Save WHERE user = :user AND idSave = :id');
    $stmt->execute(array(":user" => $_SESSION["user"], ":id" => $saveID));
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $fileLocation = $result[0]["fileLocation"];
    $fileContents = file_get_contents('/var/www/private/ca/saves/'.$fileLocation);
    return json_Decode($fileContents);
}
/**
 * Fetches all save files for the current user
 * @author Ignacy Debicki
 * @return array Array of save objects containing values ["id" => id, "lastUpdate" => lastUpdate, "name" => name, "thumbnail" => "thumbnail" => thumbnail, "map"=>map]
 */
function get_saves(){
    $conn = request_connection();
    $stmt = $conn->prepare('SELECT idSave AS id, UNIX_TIMESTAMP(lastUpdate) AS lastUpdate, name, thumbnail, map FROM Save WHERE user = :user ORDER BY lastUpdate DESC');
    $stmt->execute(array(":user" => $_SESSION["user"]));
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}
/**
 * Updates a save
 * @author Ignacy Debicki
 * @param  integer $saveID    Id of save to update
 * @param  object  $newData   New save data
 * @param  string  $thumbnail Filename of new thumbnail
 * @return boolean If update was succesfull
 */
function update_save($saveID,$newData,$thumbnail){
    $conn = request_connection();
    $stmt = $conn->prepare('SELECT fileLocation,thumbnail FROM Save WHERE user = :user AND idSave = :id');
    $stmt->execute(array(":user" => $_SESSION["user"], ":id" => $saveID));
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $fileLocation = $result[0]["fileLocation"];
    $oldThumbnailFile = $result[0]["thumbnail"];
    if (file_put_contents('/var/www/private/ca/saves/'.$fileLocation,json_encode($newData)) == false){
        throw new Exception("Failed to write file",608);
    }else{
        //Not curently using thumbnail
        /**remove_thumbnail_file($oldThumbnailFile);
        $stmt = $conn->prepare('UPDATE Save SET thumbnail = :thumbnail WHERE user = :user AND idSave = :id');
        $stmt->execute(array(":user" => $_SESSION["user"], ":id" => $saveID, ":thumbnail" => $thumbnail));
        $affectedRows = $stmt->rowCount();
        if ($affectedRows>0){
            return true;
        }else{
            throw new Exception("Failed to write to database",501);
        }*/
        return true;
        
    }
}
/**
 * Creates a new save
 * @author Ignacy Debicki
 * @param  object  $newData   New save data
 * @param  string  $name      Name of save
 * @param  string  $thumbnail Filename of thumbnail
 * @param  integer $map       Map id of map the save was created from
 * @return boolean If succesfull
 */
function create_save($newData,$name,$thumbnail,$map){
    //generate unique filename
    $uniqueFilename = false;
    $filename=null;
    $repetitions = 0;
    do{
        $filename = generate_filename(10) . ".msd";
        $uniqueFilename = !(file_exists('/var/www/private/ca/saves/' . $filename));
        $repetitions++;
    }while($uniqueFilename==false && $repetitions < 5);
    
    if (file_put_contents('/var/www/private/ca/saves/'.$filename,json_encode($newData)) == false){
        throw new Exception("Failed to write file",608);
    }
    $conn = request_connection();
    $stmt = $conn->prepare('INSERT INTO Save (user,name,fileLocation,thumbnail,map) VALUES (:user, :name, :fileLocation, :thumbnail, :map)');
    $stmt->execute(array(":user" => $_SESSION["user"], ":name" => $name, ":fileLocation" => $filename, ":thumbnail" => $thumbnail, ":map" => $map));
    $affectedRows = $stmt->rowCount();
    if ($affectedRows > 0){
        return true;
    }else{
        throw new Exception("Failed to write to database",501);
    }
}
/**
 * Updates the name of a save
 * @author Ignacy Debicki
 * @param  integer $saveID  Identifier of save
 * @param  string  $newName New name of the map
 * @return boolean If succesfull
 */
function update_name($saveID, $newName){
    $conn = request_connection();
    $stmt = $conn->prepare('UPDATE Save SET name = :name WHERE user = :user AND idSave = :id');
    $stmt->execute(array(":user" => $_SESSION["user"], ":id" => $saveID, ":name" => $newName));
    $affectedRows = $stmt->rowCount();
    if ($affectedRows>0){
        return true;
    }else{
        throw new Exception("Failed to write to database",501);
    }
}
/**
 * Deletes a save
 * @author Ignacy Debicki
 * @param  integer $saveID Identifier of save to delete
 * @return boolean If succesfull
 */
function delete_save($saveID){
    $conn = request_connection();
    $stmt = $conn->prepare('SELECT fileLocation,thumbnail FROM Save WHERE user = :user AND idSave = :id');
    $stmt->execute(array(":user" => $_SESSION["user"], ":id" => $saveID));
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $fileLocation = $result[0]["fileLocation"];
    $thumbnailFile = $result[0]["thumbnail"];
    remove_save_file($fileLocation);
    //remove_thumbnail_file($thumbnailFile);
    $stmt = $conn->prepare('DELETE FROM Save WHERE user = :user AND idSave =:id');
    $stmt->execute(array(":user" => $_SESSION["user"], ":id" => $saveID));
    $affectedRows = $stmt->rowCount();
    if ($affectedRows>0){
        return true;
    }else{
        throw new Exception("Failed to write to database",501);
    }
}

?>
