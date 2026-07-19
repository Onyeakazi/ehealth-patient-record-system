<?php
// receptionist/appointments.php
$path_to_root = '../';
$page_title = 'Manage Appointments';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

// 1. Handle New Appointment Booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $patient_id = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;
    $doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
    $appointment_date = isset($_POST['appointment_date']) ? $_POST['appointment_date'] : '';
    
    if ($patient_id <= 0 || $doctor_id <= 0 || empty($appointment_date)) {
        $_SESSION['error_message'] = "Please select a patient, doctor, and date/time.";
    } else {
        try {
            $insert_stmt = $pdo->prepare("
                INSERT INTO appointments (patient_id, doctor_id, appointment_date, status) 
                VALUES (?, ?, ?, 'Pending')
            ");
            $insert_stmt->execute([$patient_id, $doctor_id, $appointment_date]);
            
            // Get patient and doctor name for logging
            $p_name = $pdo->query("SELECT full_name FROM patients WHERE patient_id = $patient_id")->fetchColumn();
            $d_name = $pdo->query("SELECT full_name FROM users WHERE user_id = $doctor_id")->fetchColumn();
            
            log_audit_action($pdo, $_SESSION['user_id'], "Booked appointment for patient $p_name with Dr. $d_name on $appointment_date");
            $_SESSION['success_message'] = "Appointment booked successfully.";
            
            header("Location: appointments.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Database error booking appointment: " . $e->getMessage();
        }
    }
}

// 2. Handle Appointment Status Update
if (isset($_GET['action']) && isset($_GET['id'])) {
    $appt_id = intval($_GET['id']);
    $action = $_GET['action'];
    $allowed_statuses = ['Pending', 'Confirmed', 'Completed', 'Cancelled', 'Discharged'];
    
    if (in_array($action, $allowed_statuses)) {
        try {
            $update_stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ?");
            $update_stmt->execute([$action, $appt_id]);
            
            // Log audit
            $p_info = $pdo->query("
                SELECT p.full_name, a.appointment_date 
                FROM appointments a 
                JOIN patients p ON a.patient_id = p.patient_id 
                WHERE a.appointment_id = $appt_id
            ")->fetch();
            
            log_audit_action($pdo, $_SESSION['user_id'], "Updated appointment status to '$action' for " . $p_info['full_name'] . " scheduled on " . $p_info['appointment_date']);
            $_SESSION['success_message'] = "Appointment status updated to <strong>$action</strong>.";
            
            header("Location: appointments.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Database error updating appointment: " . $e->getMessage();
        }
    }
}

// 3. Fetch Doctors and Patients for the Form
try {
    $patients = $pdo->query("SELECT patient_id, patient_number, full_name FROM patients ORDER BY full_name ASC")->fetchAll();
    $doctors = $pdo->query("SELECT user_id, full_name FROM users WHERE role = 'doctor' AND status = 'active' ORDER BY full_name ASC")->fetchAll();
    
    // Fetch all appointments
    $appointments = $pdo->query("
        SELECT a.appointment_id, p.patient_number, p.full_name as patient_name, u.full_name as doctor_name, a.appointment_date, a.status 
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users u ON a.doctor_id = u.user_id
        ORDER BY a.appointment_date DESC
    ")->fetchAll();
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error loading appointments: " . $e->getMessage();
    $patients = [];
    $doctors = [];
    $appointments = [];
}
?>

<div class="clinical-card">
    <div class="card-header">
        <h5>Clinic Appointments Schedule</h5>
        <div class="d-flex gap-2">
            <input type="text" id="tableSearch" class="form-control form-control-sm py-2 px-3" placeholder="Search appointments..." style="border-radius: 8px; width: 250px; font-size: 0.8rem; border-color: var(--card-border);">
            <button class="btn btn-sm btn-primary py-2 px-3" data-bs-toggle="modal" data-bs-target="#bookAppointmentModal" style="font-size: 0.8rem;">
                <i class="fas fa-calendar-plus me-1"></i> Book Appointment
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($appointments)): ?>
            <div class="text-center py-5 text-muted">
                <i class="far fa-calendar-alt mb-3" style="font-size: 3rem; color: #cbd5e1;"></i>
                <h5>No Appointments Found</h5>
                <p>Schedule a new medical consultation using the button above.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table custom-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Doctor Assigned</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
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
                                    <i class="fas fa-user-md text-muted me-1"></i> <?php echo htmlspecialchars($appt['doctor_name']); ?>
                                </td>
                                <td>
                                    <i class="far fa-clock text-muted me-1"></i> <?php echo date('M d, Y - h:i A', strtotime($appt['appointment_date'])); ?>
                                </td>
                                <td>
                                    <span class="badge badge-role status-<?php echo strtolower($appt['status']); ?>">
                                        <?php echo htmlspecialchars($appt['status']); ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="dropdown d-inline-block">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false" style="border-radius: 6px; font-size: 0.75rem;">
                                            Change Status
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size: 0.85rem; border-color: var(--card-border);">
                                            <li><a class="dropdown-item" href="appointments.php?action=Confirmed&id=<?php echo $appt['appointment_id']; ?>"><i class="fas fa-check text-info me-2"></i> Confirm</a></li>
                                            <li><a class="dropdown-item text-danger" href="appointments.php?action=Cancelled&id=<?php echo $appt['appointment_id']; ?>"><i class="fas fa-times-circle me-2"></i> Cancel</a></li>
                                            <li><a class="dropdown-item" href="appointments.php?action=Pending&id=<?php echo $appt['appointment_id']; ?>"><i class="fas fa-history text-muted me-2"></i> Set Pending</a></li>
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

<!-- Booking Appointment Modal -->
<div class="modal fade" id="bookAppointmentModal" tabindex="-1" aria-labelledby="bookAppointmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bookAppointmentModalLabel"><i class="fas fa-calendar-plus text-teal me-2"></i>Book New Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="appointments.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="book_appointment" value="1">
                    
                    <div class="mb-3">
                        <label for="patient_id" class="form-label">Select Patient <span class="text-danger">*</span></label>
                        <select name="patient_id" id="patient_id" class="form-select" required>
                            <option value="">-- Choose Patient --</option>
                            <?php foreach ($patients as $p): ?>
                                <option value="<?php echo $p['patient_id']; ?>">
                                    <?php echo htmlspecialchars($p['full_name']); ?> (<?php echo htmlspecialchars($p['patient_number']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="doctor_id" class="form-label">Assign Doctor <span class="text-danger">*</span></label>
                        <select name="doctor_id" id="doctor_id" class="form-select" required>
                            <option value="">-- Choose Doctor --</option>
                            <?php foreach ($doctors as $d): ?>
                                <option value="<?php echo $d['user_id']; ?>">
                                    <?php echo htmlspecialchars($d['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="appointment_date" class="form-label">Appointment Date & Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="appointment_date" id="appointment_date" class="form-control" min="<?php echo date('Y-m-d\TH:i'); ?>" required>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Book Appointment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
