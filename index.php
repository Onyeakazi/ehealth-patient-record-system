<?php
// index.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user session exists
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    
    // Redirect to respective module dashboard based on role
    switch ($role) {
        case 'admin':
            header("Location: admin/dashboard.php");
            exit();
        case 'doctor':
            header("Location: doctor/dashboard.php");
            exit();
        case 'nurse':
            header("Location: nurse/dashboard.php");
            exit();
        case 'receptionist':
            header("Location: receptionist/dashboard.php");
            exit();
        default:
            // Fallback - destroy invalid session and redirect to login
            session_destroy();
            header("Location: authentication/login.php");
            exit();
    }
} else {
    // If not authenticated, redirect to login page
    header("Location: authentication/login.php");
    exit();
}
?>
