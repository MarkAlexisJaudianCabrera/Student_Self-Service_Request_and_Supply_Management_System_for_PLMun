<?php
    session_start();
    include('../../config/db.php');
    
    if (!isset($_SESSION['staffvalidated']) || $_SESSION['staffvalidated'] !== true) {
        header("Location: /404.php");
        exit();
    }

    // Get date filter
    $date_filter = $_GET['date_filter'] ?? 'all';
    $category = $_GET['category'] ?? 'acaditem';
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';

    // Build date condition
    $date_condition = "";
    if ($date_filter == 'today') {
        $date_condition = "AND DATE(r.date_requested) = CURDATE()";
    } elseif ($date_filter == 'week') {
        $date_condition = "AND YEARWEEK(r.date_requested) = YEARWEEK(CURDATE())";
    } elseif ($date_filter == 'month') {
        $date_condition = "AND MONTH(r.date_requested) = MONTH(CURDATE()) AND YEAR(r.date_requested) = YEAR(CURDATE())";
    } elseif ($date_filter == 'year') {
        $date_condition = "AND YEAR(r.date_requested) = YEAR(CURDATE())";
    } elseif ($date_filter == 'custom' && $start_date && $end_date) {
        $date_condition = "AND DATE(r.date_requested) BETWEEN '$start_date' AND '$end_date'";
    }

    // Dashboard Stats
    $totalRequests = $conn->query("SELECT COUNT(*) as c FROM requesttb")->fetch_assoc()['c'];
    $pendingRequests = $conn->query("SELECT COUNT(*) as c FROM requesttb WHERE status = 'Pending'")->fetch_assoc()['c'];
    $completedRequests = $conn->query("SELECT COUNT(*) as c FROM requesttb WHERE status = 'Completed'")->fetch_assoc()['c'];
    $totalStudents = $conn->query("SELECT COUNT(*) as c FROM students")->fetch_assoc()['c'];
    $totalItems = $conn->query("SELECT COUNT(*) as c FROM itemtb")->fetch_assoc()['c'];
    
    // Total Sales
    $salesQuery = "SELECT SUM(total_amount) as total_sales FROM requesttb WHERE status IN ('Paid', 'Completed') $date_condition";
    $totalSales = $conn->query($salesQuery)->fetch_assoc()['total_sales'] ?? 0;
    
    // Monthly Sales for Chart
    $monthlySales = [];
    for ($i = 1; $i <= 12; $i++) {
        $monthSales = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as sales FROM requesttb WHERE MONTH(date_requested) = $i AND YEAR(date_requested) = YEAR(CURDATE()) AND status IN ('Paid', 'Completed')")->fetch_assoc()['sales'];
        $monthlySales[] = $monthSales;
    }
    
    // User counts
    $staffCount = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'staff'")->fetch_assoc()['c'];
    $studentUsersCount = $conn->query("SELECT COUNT(*) as c FROM students")->fetch_assoc()['c'];
    
    // Category items query
    $stmt = $conn->prepare("
        SELECT i.name, SUM(ri.quantity) as total_qty, SUM(ri.subtotal) as total_revenue
        FROM request_items ri
        JOIN itemtb i ON ri.itemtbID = i.itemtbID
        JOIN requesttb r ON ri.request_id = r.request_id
        WHERE i.category = ? $date_condition
        GROUP BY i.name
        ORDER BY total_qty DESC
    ");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Top 5 items overall
    $topItems = $conn->query("
        SELECT i.name, SUM(ri.quantity) as total_qty
        FROM request_items ri
        JOIN itemtb i ON ri.itemtbID = i.itemtbID
        GROUP BY i.name
        ORDER BY total_qty DESC
        LIMIT 5
    ");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Admin Analytics & Sales Report - PLMUN</title>
    <link rel="stylesheet" href="/assets/styles/allstyles.css">
    <link rel="stylesheet" href="/assets/styles/navbar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="/assets/ico/logo16ico.ico">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #1a1a2e;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        /* Top Navbar - Fixed */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: #2c3136;
            padding: 10px 20px;
            z-index: 1001;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        /* Left Navbar - Fixed (Desktop) */
        .left-navbar {
            position: fixed;
            left: 0;
            top: 60px;
            width: 260px;
            height: calc(100vh - 60px);
            background: #2c3136;
            z-index: 1000;
            overflow-y: auto;
            border-right: 1px solid #4a5056;
            transition: transform 0.3s ease;
        }

        /* Mobile Menu Button */
        .mobile-menu-btn {
            display: none;
            position: fixed;
            left: 15px;
            top: 70px;
            z-index: 1002;
            background: #2ecc71;
            color: #2c3136;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            font-size: 20px;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        /* Overlay for mobile */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        /* Main Container - Adjusted for both navbars */
        .admin-analytics-container {
            margin-left: 260px;
            margin-top: 60px;
            padding: 20px;
            background: #1a1a2e;
            min-height: calc(100vh - 60px);
            width: calc(100% - 260px);
            transition: margin 0.3s ease, width 0.3s ease;
        }

        /* Stats Grid - 3 columns */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: #2c3136;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }

        .stat-icon {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #2ecc71;
        }

        .stat-label {
            color: #aaa;
            font-size: 11px;
            margin-top: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Filter Bar */
        .filter-bar {
            background: #2c3136;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
        }

        .filter-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .filter-btn {
            padding: 6px 14px;
            background: #3a4046;
            border: none;
            border-radius: 20px;
            text-decoration: none;
            color: #fff;
            font-size: 12px;
            transition: all 0.3s;
        }

        .filter-btn:hover, .filter-btn.active {
            background: #2ecc71;
            color: #2c3136;
        }

        .custom-date {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .custom-date input {
            padding: 6px 10px;
            background: #3a4046;
            border: 1px solid #4a5056;
            border-radius: 5px;
            color: #fff;
            font-size: 12px;
        }

        .custom-date button {
            padding: 6px 14px;
            background: #2ecc71;
            border: none;
            border-radius: 5px;
            color: #2c3136;
            cursor: pointer;
            font-weight: bold;
            font-size: 12px;
        }

        /* Two Column Layout */
        .two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .chart-card, .top-items-card {
            background: #2c3136;
            border-radius: 10px;
            padding: 15px;
        }

        .chart-card h3, .top-items-card h3 {
            color: #2ecc71;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #2ecc71;
            font-size: 15px;
        }

        .chart-card canvas {
            max-height: 220px;
            width: 100%;
        }

        /* Top Items List with Ellipsis */
        .top-items-list {
            list-style: none;
        }

        .top-items-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #4a5056;
        }

        .top-items-list li:last-child {
            border-bottom: none;
        }

        .item-rank {
            width: 24px;
            height: 24px;
            background: #2ecc71;
            color: #2c3136;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 11px;
            flex-shrink: 0;
        }

        .item-name {
            flex: 1;
            margin-left: 10px;
            color: #fff;
            font-size: 12px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 180px;
        }

        .item-qty {
            font-weight: bold;
            color: #2ecc71;
            font-size: 12px;
            flex-shrink: 0;
        }

        /* User Stats Row */
        .user-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .user-card {
            background: #2c3136;
            border-radius: 10px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-icon {
            width: 45px;
            height: 45px;
            background: rgba(46, 204, 113, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .user-info h4 {
            color: #aaa;
            font-size: 12px;
            margin-bottom: 3px;
        }

        .user-info .count {
            font-size: 24px;
            font-weight: bold;
            color: #2ecc71;
        }

        /* Analytics Table */
        .analytics-table-container {
            background: #2c3136;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .analytics-table-container h3 {
            color: #2ecc71;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #2ecc71;
            font-size: 15px;
        }

        .category-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .category-btn {
            padding: 6px 16px;
            background: #3a4046;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            color: #fff;
            font-size: 12px;
            transition: all 0.3s;
        }

        .category-btn.active {
            background: #2ecc71;
            color: #2c3136;
        }

        .analytics-table {
            overflow-x: auto;
        }

        .analytics-table table {
            width: 100%;
            border-collapse: collapse;
            min-width: 500px;
        }

        .analytics-table th {
            background: #3a4046;
            color: #2ecc71;
            padding: 10px;
            text-align: left;
            border: 1px solid #4a5056;
            font-size: 12px;
        }

        .analytics-table td {
            padding: 8px 10px;
            color: #fff;
            border: 1px solid #4a5056;
            font-size: 12px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }

        .analytics-table tr:hover {
            background: #3a4046;
        }

        .revenue-col {
            color: #2ecc71;
            font-weight: bold;
        }

        .no-data {
            text-align: center;
            padding: 30px;
            color: #aaa;
        }

        /* Logout Button */
        .logout-container {
            text-align: center;
            margin-top: 10px;
            padding-bottom: 20px;
        }

        .logout-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .logout-btn:hover {
            background: #c0392b;
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }

        ::-webkit-scrollbar-track {
            background: #3a4046;
        }

        ::-webkit-scrollbar-thumb {
            background: #2ecc71;
            border-radius: 5px;
        }

        /* ========== MOBILE RESPONSIVE ========== */
        @media (max-width: 768px) {
            /* Show mobile menu button */
            .mobile-menu-btn {
                display: block;
            }
            
            .overlay {
                display: none;
            }
            
            .overlay.active {
                display: block;
            }
            
            /* Left navbar hidden by default on mobile */
            .left-navbar {
                transform: translateX(-100%);
                width: 80%;
                max-width: 280px;
                top: 60px;
                height: calc(100vh - 60px);
                z-index: 1002;
            }
            
            .left-navbar.open {
                transform: translateX(0);
            }
            
            /* Main container full width on mobile */
            .admin-analytics-container {
                margin-left: 0;
                width: 100%;
                padding: 15px;
                margin-top: 60px;
            }
            
            /* Stats grid - 2 columns on tablet, 1 on mobile */
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
            
            .stat-value {
                font-size: 20px;
            }
            
            .stat-icon {
                font-size: 24px;
            }
            
            .stat-label {
                font-size: 10px;
            }
            
            /* Two columns become single column */
            .two-columns {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            /* User stats single column */
            .user-stats {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            /* Filter bar column layout */
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
                padding: 12px;
            }
            
            .filter-buttons {
                justify-content: center;
            }
            
            .custom-date {
                justify-content: center;
            }
            
            .custom-date input {
                font-size: 11px;
                padding: 5px 8px;
            }
            
            .custom-date button {
                font-size: 11px;
                padding: 5px 12px;
            }
            
            /* Chart adjustments */
            .chart-card canvas {
                max-height: 200px;
            }
            
            /* Top items list */
            .item-name {
                max-width: 140px;
                font-size: 11px;
            }
            
            .item-qty {
                font-size: 11px;
            }
            
            /* Analytics table */
            .analytics-table th,
            .analytics-table td {
                font-size: 11px;
                padding: 6px 8px;
            }
            
            /* Category buttons */
            .category-buttons {
                justify-content: center;
            }
            
            /* User card */
            .user-card {
                padding: 12px;
            }
            
            .user-icon {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }
            
            .user-info .count {
                font-size: 20px;
            }
            
            .user-info h4 {
                font-size: 11px;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .admin-analytics-container {
                padding: 10px;
            }
            
            .stat-card {
                padding: 12px;
            }
            
            .filter-btn {
                padding: 5px 10px;
                font-size: 11px;
            }
            
            .custom-date {
                flex-wrap: wrap;
            }
            
            .item-name {
                max-width: 120px;
            }
            
            .chart-card, .top-items-card, .analytics-table-container {
                padding: 12px;
            }
            
            .chart-card h3, .top-items-card h3, .analytics-table-container h3 {
                font-size: 13px;
            }
        }
    </style>
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
    
    <!-- Overlay -->
    <div class="overlay" id="overlay"></div>
    
    <!-- Left Navbar -->
    <div class="left-navbar" id="leftNavbar">
        <?php include('../left-navbar.php'); ?>
    </div>
    
    <div class="admin-analytics-container">
        <!-- Stats Row -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-value">₱<?php echo number_format($totalSales, 2); ?></div>
                <div class="stat-label">Total Sales Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-value"><?php echo $totalRequests; ?></div>
                <div class="stat-label">Total Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-value"><?php echo $pendingRequests; ?></div>
                <div class="stat-label">Pending Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-value"><?php echo $completedRequests; ?></div>
                <div class="stat-label">Completed Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👨‍🎓</div>
                <div class="stat-value"><?php echo $totalStudents; ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-value"><?php echo $totalItems; ?></div>
                <div class="stat-label">Total Items</div>
            </div>
        </div>
        
        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="filter-buttons">
                <a href="?category=<?php echo $category; ?>&date_filter=all" class="filter-btn <?php echo $date_filter == 'all' ? 'active' : ''; ?>">All Time</a>
                <a href="?category=<?php echo $category; ?>&date_filter=today" class="filter-btn <?php echo $date_filter == 'today' ? 'active' : ''; ?>">Today</a>
                <a href="?category=<?php echo $category; ?>&date_filter=week" class="filter-btn <?php echo $date_filter == 'week' ? 'active' : ''; ?>">This Week</a>
                <a href="?category=<?php echo $category; ?>&date_filter=month" class="filter-btn <?php echo $date_filter == 'month' ? 'active' : ''; ?>">This Month</a>
                <a href="?category=<?php echo $category; ?>&date_filter=year" class="filter-btn <?php echo $date_filter == 'year' ? 'active' : ''; ?>">This Year</a>
            </div>
            <form method="GET" class="custom-date">
                <input type="hidden" name="category" value="<?php echo $category; ?>">
                <input type="hidden" name="date_filter" value="custom">
                <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                <span>→</span>
                <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                <button type="submit">Apply</button>
            </form>
        </div>
        
        <!-- Chart & Top Items -->
        <div class="two-columns">
            <div class="chart-card">
                <h3><i class="fas fa-chart-line"></i> Monthly Sales (<?php echo date('Y'); ?>)</h3>
                <canvas id="salesChart"></canvas>
            </div>
            <div class="top-items-card">
                <h3><i class="fas fa-trophy"></i> Top Requested Items</h3>
                <ul class="top-items-list">
                    <?php 
                    $rank = 1;
                    while($item = $topItems->fetch_assoc()): 
                    ?>
                    <li>
                        <span class="item-rank"><?php echo $rank++; ?></span>
                        <span class="item-name" title="<?php echo htmlspecialchars($item['name']); ?>"><?php echo htmlspecialchars($item['name']); ?></span>
                        <span class="item-qty"><?php echo $item['total_qty']; ?> pcs</span>
                    </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </div>
        
        <!-- User Stats -->
        <div class="user-stats">
            <div class="user-card">
                <div class="user-icon">👥</div>
                <div class="user-info">
                    <h4>Users (Staff)</h4>
                    <div class="count"><?php echo $staffCount; ?></div>
                </div>
            </div>
            <div class="user-card">
                <div class="user-icon">👨‍🎓</div>
                <div class="user-info">
                    <h4>Users (Student)</h4>
                    <div class="count"><?php echo $studentUsersCount; ?></div>
                </div>
            </div>
        </div>
        
        <!-- Analytics Table -->
        <div class="analytics-table-container">
            <h3><i class="fas fa-chart-bar"></i> Item Analytics - <?php echo ucfirst($category == 'acaditem' ? 'Academic Items' : 'Supply Items'); ?></h3>
            <div class="category-buttons">
                <a href="?category=acaditem&date_filter=<?php echo $date_filter; ?><?php echo $start_date ? "&start_date=$start_date&end_date=$end_date" : ''; ?>" class="category-btn <?php echo $category == 'acaditem' ? 'active' : ''; ?>">📚 Academic Items</a>
                <a href="?category=suppitem&date_filter=<?php echo $date_filter; ?><?php echo $start_date ? "&start_date=$start_date&end_date=$end_date" : ''; ?>" class="category-btn <?php echo $category == 'suppitem' ? 'active' : ''; ?>">✏️ Supply Items</a>
            </div>
            <div class="analytics-table">
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Item Name</th>
                                <th>Total Quantity</th>
                                <th>Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $count = 1;
                            $hasData = false;
                            while($row = $result->fetch_assoc()): 
                                $hasData = true;
                            ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td title="<?php echo htmlspecialchars($row['name']); ?>"><?php echo htmlspecialchars($row['name']); ?></span></td>
                                <td><?php echo number_format($row['total_qty']); ?> pcs</span></td>
                                <td class="revenue-col">₱<?php echo number_format($row['total_revenue'] ?? 0, 2); ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if (!$hasData): ?>
                            <tr>
                                <td colspan="4" class="no-data">
                                    <i class="fas fa-chart-line"></i> No data available
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Logout Button -->
        <div class="logout-container">
            <a href="../../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
        
        // Close menu on window resize if screen becomes desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                closeMobileMenu();
            }
        });
        
        // Sales Chart
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Monthly Sales (₱)',
                    data: <?php echo json_encode($monthlySales); ?>,
                    backgroundColor: 'rgba(46, 204, 113, 0.1)',
                    borderColor: '#2ecc71',
                    borderWidth: 2,
                    pointBackgroundColor: '#2ecc71',
                    pointBorderColor: '#2c3136',
                    pointRadius: 3,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { 
                        position: 'top',
                        labels: { color: '#fff', font: { size: 11 } }
                    }
                },
                scales: { 
                    y: { 
                        beginAtZero: true, 
                        ticks: { 
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            },
                            color: '#aaa',
                            font: { size: 10 }
                        },
                        grid: { color: '#4a5056' }
                    },
                    x: {
                        ticks: { color: '#aaa', font: { size: 10 } },
                        grid: { color: '#4a5056' }
                    }
                }
            }
        });
    </script>
</body>
</html>