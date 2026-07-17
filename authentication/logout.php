<?php
// authentication/logout.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$path_to_root = '../';
require_once __DIR__ . '/../config/database.php';

// Log the activity if user was logged in
if (isset($_SESSION['user_id'])) {
    log_audit_action($pdo, $_SESSION['user_id'], "User logged out successfully");
}

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie if used
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php?msg=You+have+been+logged+out+successfully.");
exit();
?>
