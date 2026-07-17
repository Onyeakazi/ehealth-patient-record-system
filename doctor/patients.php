<?php
// doctor/patients.php
$path_to_root = '../';
$page_title = 'Patient Registry';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

try {
    // Fetch all patients
    $patients = $pdo->query("SELECT * FROM patients ORDER BY full_name ASC")->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error loading patient database: " . $e->getMessage();
    $patients = [];
}
?>

<div class="clinical-card">
    <div class="card-header">
        <h5>Patient Directory & Clinical Portals</h5>
        <div>
            <input type="text" id="tableSearch" class="form-control form-control-sm py-2 px-3" placeholder="Search patients..." style="border-radius: 8px; width: 250px; font-size: 0.8rem; border-color: var(--card-border);">
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($patients)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-users mb-3" style="font-size: 3rem; color: #cbd5e1;"></i>
                <h5>No Patients Registered</h5>
                <p>Register patients via receptionist account to view records here.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table custom-table">
                    <thead>
                        <tr>
                            <th>Patient No.</th>
                            <th>Full Name</th>
                            <th>Gender</th>
                            <th>Date of Birth</th>
                            <th>Phone</th>
                            <th>Blood Group</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($patients as $pat): ?>
                            <tr>
                                <td><span class="badge bg-light text-dark border p-2" style="font-size: 0.8rem; font-weight: 600;"><?php echo htmlspecialchars($pat['patient_number']); ?></span></td>
                                <td><strong><?php echo htmlspecialchars($pat['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($pat['gender']); ?></td>
                                <td><?php echo htmlspecialchars($pat['date_of_birth']); ?></td>
                                <td><?php echo htmlspecialchars($pat['phone']); ?></td>
                                <td><span class="badge bg-danger bg-opacity-10 text-danger p-2" style="font-size: 0.8rem;"><?php echo htmlspecialchars($pat['blood_group']); ?></span></td>
                                <td class="text-end">
                                    <a href="patient_profile.php?id=<?php echo $pat['patient_id']; ?>" class="btn btn-sm btn-primary py-2 px-3" style="font-size: 0.75rem; border-radius: 6px;">
                                        <i class="fas fa-folder-open me-1"></i> Open Chart
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
