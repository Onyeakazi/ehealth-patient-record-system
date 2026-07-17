<?php
// receptionist/patients.php
$path_to_root = '../';
$page_title = 'Patient Directory';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

// Handle Basic Patient Info Update Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_patient'])) {
    $patient_id = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $emergency_contact = isset($_POST['emergency_contact']) ? trim($_POST['emergency_contact']) : '';
    $blood_group = isset($_POST['blood_group']) ? $_POST['blood_group'] : '';

    if ($patient_id <= 0 || empty($full_name) || empty($phone) || empty($address) || empty($emergency_contact) || empty($blood_group)) {
        $_SESSION['error_message'] = "Please fill in all required fields.";
    } else {
        try {
            // Get patient number for auditing
            $num_stmt = $pdo->prepare("SELECT patient_number FROM patients WHERE patient_id = ?");
            $num_stmt->execute([$patient_id]);
            $pat_num = $num_stmt->fetchColumn();

            $update_stmt = $pdo->prepare("
                UPDATE patients 
                SET full_name = ?, phone = ?, email = ?, address = ?, emergency_contact = ?, blood_group = ?
                WHERE patient_id = ?
            ");
            $update_stmt->execute([
                $full_name,
                $phone,
                empty($email) ? null : $email,
                $address,
                $emergency_contact,
                $blood_group,
                $patient_id
            ]);

            log_audit_action($pdo, $_SESSION['user_id'], "Updated patient info for: $pat_num ($full_name)");
            $_SESSION['success_message'] = "Patient details updated successfully.";
            
            header("Location: patients.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Database error updating record: " . $e->getMessage();
        }
    }
}

// Fetch all patients
try {
    $patients = $pdo->query("SELECT * FROM patients ORDER BY created_at DESC")->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error retrieving patients: " . $e->getMessage();
    $patients = [];
}
?>

<div class="clinical-card">
    <div class="card-header">
        <h5>Patient Registry Directory</h5>
        <div class="d-flex gap-2">
            <div class="position-relative">
                <input type="text" id="tableSearch" class="form-control form-control-sm py-2 px-3" placeholder="Search patients..." style="border-radius: 8px; width: 250px; font-size: 0.8rem; border-color: var(--card-border);">
            </div>
            <a href="register.php" class="btn btn-sm btn-primary py-2 px-3" style="font-size: 0.8rem;">
                <i class="fas fa-user-plus me-1"></i> Register Patient
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($patients)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-user-injured mb-3" style="font-size: 3rem; color: #cbd5e1;"></i>
                <h5>No Registered Patients</h5>
                <p>Register new patients using the action button above.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table custom-table">
                    <thead>
                        <tr>
                            <th>Patient No.</th>
                            <th>Full Name</th>
                            <th>Gender / DOB</th>
                            <th>Contact</th>
                            <th>Blood Group</th>
                            <th>Registered Date</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($patients as $pat): ?>
                            <tr>
                                <td><span class="badge bg-light text-dark border p-2" style="font-size: 0.8rem; font-weight: 600;"><?php echo htmlspecialchars($pat['patient_number']); ?></span></td>
                                <td><strong><?php echo htmlspecialchars($pat['full_name']); ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($pat['gender']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($pat['date_of_birth']); ?></small>
                                </td>
                                <td>
                                    <i class="fas fa-phone-alt text-muted me-1" style="font-size: 0.75rem;"></i> <?php echo htmlspecialchars($pat['phone']); ?><br>
                                    <?php if ($pat['email']): ?>
                                        <i class="fas fa-envelope text-muted me-1" style="font-size: 0.75rem;"></i> <small class="text-muted"><?php echo htmlspecialchars($pat['email']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-danger bg-opacity-10 text-danger p-2" style="font-size: 0.8rem;"><?php echo htmlspecialchars($pat['blood_group']); ?></span></td>
                                <td><small class="text-muted"><?php echo date('M d, Y h:i A', strtotime($pat['created_at'])); ?></small></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary edit-patient-btn" 
                                            data-id="<?php echo $pat['patient_id']; ?>"
                                            data-number="<?php echo htmlspecialchars($pat['patient_number']); ?>"
                                            data-name="<?php echo htmlspecialchars($pat['full_name']); ?>"
                                            data-gender="<?php echo htmlspecialchars($pat['gender']); ?>"
                                            data-dob="<?php echo htmlspecialchars($pat['date_of_birth']); ?>"
                                            data-phone="<?php echo htmlspecialchars($pat['phone']); ?>"
                                            data-email="<?php echo htmlspecialchars($pat['email']); ?>"
                                            data-address="<?php echo htmlspecialchars($pat['address']); ?>"
                                            data-contact="<?php echo htmlspecialchars($pat['emergency_contact']); ?>"
                                            data-blood="<?php echo htmlspecialchars($pat['blood_group']); ?>"
                                            style="border-radius: 6px;"
                                            title="Edit Basic Details">
                                        <i class="fas fa-edit"></i> Edit
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

<!-- Dynamic Patient Edit Modal -->
<div class="modal fade" id="editPatientModal" tabindex="-1" aria-labelledby="editPatientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPatientModalLabel"><i class="fas fa-user-edit text-teal me-2"></i>Edit Patient Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="patients.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="patient_id" id="edit_id">
                    <input type="hidden" name="update_patient" value="1">
                    
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="edit_number" class="form-label">Patient Number</label>
                            <input type="text" id="edit_number" class="form-control" readonly style="background-color: #f1f5f9;">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="edit_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" id="edit_name" class="form-control" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="edit_phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" name="phone" id="edit_phone" class="form-control" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="edit_email" class="form-label">Email Address</label>
                            <input type="email" name="email" id="edit_email" class="form-control">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="edit_blood" class="form-label">Blood Group <span class="text-danger">*</span></label>
                            <select name="blood_group" id="edit_blood" class="form-select" required>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="edit_contact" class="form-label">Emergency Contact <span class="text-danger">*</span></label>
                            <input type="text" name="emergency_contact" id="edit_contact" class="form-control" required>
                        </div>

                        <div class="col-12">
                            <label for="edit_address" class="form-label">Residential Address <span class="text-danger">*</span></label>
                            <textarea name="address" id="edit_address" class="form-control" rows="3" required></textarea>
                        </div>
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
    const editButtons = document.querySelectorAll('.edit-patient-btn');
    const modal = new bootstrap.Modal(document.getElementById('editPatientModal'));
    
    editButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            // Retrieve details
            document.getElementById('edit_id').value = this.getAttribute('data-id');
            document.getElementById('edit_number').value = this.getAttribute('data-number');
            document.getElementById('edit_name').value = this.getAttribute('data-name');
            document.getElementById('edit_phone').value = this.getAttribute('data-phone');
            document.getElementById('edit_email').value = this.getAttribute('data-email') || '';
            document.getElementById('edit_address').value = this.getAttribute('data-address');
            document.getElementById('edit_contact').value = this.getAttribute('data-contact');
            document.getElementById('edit_blood').value = this.getAttribute('data-blood');
            
            modal.show();
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
