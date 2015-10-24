<?php
require_once("../../../private/ca/sessionFunctions.php");
require_once("../../../private/ca/generalFunctions.php");
require_once("../../../private/ca/dbInteractions.php");
require_once("../../../private/ca/errorReporting.php");
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');
try{
    if (is_logged_in() && is_session_valid()){
        //already logged in
        log_login($user);
        echo '{"success":true,"username":"' . $_SESSION['user'] . '"}';
        exit(0);
    }
    
    $uType = null;
    $uUser = null;
    $uPassword = null;
    $uPasswordR = null;
    $uEmail = null;
    
    try{
        $uType = $_POST["type"];
        $uUser = $_POST["user"];
        $uPassword = $_POST["password"];
        if ($uType == "create"){
            $uPasswordR = $_POST["passwordR"];
            $uEmail = $_POST["email"];
        }
    }catch(Exception $e){
        throw new Exception("Not all POST arguments supplied",100,$e);
    }
    if ($uType == "create"){
        //create account
        //encode into json
        if ($uPassword == $uPasswordR && login_format_check($uUser,$uPassword)){
            $success = create_account(array("username" => $uUser,"password" => $uPassword,"email" => $uEmail));
            if ($success==true){
                echo '{"success":true}';
            }else if ($success==false){
                echo '{"success":false, "error":"Failed to write to database (unknown error)", "errorCode":501}';
            }
        }
    }else{
        //login
        //correctly formatted ?
        if (login_format_check($uUser,$uPassword)){
            $user = $uUser;
            $password = $uPassword;
            //check correct password
            if (validate_user(array("username" => $user, "password" => $password))){
                //write login to database
                log_login($user);
                //set session to logged in
                successful_login($user);
                //succesfull login
                echo '{"success":true, "username":"' .$user . '"}';

            }else{
                echo '{"success":false, "error":"Invalid username or password", "errorCode":500}';   
            }

        }
        
    }
}catch(Exception $e){
    error_handler($e);   
}

?>
