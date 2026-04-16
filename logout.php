<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'config.php';

// Log the departure if the function exists and user was logged in
if(isset($_SESSION['auth']) && $_SESSION['auth'] === true && function_exists('logAction')) {
    logAction($conn, 'admin', 'System Disconnect / Logout');
}

// Annihilate the session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Terminate the session
session_destroy();

// Redirect to the authorization gateway
header("Location: login.php");
exit();
?>