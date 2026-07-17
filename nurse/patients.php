<?php
// nurse/patients.php
$path_to_root = '../';
$page_title = 'Patient Registry';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

try {
    // Retrieve all patients along with their most recent vital sign entry (if exists)
    $patients = $pdo->query("
        SELECT p.*, 
               v.temperature, v.blood_pressure, v.weight, v.height, v.created_at as last_vitals_date 
        FROM patients p
        LEFT JOIN (
            SELECT v1.* 
            FROM vital_signs v1
            JOIN (
                SELECT patient_id, MAX(created_at) as max_date 
                FROM vital_signs 
                GROUP BY patient_id
            ) v2 ON v1.patient_id = v2.patient_id AND v1.created_at = v2.max_date
        ) v ON p.patient_id = v.patient_id
        ORDER BY p.full_name ASC
    ")->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error retrieving patient data: " . $e->getMessage();
    $patients = [];
}
?>

<div class="clinical-card">
    <div class="card-header">
        <h5>All Active Patient Records</h5>
        <div>
            <input type="text" id="tableSearch" class="form-control form-control-sm py-2 px-3" placeholder="Search patients..." style="border-radius: 8px; width: 250px; font-size: 0.8rem; border-color: var(--card-border);">
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($patients)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-users mb-3" style="font-size: 3rem; color: #cbd5e1;"></i>
                <h5>No Patient Records</h5>
                <p>There are no patients registered in the system yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table custom-table">
                    <thead>
                        <tr>
                            <th>Patient No.</th>
                            <th>Full Name</th>
                            <th>Gender / Age</th>
                            <th>Blood Group</th>
                            <th>Last Vitals Recorded</th>
                            <th>Status Summary</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($patients as $pat): ?>
                            <tr>
                                <td><span class="badge bg-light text-dark border p-2" style="font-size: 0.8rem; font-weight: 600;"><?php echo htmlspecialchars($pat['patient_number']); ?></span></td>
                                <td><strong><?php echo htmlspecialchars($pat['full_name']); ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($pat['gender']); ?><br>
                                    <small class="text-muted">
                                        <?php 
                                            $dob = new DateTime($pat['date_of_birth']);
                                            $today = new DateTime();
                                            $age = $today->diff($dob)->y;
                                            echo $age . " years old";
                                        ?>
                                    </small>
                                </td>
                                <td><span class="badge bg-danger bg-opacity-10 text-danger p-2" style="font-size: 0.8rem;"><?php echo htmlspecialchars($pat['blood_group']); ?></span></td>
                                <td>
                                    <?php if ($pat['last_vitals_date']): ?>
                                        <small class="text-dark">
                                            Temp: <strong><?php echo htmlspecialchars($pat['temperature']); ?>&deg;C</strong> &bull; 
                                            BP: <strong><?php echo htmlspecialchars($pat['blood_pressure']); ?></strong>
                                        </small><br>
                                        <small class="text-muted" style="font-size: 0.75rem;">Recorded: <?php echo date('M d, Y', strtotime($pat['last_vitals_date'])); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size: 0.8rem;">No Vitals Logged</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($pat['last_vitals_date']): ?>
                                        <?php if ($pat['temperature'] > 37.5 || $pat['temperature'] < 35.5): ?>
                                            <span class="badge bg-warning bg-opacity-10 text-warning px-2 py-1"><i class="fas fa-exclamation-circle me-1"></i> Abnormal Temp</span>
                                        <?php else: ?>
                                            <span class="badge bg-success bg-opacity-10 text-success px-2 py-1"><i class="fas fa-check-circle me-1"></i> Normal</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary px-2 py-1">Pending Exam</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="vital_signs.php?patient_id=<?php echo $pat['patient_id']; ?>" class="btn btn-sm btn-primary py-2 px-3" style="font-size: 0.75rem; border-radius: 6px;">
                                        <i class="fas fa-heartbeat me-1"></i> Log Vitals
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

<?php require_once __DIR__ . '/../includes/header.php'; ?>
