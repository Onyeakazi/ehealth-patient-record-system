<?php
// includes/sidebar.php

$current_page = basename($_SERVER['PHP_SELF']);
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$full_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Healthcare Worker';
$path_to_root = isset($path_to_root) ? $path_to_root : '../';
?>
<aside id="sidebar">
    <div class="brand">
        <i class="fas fa-heartbeat brand-icon"></i>
        <span class="brand-title">MediRecord EHR</span>
    </div>
    
    <div class="user-info">
        <div class="user-name text-truncate" title="<?php echo htmlspecialchars($full_name); ?>">
            <?php echo htmlspecialchars($full_name); ?>
        </div>
        <div class="user-role">
            <?php echo htmlspecialchars($role); ?>
        </div>
    </div>
    
    <ul class="nav-links">
        <?php if ($role === 'admin'): ?>
            <!-- Administrator Navigation Links -->
            <li class="<?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>">
                <a href="<?php echo $path_to_root; ?>admin/dashboard.php">
                    <i class="fas fa-chart-pie"></i> Dashboard
                </a>
            </li>
            <li class="<?php echo ($current_page === 'users.php') ? 'active' : ''; ?>">
                <a href="<?php echo $path_to_root; ?>admin/users.php">
                    <i class="fas fa-users-cog"></i> Manage Staff
                </a>
            </li>
            <li class="<?php echo ($current_page === 'patients.php') ? 'active' : ''; ?>">
                <a href="<?php echo $path_to_root; ?>admin/patients.php">
                    <i class="fas fa-user-injured"></i> View Patients
                </a>
            </li>
            <li class="<?php echo ($current_page === 'reports.php') ? 'active' : ''; ?>">
                <a href="<?php echo $path_to_root; ?>admin/reports.php">
                    <i class="fas fa-file-invoice-dollar"></i> System Reports
                </a>
            </li>
            <li class="<?php echo ($current_page === 'settings.php') ? 'active' : ''; ?>">
                <a href="<?php echo $path_to_root; ?>admin/settings.php">
                    <i class="fas fa-sliders-h"></i> System Settings
                </a>
            </li>
            <li class="<?php echo ($current_page === 'profile.php') ? 'active' : ''; ?>">
                <a href="<?php echo $path_to_root; ?>admin/profile.php">
                    <i class="fas fa-user-circle"></i> Edit Profile
                </a>
            </li>
            
        <?php elseif ($role === 'doctor'): ?>
            <!-- Doctor Navigation Links -->
            <li class="<?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>">
                <a href="<?php echo $path_to_root; ?>doctor/dashboard.php">
                    <i class="fas fa-stethoscope"></i> Dashboard
                </a>
            </li>
            <li class="<?php echo ($current_page === 'patients.php' || $current_page === 'patient_profile.php') ? 'active' : ''; ?>">
                <a href="<?php echo $path_to_root; ?>doctor/patients.php">
                    <i class="fas fa-hospital-user"></i> My Patients
                </a>
            </li>
            <li class="<?php echo ($current_page === 'medical_records.php') ? 'active' : ''; ?>">
                <a href="<?php echo $path_to_root; ?>doctor/medical_records.php">
                    <i class="fas fa-notes-medical"></i> Medical Records
                </a>
            </li>
            <li class="<?php echo ($current_page === 'appointments.php') ? 'active' : ''; ?>">
                <a href="<?php echo $path_to_root; ?>doctor/appointments.php">
                    <i class="fas fa-calendar-alt"></i> Appointments
                </a>
            </li>
            <li class="<?php echo ($current_page === 'profile.php') ? 'active' : ''; ?>">
                <a href="<?php echo $path_to_root; ?>doctor/profile.php">
                    <i class="fas fa-user-md"></i> Edit Profile
                </a>
            </li>

        <?php elseif ($role === 'nurse'): ?>
            <!-- Nurse Navigation Links -->
            <li class="<?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>">
                <a href="<?php echo $path_to_root; ?>nurse/dashboard.php">
                    <i class="fas fa-clinic-medical"></i> Dashboard
                </a>
            </li>
            <li class="<?php echo ($current_page === 'patients.php') ? 'active' : ''; ?>">
                <a href="<?php echo $path_to_root; ?>nurse/patients.php">
                    <i class="fas fa-user-injured"></i> View Patients
                </a>
            </li>
            <li class="<?php echo ($current_page === 'vital_signs.php') ? 'active' : ''; ?>">
                <a href="<?php echo $path_to_root; ?>nurse/vital_signs.php">
                    <i class="fas fa-heartbeat"></i> Vital Signs
                </a>
            </li>
            <li class="<?php echo ($current_page === 'profile.php') ? 'active' : ''; ?>">
                <a href="<?php echo $path_to_root; ?>nurse/profile.php">
                    <i class="fas fa-user-nurse"></i> Edit Profile
                </a>
            </li>

        <?php elseif ($role === 'receptionist'): ?>
            <!-- Receptionist Navigation Links -->
            <li class="<?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>">
                <a href="<?php echo $path_to_root; ?>receptionist/dashboard.php">
                    <i class="fas fa-laptop-medical"></i> Dashboard
                </a>
            </li>
            <li class="<?php echo ($current_page === 'register.php') ? 'active' : ''; ?>">
                <a href="<?php echo $path_to_root; ?>receptionist/register.php">
                    <i class="fas fa-user-plus"></i> Register Patient
                </a>
            </li>
            <li class="<?php echo ($current_page === 'patients.php') ? 'active' : ''; ?>">
                <a href="<?php echo $path_to_root; ?>receptionist/patients.php">
                    <i class="fas fa-address-book"></i> Patient Records
                </a>
            </li>
            <li class="<?php echo ($current_page === 'appointments.php') ? 'active' : ''; ?>">
                <a href="<?php echo $path_to_root; ?>receptionist/appointments.php">
                    <i class="fas fa-calendar-plus"></i> Appointments
                </a>
            </li>
            <li class="<?php echo ($current_page === 'profile.php') ? 'active' : ''; ?>">
                <a href="<?php echo $path_to_root; ?>receptionist/profile.php">
                    <i class="fas fa-user-cog"></i> Edit Profile
                </a>
            </li>
        <?php endif; ?>
    </ul>
    
    <div class="logout-section">
        <a href="<?php echo $path_to_root; ?>authentication/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</aside>
