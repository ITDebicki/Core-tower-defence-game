<?php
/**
 * Validates the user and password to make sure they are reasonable
 * @author Ignacy Debicki
 * @param  string  $user Username to validate
 * @param  string  $pass Password to validate
 * @return boolean If valid
 */
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
/**
 * Attempts to decode a json
 * @author Ignacy Debicki
 * @param string $jsonString JSON string to return
 * @return object Parsed JSON Dictionary
 */
function decode_JSON($jsonString){
    try{
        $jsonData = json_decode($jsonString);
        return $jsonData;
    } catch(Exception $e){
       throw new Exception("Invalid json",400,$e);
    }
}
/**
 * Checks if a value has any characters, symbols or numbers, not just whitespace
 * @author Ignacy Debicki
 * @param  string  $value String to check
 * @return boolean If has presence
 */
function has_presence($value) {
    //trim all spaces
    $trimmed_value = trim($value);
    //check if not blank
    return isset($trimmed_value) && $trimmed_value !== "";
}

?>