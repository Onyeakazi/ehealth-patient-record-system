<?php
// database/setup.php

require_once __DIR__ . '/../config/database.php';

echo "<h2>E-Health Patient Record System Database Setup</h2>";
echo "Starting setup...<br>";

try {
    // Read SQL file
    $sql_file = __DIR__ . '/database.sql';
    if (!file_exists($sql_file)) {
        throw new Exception("Schema file database.sql not found in " . __DIR__);
    }
    
    $sql = file_get_contents($sql_file);
    
    // Execute SQL query
    $pdo->exec($sql);
    echo "Tables created successfully.<br>";
    
    // Seed default users if they don't exist
    $users = [
        [
            'full_name' => 'System Administrator',
            'username' => 'admin',
            'password' => password_hash('Admin@123', PASSWORD_DEFAULT),
            'email' => 'admin@medirecord.com',
            'phone' => '08012345678',
            'role' => 'admin'
        ],
        [
            'full_name' => 'Dr. John Doe',
            'username' => 'doctor',
            'password' => password_hash('Doctor@123', PASSWORD_DEFAULT),
            'email' => 'johndoe@medirecord.com',
            'phone' => '08087654321',
            'role' => 'doctor'
        ],
        [
            'full_name' => 'Nurse Jane Smith',
            'username' => 'nurse',
            'password' => password_hash('Nurse@123', PASSWORD_DEFAULT),
            'email' => 'janesmith@medirecord.com',
            'phone' => '08055555555',
            'role' => 'nurse'
        ],
        [
            'full_name' => 'Receptionist Alice Brown',
            'username' => 'receptionist',
            'password' => password_hash('Receptionist@123', PASSWORD_DEFAULT),
            'email' => 'alicebrown@medirecord.com',
            'phone' => '08099999999',
            'role' => 'receptionist'
        ]
    ];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $insert_stmt = $pdo->prepare("INSERT INTO users (full_name, username, password, email, phone, role, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
    
    foreach ($users as $user) {
        $stmt->execute([$user['username']]);
        if ($stmt->fetchColumn() == 0) {
            $insert_stmt->execute([
                $user['full_name'],
                $user['username'],
                $user['password'],
                $user['email'],
                $user['phone'],
                $user['role']
            ]);
            echo "Seeded default user: <strong>" . $user['username'] . "</strong> (Role: " . $user['role'] . ")<br>";
        } else {
            echo "User <strong>" . $user['username'] . "</strong> already exists.<br>";
        }
    }
    
    // Seed some dummy patients and records to make the dashboards instantly impressive
    $check_patients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
    if ($check_patients == 0) {
        // Insert dummy patients
        $patients = [
            ['PAT-2026-0001', 'Emmanuel Nwosu', 'Male', '1985-05-15', '08031112222', 'emmanuel@gmail.com', '12 Awolowo Road, Lagos', 'Chidi Nwosu (Brother) - 08031112223', 'O+'],
            ['PAT-2026-0002', 'Fatima Bello', 'Female', '1992-09-22', '08032223333', 'fatima@gmail.com', '45 Gwarinpa Estate, Abuja', 'Aliyu Bello (Spouse) - 08032223334', 'A-'],
            ['PAT-2026-0003', 'Chioma Adebayo', 'Female', '2001-11-05', '08033334444', 'chioma@gmail.com', '78 Ring Road, Ibadan', 'Seyi Adebayo (Father) - 08033334445', 'B+']
        ];
        
        $p_insert = $pdo->prepare("INSERT INTO patients (patient_number, full_name, gender, date_of_birth, phone, email, address, emergency_contact, blood_group) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($patients as $p) {
            $p_insert->execute($p);
        }
        echo "Seeded 3 dummy patient records.<br>";
        
        // Get inserted patient IDs and doctor ID
        $doc_id = $pdo->query("SELECT user_id FROM users WHERE username = 'doctor'")->fetchColumn();
        $nurse_id = $pdo->query("SELECT user_id FROM users WHERE username = 'nurse'")->fetchColumn();
        
        $pat_ids = $pdo->query("SELECT patient_id FROM patients")->fetchAll(PDO::FETCH_COLUMN);
        
        // Seed vital signs
        $v_insert = $pdo->prepare("INSERT INTO vital_signs (patient_id, nurse_id, temperature, blood_pressure, weight, height) VALUES (?, ?, ?, ?, ?, ?)");
        $v_insert->execute([$pat_ids[0], $nurse_id, 36.7, '120/80', 78.5, 175]);
        $v_insert->execute([$pat_ids[1], $nurse_id, 38.2, '130/85', 64.0, 162]);
        $v_insert->execute([$pat_ids[2], $nurse_id, 36.5, '115/75', 55.2, 168]);
        echo "Seeded vital signs for patients.<br>";
        
        // Seed medical records
        $m_insert = $pdo->prepare("INSERT INTO medical_records (patient_id, doctor_id, symptoms, diagnosis, treatment, prescription, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $m_insert->execute([
            $pat_ids[0], 
            $doc_id, 
            'Routine medical checkup. Complaining of slight lower back pain.', 
            'Mild Lumbar Muscle Strain', 
            'Physiotherapy and pain management', 
            'Tab Ibuprofen 400mg twice daily for 5 days. Tab Paracetamol 1g thrice daily for 3 days.', 
            'Advised patient to avoid lifting heavy loads and practice stretching exercises.'
        ]);
        $m_insert->execute([
            $pat_ids[1], 
            $doc_id, 
            'High fever, chills, severe headache, and joint pain.', 
            'Acute Malaria Infection', 
            'Antimalarial therapy', 
            'Artemether-Lumefantrine (Coartem) twice daily for 3 days. Tab Paracetamol 1g thrice daily for 3 days.', 
            'Patient vitals showed temperature of 38.2C. Requested bed rest and hydration.'
        ]);
        echo "Seeded medical records.<br>";
        
        // Seed appointments
        $a_insert = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, status) VALUES (?, ?, ?, ?)");
        $a_insert->execute([$pat_ids[0], $doc_id, date('Y-m-d H:i:s', strtotime('+2 days 10:00:00')), 'Confirmed']);
        $a_insert->execute([$pat_ids[1], $doc_id, date('Y-m-d H:i:s', strtotime('+1 days 14:30:00')), 'Pending']);
        $a_insert->execute([$pat_ids[2], $doc_id, date('Y-m-d H:i:s', strtotime('+3 days 09:00:00')), 'Pending']);
        echo "Seeded upcoming appointments.<br>";
    }
    
    echo "<br><strong>Database setup completed successfully!</strong><br>";
    echo "You can now log in at <a href='../index.php'>index.php</a> using standard accounts:<br>";
    echo "- Admin: <code>admin</code> / <code>Admin@123</code><br>";
    echo "- Doctor: <code>doctor</code> / <code>Doctor@123</code><br>";
    echo "- Nurse: <code>nurse</code> / <code>Nurse@123</code><br>";
    echo "- Receptionist: <code>receptionist</code> / <code>Receptionist@123</code><br>";
    
} catch (Exception $e) {
    echo "<br><span style='color: red;'><strong>Setup Error:</strong> " . $e->getMessage() . "</span><br>";
}
?>
