<?php
function login_format_check($user,$pass){
    //check if user is alphanumeric
    
    if(ctype_alnum($user)==false||has_presence($user)==false){
        throw new Exception("Invalid username",300);
    }
    //check format of password
    if(has_presence($pass)==false){
        throw new Exception("Invalid password",301);
    }
    return true;
}

function decode_JSON($jsonString){
    try{
        $jsonData = json_decode($jsonString);
    } catch(Exception $e){
       throw new Exception("Invalid json",400,$e);
    }
}
function has_presence($value) {
    //trim all spaces
    $trimmed_value = trim($value);
    //check if not blank
    return isset($trimmed_value) && $trimmed_value !== "";
}

?>