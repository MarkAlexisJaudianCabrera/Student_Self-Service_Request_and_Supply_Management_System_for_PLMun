<?php
    session_start();
    include('../../config/db.php');
    
    if (!isset($_SESSION['staffvalidated']) || $_SESSION['staffvalidated'] !== true) {
        header("Location: /404.php");
        exit();
    }
    
    $result = $conn->query("SELECT * FROM students ORDER BY id DESC");
    
    // Handle success/error messages
    $message = '';
    $messageType = '';
    if (isset($_GET['success'])) {
        $message = $_GET['success'] == 'added' ? 'Student added successfully!' : 'Student deleted successfully!';
        $messageType = 'success';
    }
    if (isset($_GET['error'])) {
        $message = 'Operation failed. Please try again.';
        $messageType = 'error';
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Admin - Manage Students | PLMUN</title>
    <link rel="stylesheet" href="/assets/styles/allstyles.css">
    <link rel="stylesheet" href="/assets/styles/navbar.css">
    <link rel="stylesheet" href="/assets/styles/adminstyles/adminusrstud.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" type="image/x-icon" sizes="16x16" href="/assets/ico/logo16ico.ico">
    <link rel="icon" type="image/x-icon" sizes="32x32" href="/assets/ico/logo32ico.ico">
    <link rel="icon" type="image/x-icon" sizes="96x96" href="/assets/ico/logo96ico.ico">
    <link rel="icon" type="image/x-icon" sizes="192x192" href="/assets/ico/logo192ico.ico">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <!-- Top Navbar -->
    <nav class="navbar">
        <a href="/landingpage.html"><img src="/assets/img/schl_logo-1.png" alt="Logo"></a>
    </nav>

    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn" id="mobileMenuBtn">
        <i class="fas fa-bars"></i>
    </button>

    <div class="overlay" id="overlay"></div>

    <!-- Left Navbar -->
    <div class="left-navbar" id="leftNavbar">
        <?php include('../left-navbar.php'); ?>
    </div>

    <div class="adminusr-megacontainer">
        <!-- Header -->
        <div class="page-header">
            <h2><i class="fas fa-user-graduate"></i> Manage Students</h2>
            <p>Add, edit, or delete student accounts</p>
        </div>

        <!-- Stats Row -->
        <?php
        $totalStudents = $conn->query("SELECT COUNT(*) as c FROM students")->fetch_assoc()['c'];
        $firstYear = $conn->query("SELECT COUNT(*) as c FROM students WHERE year = '1' OR year = '1st'")->fetch_assoc()['c'];
        $secondYear = $conn->query("SELECT COUNT(*) as c FROM students WHERE year = '2' OR year = '2nd'")->fetch_assoc()['c'];
        $thirdYear = $conn->query("SELECT COUNT(*) as c FROM students WHERE year = '3' OR year = '3rd'")->fetch_assoc()['c'];
        $fourthYear = $conn->query("SELECT COUNT(*) as c FROM students WHERE year = '4' OR year = '4th'")->fetch_assoc()['c'];
        ?>
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon">👨‍🎓</div>
                <div class="stat-value"><?php echo $totalStudents; ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">1️⃣</div>
                <div class="stat-value"><?php echo $firstYear; ?></div>
                <div class="stat-label">1st Year</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">2️⃣</div>
                <div class="stat-value"><?php echo $secondYear; ?></div>
                <div class="stat-label">2nd Year</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">3️⃣</div>
                <div class="stat-value"><?php echo $thirdYear; ?></div>
                <div class="stat-label">3rd Year</div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Add Student Form -->
        <div class="form-section">
            <h3><i class="fas fa-user-plus"></i> Add New Student</h3>
            <form method="POST" action="actions/student_action.php">
                <div class="form-grid">
                    <div class="form-group">
                        <label><i class="fas fa-id-card"></i> Student Number</label>
                        <input type="text" name="student_no" placeholder="e.g., 2024-0001" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Institutional Email</label>
                        <input type="email" name="instiemail" placeholder="student@plmun.edu.ph" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Full Name</label>
                        <input type="text" name="fullname" placeholder="Last Name, First Name Middle" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-graduation-cap"></i> Course</label>
                        <input type="text" name="course" placeholder="e.g., BS Information Technology" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Year Level</label>
                        <select name="year" required>
                            <option value="">Select Year</option>
                            <option value="1st">1st Year</option>
                            <option value="2nd">2nd Year</option>
                            <option value="3rd">3rd Year</option>
                            <option value="4th">4th Year</option>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="add" class="btn-primary">
                        <i class="fas fa-plus"></i> Add Student
                    </button>
                </div>
            </form>
        </div>

        <!-- Search Bar -->
        <div class="search-bar">
            <input type="text" id="searchInput" class="search-input" placeholder="Search by Student Number, Name, Email, or Course..." autocomplete="off">
            <button class="search-btn" onclick="searchTable()"><i class="fas fa-search"></i> Search</button>
        </div>

        <!-- Students List Table -->
        <div class="table-container">
            <div class="table-header">
                <h3><i class="fas fa-list"></i> Students List</h3>
                <span style="color: #aaa; font-size: 11px;">Total: <?php echo $totalStudents; ?> students</span>
            </div>
            <div style="overflow-x: auto;">
                <table class="student-table" id="studentTable">
                    <thead>
                        <tr>
                            <th>Student No</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Course</th>
                            <th>Year</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['student_no']); ?></td>
                                <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                                <td><?php echo htmlspecialchars($row['instiemail']); ?></td>
                                <td><?php echo htmlspecialchars($row['course']); ?></td>
                                <td><span class="year-badge"><?php echo htmlspecialchars($row['year']); ?> Year</span></td>
                                <td>
                                    <a href="actions/student_action.php?delete=<?php echo $row['id']; ?>" class="delete-btn" onclick="return confirmDelete('<?php echo htmlspecialchars($row['fullname']); ?>', '<?php echo htmlspecialchars($row['student_no']); ?>')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="no-data">
                                    <i class="fas fa-users"></i>
                                    <p>No students found</p>
                                    <small>Click "Add Student" to add new students</small>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const leftNavbar = document.getElementById('leftNavbar');
        const overlay = document.getElementById('overlay');

        function toggleMobileMenu() {
            leftNavbar.classList.toggle('open');
            overlay.classList.toggle('active');
            document.body.style.overflow = leftNavbar.classList.contains('open') ? 'hidden' : '';
        }

        function closeMobileMenu() {
            leftNavbar.classList.remove('open');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', toggleMobileMenu);
        }

        if (overlay) {
            overlay.addEventListener('click', closeMobileMenu);
        }

        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                closeMobileMenu();
            }
        });

        // Search function
        function searchTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('studentTable');
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                const studentNo = rows[i].getElementsByTagName('td')[0];
                const fullname = rows[i].getElementsByTagName('td')[1];
                const email = rows[i].getElementsByTagName('td')[2];
                const course = rows[i].getElementsByTagName('td')[3];
                
                if (studentNo || fullname || email || course) {
                    const studentNoValue = studentNo ? studentNo.textContent || studentNo.innerText : '';
                    const fullnameValue = fullname ? fullname.textContent || fullname.innerText : '';
                    const emailValue = email ? email.textContent || email.innerText : '';
                    const courseValue = course ? course.textContent || course.innerText : '';
                    
                    if (studentNoValue.toLowerCase().indexOf(filter) > -1 ||
                        fullnameValue.toLowerCase().indexOf(filter) > -1 ||
                        emailValue.toLowerCase().indexOf(filter) > -1 ||
                        courseValue.toLowerCase().indexOf(filter) > -1) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            }
        }

        // Trigger search on Enter key
        document.getElementById('searchInput').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                searchTable();
            }
        });

        // Confirm Delete
        function confirmDelete(fullname, studentNo) {
            Swal.fire({
                title: 'Delete Student?',
                text: `Are you sure you want to delete "${fullname}" (${studentNo})?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#2ecc71',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                background: '#2c3136',
                color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) {
                    return true;
                }
            });
            return false;
        }
    </script>
</body>
</html>