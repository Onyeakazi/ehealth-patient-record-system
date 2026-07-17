<?php
// authentication/login.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$path_to_root = '../';
require_once __DIR__ . '/../config/database.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . $path_to_root . $_SESSION['role'] . "/dashboard.php");
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        try {
            // Find user
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] !== 'active') {
                    $error_message = 'Your account has been deactivated. Please contact the administrator.';
                } else {
                    // Start Session and store details
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Track in audit trail
                    log_audit_action($pdo, $user['user_id'], "User logged in successfully");
                    
                    // Redirect to corresponding dashboard
                    header("Location: " . $path_to_root . $user['role'] . "/dashboard.php");
                    exit();
                }
            } else {
                $error_message = 'Invalid username or password.';
                // Log failed attempt if username exists
                if ($user) {
                    log_audit_action($pdo, $user['user_id'], "Failed login attempt (Incorrect password)");
                }
            }
        } catch (PDOException $e) {
            $error_message = 'An unexpected database error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Access - MediRecord EHR</title>
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom styling -->
    <link rel="stylesheet" href="<?php echo $path_to_root; ?>assets/css/style.css">
</head>
<body class="login-body">

    <div class="login-container">
        
        <!-- Left Column: Branding Showcase -->
        <div class="login-branding d-none d-lg-flex">
            <div class="login-branding-logo">
                <i class="fas fa-heartbeat"></i>
            </div>
            <h2>Modern Clinical Records. <br>Secured & Streamlined.</h2>
            <p class="subtitle">Empowering healthcare providers with real-time electronic health charting, secure role authorization, and centralized patient documentation workflows.</p>
            
            <ul class="login-feature-list">
                <li><i class="fas fa-shield-halved"></i> Role-Based Access Gatekeeper (RBAC)</li>
                <li><i class="fas fa-clock-rotate-left"></i> Centralized Activity Audit Trails</li>
                <li><i class="fas fa-chart-line"></i> Dynamic Medical Dashboard Visuals</li>
                <li><i class="fas fa-hospital-user"></i> Collaborative Diagnostics & Vitals Tracking</li>
            </ul>
        </div>
        
        <!-- Right Column: Portal Access Form -->
        <div class="login-form-area">
            <div class="login-box-wrapper">
                <h3>Portal Access</h3>
                <p>Log in using your authorized healthcare staff credentials.</p>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger" role="alert" style="border-radius: 8px; font-size: 0.875rem;">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['msg'])): ?>
                    <div class="alert alert-success" role="alert" style="border-radius: 8px; font-size: 0.875rem;">
                        <i class="fas fa-info-circle me-2"></i> <?php echo htmlspecialchars($_GET['msg']); ?>
                    </div>
                <?php endif; ?>


                <form action="login.php" method="POST" autocomplete="off">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background-color: #f8fafc; border-color: #cbd5e1; border-radius: 8px 0 0 8px;">
                                <i class="fas fa-user text-muted"></i>
                            </span>
                            <input type="text" name="username" id="username" class="form-control" placeholder="Enter username" required style="border-radius: 0 8px 8px 0;">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background-color: #f8fafc; border-color: #cbd5e1; border-radius: 8px 0 0 8px;">
                                <i class="fas fa-lock text-muted"></i>
                            </span>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Enter password" required style="border-radius: 0 8px 0 0;">
                            <button type="button" class="btn btn-outline-secondary toggle-password-btn" data-target="password" style="border-color: #cbd5e1; border-radius: 0 8px 8px 0;" aria-label="Toggle Password Visibility">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2 mt-4" style="border-radius: 8px;">
                        <i class="fas fa-sign-in-alt me-2"></i> Access Portal
                    </button>
                </form>
            </div>
        </div>
        
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    
    <!-- Script to manage dynamic pre-fills and animation feedback -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // Eye toggler script
        const toggleBtn = document.querySelector('.toggle-password-btn');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function () {
                const passwordField = document.getElementById('password');
                if (passwordField) {
                    if (passwordField.type === 'password') {
                        passwordField.type = 'text';
                        this.innerHTML = '<i class="fas fa-eye-slash"></i>';
                    } else {
                        passwordField.type = 'password';
                        this.innerHTML = '<i class="fas fa-eye"></i>';
                    }
                }
            });
        }
    });
    </script>
</body>
</html>
