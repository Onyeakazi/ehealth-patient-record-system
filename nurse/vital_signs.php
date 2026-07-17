<?php
// nurse/vital_signs.php
$path_to_root = '../';
$page_title = 'Record Vital Signs';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

$selected_patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;

// Handle Vital Signs Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_vitals'])) {
    $patient_id = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;
    $nurse_id = $_SESSION['user_id'];
    $temperature = isset($_POST['temperature']) ? floatval($_POST['temperature']) : 0;
    $blood_pressure = isset($_POST['blood_pressure']) ? trim($_POST['blood_pressure']) : '';
    $weight = isset($_POST['weight']) ? floatval($_POST['weight']) : 0;
    $height = isset($_POST['height']) ? floatval($_POST['height']) : 0;

    if ($patient_id <= 0 || $temperature <= 0 || empty($blood_pressure) || $weight <= 0 || $height <= 0) {
        $_SESSION['error_message'] = "Please fill in all vital measurement details correctly.";
    } else {
        try {
            $insert_stmt = $pdo->prepare("
                INSERT INTO vital_signs (patient_id, nurse_id, temperature, blood_pressure, weight, height) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $insert_stmt->execute([$patient_id, $nurse_id, $temperature, $blood_pressure, $weight, $height]);
            
            // Get patient info for logging
            $p_stmt = $pdo->prepare("SELECT patient_number, full_name FROM patients WHERE patient_id = ?");
            $p_stmt->execute([$patient_id]);
            $pat = $p_stmt->fetch();
            
            log_audit_action($pdo, $nurse_id, "Recorded vitals for " . $pat['full_name'] . " (" . $pat['patient_number'] . "): Temp: {$temperature}°C, BP: $blood_pressure, Weight: {$weight}kg, Height: {$height}cm");
            
            $_SESSION['success_message'] = "Vital signs for <strong>" . htmlspecialchars($pat['full_name']) . "</strong> recorded successfully.";
            
            header("Location: vital_signs.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Database error recording vital signs: " . $e->getMessage();
        }
    }
}

// Fetch lists of patients and vitals logs
try {
    $patients = $pdo->query("SELECT patient_id, patient_number, full_name FROM patients ORDER BY full_name ASC")->fetchAll();
    
    $vitals_logs = $pdo->query("
        SELECT v.*, p.full_name as patient_name, p.patient_number, u.full_name as nurse_name 
        FROM vital_signs v
        JOIN patients p ON v.patient_id = p.patient_id
        JOIN users u ON v.nurse_id = u.user_id
        ORDER BY v.created_at DESC
    ")->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error loading database elements: " . $e->getMessage();
    $patients = [];
    $vitals_logs = [];
}
?>

<div class="row g-4">
    <!-- Log Form (4 Cols) -->
    <div class="col-12 col-lg-4">
        <div class="clinical-card">
            <div class="card-header">
                <h5>Log Patient Measurements</h5>
            </div>
            <div class="card-body">
                <form action="vital_signs.php" method="POST">
                    <input type="hidden" name="save_vitals" value="1">
                    
                    <div class="mb-3">
                        <label for="patient_id" class="form-label">Select Patient <span class="text-danger">*</span></label>
                        <select name="patient_id" id="patient_id" class="form-select" required>
                            <option value="">-- Choose Patient --</option>
                            <?php foreach ($patients as $p): ?>
                                <option value="<?php echo $p['patient_id']; ?>" <?php echo ($selected_patient_id === intval($p['patient_id'])) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['full_name']); ?> (<?php echo htmlspecialchars($p['patient_number']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="temperature" class="form-label">Temperature (&deg;C) <span class="text-danger">*</span></label>
                        <input type="number" step="0.1" name="temperature" id="temperature" class="form-control" placeholder="e.g. 36.8" min="30" max="45" required>
                    </div>

                    <div class="mb-3">
                        <label for="blood_pressure" class="form-label">Blood Pressure <span class="text-danger">*</span></label>
                        <input type="text" name="blood_pressure" id="blood_pressure" class="form-control" placeholder="e.g. 120/80" pattern="^[0-9]{2,3}\/[0-9]{2,3}$" title="Format must be Systolic/Diastolic e.g. 120/80" required>
                        <small class="text-muted" style="font-size: 0.7rem;">Format: Systolic/Diastolic (e.g. 120/80)</small>
                    </div>

                    <div class="mb-3">
                        <label for="weight" class="form-label">Weight (kg) <span class="text-danger">*</span></label>
                        <input type="number" step="0.1" name="weight" id="weight" class="form-control" placeholder="e.g. 72.5" min="1" max="500" required>
                    </div>

                    <div class="mb-3">
                        <label for="height" class="form-label">Height (cm) <span class="text-danger">*</span></label>
                        <input type="number" step="0.1" name="height" id="height" class="form-control" placeholder="e.g. 175.2" min="10" max="300" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2 mt-2">
                        <i class="fas fa-save me-1"></i> Save Vital Signs
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Logs List (8 Cols) -->
    <div class="col-12 col-lg-8">
        <div class="clinical-card">
            <div class="card-header">
                <h5>Historical Vital Signs Log</h5>
                <div>
                    <input type="text" id="tableSearch" class="form-control form-control-sm py-2 px-3" placeholder="Search vital records..." style="border-radius: 8px; width: 220px; font-size: 0.8rem; border-color: var(--card-border);">
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($vitals_logs)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-heartbeat mb-3" style="font-size: 3rem; color: #cbd5e1;"></i>
                        <h5>No Vital Sign Entries</h5>
                        <p>No historical records exist. Use the entry form to log first set of vital signs.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table custom-table">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Temp</th>
                                    <th>Blood Pressure</th>
                                    <th>Weight</th>
                                    <th>Height</th>
                                    <th>Recorded By</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vitals_logs as $log): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($log['patient_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($log['patient_number']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-opacity-10 p-2 <?php echo ($log['temperature'] > 37.5 || $log['temperature'] < 35.5) ? 'bg-danger text-danger' : 'bg-success text-success'; ?>" style="font-size: 0.8rem;">
                                                <?php echo htmlspecialchars($log['temperature']); ?>&deg;C
                                            </span>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($log['blood_pressure']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($log['weight']); ?> kg</td>
                                        <td><?php echo htmlspecialchars($log['height']); ?> cm</td>
                                        <td><small class="text-muted"><?php echo htmlspecialchars($log['nurse_name']); ?></small></td>
                                        <td><small class="text-muted"><?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?></small></td>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
