// assets/js/main.js

document.addEventListener('DOMContentLoaded', function () {
    // 1. Sidebar Toggle for Mobile Viewports
    const toggleBtn = document.querySelector('.toggle-sidebar');
    const sidebar = document.getElementById('sidebar');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            sidebar.classList.toggle('show');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function (e) {
            if (window.innerWidth < 992) {
                if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    }

    // 2. Real-time Table Filter/Search (Premium Instant Filtering)
    const tableSearch = document.getElementById('tableSearch');
    const dataTable = document.querySelector('.custom-table');

    if (tableSearch && dataTable) {
        tableSearch.addEventListener('keyup', function () {
            const query = this.value.toLowerCase().trim();
            const rows = dataTable.querySelectorAll('tbody tr');

            rows.forEach(row => {
                let match = false;
                const cells = row.querySelectorAll('td');
                cells.forEach(cell => {
                    if (cell.textContent.toLowerCase().includes(query)) {
                        match = true;
                    }
                });

                if (match) {
                    row.style.display = '';
                    row.style.opacity = '1';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // 3. Automated Age Calculator on Date of Birth Change
    const dobInput = document.getElementById('dob_input');
    const ageInput = document.getElementById('age_display');

    if (dobInput && ageInput) {
        dobInput.addEventListener('change', function () {
            const birthDate = new Date(this.value);
            if (isNaN(birthDate.getTime())) {
                ageInput.value = '';
                return;
            }
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            
            ageInput.value = age >= 0 ? age : 0;
        });
    }

    // 4. Password Visibility Toggle helper
    const togglePass = document.querySelectorAll('.toggle-password-btn');
    togglePass.forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const passwordField = document.getElementById(targetId);
            if (passwordField) {
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    this.innerHTML = '<i class="fas fa-eye-slash"></i>';
                } else {
                    passwordField.type = 'password';
                    this.innerHTML = '<i class="fas fa-eye"></i>';
                }
            }
        });
    });

    // 5. Bootstrap Auto-dismiss Alert fade out
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
    });
});
