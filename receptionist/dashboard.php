<?php
// receptionist/dashboard.php
$path_to_root = '../';
$page_title = 'Receptionist Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

// Handle Checkout / Discharge Action (when receptionist clicks to open/view slip)
if (isset($_GET['action']) && $_GET['action'] === 'checkout' && isset($_GET['id'])) {
    $appt_id = intval($_GET['id']);
    try {
        // Update appointment status to 'Discharged'
        $update_stmt = $pdo->prepare("UPDATE appointments SET status = 'Discharged' WHERE appointment_id = ?");
        $update_stmt->execute([$appt_id]);
        
        // Log action in audit logs
        log_audit_action($pdo, $_SESSION['user_id'], "Checked out and discharged patient for appointment ID: $appt_id");
        
        // Store in session to automatically trigger opening this prescription slip on reload
        $_SESSION['open_checkout_slip_id'] = $appt_id;
        
        header("Location: dashboard.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error discharging patient: " . $e->getMessage();
    }
}

try {
    // 1. Get Total Patients
    $total_patients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
    
    // 2. Get Today's Appointments
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE DATE(appointment_date) = ?");
    $stmt->execute([$today]);
    $today_appointments = $stmt->fetchColumn();
    
    // 3. Get Recent Patient Registrations (last 5)
    $recent_patients = $pdo->query("SELECT * FROM patients ORDER BY patient_id DESC LIMIT 5")->fetchAll();
    
    // 4. Get Today's Appointments Detailed List
    $appt_stmt = $pdo->prepare("
        SELECT a.appointment_id, p.full_name as patient_name, u.full_name as doctor_name, a.appointment_date, a.status 
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users u ON a.doctor_id = u.user_id
        WHERE DATE(a.appointment_date) = ?
        ORDER BY a.appointment_date ASC
    ");
    $appt_stmt->execute([$today]);
    $today_appointments_list = $appt_stmt->fetchAll();

    // 5. Get count of Completed Consultations today (ensuring latest appointment status is active 'Completed')
    $completed_today_stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM medical_records mr
        WHERE DATE(mr.created_at) = ?
          AND (
              SELECT status FROM appointments 
              WHERE patient_id = mr.patient_id AND doctor_id = mr.doctor_id 
              ORDER BY appointment_date DESC LIMIT 1
          ) = 'Completed'
    ");
    $completed_today_stmt->execute([$today]);
    $completed_today_count = $completed_today_stmt->fetchColumn();

    // 6. Get Completed Consultations list today (ensuring latest appointment status is active 'Completed')
    $completed_stmt = $pdo->prepare("
        SELECT mr.record_id, mr.patient_id, mr.doctor_id, mr.symptoms, mr.diagnosis, mr.treatment, mr.prescription, mr.created_at,
               (
                   SELECT appointment_id FROM appointments 
                   WHERE patient_id = mr.patient_id AND doctor_id = mr.doctor_id 
                   ORDER BY appointment_date DESC LIMIT 1
               ) as appointment_id,
               p.full_name as patient_name, p.patient_number, p.gender, p.date_of_birth,
               u.full_name as doctor_name
        FROM medical_records mr
        JOIN patients p ON mr.patient_id = p.patient_id
        JOIN users u ON mr.doctor_id = u.user_id
        WHERE DATE(mr.created_at) = ?
          AND (
              SELECT status FROM appointments 
              WHERE patient_id = mr.patient_id AND doctor_id = mr.doctor_id 
              ORDER BY appointment_date DESC LIMIT 1
          ) = 'Completed'
        ORDER BY mr.created_at DESC
    ");
    $completed_stmt->execute([$today]);
    $completed_list = $completed_stmt->fetchAll();

    // Attach latest vitals and age to each completed consultation
    foreach ($completed_list as &$rec) {
        $v_stmt = $pdo->prepare("SELECT * FROM vital_signs WHERE patient_id = ? ORDER BY created_at DESC LIMIT 1");
        $v_stmt->execute([$rec['patient_id']]);
        $vitals = $v_stmt->fetch();
        $rec['vitals_temp'] = $vitals ? $vitals['temperature'] . ' °C' : 'N/A';
        $rec['vitals_bp'] = $vitals ? $vitals['blood_pressure'] : 'N/A';
        $rec['vitals_weight'] = $vitals ? $vitals['weight'] . ' kg' : 'N/A';
        $rec['vitals_height'] = $vitals ? $vitals['height'] . ' cm' : 'N/A';
        
        // Calculate age
        $dob = new DateTime($rec['date_of_birth']);
        $now = new DateTime();
        $rec['age'] = $now->diff($dob)->y;
    }
    unset($rec);
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error loading dashboard metrics: " . $e->getMessage();
    $total_patients = 0;
    $today_appointments = 0;
    $recent_patients = [];
    $today_appointments_list = [];
    $completed_today_count = 0;
    $completed_list = [];
}
?>

<div class="row g-4 mb-4">
    <!-- Stat Cards -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <h5>Total Patients</h5>
                <h2><?php echo $total_patients; ?></h2>
            </div>
            <div class="stat-icon teal-bg">
                <i class="fas fa-user-injured"></i>
            </div>
        </div>
    </div>
    
    <div class="col-12 col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <h5>Today's Appointments</h5>
                <h2><?php echo $today_appointments; ?></h2>
            </div>
            <div class="stat-icon blue-bg">
                <i class="fas fa-calendar-check"></i>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <h5>Completed Today</h5>
                <div class="d-flex align-items-center gap-2">
                    <h2 class="mb-0"><?php echo $completed_today_count; ?></h2>
                    <?php if ($completed_today_count > 0): ?>
                        <span class="badge bg-danger animation-pulse" style="font-size: 0.7rem;">New</span>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2 mt-2" data-bs-toggle="modal" data-bs-target="#checkoutListModal" style="font-size: 0.7rem; border-radius: 6px;">
                    <i class="fas fa-search me-1"></i> View Checkouts
                </button>
            </div>
            <div class="stat-icon" style="background-color: #e6fcf5; color: var(--primary-color);">
                <i class="fas fa-check-double"></i>
            </div>
        </div>
    </div>
    
    <div class="col-12 col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <h5>Quick Actions</h5>
                <div class="mt-2 d-flex gap-2">
                    <a href="register.php" class="btn btn-sm btn-primary py-2 px-3" style="font-size: 0.8rem;">
                        <i class="fas fa-plus me-1"></i> Register Patient
                    </a>
                    <a href="appointments.php" class="btn btn-sm btn-outline-secondary py-2 px-3" style="font-size: 0.8rem; border-color: var(--card-border);">
                        <i class="fas fa-calendar-plus me-1"></i> Book Appt
                    </a>
                </div>
            </div>
            <div class="stat-icon purple-bg">
                <i class="fas fa-hospital-symbol"></i>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Today's Appointments -->
    <div class="col-12 col-lg-7">
        <div class="clinical-card">
            <div class="card-header">
                <h5>Today's Scheduled Appointments</h5>
                <span class="badge bg-teal py-2 px-3" style="background-color: var(--primary-color) !important; font-size: 0.75rem; font-weight:600;">
                    <?php echo date('M d, Y'); ?>
                </span>
            </div>
            <div class="card-body">
                <?php if (empty($today_appointments_list)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="far fa-calendar-times mb-2" style="font-size: 2.5rem; color: #cbd5e1;"></i>
                        <p class="mb-0">No appointments scheduled for today.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table custom-table">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Assigned Doctor</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($today_appointments_list as $appt): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($appt['patient_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($appt['doctor_name']); ?></td>
                                        <td><?php echo date('h:i A', strtotime($appt['appointment_date'])); ?></td>
                                        <td>
                                            <span class="badge badge-role status-<?php echo strtolower($appt['status']); ?>">
                                                <?php echo htmlspecialchars($appt['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent Registrations -->
    <div class="col-12 col-lg-5">
        <div class="clinical-card">
            <div class="card-header">
                <h5>Recent Patient Registrations</h5>
                <a href="patients.php" class="btn btn-sm btn-link text-decoration-none" style="color: var(--primary-color); font-weight: 600; font-size: 0.8rem;">
                    View All <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_patients)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-users mb-2" style="font-size: 2.5rem; color: #cbd5e1;"></i>
                        <p class="mb-0">No patient records found.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_patients as $pat): ?>
                            <div class="list-group-item px-0 py-3 d-flex align-items-center justify-content-between" style="border-color: #f1f5f9;">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; border-radius: 50%; background-color: #f1f5f9; color: var(--primary-color); font-weight: 700; font-size: 0.85rem;">
                                        <?php 
                                            $parts = explode(' ', $pat['full_name']);
                                            echo isset($parts[0][0]) ? $parts[0][0] : '';
                                            echo isset($parts[1][0]) ? $parts[1][0] : '';
                                        ?>
                                    </div>
                                    <div>
                                        <h6 class="mb-0" style="font-size: 0.9rem; font-weight: 600;"><?php echo htmlspecialchars($pat['full_name']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($pat['patient_number']); ?> &bull; <?php echo htmlspecialchars($pat['gender']); ?></small>
                                    </div>
                                </div>
                                <span class="text-muted" style="font-size: 0.75rem;"><?php echo date('M d', strtotime($pat['created_at'])); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Checkout List Modal -->
<div class="modal fade" id="checkoutListModal" tabindex="-1" aria-labelledby="checkoutListModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
            <div class="modal-header bg-teal text-white" style="border-radius: 12px 12px 0 0; background-color: var(--primary-color) !important;">
                <h5 class="modal-title" id="checkoutListModalLabel"><i class="fas fa-clipboard-list me-2"></i> Today's Completed Consultations</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <?php if (empty($completed_list)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-notes-medical mb-3" style="font-size: 3rem; color: #cbd5e1;"></i>
                        <h5>No Completed Consultations Today</h5>
                        <p class="mb-0">Once doctors complete patient consultation entries, they will appear here for checkout.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table custom-table">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Diagnosis</th>
                                    <th>Consulting Doctor</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($completed_list as $rec): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($rec['patient_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($rec['patient_number']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-teal bg-opacity-10 text-teal p-2" style="font-size: 0.8rem; color: var(--primary-color) !important; font-weight: 600;">
                                                <?php echo htmlspecialchars($rec['diagnosis']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><i class="fas fa-user-md text-muted me-1"></i> <?php echo htmlspecialchars($rec['doctor_name']); ?></small>
                                        </td>
                                        <td class="text-end">
                                             <a href="dashboard.php?action=checkout&id=<?php echo $rec['appointment_id']; ?>" 
                                                class="btn btn-sm btn-outline-primary py-2 px-3" 
                                                style="font-size: 0.75rem; border-radius: 6px;">
                                                 <i class="fas fa-file-invoice me-1"></i> View Slip
                                             </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary px-4 py-2" data-bs-dismiss="modal" style="border-radius: 8px;">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Checkout Slip Modal -->
<div class="modal fade" id="checkoutSlipModal" tabindex="-1" aria-labelledby="checkoutSlipModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
            <div class="modal-header bg-teal text-white" style="border-radius: 12px 12px 0 0; background-color: var(--primary-color) !important;">
                <h5 class="modal-title" id="checkoutSlipModalLabel"><i class="fas fa-file-invoice-dollar me-2"></i> Patient Discharge & Prescription Slip</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="printArea">
                <!-- Header of Slip -->
                <div class="text-center mb-4 pb-3 border-bottom">
                    <h3 class="fw-bold text-teal mb-1"><?php echo htmlspecialchars($system_name); ?></h3>
                    <p class="text-muted small mb-0">Official Patient Clinical Summary & Prescription Sheet</p>
                    <p class="text-muted small">Date: <span id="slipDate"></span></p>
                </div>
                
                <!-- Patient Info Row -->
                <div class="row mb-4 bg-light p-3 rounded" style="font-size: 0.9rem; margin: 0 0 1.5rem 0;">
                    <div class="col-6 mb-2">
                        <span class="text-muted">Patient Name:</span> <strong class="text-dark" id="slipPatName"></strong>
                    </div>
                    <div class="col-6 mb-2">
                        <span class="text-muted">Patient Number:</span> <strong class="text-dark" id="slipPatNum"></strong>
                    </div>
                    <div class="col-4">
                        <span class="text-muted">Gender:</span> <strong class="text-dark" id="slipPatGender"></strong>
                    </div>
                    <div class="col-4">
                        <span class="text-muted">Age:</span> <strong class="text-dark" id="slipPatAge"></strong> yrs
                    </div>
                    <div class="col-4">
                        <span class="text-muted">Physician:</span> <strong class="text-dark" id="slipDocName"></strong>
                    </div>
                </div>

                <!-- Triage Vitals Row -->
                <h6 class="text-teal border-bottom pb-2 mb-3"><i class="fas fa-heartbeat me-2"></i> Triage Vital Signs</h6>
                <div class="row mb-4 text-center" style="font-size: 0.9rem; margin: 0 0 1.5rem 0;">
                    <div class="col-3 border-end">
                        <small class="text-muted d-block">Temperature</small>
                        <strong class="text-dark" id="slipTemp"></strong>
                    </div>
                    <div class="col-3 border-end">
                        <small class="text-muted d-block">Blood Pressure</small>
                        <strong class="text-dark" id="slipBP"></strong>
                    </div>
                    <div class="col-3 border-end">
                        <small class="text-muted d-block">Weight</small>
                        <strong class="text-dark" id="slipWeight"></strong>
                    </div>
                    <div class="col-3">
                        <small class="text-muted d-block">Height</small>
                        <strong class="text-dark" id="slipHeight"></strong>
                    </div>
                </div>

                <!-- Clinical Details -->
                <h6 class="text-teal border-bottom pb-2 mb-3"><i class="fas fa-diagnoses me-2"></i> Medical Assessment</h6>
                <div class="mb-4" style="font-size: 0.9rem;">
                    <div class="mb-3">
                        <span class="text-muted d-block mb-1">Symptoms / Chief Complaint:</span>
                        <div class="p-3 border rounded bg-white" id="slipSymptoms" style="min-height: 50px;"></div>
                    </div>
                    <div>
                        <span class="text-muted d-block mb-1">Diagnosis:</span>
                        <div class="p-3 border rounded bg-white fw-bold text-success" id="slipDiagnosis"></div>
                    </div>
                </div>

                <!-- Prescriptions & Advice -->
                <h6 class="text-danger border-bottom pb-2 mb-3"><i class="fas fa-prescription-bottle-alt me-2"></i> Rx (Prescriptions & Drugs)</h6>
                <div class="mb-4" style="font-size: 0.9rem;">
                    <div class="mb-3">
                        <span class="text-muted d-block mb-1 font-semibold">Prescribed Medications:</span>
                        <div class="p-3 border rounded text-danger bg-danger bg-opacity-10 fw-bold" id="slipPrescription" style="white-space: pre-wrap; font-family: inherit;"></div>
                    </div>
                    <div>
                        <span class="text-muted d-block mb-1 font-semibold">Treatment & Physician Advice:</span>
                        <div class="p-3 border rounded bg-white text-muted" id="slipTreatment" style="white-space: pre-wrap;"></div>
                    </div>
                </div>

                <div class="text-center mt-5 text-muted small border-top pt-3">
                    Thank you for choosing <?php echo htmlspecialchars($system_name); ?>. Please visit the clinic if symptoms persist.
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary px-4 py-2" data-bs-dismiss="modal" style="border-radius: 8px;">Close</button>
                <button type="button" class="btn btn-primary px-4 py-2" onclick="printCheckoutSlip()" style="border-radius: 8px; background-color: var(--primary-color); border-color: var(--primary-color);">
                    <i class="fas fa-print me-2"></i> Print / Save PDF
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkoutModal = document.getElementById('checkoutSlipModal');
    if (checkoutModal) {
        checkoutModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            
            // Extract info from data-* attributes
            const name = button.getAttribute('data-name');
            const number = button.getAttribute('data-number');
            const gender = button.getAttribute('data-gender');
            const age = button.getAttribute('data-age');
            const doctor = button.getAttribute('data-doctor');
            const date = button.getAttribute('data-date');
            const temp = button.getAttribute('data-temp');
            const bp = button.getAttribute('data-bp');
            const weight = button.getAttribute('data-weight');
            const height = button.getAttribute('data-height');
            const symptoms = button.getAttribute('data-symptoms');
            const diagnosis = button.getAttribute('data-diagnosis');
            const prescription = button.getAttribute('data-prescription');
            const treatment = button.getAttribute('data-treatment');
            
            // Populate modal fields
            document.getElementById('slipDate').textContent = date;
            document.getElementById('slipPatName').textContent = name;
            document.getElementById('slipPatNum').textContent = number;
            document.getElementById('slipPatGender').textContent = gender;
            document.getElementById('slipPatAge').textContent = age;
            document.getElementById('slipDocName').textContent = doctor;
            document.getElementById('slipTemp').textContent = temp;
            document.getElementById('slipBP').textContent = bp;
            document.getElementById('slipWeight').textContent = weight;
            document.getElementById('slipHeight').textContent = height;
            document.getElementById('slipSymptoms').textContent = symptoms;
            document.getElementById('slipDiagnosis').textContent = diagnosis;
            document.getElementById('slipPrescription').textContent = prescription;
            document.getElementById('slipTreatment').textContent = treatment;
        });
    }
});

function printCheckoutSlip() {
    window.print();
}
</script>

<?php if (isset($_SESSION['open_checkout_slip_id'])): ?>
    <?php
    $slip_id = $_SESSION['open_checkout_slip_id'];
    unset($_SESSION['open_checkout_slip_id']); // Clear immediately
    
    // Fetch this specific checkout record details
    $slip_stmt = $pdo->prepare("
        SELECT mr.*, p.full_name as patient_name, p.patient_number, p.gender, p.date_of_birth,
               u.full_name as doctor_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users u ON a.doctor_id = u.user_id
        JOIN medical_records mr ON mr.patient_id = a.patient_id AND mr.doctor_id = a.doctor_id
        WHERE a.appointment_id = ?
        ORDER BY mr.created_at DESC LIMIT 1
    ");
    $slip_stmt->execute([$slip_id]);
    $slip_rec = $slip_stmt->fetch();
    
    if ($slip_rec):
        // Calculate age
        $dob = new DateTime($slip_rec['date_of_birth']);
        $now = new DateTime();
        $slip_age = $now->diff($dob)->y;
        
        // Fetch vitals
        $v_stmt = $pdo->prepare("SELECT * FROM vital_signs WHERE patient_id = ? ORDER BY created_at DESC LIMIT 1");
        $v_stmt->execute([$slip_rec['patient_id']]);
        $vitals = $v_stmt->fetch();
        $slip_temp = $vitals ? $vitals['temperature'] . ' °C' : 'N/A';
        $slip_bp = $vitals ? $vitals['blood_pressure'] : 'N/A';
        $slip_weight = $vitals ? $vitals['weight'] . ' kg' : 'N/A';
        $slip_height = $vitals ? $vitals['height'] . ' cm' : 'N/A';
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Populate modal details
        document.getElementById('slipDate').textContent = "<?php echo date('M d, Y h:i A', strtotime($slip_rec['created_at'])); ?>";
        document.getElementById('slipPatName').textContent = "<?php echo addslashes($slip_rec['patient_name']); ?>";
        document.getElementById('slipPatNum').textContent = "<?php echo addslashes($slip_rec['patient_number']); ?>";
        document.getElementById('slipPatGender').textContent = "<?php echo $slip_rec['gender']; ?>";
        document.getElementById('slipPatAge').textContent = "<?php echo $slip_age; ?>";
        document.getElementById('slipDocName').textContent = "<?php echo addslashes($slip_rec['doctor_name']); ?>";
        document.getElementById('slipTemp').textContent = "<?php echo $slip_temp; ?>";
        document.getElementById('slipBP').textContent = "<?php echo $slip_bp; ?>";
        document.getElementById('slipWeight').textContent = "<?php echo $slip_weight; ?>";
        document.getElementById('slipHeight').textContent = "<?php echo $slip_height; ?>";
        document.getElementById('slipSymptoms').textContent = "<?php echo addslashes($slip_rec['symptoms']); ?>";
        document.getElementById('slipDiagnosis').textContent = "<?php echo addslashes($slip_rec['diagnosis']); ?>";
        document.getElementById('slipPrescription').textContent = <?php echo json_encode($slip_rec['prescription']); ?>;
        document.getElementById('slipTreatment').textContent = <?php echo json_encode($slip_rec['treatment']); ?>;
        
        // Show modal
        const slipModal = new bootstrap.Modal(document.getElementById('checkoutSlipModal'));
        slipModal.show();
    });
    </script>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
