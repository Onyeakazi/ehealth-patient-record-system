<?php
// admin/dashboard.php
$path_to_root = '../';
$page_title = 'Administrator Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

try {
    // 1. Get stats counts
    $total_patients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
    $total_doctors = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'doctor'")->fetchColumn();
    $total_nurses = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'nurse'")->fetchColumn();
    $total_receptionists = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'receptionist'")->fetchColumn();
    $total_appointments = $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
    
    // 2. Fetch Recent Activities (Audit Logs)
    $audit_logs = $pdo->query("
        SELECT al.*, u.username, u.role 
        FROM audit_logs al
        LEFT JOIN users u ON al.user_id = u.user_id
        ORDER BY al.created_at DESC
        LIMIT 8
    ")->fetchAll();
    
    // 3. Fetch Appointment status statistics for Chart.js
    $status_stats = $pdo->query("
        SELECT status, COUNT(*) as count 
        FROM appointments 
        GROUP BY status
    ")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Fill default values if empty
    $statuses = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];
    $chart_data = [];
    foreach ($statuses as $st) {
        $chart_data[$st] = isset($status_stats[$st]) ? $status_stats[$st] : 0;
    }
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error loading admin metrics: " . $e->getMessage();
    $total_patients = 0;
    $total_doctors = 0;
    $total_nurses = 0;
    $total_receptionists = 0;
    $total_appointments = 0;
    $audit_logs = [];
    $chart_data = ['Pending' => 0, 'Confirmed' => 0, 'Completed' => 0, 'Cancelled' => 0];
}
?>

<!-- Statistical Cards Grid -->
<div class="row g-4 mb-4">
    <div class="col-6 col-lg-3">
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
    
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <h5>Doctors</h5>
                <h2><?php echo $total_doctors; ?></h2>
            </div>
            <div class="stat-icon blue-bg">
                <i class="fas fa-user-md"></i>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <h5>Nurses</h5>
                <h2><?php echo $total_nurses; ?></h2>
            </div>
            <div class="stat-icon purple-bg">
                <i class="fas fa-user-nurse"></i>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <h5>Receptionists</h5>
                <h2><?php echo $total_receptionists; ?></h2>
            </div>
            <div class="stat-icon yellow-bg">
                <i class="fas fa-laptop-medical"></i>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Chart Component -->
    <div class="col-12 col-lg-5">
        <div class="clinical-card">
            <div class="card-header">
                <h5>Consultations Distribution</h5>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center" style="min-height: 320px;">
                <canvas id="adminStatsChart" style="max-width: 320px; max-height: 320px;"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Audit Trail log tracker -->
    <div class="col-12 col-lg-7">
        <div class="clinical-card">
            <div class="card-header">
                <h5>Recent Audit Log Operations</h5>
                <a href="reports.php" class="btn btn-sm btn-link text-decoration-none" style="color: var(--primary-color); font-weight: 600; font-size: 0.8rem;">
                    Full Logs <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($audit_logs)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-history mb-2" style="font-size: 2.5rem; color: #cbd5e1;"></i>
                        <p class="mb-0">No activity logged in audit trail database.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table custom-table" style="font-size: 0.8rem;">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>User</th>
                                    <th>Operation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($audit_logs as $log): ?>
                                    <tr>
                                        <td><small class="text-muted"><?php echo date('M d, H:i:s', strtotime($log['created_at'])); ?></small></td>
                                        <td>
                                            <strong><?php echo $log['username'] ? htmlspecialchars($log['username']) : 'System'; ?></strong>
                                            <span class="badge bg-secondary p-1" style="font-size: 0.65rem; font-weight: 500; text-transform: uppercase;"><?php echo htmlspecialchars($log['role'] ?? 'system'); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($log['action']); ?></td>
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

<!-- Embed Chart script parameters safely using raw JS execution in footer callback -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('adminStatsChart').getContext('2d');
    
    const data = {
        labels: ['Pending', 'Confirmed', 'Completed', 'Cancelled'],
        datasets: [{
            label: 'Appointments Status Count',
            data: [
                <?php echo $chart_data['Pending']; ?>, 
                <?php echo $chart_data['Confirmed']; ?>, 
                <?php echo $chart_data['Completed']; ?>, 
                <?php echo $chart_data['Cancelled']; ?>
            ],
            backgroundColor: [
                '#fef3c7', // Yellow Pending
                '#e0f2fe', // Blue Confirmed
                '#d1fae5', // Green Completed
                '#fee2e2'  // Red Cancelled
            ],
            borderColor: [
                '#d97706',
                '#0284c7',
                '#059669',
                '#dc2626'
            ],
            borderWidth: 1.5
        }]
    };
    
    new Chart(ctx, {
        type: 'doughnut',
        data: data,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        padding: 15,
                        font: {
                            family: 'Plus Jakarta Sans',
                            size: 11
                        }
                    }
                }
            },
            cutout: '65%'
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
