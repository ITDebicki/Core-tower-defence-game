<?php
//$current_connection = null;
/**
 * Requests an open connection to the database
 * @author Ignacy Debicki
 * @return PDO A database connection
 */
function request_connection(){
    //if ($current_connection){
    return create_connection();
    //}
    //return $current_connection;
}
/**
 * Creates a new PDO connection to the database specified in the configuration file
 * @author Ignacy Debicki
 * @return PDO A new open PDO connection to the database
 */
function create_connection(){
    try {
        $config = parse_ini_file('caDB.ini');
        $conn = new PDO('mysql' . ':host=' . $config['dbHost'] . ';dbname=' . $config['db'],$config['dbPHPUser'], $config['dbPHPPass']);
        //$conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        date_default_timezone_set($config['dbTimezone']);
        return $conn;
    } catch(PDOException $e){
        throw new Exception("Failed to initiate connection",102,$e);
    }   
}
/**
 * Closes the PDO connection
 * @author Ignacy Debicki
 * @param  PDO     $conn An open PDO connection
 * @return boolean If termaination of PDO was succesfull
 */
function close_connection($conn){
    try{
        $conn = null;
        return 1;
    } catch(Exception $e){
        throw new Exception("Failed to close connection",103,$e);
    }
}


?>