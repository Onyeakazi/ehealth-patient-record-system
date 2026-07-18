<?php
// doctor/appointments.php
$path_to_root = '../';
$page_title = 'My Schedule';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

$doctor_id = $_SESSION['user_id'];

// Handle Appointment Status Update
if (isset($_GET['action']) && isset($_GET['id'])) {
    $appt_id = intval($_GET['id']);
    $action = $_GET['action'];
    $allowed_statuses = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];
    
    if (in_array($action, $allowed_statuses)) {
        try {
            // Verify appointment belongs to this doctor
            $verify_stmt = $pdo->prepare("SELECT doctor_id FROM appointments WHERE appointment_id = ?");
            $verify_stmt->execute([$appt_id]);
            $assigned_doc = $verify_stmt->fetchColumn();
            
            if ($assigned_doc == $doctor_id) {
                $update_stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ?");
                $update_stmt->execute([$action, $appt_id]);
                
                // Get patient name for auditing
                $p_info = $pdo->query("
                    SELECT p.full_name, a.appointment_date 
                    FROM appointments a 
                    JOIN patients p ON a.patient_id = p.patient_id 
                    WHERE a.appointment_id = $appt_id
                ")->fetch();
                
                log_audit_action($pdo, $doctor_id, "Updated appointment status to '$action' for patient " . $p_info['full_name'] . " scheduled on " . $p_info['appointment_date']);
                $_SESSION['success_message'] = "Appointment status marked as <strong>$action</strong>.";
            } else {
                $_SESSION['error_message'] = "Unauthorized operation. You can only update your own appointments.";
            }
            
            header("Location: appointments.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Database error updating appointment: " . $e->getMessage();
        }
    }
}

// Fetch all appointments for this doctor
try {
    $stmt = $pdo->prepare("
        SELECT a.appointment_id, p.patient_id, p.patient_number, p.full_name as patient_name, a.appointment_date, a.status 
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        WHERE a.doctor_id = ?
        ORDER BY a.appointment_date DESC
    ");
    $stmt->execute([$doctor_id]);
    $appointments = $stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error retrieving appointments list: " . $e->getMessage();
    $appointments = [];
}
?>

<div class="clinical-card">
    <div class="card-header">
        <h5>My Consultations Schedule Calendar</h5>
        <div>
            <input type="text" id="tableSearch" class="form-control form-control-sm py-2 px-3" placeholder="Search consultations..." style="border-radius: 8px; width: 250px; font-size: 0.8rem; border-color: var(--card-border);">
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($appointments)): ?>
            <div class="text-center py-5 text-muted">
                <i class="far fa-calendar-alt mb-3" style="font-size: 3rem; color: #cbd5e1;"></i>
                <h5>No Scheduled Appointments</h5>
                <p>You do not have any patient consultations booked in your queue.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table custom-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Schedule Date</th>
                            <th>Status</th>
                            <th>Clinical Options</th>
                            <th class="text-end">Manage Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appt): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($appt['patient_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($appt['patient_number']); ?></small>
                                </td>
                                <td>
                                    <i class="far fa-calendar-alt text-muted me-1"></i> <?php echo date('M d, Y', strtotime($appt['appointment_date'])); ?><br>
                                    <small class="text-muted"><i class="far fa-clock me-1"></i> <?php echo date('h:i A', strtotime($appt['appointment_date'])); ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-role status-<?php echo strtolower($appt['status']); ?>">
                                        <?php echo htmlspecialchars($appt['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="patient_profile.php?id=<?php echo $appt['patient_id']; ?>" class="btn btn-sm btn-light py-2 px-3" style="border: 1px solid var(--card-border); font-size: 0.75rem; border-radius: 6px;">
                                        <i class="fas fa-file-medical text-teal me-1"></i> Open Chart / Add Notes
                                    </a>
                                </td>
                                <td class="text-end">
                                    <div class="dropdown d-inline-block">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border-radius: 6px; font-size: 0.75rem;">
                                            Change Status
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size: 0.85rem; border-color: var(--card-border);">
                                            <li><a class="dropdown-item" href="appointments.php?action=Completed&id=<?php echo $appt['appointment_id']; ?>"><i class="fas fa-check-double text-success me-2"></i> Mark Completed</a></li>
                                            <li><a class="dropdown-item text-danger" href="appointments.php?action=Cancelled&id=<?php echo $appt['appointment_id']; ?>"><i class="fas fa-times-circle me-2"></i> Cancel</a></li>
                                        </ul>
                                    </div>
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
