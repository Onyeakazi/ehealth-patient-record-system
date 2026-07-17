<?php
// config/database.php

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'ehr_system';

try {
    // First connect without specifying the database to ensure we can run setup scripts if needed
    $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Switch to database
    $pdo->exec("USE `$db_name`");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

/**
 * Log user actions in the audit trail.
 */
function log_audit_action($pdo, $user_id, $action) {
    try {
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)");
        $stmt->execute([$user_id, $action]);
    } catch (PDOException $e) {
        // Silently ignore or write to error log to avoid interrupting the main user flow
        error_log("Audit log failed: " . $e->getMessage());
    }
}
?>
