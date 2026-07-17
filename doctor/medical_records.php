<?php
// doctor/medical_records.php
$path_to_root = '../';
$page_title = 'Clinical Notes Directory';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

try {
    // Fetch all medical records along with patient and doctor details
    $medical_records = $pdo->query("
        SELECT mr.*, p.full_name as patient_name, p.patient_number, u.full_name as doctor_name 
        FROM medical_records mr
        JOIN patients p ON mr.patient_id = p.patient_id
        JOIN users u ON mr.doctor_id = u.user_id
        ORDER BY mr.created_at DESC
    ")->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error loading medical entries: " . $e->getMessage();
    $medical_records = [];
}
?>

<div class="clinical-card">
    <div class="card-header">
        <h5>Patient Medical Records History</h5>
        <div>
            <input type="text" id="tableSearch" class="form-control form-control-sm py-2 px-3" placeholder="Search clinical notes..." style="border-radius: 8px; width: 250px; font-size: 0.8rem; border-color: var(--card-border);">
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($medical_records)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-notes-medical mb-3" style="font-size: 3rem; color: #cbd5e1;"></i>
                <h5>No Consultation Entries</h5>
                <p>No medical records have been logged in the system database yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table custom-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Patient</th>
                            <th>Diagnosis</th>
                            <th>Treatment & Prescription</th>
                            <th>Consulting Doctor</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medical_records as $rec): ?>
                            <tr>
                                <td>
                                    <small class="text-muted fw-semibold"><?php echo date('M d, Y', strtotime($rec['created_at'])); ?></small><br>
                                    <small class="text-muted" style="font-size: 0.75rem;"><?php echo date('h:i A', strtotime($rec['created_at'])); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($rec['patient_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($rec['patient_number']); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-teal bg-opacity-10 text-teal p-2" style="font-size: 0.85rem; color: var(--primary-color) !important;">
                                        <?php echo htmlspecialchars($rec['diagnosis']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="text-truncate mb-1" style="max-width: 250px;" title="Treatment: <?php echo htmlspecialchars($rec['treatment']); ?>">
                                        <small>Tx: <?php echo htmlspecialchars($rec['treatment']); ?></small>
                                    </div>
                                    <div class="text-truncate text-danger font-semibold" style="max-width: 250px;" title="Prescription: <?php echo htmlspecialchars($rec['prescription']); ?>">
                                        <i class="fas fa-prescription me-1"></i> <small><?php echo htmlspecialchars($rec['prescription']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <small><i class="fas fa-user-md text-muted me-1"></i> Dr. <?php echo htmlspecialchars($rec['doctor_name']); ?></small>
                                </td>
                                <td class="text-end">
                                    <a href="patient_profile.php?id=<?php echo $rec['patient_id']; ?>" class="btn btn-sm btn-outline-primary py-2 px-3" style="font-size: 0.75rem; border-radius: 6px;">
                                        <i class="fas fa-chart-line me-1"></i> View Chart
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
