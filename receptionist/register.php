<?php
// receptionist/register.php
$path_to_root = '../';
$page_title = 'Register New Patient';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
    $date_of_birth = isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $emergency_contact = isset($_POST['emergency_contact']) ? trim($_POST['emergency_contact']) : '';
    $blood_group = isset($_POST['blood_group']) ? $_POST['blood_group'] : '';

    if (empty($full_name) || empty($gender) || empty($date_of_birth) || empty($phone) || empty($address) || empty($emergency_contact) || empty($blood_group)) {
        $_SESSION['error_message'] = "Please fill in all required fields.";
    } else {
        try {
            // Auto-generate patient number (Format: PAT-YYYY-XXXX)
            $year = date('Y');
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM patients WHERE patient_number LIKE ?");
            $stmt->execute(["PAT-$year-%"]);
            $count = $stmt->fetchColumn() + 1;
            $patient_number = "PAT-" . $year . "-" . str_pad($count, 4, '0', STR_PAD_LEFT);
            
            // Double check uniqueness in case of deleted records or collisions
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM patients WHERE patient_number = ?");
            $check_stmt->execute([$patient_number]);
            if ($check_stmt->fetchColumn() > 0) {
                // Suffix with microtime details or add randomness to ensure absolute uniqueness
                $patient_number = "PAT-" . $year . "-" . str_pad($count + rand(1, 99), 4, '0', STR_PAD_LEFT);
            }

            // Insert into Database
            $insert_stmt = $pdo->prepare("
                INSERT INTO patients (patient_number, full_name, gender, date_of_birth, phone, email, address, emergency_contact, blood_group) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $insert_stmt->execute([
                $patient_number,
                $full_name,
                $gender,
                $date_of_birth,
                $phone,
                empty($email) ? null : $email,
                $address,
                $emergency_contact,
                $blood_group
            ]);
            
            // Log audit trail
            log_audit_action($pdo, $_SESSION['user_id'], "Registered new patient record: $patient_number ($full_name)");
            
            $_SESSION['success_message'] = "Patient registered successfully! Patient Number: <strong>$patient_number</strong>";
            header("Location: patients.php");
            exit();
            
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Failed to register patient: " . $e->getMessage();
        }
    }
}
?>

<div class="clinical-card">
    <div class="card-header">
        <h5>Patient Demographics Registration</h5>
        <a href="patients.php" class="btn btn-sm btn-outline-secondary py-2 px-3" style="font-size: 0.8rem; border-color: var(--card-border);">
            <i class="fas fa-arrow-left me-1"></i> Back to Patient Directory
        </a>
    </div>
    <div class="card-body">
        <form action="register.php" method="POST">
            <div class="row g-3">
                <!-- Personal details -->
                <div class="col-12 col-md-6">
                    <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" id="full_name" class="form-control" placeholder="e.g. John Doe" required>
                </div>
                
                <div class="col-12 col-md-3">
                    <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                    <select name="gender" id="gender" class="form-select" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label for="blood_group" class="form-label">Blood Group <span class="text-danger">*</span></label>
                    <select name="blood_group" id="blood_group" class="form-select" required>
                        <option value="">Select Blood Group</option>
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

                <div class="col-12 col-md-4">
                    <label for="dob_input" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                    <input type="date" name="date_of_birth" id="dob_input" class="form-control" max="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="col-12 col-md-2">
                    <label for="age_display" class="form-label">Calculated Age</label>
                    <input type="text" id="age_display" class="form-control" placeholder="0" readonly style="background-color: #f1f5f9;">
                </div>

                <div class="col-12 col-md-6">
                    <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                    <input type="tel" name="phone" id="phone" class="form-control" placeholder="e.g. 08012345678" required>
                </div>

                <div class="col-12 col-md-6">
                    <label for="email" class="form-label">Email Address (Optional)</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="e.g. john@example.com">
                </div>

                <div class="col-12 col-md-6">
                    <label for="emergency_contact" class="form-label">Emergency Contact <span class="text-danger">*</span></label>
                    <input type="text" name="emergency_contact" id="emergency_contact" class="form-control" placeholder="Name (Relationship) - Phone" required>
                </div>

                <div class="col-12">
                    <label for="address" class="form-label">Residential Address <span class="text-danger">*</span></label>
                    <textarea name="address" id="address" class="form-control" rows="3" placeholder="Enter home address" required></textarea>
                </div>
            </div>
            
            <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                <button type="reset" class="btn btn-secondary py-2">
                    <i class="fas fa-undo me-1"></i> Reset Form
                </button>
                <button type="submit" class="btn btn-primary py-2 px-4">
                    <i class="fas fa-save me-1"></i> Register Patient
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
