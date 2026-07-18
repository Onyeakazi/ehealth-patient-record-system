<?php
// admin/reports.php
$path_to_root = '../';
$page_title = 'System Reports & Logs';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

try {
    // 1. Fetch Patient registry reports
    $patient_report = $pdo->query("SELECT * FROM patients ORDER BY created_at DESC")->fetchAll();
    
    // 2. Fetch Medical consultations report
    $medical_report = $pdo->query("
        SELECT mr.*, p.full_name as patient_name, p.patient_number, u.full_name as doctor_name 
        FROM medical_records mr
        JOIN patients p ON mr.patient_id = p.patient_id
        JOIN users u ON mr.doctor_id = u.user_id
        ORDER BY mr.created_at DESC
    ")->fetchAll();
    
    // 3. Fetch Appointments distribution report
    $appointment_report = $pdo->query("
        SELECT a.*, p.full_name as patient_name, p.patient_number, u.full_name as doctor_name 
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users u ON a.doctor_id = u.user_id
        ORDER BY a.appointment_date DESC
    ")->fetchAll();
    
    // 4. Fetch Full Audit trail log
    $audit_report = $pdo->query("
        SELECT al.*, u.full_name as staff_name, u.username, u.role 
        FROM audit_logs al
        LEFT JOIN users u ON al.user_id = u.user_id
        ORDER BY al.created_at DESC
    ")->fetchAll();
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error loading reports: " . $e->getMessage();
    $patient_report = [];
    $medical_report = [];
    $appointment_report = [];
    $audit_report = [];
}
?>

<div class="clinical-card">
    <div class="card-header" style="padding-bottom: 0;">
        <ul class="nav custom-nav-tabs" id="reportTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="patients-report-tab" data-bs-toggle="tab" data-bs-target="#patients-report" type="button" role="tab" aria-controls="patients-report" aria-selected="true">
                    <i class="fas fa-user-injured me-1"></i> Patient Registry (<?php echo count($patient_report); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="records-report-tab" data-bs-toggle="tab" data-bs-target="#records-report" type="button" role="tab" aria-controls="records-report" aria-selected="false">
                    <i class="fas fa-file-medical-alt me-1"></i> Medical History (<?php echo count($medical_report); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="appts-report-tab" data-bs-toggle="tab" data-bs-target="#appts-report" type="button" role="tab" aria-controls="appts-report" aria-selected="false">
                    <i class="far fa-calendar-alt me-1"></i> Appointments List (<?php echo count($appointment_report); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="audit-report-tab" data-bs-toggle="tab" data-bs-target="#audit-report" type="button" role="tab" aria-controls="audit-report" aria-selected="false">
                    <i class="fas fa-history me-1"></i> Audit Trail (<?php echo count($audit_report); ?>)
                </button>
            </li>
        </ul>
    </div>
    
    <div class="card-body">
        <!-- Live search box across reports -->
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <span class="text-muted" style="font-size: 0.85rem;">Filters apply to the current active tab's dataset.</span>
            <input type="text" id="tableSearch" class="form-control form-control-sm py-2 px-3" placeholder="Filter active report..." style="border-radius: 8px; width: 250px; font-size: 0.8rem; border-color: var(--card-border);">
        </div>
        
        <div class="tab-content" id="reportTabsContent">
            
            <!-- Patient Registry Report -->
            <div class="tab-pane fade show active" id="patients-report" role="tabpanel" aria-labelledby="patients-report-tab">
                <div class="table-responsive">
                    <table class="table custom-table">
                        <thead>
                            <tr>
                                <th>Patient No.</th>
                                <th>Full Name</th>
                                <th>Gender</th>
                                <th>DOB (Age)</th>
                                <th>Phone</th>
                                <th>Blood Group</th>
                                <th>Registered Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($patient_report as $p): ?>
                                <tr>
                                    <td><span class="badge bg-light text-dark border p-2" style="font-size: 0.8rem; font-weight: 600;"><?php echo htmlspecialchars($p['patient_number']); ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($p['full_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($p['gender']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($p['date_of_birth']); ?><br>
                                        <small class="text-muted">
                                            <?php 
                                                $dob = new DateTime($p['date_of_birth']);
                                                $today = new DateTime();
                                                echo $today->diff($dob)->y . " years old";
                                            ?>
                                        </small>
                                    </td>
                                    <td><?php echo htmlspecialchars($p['phone']); ?></td>
                                    <td><span class="badge bg-danger bg-opacity-10 text-danger p-2" style="font-size: 0.8rem;"><?php echo htmlspecialchars($p['blood_group']); ?></span></td>
                                    <td><small class="text-muted"><?php echo date('M d, Y', strtotime($p['created_at'])); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Medical History Report -->
            <div class="tab-pane fade" id="records-report" role="tabpanel" aria-labelledby="records-report-tab">
                <div class="table-responsive">
                    <table class="table custom-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Patient Details</th>
                                <th>Diagnosis</th>
                                <th>Treatment Protocol</th>
                                <th>Prescription</th>
                                <th>Consulting Doctor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($medical_report as $rec): ?>
                                <tr>
                                    <td><small class="text-muted"><?php echo date('M d, Y h:i A', strtotime($rec['created_at'])); ?></small></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($rec['patient_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($rec['patient_number']); ?></small>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($rec['diagnosis']); ?></strong></td>
                                    <td><small><?php echo htmlspecialchars($rec['treatment']); ?></small></td>
                                    <td><small class="text-danger fw-bold"><i class="fas fa-prescription me-1"></i> <?php echo htmlspecialchars($rec['prescription']); ?></small></td>
                                    <td><small><i class="fas fa-user-md text-muted me-1"></i> <?php echo htmlspecialchars($rec['doctor_name']); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Appointments Report -->
            <div class="tab-pane fade" id="appts-report" role="tabpanel" aria-labelledby="appts-report-tab">
                <div class="table-responsive">
                    <table class="table custom-table">
                        <thead>
                            <tr>
                                <th>Patient No.</th>
                                <th>Patient Name</th>
                                <th>Doctor Assigned</th>
                                <th>Consultation Date</th>
                                <th>Status</th>
                                <th>Scheduled On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointment_report as $appt): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($appt['patient_number']); ?></code></td>
                                    <td><strong><?php echo htmlspecialchars($appt['patient_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($appt['doctor_name']); ?></td>
                                    <td><i class="far fa-clock text-muted me-1"></i> <?php echo date('M d, Y h:i A', strtotime($appt['appointment_date'])); ?></td>
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
            </div>
            
            <!-- Audit Trail Report -->
            <div class="tab-pane fade" id="audit-report" role="tabpanel" aria-labelledby="audit-report-tab">
                <div class="table-responsive">
                    <table class="table custom-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Timestamp</th>
                                <th>Username</th>
                                <th>Staff Name</th>
                                <th>Role</th>
                                <th>Action Performed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($audit_report as $log): ?>
                                <tr>
                                    <td><code>#<?php echo $log['log_id']; ?></code></td>
                                    <td><small class="text-muted"><?php echo date('M d, Y - h:i:s A', strtotime($log['created_at'])); ?></small></td>
                                    <td><code><?php echo htmlspecialchars($log['username'] ?? 'system'); ?></code></td>
                                    <td><?php echo htmlspecialchars($log['staff_name'] ?? 'System Process'); ?></td>
                                    <td>
                                        <span class="badge bg-secondary p-1 text-uppercase" style="font-size: 0.65rem;">
                                            <?php echo htmlspecialchars($log['role'] ?? 'system'); ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($log['action']); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
