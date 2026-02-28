<?php

//  db_config.php — Database Connection

define('DB_HOST',     '127.0.0.1:3307');
define('DB_USER',     'root');  
define('DB_PASS',     '');
define('DB_NAME',     'yalasafari'); 
define('DB_CHARSET',  'utf8mb4');

// Create connection 
function getDB(): mysqli {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset(DB_CHARSET);
        if ($conn->connect_error) {
            error_log('DB Connection failed: ' . $conn->connect_error);
            die(json_encode(['error' => 'Database connection failed.']));
        }
    }
    return $conn;
}
