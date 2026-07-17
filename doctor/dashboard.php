<?php
// doctor/dashboard.php
$path_to_root = '../';
$page_title = 'Doctor Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

$doctor_id = $_SESSION['user_id'];
$today = date('Y-m-d');

try {
    // 1. Get unique assigned patients (patients scheduled with this doctor)
    $stmt_patients = $pdo->prepare("SELECT COUNT(DISTINCT patient_id) FROM appointments WHERE doctor_id = ?");
    $stmt_patients->execute([$doctor_id]);
    $assigned_patients = $stmt_patients->fetchColumn();
    
    // 2. Get today's appointments count
    $stmt_appt_count = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND DATE(appointment_date) = ?");
    $stmt_appt_count->execute([$doctor_id, $today]);
    $today_appt_count = $stmt_appt_count->fetchColumn();
    
    // 3. Get recent medical records entered by this doctor (last 5)
    $stmt_records = $pdo->prepare("
        SELECT mr.*, p.full_name as patient_name, p.patient_number 
        FROM medical_records mr
        JOIN patients p ON mr.patient_id = p.patient_id
        WHERE mr.doctor_id = ?
        ORDER BY mr.created_at DESC
        LIMIT 5
    ");
    $stmt_records->execute([$doctor_id]);
    $recent_records = $stmt_records->fetchAll();
    
    // 4. Get today's appointments details
    $stmt_appt_list = $pdo->prepare("
        SELECT a.appointment_id, a.patient_id, p.full_name as patient_name, p.patient_number, a.appointment_date, a.status 
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        WHERE a.doctor_id = ? AND DATE(a.appointment_date) = ?
        ORDER BY a.appointment_date ASC
    ");
    $stmt_appt_list->execute([$doctor_id, $today]);
    $today_appointments = $stmt_appt_list->fetchAll();
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error loading doctor dashboard: " . $e->getMessage();
    $assigned_patients = 0;
    $today_appt_count = 0;
    $recent_records = [];
    $today_appointments = [];
}
?>

<div class="row g-4 mb-4">
    <!-- Stat Cards -->
    <div class="col-12 col-md-6 col-lg-4">
        <div class="stat-card">
            <div class="stat-info">
                <h5>Assigned Patients</h5>
                <h2><?php echo $assigned_patients; ?></h2>
            </div>
            <div class="stat-icon teal-bg">
                <i class="fas fa-user-injured"></i>
            </div>
        </div>
    </div>
    
    <div class="col-12 col-md-6 col-lg-4">
        <div class="stat-card">
            <div class="stat-info">
                <h5>Today's Consultations</h5>
                <h2><?php echo $today_appt_count; ?></h2>
            </div>
            <div class="stat-icon blue-bg">
                <i class="fas fa-calendar-alt"></i>
            </div>
        </div>
    </div>
    
    <div class="col-12 col-md-6 col-lg-4">
        <div class="stat-card">
            <div class="stat-info">
                <h5>Today's Status</h5>
                <p class="text-muted mb-0 mt-2" style="font-size: 0.85rem;">
                    You have <strong><?php echo $today_appt_count; ?></strong> patients scheduled on your calendar today.
                </p>
            </div>
            <div class="stat-icon purple-bg">
                <i class="fas fa-user-clock"></i>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Today's Appointments -->
    <div class="col-12 col-lg-6">
        <div class="clinical-card">
            <div class="card-header">
                <h5>My Consultations Today</h5>
                <a href="appointments.php" class="btn btn-sm btn-link text-decoration-none" style="color: var(--primary-color); font-weight: 600; font-size: 0.8rem;">
                    View Schedule <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($today_appointments)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="far fa-calendar-times mb-2" style="font-size: 2.5rem; color: #cbd5e1;"></i>
                        <p class="mb-0">No consultations scheduled for today.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table custom-table">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($today_appointments as $appt): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($appt['patient_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($appt['patient_number']); ?></small>
                                        </td>
                                        <td><?php echo date('h:i A', strtotime($appt['appointment_date'])); ?></td>
                                        <td>
                                            <span class="badge badge-role status-<?php echo strtolower($appt['status']); ?>">
                                                <?php echo htmlspecialchars($appt['status']); ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="patient_profile.php?id=<?php echo $appt['patient_id']; ?>" class="btn btn-xs btn-primary p-1 px-2" style="font-size: 0.75rem; border-radius: 4px;">
                                                View
                                            </a>
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
    
    <!-- Recent Clinical Notes -->
    <div class="col-12 col-lg-6">
        <div class="clinical-card">
            <div class="card-header">
                <h5>Recent Diagnosis & Notes</h5>
                <a href="medical_records.php" class="btn btn-sm btn-link text-decoration-none" style="color: var(--primary-color); font-weight: 600; font-size: 0.8rem;">
                    All History <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_records)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-file-medical-alt mb-2" style="font-size: 2.5rem; color: #cbd5e1;"></i>
                        <p class="mb-0">No recent diagnoses recorded.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_records as $rec): ?>
                            <div class="list-group-item px-0 py-3" style="border-color: #f1f5f9;">
                                <div class="d-flex justify-content-between mb-1">
                                    <h6 class="mb-0" style="font-weight: 700; font-size: 0.9rem;"><?php echo htmlspecialchars($rec['patient_name']); ?></h6>
                                    <small class="text-muted"><?php echo date('M d, Y', strtotime($rec['created_at'])); ?></small>
                                </div>
                                <div class="mb-1 text-muted" style="font-size: 0.8rem;">
                                    Diagnosis: <strong class="text-dark"><?php echo htmlspecialchars($rec['diagnosis']); ?></strong>
                                </div>
                                <p class="mb-0 text-truncate text-muted" style="font-size: 0.8rem; max-width: 100%;">
                                    Treatment: <?php echo htmlspecialchars($rec['treatment']); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
