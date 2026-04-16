<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'campus_db';

$conn = mysqli_connect($host, $user, $pass);
if (!$conn) {
    die("Matrix Connection Failed: " . mysqli_connect_error());
}

mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $db");
mysqli_select_db($conn, $db);

function getCount($conn, $table, $where = "") {
    $q = "SELECT COUNT(*) as c FROM $table $where";
    $res = @mysqli_query($conn, $q);
    return $res ? mysqli_fetch_assoc($res)['c'] : 0;
}

function logAction($conn, $user, $action) {
    $patch = "CREATE TABLE IF NOT EXISTS campus_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        log_user VARCHAR(50),
        log_action VARCHAR(255),
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    try { @mysqli_query($conn, $patch); } catch (Exception $e) {}
    
    $cols = ["log_user VARCHAR(50)", "log_action VARCHAR(255)", "timestamp DATETIME DEFAULT CURRENT_TIMESTAMP"];
    foreach($cols as $c) { 
        try { @mysqli_query($conn, "ALTER TABLE campus_logs ADD COLUMN $c"); } catch (Exception $e) {} 
    }
    
    $u = mysqli_real_escape_string($conn, $user);
    $a = mysqli_real_escape_string($conn, $action);
    @mysqli_query($conn, "INSERT INTO campus_logs (log_user, log_action) VALUES ('$u', '$a')");
}
?>