<?php
// includes/header.php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Path resolution helper
$path_to_root = isset($path_to_root) ? $path_to_root : './';

// 1. Authentication Check
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $path_to_root . "authentication/login.php");
    exit();
}

// 2. Role-Based Directory Security Enforcement (RBAC)
$role = $_SESSION['role'];
$script_path = $_SERVER['SCRIPT_NAME'];

if (strpos($script_path, '/admin/') !== false && $role !== 'admin') {
    header("Location: " . $path_to_root . $role . "/dashboard.php?error=Unauthorized+Access");
    exit();
}
if (strpos($script_path, '/doctor/') !== false && $role !== 'doctor') {
    header("Location: " . $path_to_root . $role . "/dashboard.php?error=Unauthorized+Access");
    exit();
}
if (strpos($script_path, '/nurse/') !== false && $role !== 'nurse') {
    header("Location: " . $path_to_root . $role . "/dashboard.php?error=Unauthorized+Access");
    exit();
}
if (strpos($script_path, '/receptionist/') !== false && $role !== 'receptionist') {
    header("Location: " . $path_to_root . $role . "/dashboard.php?error=Unauthorized+Access");
    exit();
}

// System settings placeholder (can be customized by admin)
$system_name = isset($_SESSION['system_name']) ? $_SESSION['system_name'] : 'MediRecord EHR';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . $system_name : $system_name; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Healthcare Styling -->
    <link rel="stylesheet" href="<?php echo $path_to_root; ?>assets/css/style.css">
</head>
<body>
    <div id="app-wrapper">
        <!-- Sidebar layout is inserted here -->
        <?php include_once __DIR__ . '/sidebar.php'; ?>
        
        <div id="main-content">
            <header class="top-nav">
                <div class="d-flex align-items-center gap-3">
                    <button class="toggle-sidebar" aria-label="Toggle Sidebar Menu">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="nav-title"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h1>
                </div>
                
                <div class="d-flex align-items-center gap-3">
                    <!-- Dropdown for logged-in user profile -->
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2" type="button" id="userMenuButton" data-bs-toggle="dropdown" aria-expanded="false" style="border: 1px solid var(--card-border); border-radius: 8px;">
                            <i class="fas fa-user-md text-teal"></i>
                            <span class="d-none d-md-inline font-semibold"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userMenuButton" style="border-radius: 8px; border: 1px solid var(--card-border);">
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2" href="<?php echo $path_to_root . $_SESSION['role']; ?>/profile.php">
                                    <i class="fas fa-id-card text-muted"></i> View Profile
                                </a>
                            </li>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2" href="<?php echo $path_to_root; ?>admin/settings.php">
                                    <i class="fas fa-cog text-muted"></i> System Settings
                                </a>
                            </li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger d-flex align-items-center gap-2" href="<?php echo $path_to_root; ?>authentication/logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>
            
            <main class="dashboard-container">
                <!-- Notifications/Alert Handling -->
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 8px; font-weight: 500;">
                        <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 8px; font-weight: 500;">
                        <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 8px; font-weight: 500;">
                        <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $_SESSION['error_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 8px; font-weight: 500;">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>
