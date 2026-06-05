<?php
    session_start();
    include('../../config/db.php');
    
    if (!isset($_SESSION['staffvalidated']) || $_SESSION['staffvalidated'] !== true) {
        header("Location: /404.php");
        exit();
    }
    
    $result = $conn->query("SELECT * FROM users WHERE role != 'student' ORDER BY id DESC");
    
    // Handle success/error messages
    $message = '';
    $messageType = '';
    if (isset($_GET['success'])) {
        $message = $_GET['success'] == 'added' ? 'Staff user added successfully!' : 'Staff user deleted successfully!';
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
    <title>Admin - Manage Staff Users | PLMUN</title>
    <link rel="stylesheet" href="/assets/styles/allstyles.css">
    <link rel="stylesheet" href="/assets/styles/navbar.css">
    <link rel="stylesheet" href="/assets/styles/adminstyles/adminusrstaff.css">
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
            <h2><i class="fas fa-users"></i> Staff Users</h2>
            <p>Manage staff user accounts (Admin, Registrar, Business Center, Cashier)</p>
        </div>

        <!-- Stats Row -->
        <?php
        $totalStaff = $conn->query("SELECT COUNT(*) as c FROM users WHERE role != 'student'")->fetch_assoc()['c'];
        $adminCount = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'admin'")->fetch_assoc()['c'];
        $registrarCount = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'registrar'")->fetch_assoc()['c'];
        $businessCount = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'business'")->fetch_assoc()['c'];
        $cashierCount = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'cashier'")->fetch_assoc()['c'];
        ?>
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?php echo $totalStaff; ?></div>
                <div class="stat-label">Total Staff</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👑</div>
                <div class="stat-value"><?php echo $adminCount; ?></div>
                <div class="stat-label">Admins</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-value"><?php echo $registrarCount; ?></div>
                <div class="stat-label">Registrars</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💼</div>
                <div class="stat-value"><?php echo $businessCount; ?></div>
                <div class="stat-label">Business Center</div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Add Staff Form -->
        <div class="form-section">
            <h3><i class="fas fa-user-plus"></i> Add New Staff User</h3>
            <form method="POST" action="actions/user_action.php">
                <div class="form-grid">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Username</label>
                        <input type="text" name="username" placeholder="Enter username" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Password</label>
                        <input type="password" name="password" placeholder="Enter password" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Role</label>
                        <select name="role">
                            <option value="admin">👑 Admin</option>
                            <option value="registrar">📋 Registrar</option>
                            <option value="business">💼 Business Center</option>
                            <option value="cashier">💰 Cashier</option>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="add" class="btn-primary">
                        <i class="fas fa-plus"></i> Add Staff User
                    </button>
                </div>
            </form>
        </div>

        <!-- Staff List Table -->
        <div class="table-container">
            <div class="table-header">
                <h3><i class="fas fa-list"></i> Staff Users List</h3>
            </div>
            <div style="overflow-x: auto;">
                <table class="staff-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Password (Encrypted)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): 
                                $roleClass = '';
                                switch($row['role']) {
                                    case 'admin': $roleClass = 'role-admin'; break;
                                    case 'registrar': $roleClass = 'role-registrar'; break;
                                    case 'business': $roleClass = 'role-business'; break;
                                    case 'cashier': $roleClass = 'role-cashier'; break;
                                    default: $roleClass = 'role-admin';
                                }
                                $roleIcon = '';
                                switch($row['role']) {
                                    case 'admin': $roleIcon = '👑'; break;
                                    case 'registrar': $roleIcon = '📋'; break;
                                    case 'business': $roleIcon = '💼'; break;
                                    case 'cashier': $roleIcon = '💰'; break;
                                    default: $roleIcon = '👤';
                                }
                            ?>
                            <tr>
                                <td><i class="fas fa-user-circle" style="color: #2ecc71; margin-right: 8px;"></i><?php echo htmlspecialchars($row['username']); ?></td>
                                <td>
                                    <span class="role-badge <?php echo $roleClass; ?>">
                                        <?php echo $roleIcon; ?> <?php echo ucfirst($row['role']); ?>
                                    </span>
                                </td>
                                <td class="password-cell">••••••••</span></td>
                                <td>
                                    <a href="actions/user_action.php?delete=<?php echo $row['id']; ?>" class="delete-btn" onclick="return confirmDelete('<?php echo htmlspecialchars($row['username']); ?>')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="no-data">
                                    <i class="fas fa-users"></i>
                                    <p>No staff users found</p>
                                    <small>Click "Add Staff User" to create one</small>
                                </span>
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

        // Confirm Delete
        function confirmDelete(username) {
            Swal.fire({
                title: 'Delete Staff User?',
                text: `Are you sure you want to delete "${username}"?`,
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
                    window.location.href = `actions/user_action.php?delete=${id}`;
                }
            });
            return false;
        }
    </script>
</body>
</html>