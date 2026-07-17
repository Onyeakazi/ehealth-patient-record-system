<?php
// nurse/dashboard.php
$path_to_root = '../';
$page_title = 'Nurse Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

try {
    // 1. Get Total Patients Count
    $total_patients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
    
    // 2. Get Total Vitals Logged
    $total_vitals = $pdo->query("SELECT COUNT(*) FROM vital_signs")->fetchColumn();
    
    // 3. Get Recent Vital Sign Records (last 5)
    $vitals_list = $pdo->query("
        SELECT v.*, p.full_name as patient_name, p.patient_number, u.full_name as nurse_name 
        FROM vital_signs v
        JOIN patients p ON v.patient_id = p.patient_id
        JOIN users u ON v.nurse_id = u.user_id
        ORDER BY v.created_at DESC
        LIMIT 5
    ")->fetchAll();
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error loading nurse metrics: " . $e->getMessage();
    $total_patients = 0;
    $total_vitals = 0;
    $vitals_list = [];
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
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>
    
    <div class="col-12 col-md-6 col-lg-4">
        <div class="stat-card">
            <div class="stat-info">
                <h5>Total Vitals Logs</h5>
                <h2><?php echo $total_vitals; ?></h2>
            </div>
            <div class="stat-icon blue-bg">
                <i class="fas fa-heartbeat"></i>
            </div>
        </div>
    </div>
    
    <div class="col-12 col-md-6 col-lg-4">
        <div class="stat-card">
            <div class="stat-info">
                <h5>Quick Action</h5>
                <div class="mt-2">
                    <a href="vital_signs.php" class="btn btn-sm btn-primary py-2 px-3 w-100" style="font-size: 0.8rem;">
                        <i class="fas fa-plus-circle me-1"></i> Capture Vital Signs
                    </a>
                </div>
            </div>
            <div class="stat-icon purple-bg">
                <i class="fas fa-file-medical-alt"></i>
            </div>
        </div>
    </div>
</div>

<div class="clinical-card">
    <div class="card-header">
        <h5>Recent Patient Vital Sign Entries</h5>
        <a href="vital_signs.php" class="btn btn-sm btn-link text-decoration-none" style="color: var(--primary-color); font-weight: 600; font-size: 0.8rem;">
            Record/View All <i class="fas fa-arrow-right ms-1"></i>
        </a>
    </div>
    <div class="card-body">
        <?php if (empty($vitals_list)): ?>
            <div class="text-center py-4 text-muted">
                <i class="fas fa-heartbeat mb-2" style="font-size: 2.5rem; color: #cbd5e1;"></i>
                <p class="mb-0">No vital sign entries recorded yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table custom-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Temperature (&deg;C)</th>
                            <th>Blood Pressure</th>
                            <th>Weight (kg)</th>
                            <th>Height (cm)</th>
                            <th>Recorded By</th>
                            <th>Date / Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vitals_list as $v): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($v['patient_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($v['patient_number']); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-opacity-10 p-2 <?php echo ($v['temperature'] > 37.5 || $v['temperature'] < 35.5) ? 'bg-danger text-danger' : 'bg-success text-success'; ?>" style="font-size: 0.85rem;">
                                        <?php echo htmlspecialchars($v['temperature']); ?> &deg;C
                                    </span>
                                </td>
                                <td><strong><?php echo htmlspecialchars($v['blood_pressure']); ?></strong></td>
                                <td><?php echo htmlspecialchars($v['weight']); ?> kg</td>
                                <td><?php echo htmlspecialchars($v['height']); ?> cm</td>
                                <td><small class="text-muted"><i class="fas fa-user-nurse me-1"></i> <?php echo htmlspecialchars($v['nurse_name']); ?></small></td>
                                <td><small class="text-muted"><?php echo date('M d, Y - h:i A', strtotime($v['created_at'])); ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
