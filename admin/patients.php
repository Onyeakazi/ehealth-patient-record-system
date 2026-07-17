<?php
// admin/patients.php
$path_to_root = '../';
$page_title = 'Patient Oversight Directory';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

try {
    // Fetch all patients
    $patients = $pdo->query("SELECT * FROM patients ORDER BY full_name ASC")->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error loading patients directory: " . $e->getMessage();
    $patients = [];
}
?>

<div class="clinical-card">
    <div class="card-header">
        <h5>Patient Registry System Logs</h5>
        <div>
            <input type="text" id="tableSearch" class="form-control form-control-sm py-2 px-3" placeholder="Search patients..." style="border-radius: 8px; width: 250px; font-size: 0.8rem; border-color: var(--card-border);">
        </div>
    </div>
    
    <div class="card-body">
        <?php if (empty($patients)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-users mb-3" style="font-size: 3rem; color: #cbd5e1;"></i>
                <h5>No Registered Patients</h5>
                <p>Use receptionist portal to register patients.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table custom-table">
                    <thead>
                        <tr>
                            <th>Patient No.</th>
                            <th>Full Name</th>
                            <th>Gender / DOB</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Blood Group</th>
                            <th>Registered Date</th>
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
                                            echo $today->diff($dob)->y . " years old";
                                        ?>
                                    </small>
                                </td>
                                <td><?php echo htmlspecialchars($pat['phone']); ?></td>
                                <td><?php echo $pat['email'] ? htmlspecialchars($pat['email']) : '<em class="text-muted">N/A</em>'; ?></td>
                                <td><span class="badge bg-danger bg-opacity-10 text-danger p-2" style="font-size: 0.8rem;"><?php echo htmlspecialchars($pat['blood_group']); ?></span></td>
                                <td><small class="text-muted"><?php echo date('M d, Y h:i A', strtotime($pat['created_at'])); ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
