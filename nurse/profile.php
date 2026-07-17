<?php
// nurse/profile.php
$path_to_root = '../';
$page_title = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// Handle Profile Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    if (empty($full_name) || empty($email) || empty($phone)) {
        $error_message = "Required details cannot be empty.";
    } else {
        try {
            // Retrieve current user data to verify password if trying to change it
            $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            $password_updated = false;
            
            // Password change requested
            if (!empty($new_password)) {
                if (empty($current_password)) {
                    $error_message = "Please enter your current password to set a new one.";
                } elseif (!password_verify($current_password, $user['password'])) {
                    $error_message = "Current password is incorrect.";
                } elseif ($new_password !== $confirm_password) {
                    $error_message = "New password and confirmation do not match.";
                } elseif (strlen($new_password) < 6) {
                    $error_message = "New password must be at least 6 characters long.";
                } else {
                    // Hash new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_pwd = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                    $update_pwd->execute([$hashed_password, $user_id]);
                    $password_updated = true;
                }
            }
            
            if (empty($error_message)) {
                // Update basic details
                $update_stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE user_id = ?");
                $update_stmt->execute([$full_name, $email, $phone, $user_id]);
                
                // Update Session Variable
                $_SESSION['full_name'] = $full_name;
                
                // Log action
                $audit_text = "Updated profile details" . ($password_updated ? " and changed password" : "");
                log_audit_action($pdo, $user_id, $audit_text);
                
                $success_message = "Profile updated successfully.";
            }
        } catch (PDOException $e) {
            $error_message = "Error updating profile: " . $e->getMessage();
        }
    }
}

// Fetch current details
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    die("Error loading profile: " . $e->getMessage());
}
?>

<div class="row g-4">
    <!-- Profile summary card -->
    <div class="col-12 col-lg-4">
        <div class="clinical-card text-center py-4">
            <div class="card-body">
                <div class="avatar-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; border-radius: 50%; background-color: var(--accent-teal); color: var(--primary-color); font-weight: 700; font-size: 2rem;">
                    <?php 
                        $parts = explode(' ', $user['full_name']);
                        echo isset($parts[0][0]) ? $parts[0][0] : '';
                        echo isset($parts[1][0]) ? $parts[1][0] : '';
                    ?>
                </div>
                <h5 class="mb-1" style="font-weight: 700;"><?php echo htmlspecialchars($user['full_name']); ?></h5>
                <span class="badge bg-teal bg-opacity-10 text-teal px-3 py-2 mb-3" style="color: var(--primary-color) !important; font-size: 0.8rem; font-weight: 700;">
                    <?php echo htmlspecialchars(strtoupper($user['role'])); ?>
                </span>
                
                <div class="text-start border-top pt-3 mt-3">
                    <p class="mb-2 text-muted" style="font-size: 0.85rem;"><i class="fas fa-user-tag me-2"></i>Username: <strong><?php echo htmlspecialchars($user['username']); ?></strong></p>
                    <p class="mb-2 text-muted" style="font-size: 0.85rem;"><i class="far fa-envelope me-2"></i>Email: <?php echo htmlspecialchars($user['email']); ?></p>
                    <p class="mb-2 text-muted" style="font-size: 0.85rem;"><i class="fas fa-phone-alt me-2"></i>Phone: <?php echo htmlspecialchars($user['phone']); ?></p>
                    <p class="mb-0 text-muted" style="font-size: 0.85rem;"><i class="far fa-calendar-check me-2"></i>Account Active Since: <?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Profile Edit Form -->
    <div class="col-12 col-lg-8">
        <div class="clinical-card">
            <div class="card-header">
                <h5>Update Profile Information</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger" style="border-radius: 8px;">
                        <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success" style="border-radius: 8px;">
                        <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <form action="profile.php" method="POST" autocomplete="off">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" id="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label for="username" class="form-label">Username (Read Only)</label>
                            <input type="text" id="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" readonly style="background-color: #f1f5f9;">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" name="phone" id="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                        </div>
                        
                        <h6 class="mt-4 pt-3 border-top mb-1" style="font-weight: 700; color: var(--dark-slate);"><i class="fas fa-lock me-2 text-muted"></i>Change Password (Leave blank to keep current)</h6>
                        
                        <div class="col-12">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" name="current_password" id="current_password" class="form-control" placeholder="Enter current password">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Enter new password">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm new password">
                        </div>
                    </div>
                    
                    <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary py-2 px-4"><i class="fas fa-save me-1"></i> Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
