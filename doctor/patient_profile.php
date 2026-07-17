<?php
// doctor/patient_profile.php
$path_to_root = '../';
$page_title = 'Patient Medical Chart';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

$patient_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$doctor_id = $_SESSION['user_id'];

if ($patient_id <= 0) {
    $_SESSION['error_message'] = "Invalid Patient ID.";
    header("Location: patients.php");
    exit();
}

// 1. Handle New Medical Record Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_medical_record'])) {
    $symptoms = isset($_POST['symptoms']) ? trim($_POST['symptoms']) : '';
    $diagnosis = isset($_POST['diagnosis']) ? trim($_POST['diagnosis']) : '';
    $treatment = isset($_POST['treatment']) ? trim($_POST['treatment']) : '';
    $prescription = isset($_POST['prescription']) ? trim($_POST['prescription']) : '';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

    if (empty($symptoms) || empty($diagnosis) || empty($treatment) || empty($prescription)) {
        $_SESSION['error_message'] = "Please fill in all clinical fields (Symptoms, Diagnosis, Treatment, Prescription).";
    } else {
        try {
            $insert_stmt = $pdo->prepare("
                INSERT INTO medical_records (patient_id, doctor_id, symptoms, diagnosis, treatment, prescription, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $insert_stmt->execute([$patient_id, $doctor_id, $symptoms, $diagnosis, $treatment, $prescription, $notes]);
            
            // Get patient info for logging
            $p_stmt = $pdo->prepare("SELECT patient_number, full_name FROM patients WHERE patient_id = ?");
            $p_stmt->execute([$patient_id]);
            $pat = $p_stmt->fetch();
            
            log_audit_action($pdo, $doctor_id, "Added medical record for " . $pat['full_name'] . " (" . $pat['patient_number'] . "): Diagnosis - $diagnosis");
            
            $_SESSION['success_message'] = "Clinical record added successfully.";
            header("Location: patient_profile.php?id=" . $patient_id);
            exit();
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Database error saving medical record: " . $e->getMessage();
        }
    }
}

try {
    // 2. Fetch Patient Demographics
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch();
    
    if (!$patient) {
        $_SESSION['error_message'] = "Patient record not found.";
        header("Location: patients.php");
        exit();
    }
    
    // 3. Fetch Patient Medical Records
    $stmt_med = $pdo->prepare("
        SELECT mr.*, u.full_name as doctor_name 
        FROM medical_records mr
        JOIN users u ON mr.doctor_id = u.user_id
        WHERE mr.patient_id = ?
        ORDER BY mr.created_at DESC
    ");
    $stmt_med->execute([$patient_id]);
    $medical_history = $stmt_med->fetchAll();
    
    // 4. Fetch Patient Vitals History
    $stmt_vit = $pdo->prepare("
        SELECT v.*, u.full_name as nurse_name 
        FROM vital_signs v
        JOIN users u ON v.nurse_id = u.user_id
        WHERE v.patient_id = ?
        ORDER BY v.created_at DESC
    ");
    $stmt_vit->execute([$patient_id]);
    $vitals_history = $stmt_vit->fetchAll();
    
    // 5. Fetch Patient Appointment History
    $stmt_appt = $pdo->prepare("
        SELECT a.*, u.full_name as doctor_name 
        FROM appointments a
        JOIN users u ON a.doctor_id = u.user_id
        WHERE a.patient_id = ?
        ORDER BY a.appointment_date DESC
    ");
    $stmt_appt->execute([$patient_id]);
    $appointment_history = $stmt_appt->fetchAll();
    
} catch (PDOException $e) {
    die("Database access error: " . $e->getMessage());
}
?>

<div class="row g-4 mb-4">
    <!-- Patient Demographics Info Panel -->
    <div class="col-12 col-xl-4">
        <div class="clinical-card">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-id-card text-teal me-2"></i>Patient Information</h5>
                <span class="badge bg-teal" style="background-color: var(--primary-color) !important;"><?php echo htmlspecialchars($patient['patient_number']); ?></span>
            </div>
            <div class="card-body">
                <div class="mb-3 text-center">
                    <div class="avatar-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; border-radius: 50%; background-color: var(--accent-teal); color: var(--primary-color); font-weight: 700; font-size: 1.5rem;">
                        <?php 
                            $parts = explode(' ', $patient['full_name']);
                            echo isset($parts[0][0]) ? $parts[0][0] : '';
                            echo isset($parts[1][0]) ? $parts[1][0] : '';
                        ?>
                    </div>
                    <h5 class="mb-0" style="font-weight: 700;"><?php echo htmlspecialchars($patient['full_name']); ?></h5>
                    <small class="text-muted">Registered since <?php echo date('M Y', strtotime($patient['created_at'])); ?></small>
                </div>
                
                <table class="table table-sm table-borderless mt-3" style="font-size: 0.85rem;">
                    <tbody>
                        <tr>
                            <td class="text-muted" style="width: 35%;">Gender:</td>
                            <td><strong><?php echo htmlspecialchars($patient['gender']); ?></strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Date of Birth:</td>
                            <td><strong><?php echo htmlspecialchars($patient['date_of_birth']); ?></strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Age:</td>
                            <td>
                                <strong>
                                    <?php 
                                        $dob = new DateTime($patient['date_of_birth']);
                                        $today = new DateTime();
                                        echo $today->diff($dob)->y;
                                    ?> years
                                </strong>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Blood Group:</td>
                            <td><span class="badge bg-danger bg-opacity-10 text-danger px-2"><?php echo htmlspecialchars($patient['blood_group']); ?></span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Phone:</td>
                            <td><strong><?php echo htmlspecialchars($patient['phone']); ?></strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Email:</td>
                            <td class="text-truncate" style="max-width: 150px;"><strong><?php echo $patient['email'] ? htmlspecialchars($patient['email']) : 'N/A'; ?></strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Address:</td>
                            <td><strong><?php echo htmlspecialchars($patient['address']); ?></strong></td>
                        </tr>
                        <tr class="border-top">
                            <td class="text-muted pt-2">Emergency Contact:</td>
                            <td class="pt-2 text-danger"><strong><?php echo htmlspecialchars($patient['emergency_contact']); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
                
                <button class="btn btn-primary w-100 py-2 mt-3" data-bs-toggle="modal" data-bs-target="#newRecordModal">
                    <i class="fas fa-file-medical me-1"></i> Add Consult Entry
                </button>
            </div>
        </div>
    </div>
    
    <!-- Tabbed Chart Directory -->
    <div class="col-12 col-xl-8">
        <div class="clinical-card">
            <div class="card-header" style="padding-bottom: 0;">
                <ul class="nav custom-nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="records-tab" data-bs-toggle="tab" data-bs-target="#records" type="button" role="tab" aria-controls="records" aria-selected="true">
                            <i class="fas fa-notes-medical me-1"></i> Medical History (<?php echo count($medical_history); ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="vitals-tab" data-bs-toggle="tab" data-bs-target="#vitals" type="button" role="tab" aria-controls="vitals" aria-selected="false">
                            <i class="fas fa-heartbeat me-1"></i> Vitals Log (<?php echo count($vitals_history); ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="appts-tab" data-bs-toggle="tab" data-bs-target="#appts" type="button" role="tab" aria-controls="appts" aria-selected="false">
                            <i class="far fa-calendar-check me-1"></i> Appointments (<?php echo count($appointment_history); ?>)
                        </button>
                    </li>
                </ul>
            </div>
            
            <div class="card-body">
                <div class="tab-content" id="myTabContent">
                    
                    <!-- Medical Records History Tab -->
                    <div class="tab-pane fade show active" id="records" role="tabpanel" aria-labelledby="records-tab">
                        <?php if (empty($medical_history)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-file-prescription mb-3" style="font-size: 2.5rem; color: #cbd5e1;"></i>
                                <p class="mb-0">No consultation records exist for this patient chart.</p>
                            </div>
                        <?php else: ?>
                            <div class="accordion accordion-flush" id="medRecordsAccordion">
                                <?php foreach ($medical_history as $index => $rec): ?>
                                    <div class="accordion-item mb-3 border rounded shadow-sm overflow-hidden" style="border-color: var(--card-border) !important;">
                                        <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                            <button class="accordion-button <?php echo ($index === 0) ? '' : 'collapsed'; ?> bg-light text-dark p-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $index; ?>" aria-expanded="<?php echo ($index === 0) ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $index; ?>" style="font-size: 0.9rem; font-weight: 600;">
                                                <div class="d-flex justify-content-between align-items-center w-100 pe-3">
                                                    <div>
                                                        <i class="fas fa-file-invoice text-teal me-2"></i>
                                                        Diagnosis: <strong><?php echo htmlspecialchars($rec['diagnosis']); ?></strong>
                                                    </div>
                                                    <small class="text-muted"><?php echo date('M d, Y', strtotime($rec['created_at'])); ?></small>
                                                </div>
                                            </button>
                                        </h2>
                                        <div id="collapse<?php echo $index; ?>" class="accordion-collapse collapse <?php echo ($index === 0) ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $index; ?>" data-bs-parent="#medRecordsAccordion">
                                            <div class="accordion-body bg-white p-4" style="font-size: 0.875rem;">
                                                <div class="mb-2">
                                                    <span class="text-muted d-block" style="font-size: 0.75rem; text-transform:uppercase; font-weight:600;">Symptoms & Complaints</span>
                                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($rec['symptoms'])); ?></p>
                                                </div>
                                                <div class="mb-2 mt-3 border-top pt-2">
                                                    <span class="text-muted d-block" style="font-size: 0.75rem; text-transform:uppercase; font-weight:600;">Treatment Protocol</span>
                                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($rec['treatment'])); ?></p>
                                                </div>
                                                <div class="mb-2 mt-3 border-top pt-2">
                                                    <span class="text-muted d-block" style="font-size: 0.75rem; text-transform:uppercase; font-weight:600;">Prescription / Dosage</span>
                                                    <p class="mb-0 text-danger fw-bold"><i class="fas fa-prescription me-1"></i> <?php echo nl2br(htmlspecialchars($rec['prescription'])); ?></p>
                                                </div>
                                                <?php if ($rec['notes']): ?>
                                                    <div class="mb-2 mt-3 border-top pt-2">
                                                        <span class="text-muted d-block" style="font-size: 0.75rem; text-transform:uppercase; font-weight:600;">Clinical Notes</span>
                                                        <p class="mb-0 text-muted"><?php echo nl2br(htmlspecialchars($rec['notes'])); ?></p>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="mt-4 pt-2 border-top text-end text-muted" style="font-size: 0.8rem;">
                                                    Assigned Physician: <strong>Dr. <?php echo htmlspecialchars($rec['doctor_name']); ?></strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Vitals History Tab -->
                    <div class="tab-pane fade" id="vitals" role="tabpanel" aria-labelledby="vitals-tab">
                        <?php if (empty($vitals_history)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-heartbeat mb-3" style="font-size: 2.5rem; color: #cbd5e1;"></i>
                                <p class="mb-0">No vital sign entries recorded for this patient.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table custom-table">
                                    <thead>
                                        <tr>
                                            <th>Temperature</th>
                                            <th>BP</th>
                                            <th>Weight</th>
                                            <th>Height</th>
                                            <th>Logged By</th>
                                            <th>Date Logged</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($vitals_history as $v): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-opacity-10 p-2 <?php echo ($v['temperature'] > 37.5 || $v['temperature'] < 35.5) ? 'bg-danger text-danger' : 'bg-success text-success'; ?>">
                                                        <?php echo htmlspecialchars($v['temperature']); ?> &deg;C
                                                    </span>
                                                </td>
                                                <td><strong><?php echo htmlspecialchars($v['blood_pressure']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($v['weight']); ?> kg</td>
                                                <td><?php echo htmlspecialchars($v['height']); ?> cm</td>
                                                <td><small class="text-muted"><?php echo htmlspecialchars($v['nurse_name']); ?></small></td>
                                                <td><small class="text-muted"><?php echo date('M d, Y - h:i A', strtotime($v['created_at'])); ?></small></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Appointment History Tab -->
                    <div class="tab-pane fade" id="appts" role="tabpanel" aria-labelledby="appts-tab">
                        <?php if (empty($appointment_history)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="far fa-calendar mb-3" style="font-size: 2.5rem; color: #cbd5e1;"></i>
                                <p class="mb-0">No appointment records found for this patient.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table custom-table">
                                    <thead>
                                        <tr>
                                            <th>Doctor Name</th>
                                            <th>Schedule Date</th>
                                            <th>Status</th>
                                            <th>Created At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($appointment_history as $appt): ?>
                                            <tr>
                                                <td><strong>Dr. <?php echo htmlspecialchars($appt['doctor_name']); ?></strong></td>
                                                <td><?php echo date('M d, Y h:i A', strtotime($appt['appointment_date'])); ?></td>
                                                <td>
                                                    <span class="badge badge-role status-<?php echo strtolower($appt['status']); ?>">
                                                        <?php echo htmlspecialchars($appt['status']); ?>
                                                    </span>
                                                </td>
                                                <td><small class="text-muted"><?php echo date('M d, Y', strtotime($appt['created_at'])); ?></small></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: New Consult Medical Entry Form -->
<div class="modal fade" id="newRecordModal" tabindex="-1" aria-labelledby="newRecordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newRecordModalLabel"><i class="fas fa-file-prescription text-teal me-2"></i>Add Consultation Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="patient_profile.php?id=<?php echo $patient_id; ?>" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="add_medical_record" value="1">
                    
                    <div class="mb-3">
                        <label for="symptoms" class="form-label">Symptoms & Complaints <span class="text-danger">*</span></label>
                        <textarea name="symptoms" id="symptoms" class="form-control" rows="3" placeholder="Enter patient complaints and physical symptoms..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="diagnosis" class="form-label">Diagnosis <span class="text-danger">*</span></label>
                        <input type="text" name="diagnosis" id="diagnosis" class="form-control" placeholder="e.g. Essential Hypertension, Acute Tonsillitis" required>
                    </div>

                    <div class="mb-3">
                        <label for="treatment" class="form-label">Treatment Protocol <span class="text-danger">*</span></label>
                        <textarea name="treatment" id="treatment" class="form-control" rows="3" placeholder="e.g. Bed rest, dietary restrictions, follow-up in 2 weeks" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="prescription" class="form-label">Prescription / Dosage <span class="text-danger">*</span></label>
                        <textarea name="prescription" id="prescription" class="form-control" rows="3" placeholder="e.g. Tab Amlodipine 5mg once daily for 30 days" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Internal Doctor Notes (Optional)</label>
                        <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Enter any extra details or clinical warnings..."></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
