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

    // Build date condition for analytics (WITH date filter)
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

    // For queries that don't use r alias (for analytics)
    $date_condition_no_alias = "";
    if ($date_filter == 'today') {
        $date_condition_no_alias = "AND DATE(date_requested) = CURDATE()";
    } elseif ($date_filter == 'week') {
        $date_condition_no_alias = "AND YEARWEEK(date_requested) = YEARWEEK(CURDATE())";
    } elseif ($date_filter == 'month') {
        $date_condition_no_alias = "AND MONTH(date_requested) = MONTH(CURDATE()) AND YEAR(date_requested) = YEAR(CURDATE())";
    } elseif ($date_filter == 'year') {
        $date_condition_no_alias = "AND YEAR(date_requested) = YEAR(CURDATE())";
    } elseif ($date_filter == 'custom' && $start_date && $end_date) {
        $date_condition_no_alias = "AND DATE(date_requested) BETWEEN '$start_date' AND '$end_date'";
    }

    // ========== TOP STATISTICS (NOT affected by date filter) ==========
    // These show ALL TIME data regardless of filter
    $totalRequests = $conn->query("SELECT COUNT(*) as c FROM requesttb")->fetch_assoc()['c'];
    $pendingRequests = $conn->query("SELECT COUNT(*) as c FROM requesttb WHERE status = 'Pending'")->fetch_assoc()['c'];
    $completedRequests = $conn->query("SELECT COUNT(*) as c FROM requesttb WHERE status = 'Completed'")->fetch_assoc()['c'];
    $totalStudents = $conn->query("SELECT COUNT(*) as c FROM students")->fetch_assoc()['c'];
    $totalItems = $conn->query("SELECT COUNT(*) as c FROM itemtb")->fetch_assoc()['c'];
    
    // Total Sales (ALL TIME - not affected by filter)
    $salesQuery = "SELECT COALESCE(SUM(total_amount), 0) as total_sales FROM requesttb WHERE status IN ('Paid', 'Completed')";
    $totalSales = $conn->query($salesQuery)->fetch_assoc()['total_sales'] ?? 0;
    
    // ========== ANALYTICS SECTION (AFFECTED by date filter) ==========
    
    // Monthly Sales for Chart - FILTERED by the selected date range
    $monthlySales = [];
    $chartTitle = "Monthly Sales";
    
    if ($date_filter == 'custom' && $start_date && $end_date) {
        // For custom date range, get sales grouped by month within the range
        $start_year = date('Y', strtotime($start_date));
        $end_year = date('Y', strtotime($end_date));
        
        for ($i = 1; $i <= 12; $i++) {
            $monthSales = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as sales FROM requesttb WHERE MONTH(date_requested) = $i AND YEAR(date_requested) BETWEEN $start_year AND $end_year AND status IN ('Paid', 'Completed') AND DATE(date_requested) BETWEEN '$start_date' AND '$end_date'")->fetch_assoc()['sales'];
            $monthlySales[] = $monthSales;
        }
        $chartTitle = "Monthly Sales (" . date('M d, Y', strtotime($start_date)) . " - " . date('M d, Y', strtotime($end_date)) . ")";
    } elseif ($date_filter == 'year') {
        $currentYear = date('Y');
        for ($i = 1; $i <= 12; $i++) {
            $monthSales = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as sales FROM requesttb WHERE MONTH(date_requested) = $i AND YEAR(date_requested) = $currentYear AND status IN ('Paid', 'Completed')")->fetch_assoc()['sales'];
            $monthlySales[] = $monthSales;
        }
        $chartTitle = "Monthly Sales ($currentYear)";
    } elseif ($date_filter == 'month') {
        $currentMonth = date('m');
        $currentYear = date('Y');
        $monthName = date('F Y');
        for ($i = 1; $i <= 12; $i++) {
            if ($i == $currentMonth) {
                $monthSales = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as sales FROM requesttb WHERE MONTH(date_requested) = $i AND YEAR(date_requested) = $currentYear AND status IN ('Paid', 'Completed')")->fetch_assoc()['sales'];
            } else {
                $monthSales = 0;
            }
            $monthlySales[] = $monthSales;
        }
        $chartTitle = "Monthly Sales ($monthName)";
    } elseif ($date_filter == 'week') {
        $currentWeek = date('W');
        $currentYear = date('Y');
        $weekStart = date('M d', strtotime('monday this week'));
        $weekEnd = date('M d', strtotime('sunday this week'));
        for ($i = 1; $i <= 12; $i++) {
            $monthSales = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as sales FROM requesttb WHERE WEEK(date_requested, 1) = $currentWeek AND YEAR(date_requested) = $currentYear AND status IN ('Paid', 'Completed')")->fetch_assoc()['sales'];
            $monthlySales[] = $monthSales;
        }
        $chartTitle = "Monthly Sales (Week of $weekStart - $weekEnd)";
    } elseif ($date_filter == 'today') {
        $today = date('M d, Y');
        for ($i = 1; $i <= 12; $i++) {
            $monthSales = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as sales FROM requesttb WHERE DATE(date_requested) = CURDATE() AND status IN ('Paid', 'Completed')")->fetch_assoc()['sales'];
            $monthlySales[] = $monthSales;
        }
        $chartTitle = "Monthly Sales (Today - $today)";
    } else {
        // All time
        for ($i = 1; $i <= 12; $i++) {
            $monthSales = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as sales FROM requesttb WHERE MONTH(date_requested) = $i AND status IN ('Paid', 'Completed')")->fetch_assoc()['sales'];
            $monthlySales[] = $monthSales;
        }
        $chartTitle = "Monthly Sales (All Time)";
    }
    
    // User counts (never filtered)
    $staffCount = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'staff'")->fetch_assoc()['c'];
    $studentUsersCount = $conn->query("SELECT COUNT(*) as c FROM students")->fetch_assoc()['c'];
    
    // Category items query - WITH date filter (for analytics table)
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
    
    // Top 5 items overall - WITH date filter (for analytics)
    $topItemsQuery = "
        SELECT i.name, SUM(ri.quantity) as total_qty
        FROM request_items ri
        JOIN itemtb i ON ri.itemtbID = i.itemtbID
        JOIN requesttb r ON ri.request_id = r.request_id
        WHERE 1=1 $date_condition
        GROUP BY i.name
        ORDER BY total_qty DESC
        LIMIT 5
    ";
    $topItems = $conn->query($topItemsQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Admin Analytics & Sales Report - PLMUN</title>
    <link rel="stylesheet" href="/assets/styles/allstyles.css">
    <link rel="stylesheet" href="/assets/styles/navbar.css">
    <link rel="stylesheet" href="/assets/styles/adminstyles/adminhp.css">
    <link rel="icon" type="image/x-icon" sizes="16x16" href="/assets/ico/logo16ico.ico">
    <link rel="icon" type="image/x-icon" sizes="32x32" href="/assets/ico/logo32ico.ico">
    <link rel="icon" type="image/x-icon" sizes="96x96" href="/assets/ico/logo96ico.ico">
    <link rel="icon" type="image/x-icon" sizes="192x192" href="/assets/ico/logo192ico.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="/assets/ico/logo16ico.ico">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        <!-- Stats Row - ALL TIME DATA (not affected by filter) -->
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
        
        <!-- Filter Bar - Only affects analytics below -->
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
        
        <!-- Analytics Section - AFFECTED by date filter -->
        <div class="analytics-label">
            <p><i class="fas fa-chart-line"></i> Analytics Data for Selected Period</p>
        </div>
        
        <!-- Chart & Top Items -->
        <div class="two-columns">
            <div class="chart-card">
                <h3><i class="fas fa-chart-line"></i> <?php echo $chartTitle; ?></h3>
                <canvas id="salesChart"></canvas>
            </div>
            <div class="top-items-card">
                <h3><i class="fas fa-trophy"></i> Top Requested Items</h3>
                <ul class="top-items-list">
                    <?php 
                    $rank = 1;
                    if ($topItems && $topItems->num_rows > 0):
                        while($item = $topItems->fetch_assoc()): 
                    ?>
                    <li>
                        <span class="item-rank"><?php echo $rank++; ?></span>
                        <span class="item-name" title="<?php echo htmlspecialchars($item['name']); ?>"><?php echo htmlspecialchars($item['name']); ?></span>
                        <span class="item-qty"><?php echo $item['total_qty']; ?> pcs</span>
                    </li>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <li>
                        <span class="item-name">No data available for this period</span>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        
        <!-- User Stats (Never filtered - shows all time) -->
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
        
        <!-- Analytics Table - AFFECTED by date filter -->
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
                                <td title="<?php echo htmlspecialchars($row['name']); ?>"><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo number_format($row['total_qty']); ?> pcs</span></td>
                                <td class="revenue-col">₱<?php echo number_format($row['total_revenue'] ?? 0, 2); ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if (!$hasData): ?>
                            <tr>
                                <td colspan="4" class="no-data">
                                    <i class="fas fa-chart-line"></i> No data available for this period
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
    
    <style>
        .analytics-label {
            background: #2c3136;
            border-radius: 10px;
            padding: 10px 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        .analytics-label p {
            color: #2ecc71;
            font-size: 13px;
            margin: 0;
        }
        .analytics-label i {
            margin-right: 8px;
        }
    </style>
    
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
        
        // Sales Chart
        const chartData = <?php echo json_encode($monthlySales); ?>;
        const ctx = document.getElementById('salesChart').getContext('2d');
        let salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Monthly Sales (₱)',
                    data: chartData,
                    backgroundColor: 'rgba(46, 204, 113, 0.1)',
                    borderColor: '#2ecc71',
                    borderWidth: 2,
                    pointBackgroundColor: '#2ecc71',
                    pointBorderColor: '#2c3136',
                    pointRadius: 4,
                    pointHoverRadius: 6,
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
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '₱' + context.raw.toLocaleString();
                            }
                        }
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