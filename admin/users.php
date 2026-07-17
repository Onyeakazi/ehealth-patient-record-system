<?php
// admin/users.php
$path_to_root = '../';
$page_title = 'Manage Staff Accounts';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

// 1. Handle New Staff Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $role = isset($_POST['role']) ? $_POST['role'] : '';

    if (empty($full_name) || empty($username) || empty($password) || empty($email) || empty($phone) || empty($role)) {
        $_SESSION['error_message'] = "Please fill in all fields.";
    } else {
        try {
            // Check if username already exists
            $check_usr = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $check_usr->execute([$username]);
            if ($check_usr->fetchColumn() > 0) {
                $_SESSION['error_message'] = "Username already exists. Please choose a different one.";
            } else {
                // Check if email already exists
                $check_email = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
                $check_email->execute([$email]);
                
                if ($check_email->fetchColumn() > 0) {
                    $_SESSION['error_message'] = "Email address already registered.";
                } else {
                    // Create account
                    $hashed_pwd = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        INSERT INTO users (full_name, username, password, email, phone, role, status) 
                        VALUES (?, ?, ?, ?, ?, ?, 'active')
                    ");
                    $stmt->execute([$full_name, $username, $hashed_pwd, $email, $phone, $role]);
                    
                    log_audit_action($pdo, $_SESSION['user_id'], "Created staff account: $username ($full_name, Role: $role)");
                    $_SESSION['success_message'] = "Staff account created successfully.";
                    
                    header("Location: users.php");
                    exit();
                }
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Database error creating user: " . $e->getMessage();
        }
    }
}

// 2. Handle User Details Update & Optional Password Reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $role = isset($_POST['role']) ? $_POST['role'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';

    if ($user_id <= 0 || empty($full_name) || empty($email) || empty($phone) || empty($role) || empty($status)) {
        $_SESSION['error_message'] = "Please fill in all required fields.";
    } else {
        try {
            // Check email collision
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND user_id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetchColumn() > 0) {
                $_SESSION['error_message'] = "Email already used by another account.";
            } else {
                // Get target user details for logging
                $u_info = $pdo->query("SELECT username, role, status FROM users WHERE user_id = $user_id")->fetch();
                
                // Update user details
                $update_stmt = $pdo->prepare("
                    UPDATE users 
                    SET full_name = ?, email = ?, phone = ?, role = ?, status = ? 
                    WHERE user_id = ?
                ");
                $update_stmt->execute([$full_name, $email, $phone, $role, $status, $user_id]);
                
                $audit_msg = "Updated details for user " . $u_info['username'];
                if ($u_info['role'] !== $role) $audit_msg .= " (Role: " . $u_info['role'] . " -> $role)";
                if ($u_info['status'] !== $status) $audit_msg .= " (Status: " . $u_info['status'] . " -> $status)";
                
                // Process Password Reset if provided
                if (!empty($new_password)) {
                    if (strlen($new_password) < 6) {
                        $_SESSION['error_message'] = "New password must be at least 6 characters. Basic details updated, password unchanged.";
                    } else {
                        $hashed_pwd = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt_pwd = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                        $stmt_pwd->execute([$hashed_pwd, $user_id]);
                        $audit_msg .= ", reset password";
                    }
                }
                
                log_audit_action($pdo, $_SESSION['user_id'], $audit_msg);
                $_SESSION['success_message'] = "User account updated successfully.";
                
                header("Location: users.php");
                exit();
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error updating user account: " . $e->getMessage();
        }
    }
}

// 3. Fetch all users
try {
    $users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error loading user list: " . $e->getMessage();
    $users = [];
}
?>

<div class="clinical-card">
    <div class="card-header">
        <h5>Clinic Staff Directory & Access Control</h5>
        <div class="d-flex gap-2">
            <input type="text" id="tableSearch" class="form-control form-control-sm py-2 px-3" placeholder="Search staff..." style="border-radius: 8px; width: 250px; font-size: 0.8rem; border-color: var(--card-border);">
            <button class="btn btn-sm btn-primary py-2 px-3" data-bs-toggle="modal" data-bs-target="#addUserModal" style="font-size: 0.8rem;">
                <i class="fas fa-user-plus me-1"></i> Add New Staff
            </button>
        </div>
    </div>
    
    <div class="card-body">
        <?php if (empty($users)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-users-cog mb-3" style="font-size: 3rem; color: #cbd5e1;"></i>
                <h5>No Accounts Found</h5>
                <p>Register new staff accounts using the button above.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table custom-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Contact</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($u['full_name']); ?></strong>
                                    <?php if ($u['user_id'] === $_SESSION['user_id']): ?>
                                        <span class="badge bg-primary p-1" style="font-size: 0.65rem;">You</span>
                                    <?php endif; ?>
                                </td>
                                <td><code><?php echo htmlspecialchars($u['username']); ?></code></td>
                                <td>
                                    <small><?php echo htmlspecialchars($u['email']); ?></small><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($u['phone']); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary text-uppercase p-2" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                        <?php echo htmlspecialchars($u['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-role <?php echo ($u['status'] === 'active') ? 'badge-active' : 'badge-inactive'; ?>">
                                        <?php echo htmlspecialchars($u['status']); ?>
                                    </span>
                                </td>
                                <td><small class="text-muted"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></small></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary edit-user-btn" 
                                            data-id="<?php echo $u['user_id']; ?>"
                                            data-name="<?php echo htmlspecialchars($u['full_name']); ?>"
                                            data-username="<?php echo htmlspecialchars($u['username']); ?>"
                                            data-email="<?php echo htmlspecialchars($u['email']); ?>"
                                            data-phone="<?php echo htmlspecialchars($u['phone']); ?>"
                                            data-role="<?php echo htmlspecialchars($u['role']); ?>"
                                            data-status="<?php echo htmlspecialchars($u['status']); ?>"
                                            style="border-radius: 6px;"
                                            <?php echo ($u['user_id'] === $_SESSION['user_id']) ? 'disabled title="Edit via Profile page"' : ''; // restrict editing own account through user list to avoid accidental role locking ?>>
                                        <i class="fas fa-user-edit"></i> Edit
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Add User Form -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel"><i class="fas fa-user-plus text-teal me-2"></i>Create New Staff Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="users.php" method="POST" autocomplete="off">
                <div class="modal-body">
                    <input type="hidden" name="add_user" value="1">
                    
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" id="full_name" class="form-control" required placeholder="e.g. Dr. John Doe">
                    </div>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" id="username" class="form-control" required placeholder="e.g. johndoe">
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" id="password" class="form-control" required placeholder="Enter password (min 6 characters)">
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="email" class="form-control" required placeholder="e.g. jdoe@hospital.com">
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                        <input type="tel" name="phone" id="phone" class="form-control" required placeholder="e.g. 08012345678">
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Access Role <span class="text-danger">*</span></label>
                        <select name="role" id="role" class="form-select" required>
                            <option value="">-- Choose Role --</option>
                            <option value="admin">Administrator</option>
                            <option value="doctor">Doctor</option>
                            <option value="nurse">Nurse</option>
                            <option value="receptionist">Receptionist</option>
                        </select>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Register Staff</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit User Form -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel"><i class="fas fa-user-cog text-teal me-2"></i>Modify Staff Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="users.php" method="POST" autocomplete="off">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="edit_id">
                    <input type="hidden" name="update_user" value="1">
                    
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Username</label>
                        <input type="text" id="edit_username" class="form-control" readonly style="background-color: #f1f5f9;">
                    </div>

                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" id="edit_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                        <input type="tel" name="phone" id="edit_phone" class="form-control" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="edit_role" class="form-label">Access Role <span class="text-danger">*</span></label>
                            <select name="role" id="edit_role" class="form-select" required>
                                <option value="admin">Administrator</option>
                                <option value="doctor">Doctor</option>
                                <option value="nurse">Nurse</option>
                                <option value="receptionist">Receptionist</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="edit_status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" id="edit_status" class="form-select" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="border-top pt-3 mt-3">
                        <label for="edit_pwd" class="form-label">Reset Password <span class="text-muted">(Leave empty to keep current)</span></label>
                        <input type="password" name="new_password" id="edit_pwd" class="form-control" placeholder="Enter new password (min 6 characters)">
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editButtons = document.querySelectorAll('.edit-user-btn');
    const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
    
    editButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.getAttribute('data-id');
            document.getElementById('edit_username').value = this.getAttribute('data-username');
            document.getElementById('edit_name').value = this.getAttribute('data-name');
            document.getElementById('edit_email').value = this.getAttribute('data-email');
            document.getElementById('edit_phone').value = this.getAttribute('data-phone');
            document.getElementById('edit_role').value = this.getAttribute('data-role');
            document.getElementById('edit_status').value = this.getAttribute('data-status');
            document.getElementById('edit_pwd').value = ''; // Clear reset box
            
            modal.show();
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
