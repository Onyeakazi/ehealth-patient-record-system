<?php
// receptionist/dashboard.php
$path_to_root = '../';
$page_title = 'Receptionist Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

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
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error loading dashboard metrics: " . $e->getMessage();
    $total_patients = 0;
    $today_appointments = 0;
    $recent_patients = [];
    $today_appointments_list = [];
}
?>

<div class="row g-4 mb-4">
    <!-- Stat Cards -->
    <div class="col-12 col-md-6 col-lg-4">
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
    
    <div class="col-12 col-md-6 col-lg-4">
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
    
    <div class="col-12 col-md-6 col-lg-4">
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
