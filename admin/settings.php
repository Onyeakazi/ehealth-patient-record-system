<?php
// admin/settings.php
$path_to_root = '../';
$page_title = 'System Configuration';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

$settings_file = __DIR__ . '/../config/settings.json';
$success_message = '';
$error_message = '';

// Load existing settings or initialize defaults
if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true);
} else {
    $settings = [
        'system_name' => 'MediRecord EHR',
        'contact_email' => 'support@medirecord.com',
        'contact_phone' => '08012345678',
        'address' => '12 Healthcare Way, Clinic District, Nigeria'
    ];
    file_put_contents($settings_file, json_encode($settings, JSON_PRETTY_PRINT));
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $system_name = isset($_POST['system_name']) ? trim($_POST['system_name']) : '';
    $contact_email = isset($_POST['contact_email']) ? trim($_POST['contact_email']) : '';
    $contact_phone = isset($_POST['contact_phone']) ? trim($_POST['contact_phone']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';

    if (empty($system_name) || empty($contact_email) || empty($contact_phone) || empty($address)) {
        $error_message = "All settings parameters must be filled.";
    } else {
        $settings = [
            'system_name' => $system_name,
            'contact_email' => $contact_email,
            'contact_phone' => $contact_phone,
            'address' => $address
        ];
        
        if (file_put_contents($settings_file, json_encode($settings, JSON_PRETTY_PRINT))) {
            // Update session variable so it reflects instantly in header title
            $_SESSION['system_name'] = $system_name;
            
            log_audit_action($pdo, $_SESSION['user_id'], "Updated system settings: Title - $system_name");
            $success_message = "System settings updated successfully.";
        } else {
            $error_message = "Failed to write settings to server storage.";
        }
    }
}
?>

<div class="row g-4">
    <!-- Configuration Form -->
    <div class="col-12 col-lg-8">
        <div class="clinical-card">
            <div class="card-header">
                <h5>Edit System Variables</h5>
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
                
                <form action="settings.php" method="POST">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="system_name" class="form-label">Hospital / Clinic Portal Title <span class="text-danger">*</span></label>
                            <input type="text" name="system_name" id="system_name" class="form-control" value="<?php echo htmlspecialchars($settings['system_name']); ?>" required>
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label for="contact_email" class="form-label">Support Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="contact_email" id="contact_email" class="form-control" value="<?php echo htmlspecialchars($settings['contact_email']); ?>" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="contact_phone" class="form-label">Contact Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" name="contact_phone" id="contact_phone" class="form-control" value="<?php echo htmlspecialchars($settings['contact_phone']); ?>" required>
                        </div>

                        <div class="col-12">
                            <label for="address" class="form-label">Hospital Address <span class="text-danger">*</span></label>
                            <textarea name="address" id="address" class="form-control" rows="3" required><?php echo htmlspecialchars($settings['address']); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary py-2 px-4">
                            <i class="fas fa-save me-1"></i> Save Configuration
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Info Column -->
    <div class="col-12 col-lg-4">
        <div class="clinical-card bg-light">
            <div class="card-body">
                <h6 class="mb-3" style="font-weight: 700; color: var(--dark-slate);"><i class="fas fa-info-circle text-teal me-2"></i>Administration Control</h6>
                <p style="font-size: 0.85rem; line-height: 1.6;">
                    The parameters set on this page control global system variables displayed to users on top navigation title headers, report invoices, and system footers.
                </p>
                <div class="alert alert-warning mb-0 border" style="border-radius: 8px; font-size: 0.8rem; background-color: #fffbeb;">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    <strong>Caution:</strong> Changes reflect globally for all users logged in. Make sure details are typed accurately.
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
