<?php

//  includes/db — Frontend Database Connection
//  Update credentials before uploading to cPanel


// Session (start once if not already active) 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database credentials 
define('DB_HOST',    '127.0.0.1');
define('DB_PORT',    3307);
define('DB_USER',    'root');              // cPanel: e.g. db_admin
define('DB_PASS',    '');                  // cPanel: your database password
define('DB_NAME',    'yalasafari');        // cPanel: e.g. db_yalasafari
define('DB_CHARSET', 'utf8mb4');

// Connection (singleton — connects once per page load) 
function getDB(): mysqli {
    static $conn = null;
    if ($conn === null) {
        mysqli_report(MYSQLI_REPORT_OFF); // handle errors manually
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        if ($conn->connect_error) {
            // Log the real error but show a safe message to visitors
            error_log('DB Connection failed: ' . $conn->connect_error);
            die('Service temporarily unavailable. Please try again later.');
        }
        $conn->set_charset(DB_CHARSET);
    }
    return $conn;
}

// Helper: get a single site setting from DB 
function getSetting(string $key, string $default = ''): string {
    $db   = getDB();
    $stmt = $db->prepare('SELECT value FROM settings WHERE `key` = ? LIMIT 1');
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_row();
    $stmt->close();
    return $row ? (string)$row[0] : $default;
}

// Helper: get multiple settings at once 
function getSettings(array $keys): array {
    $settings = [];
    foreach ($keys as $key) {
        $settings[$key] = getSetting($key);
    }
    return $settings;
}
