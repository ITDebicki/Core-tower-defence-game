<?php
//$current_connection = null;

function request_connection(){
    //if ($current_connection){
    return create_connection();
    //}
    //return $current_connection;
}

function create_connection(){
    try {
        $config = parse_ini_file('caDB.ini');
        $conn = new PDO('mysql' . ':host=' . $config['dbHost'] . ';dbname=' . $config['db'],$config['dbPHPUser'], $config['dbPHPPass']);
        date_default_timezone_set($config['dbTimezone']);
        //$current_connection = $conn;
        return $conn;
    } catch(PDOException $e){
        throw new Exception("Failed to initiate connection",102,$e);
    }   
}
function close_connection($conn){
    try{
        $conn = null;
        return 1;
    } catch(Exception $e){
        throw new Exception("Failed to close connection",103,$e);
    }
}


?>