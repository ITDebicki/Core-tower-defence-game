<?php

require_once("dbConnection.php");
require_once("errorReporting.php");
require_once("generalFunctions.php");




function create_account($jsonData){  
    try{
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
        $conn = create_connection();
        $stmt = $conn->prepare('INSERT INTO User (username, hash, email) VALUES(:username,:hash,:email)');
        $success = $stmt->execute(array(':username' => $user, ':hash' => $hash, ':email' => $email));
        if (!$success){
             throw new Exception("Username or email already used",200);  
        }
        $affectedRows = $stmt->rowCount();
        //close connection
        $success = close_connection($conn);
        
        if ($affectedRows > 0){
            return true;
        }else{
            throw new Exception("Failed to write to database",501);
        }
    } catch(Exception $e){
        throw $e;
    }
}

function delete_account(){
    try{
        //firstly remove avatar file
        try{
            remove_avatar();
        }catch (Exception $e){
            continue;
        }
        
        //delete account
        $conn = create_connection();
        $stmt = $conn->prepare('DELETE FROM User WHERE username = :username');
        $stmt->execute(array(':username' => $_SESSION["user"]));
        $affectedRows = $stmt->rowCount();

        //close connection
        $success = close_connection($conn);

        if ($affectedRows > 0){
            //log out
            successful_logout();
            return true;
        }else{
            throw new Exception("Failed to write to database",501);
        }
    } catch(Exception $e){
        throw $e;
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
    $conn = create_connection();
    $stmt = $conn->prepare('SELECT hash FROM User WHERE username = :username');
    $stmt->execute(array(':username' => $user));
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if(count($result)!=0){
        $storedHash = $result[0]['hash'];
    }
    //close connection
    $success = close_connection($conn);

    return password_verify($password,$storedHash);   
}

function log_login($user){
    //send request
    try{
        $conn = create_connection();
        $stmt = $conn->prepare('INSERT INTO LastLogin (user) VALUES(:username)');
        $stmt->execute(array(':username' => $user));
        $affectedRows = $stmt->rowCount();

        //close connection
        $success = close_connection($conn);

        if ($affectedRows > 0){
            return true;
        }else{
            throw new Exception("Failed to write to database",501);
        }
    } catch(Exception $e){
        throw $e;
    }     
    
}

function fetch_avatar(){
    //send request
    try{
        $file= null;
        $conn = create_connection();
        $stmt = $conn->prepare('SELECT avatarFile FROM User WHERE username=:username');
        $stmt->execute(array(':username' => $_SESSION["user"]));
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if(count($result)!=0){
            $file = $result[0]['avatarFile'];
        }else{
            throw new Exception("No records found",503);
        }

        //close connection
        $success = close_connection($conn);

        return $file;
    } catch(Exception $e){
        throw $e;
    }  
    
}

function set_avatar($filename){
  //send request
    try{
        //fetch previous filename
        $prevAvatarFile=fetch_avatar();
        //remove previous file
        remove_avatar_file($prevAvatarFile);
        //set new file
        $conn = create_connection();
        $stmt = $conn->prepare('UPDATE User SET avatarFile = :file WHERE username = :username');
        //set to null if none set
        $filename = ($filename != '') ? $filename : NULL;
        
        $stmt->execute(array(':file' => $filename,':username' => $_SESSION['user']));
        $affectedRows = $stmt->rowCount();

        //close connection
        $success = close_connection($conn);

        if ($affectedRows > 0){
            return true;
        }else{
            throw new Exception("Failed to write to database",501);
        }
    } catch(Exception $e){
        throw $e;
    }    
}

function set_thumbnail($filename){
    
    
}

function remove_avatar(){
    try{
        //remove entry from database
        set_avatar("");
    } catch(Exception $e){
       throw $e; 
    }
}

function get_notifications($from,$limit){
    try{
        //if from 0, set to curent timestamp
        $_POST["original"]=array($from,$limit);
        $from = (int)($from!=0) ? $from : time();
        //if number 0, set infinity
        $limit = (int)($limit>0) ? $limit : PHP_INT_MAX;
        $_POST['additional']=array($from,$limit,$_SESSION["user"]);
        //fetch records
        $conn = create_connection();
        $stmt = $conn->prepare('SELECT `idNotification`,`type`,`title`,`message`,UNIX_TIMESTAMP(`timestamp`),`read` FROM `Notification` WHERE `user` = :username AND UNIX_TIMESTAMP(`timestamp`) < :timestamp ORDER BY `timestamp` DESC LIMIT :limit');
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
    } catch(Exception $e){
        throw $e;
    }
}

function mark_as_read($msgIDs){
    try{
        $where_in = implode(',', $msgIDs);
        $conn = create_connection();
        $stmt = $conn->prepare('UPDATE Notification SET `opened` = 1 WHERE `idNotification` IN (:msgIDs)');
        $stmt->bindParam(':msgIDs',$where_in,PDO::PARAM_STR);
        $stmt->execute();
        $affectedRows = $stmt->rowCount();
        if ($affectedRows >= count($msgIds)){
            return true;
        }else{
           throw new Exception("Failed to write all to database",501); 
        }
    } catch(Exception $e){
        throw $e;
    }
}

?>
