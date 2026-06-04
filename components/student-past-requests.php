<?php
session_start();

// Check if verified for past requests
if (!isset($_SESSION['verified_for_past']) || $_SESSION['verified_for_past'] !== true) {
    header("Location: verify-student.php");
    exit();
}

include('../config/db.php');

// Use the verified session variables
$student_no = $_SESSION['verified_student_no'] ?? null;
$fullname = $_SESSION['verified_fullname'] ?? 'Student';

if (!$student_no) {
    header("Location: verify-student.php");
    exit();
}

// Fetch all past requests for this student with product names, quantities, and total item count
$query = "
    SELECT 
        r.request_id,
        r.or_number,
        r.total_amount,
        r.status,
        r.date_requested,
        SUM(ri.quantity) as total_items,
        GROUP_CONCAT(DISTINCT CONCAT(i.name, ' (x', ri.quantity, ')') SEPARATOR '|') as product_details
    FROM requesttb r
    LEFT JOIN request_items ri ON r.request_id = ri.request_id
    LEFT JOIN itemtb i ON ri.itemtbID = i.itemtbID
    WHERE r.student_no = ?
    GROUP BY r.request_id
    ORDER BY r.date_requested DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $student_no);
$stmt->execute();
$result = $stmt->get_result();
$requests = $result->fetch_all(MYSQLI_ASSOC);

// Get statistics
$totalRequests = count($requests);
$pendingCount = 0;
$paidCount = 0;
$approvedCount = 0;
$completedCount = 0;
$rejectedCount = 0;

foreach ($requests as $req) {
    switch(strtolower($req['status'])) {
        case 'pending': $pendingCount++; break;
        case 'paid': $paidCount++; break;
        case 'approved': $approvedCount++; break;
        case 'completed': $completedCount++; break;
        case 'rejected': $rejectedCount++; break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Past Requests - Student Self-Service Request System for PLMUN</title>
    <link rel="stylesheet" href="/assets/styles/allstyles.css">
    <link rel="stylesheet" href="/assets/styles/navbar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #1a1a2e;
            min-height: 100vh;
        }

        .past-requests-container {
            max-width: 1400px;
            width: 95%;
            margin: 100px auto 50px;
            padding: 30px;
            background: #2c3136;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            max-height: 90vh !important;
        }

        .page-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #2ecc71;
        }

        .page-header h1 {
            color: #2ecc71;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .page-header h1 i {
            margin-right: 10px;
        }

        .page-header p {
            color: #aaa;
            font-size: 14px;
        }

        .student-info-card {
            background: #3a4046;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .student-details h3 {
            color: #fff;
            margin-bottom: 5px;
        }

        .student-details p {
            color: #aaa;
            font-size: 14px;
        }

        .student-details i {
            color: #2ecc71;
            margin-right: 8px;
        }

        .back-btn {
            background: #2ecc71;
            color: #2c3136;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover {
            background: #45a049;
            transform: translateY(-2px);
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #3a4046;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card i {
            font-size: 35px;
            color: #2ecc71;
            margin-bottom: 10px;
        }

        .stat-card .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #fff;
        }

        .stat-card .stat-label {
            color: #aaa;
            font-size: 13px;
            margin-top: 5px;
        }

        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            border-bottom: 1px solid #4a5056;
            padding-bottom: 15px;
        }

        .filter-tab {
            background: transparent;
            border: none;
            padding: 8px 20px;
            color: #aaa;
            cursor: pointer;
            transition: all 0.3s ease;
            border-radius: 20px;
            font-size: 14px;
        }

        .filter-tab:hover {
            color: #fff;
        }

        .filter-tab.active {
            background: #2ecc71;
            color: #2c3136;
        }

        /* Table Container with Scroll */
        .table-container {
            max-height: 300px !important;
            overflow: auto;
            border-radius: 10px;
            scrollbar-width: thin;
            scrollbar-color: #2ecc71 #3a4046;
        }

        .table-container::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .table-container::-webkit-scrollbar-track {
            background: #3a4046;
            border-radius: 10px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: #2ecc71;
            border-radius: 10px;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background: #45a049;
        }

        .requests-table {
            background: #3a4046;
            border-radius: 10px;
            width: 100%;
            min-width: 800px;
            border-collapse: collapse;
        }

        .requests-table thead {
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .requests-table th {
            background: #2c3136;
            padding: 15px;
            text-align: left;
            color: #2ecc71;
            font-weight: bold;
            border-bottom: 2px solid #2ecc71;
            position: sticky;
            top: 0;
        }

        .requests-table td {
            padding: 15px;
            border-bottom: 1px solid #4a5056;
            color: #ddd;
            vertical-align: top;
        }

        .requests-table tbody tr:hover {
            background: #40464d;
        }

        .product-list {
            max-width: 350px;
        }

        .product-item {
            display: inline-block;
            background: #2c3136;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
            margin: 3px;
            white-space: nowrap;
            color: #fff;
        }

        .product-item i {
            color: #2ecc71;
            margin-right: 5px;
            font-size: 10px;
        }

        .product-more {
            color: #2ecc71;
            font-size: 11px;
            cursor: pointer;
            display: inline-block;
            margin: 3px;
            padding: 3px 8px;
            background: #2c3136;
            border-radius: 12px;
        }

        .product-more:hover {
            background: #2ecc71;
            color: #2c3136;
        }

        .total-items {
            font-weight: bold;
            color: #2ecc71;
            font-size: 14px;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-pending {
            background: #ffc107;
            color: #2c3136;
        }

        .status-approved {
            background: #2ecc71;
            color: #2c3136;
        }

        .status-rejected {
            background: #dc3545;
            color: #fff;
        }

        .status-completed {
            background: #17a2b8;
            color: #fff;
        }

        .status-unpaid {
            background: #fd7e14;
            color: #fff;
        }

        .status-paid {
            background: #28a745;
            color: #fff;
        }

        .view-details-btn {
            background: #2ecc71;
            color: #2c3136;
            border: none;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
        }

        .view-details-btn:hover {
            background: #45a049;
            transform: scale(1.05);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: #2c3136;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 20px;
            border-bottom: 2px solid #2ecc71;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            color: #2ecc71;
        }

        .modal-header h3 i {
            margin-right: 10px;
        }

        .close-modal {
            background: none;
            border: none;
            color: #fff;
            font-size: 24px;
            cursor: pointer;
            transition: 0.3s;
        }

        .close-modal:hover {
            color: #dc3545;
        }

        .modal-body {
            padding: 20px;
        }

        .request-info {
            background: #3a4046;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .request-info p {
            margin: 8px 0;
            color: #ddd;
        }

        .request-info strong {
            color: #2ecc71;
        }

        .items-table {
            width: 100%;
            margin-top: 15px;
            border-collapse: collapse;
        }

        .items-table th, .items-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #4a5056;
        }
        #requestsTableBody{
            overflow-y: scroll;
            height: 400px;
        }
        .items-table th {
            background: #2c3136;
            color: #2ecc71;
        }

        .tracking-timeline {
            margin-top: 20px;
            padding: 15px;
            background: #3a4046;
            border-radius: 10px;
        }

        .timeline-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            position: relative;
        }

        .timeline-icon {
            width: 40px;
            height: 40px;
            background: #2c3136;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            z-index: 1;
        }

        .timeline-icon i {
            color: #2ecc71;
            font-size: 18px;
        }

        .timeline-content {
            flex: 1;
        }

        .timeline-content .status {
            font-weight: bold;
            color: #fff;
        }

        .timeline-content .date {
            font-size: 12px;
            color: #aaa;
        }

        .timeline-line {
            position: absolute;
            left: 20px;
            top: 40px;
            width: 2px;
            height: calc(100% - 20px);
            background: #4a5056;
        }

        .timeline-item:last-child .timeline-line {
            display: none;
        }

        .no-results {
            text-align: center;
            padding: 50px;
            color: #888;
        }

        .no-results i {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .or-number {
            font-family: monospace;
            font-size: 13px;
            font-weight: bold;
            color: #2ecc71;
        }

        @media (max-width: 768px) {
            .past-requests-container {
                margin: 80px 15px 30px;
                padding: 20px;
                width: calc(100% - 30px);
            }

            .requests-table th, 
            .requests-table td {
                padding: 10px;
                font-size: 12px;
            }

            .student-info-card {
                flex-direction: column;
                text-align: center;
            }

            .stats-row {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .stat-card {
                padding: 15px;
            }

            .stat-card .stat-number {
                font-size: 22px;
            }

            .filter-tab {
                padding: 5px 12px;
                font-size: 12px;
            }
            
            .or-number {
                font-size: 11px;
            }
            
            .product-list {
                max-width: 200px;
            }
            
            .product-item {
                font-size: 10px;
                white-space: normal;
            }
        }

        @media (max-width: 480px) {
            .requests-table th, 
            .requests-table td {
                padding: 8px;
                font-size: 10px;
            }
            
            .view-details-btn {
                padding: 4px 8px;
                font-size: 10px;
            }
            
            .status-badge {
                padding: 3px 8px;
                font-size: 10px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="/landingpage.html"><img src="/assets/img/schl_logo-1.png" alt="Logo"></a>
    </nav>

    <div class="past-requests-container">
        <div class="page-header">
            <h1><i class="fas fa-history"></i> Past Requests</h1>
            <p>View and track all your submitted requests</p>
        </div>

        <div class="student-info-card">
            <div class="student-details">
                <h3><i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($fullname); ?></h3>
                <p><i class="fas fa-id-card"></i> Student No: <?php echo htmlspecialchars($student_no); ?></p>
            </div>
            <a href="/landingpage.html" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>

        <div class="stats-row">
            <div class="stat-card">
                <i class="fas fa-clipboard-list"></i>
                <div class="stat-number"><?php echo $totalRequests; ?></div>
                <div class="stat-label">Total Requests</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-clock"></i>
                <div class="stat-number"><?php echo $pendingCount; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-credit-card"></i>
                <div class="stat-number"><?php echo $paidCount; ?></div>
                <div class="stat-label">Paid</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle"></i>
                <div class="stat-number"><?php echo $approvedCount; ?></div>
                <div class="stat-label">Approved</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-double"></i>
                <div class="stat-number"><?php echo $completedCount; ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </div>

        <div class="filter-tabs">
            <button class="filter-tab active" data-filter="all">All Requests</button>
            <button class="filter-tab" data-filter="pending">Pending</button>
            <button class="filter-tab" data-filter="paid">Paid</button>
            <button class="filter-tab" data-filter="approved">Approved</button>
            <button class="filter-tab" data-filter="completed">Completed</button>
            <button class="filter-tab" data-filter="rejected">Rejected</button>
        </div>

        <!-- Table Container with Scroll -->
        <div class="table-container">
            <table class="requests-table">
                <thead>
                    <tr>
                        <th>OR Number</th>
                        <th>Date Requested</th>
                        <th>Product(s)</th>
                        <th>Items</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="requestsTableBody">
                    <?php if (count($requests) > 0): ?>
                        <?php foreach ($requests as $request): ?>
                            <tr data-status="<?php echo strtolower($request['status']); ?>">
                                <td class="or-number"><?php echo htmlspecialchars($request['or_number']); ?></td>
                                <td><?php echo date('M d, Y h:i A', strtotime($request['date_requested'])); ?></td>
                                <td class="product-list">
                                    <?php 
                                    if (!empty($request['product_details'])) {
                                        $products = explode('|', $request['product_details']);
                                        $displayProducts = array_slice($products, 0, 3);
                                        $remaining = count($products) - 3;
                                        
                                        foreach ($displayProducts as $product):
                                            if (!empty($product)):
                                    ?>
                                        <span class="product-item"><i class="fas fa-box"></i> <?php echo htmlspecialchars($product); ?></span>
                                    <?php 
                                            endif;
                                        endforeach; 
                                        
                                        if ($remaining > 0):
                                    ?>
                                        <span class="product-more" onclick="showAllProducts('<?php echo addslashes($request['product_details']); ?>')">+<?php echo $remaining; ?> more</span>
                                    <?php 
                                        endif;
                                    } else {
                                    ?>
                                        <span class="product-item"><i class="fas fa-box"></i> No products</span>
                                    <?php } ?>
                                </td>
                                <td class="total-items"><?php echo $request['total_items']; ?> item(s)</td>
                                <td>₱<?php echo number_format($request['total_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($request['status']); ?>">
                                        <?php echo $request['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="view-details-btn" onclick="viewRequestDetails(<?php echo $request['request_id']; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">
                                <div class="no-results">
                                    <i class="fas fa-inbox"></i>
                                    <p>No requests found</p>
                                    <small>You haven't submitted any requests yet</small>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="productModal" class="modal">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h3><i class="fas fa-boxes"></i> All Products</h3>
                <button class="close-modal" onclick="closeProductModal()">&times;</button>
            </div>
            <div class="modal-body" id="productModalBody">
            </div>
        </div>
    </div>

    <div id="requestModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-receipt"></i> Request Details</h3>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modal-body">
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 40px; color: #2ecc71;"></i>
                    <p>Loading...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showAllProducts(productsDetails) {
            const products = productsDetails.split('|');
            let html = '<div style="display: flex; flex-direction: column; gap: 10px;">';
            products.forEach(product => {
                if (product.trim()) {
                    html += `<div style="padding: 10px; background: #3a4046; border-radius: 8px; display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-box" style="color: #2ecc71; font-size: 16px;"></i>
                                <span style="flex: 1;">${product}</span>
                            </div>`;
                }
            });
            html += '</div>';
            
            document.getElementById('productModalBody').innerHTML = html;
            document.getElementById('productModal').style.display = 'flex';
        }
        
        function closeProductModal() {
            document.getElementById('productModal').style.display = 'none';
        }

        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                const rows = document.querySelectorAll('.requests-table tbody tr');
                
                rows.forEach(row => {
                    if (filter === 'all') {
                        row.style.display = '';
                    } else {
                        const status = row.dataset.status;
                        if (status === filter) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    }
                });
            });
        });

        function viewRequestDetails(requestId) {
            const modal = document.getElementById('requestModal');
            modal.style.display = 'flex';
            
            fetch(`../get_request_details.php?request_id=${requestId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        displayRequestDetails(data);
                    } else {
                        document.getElementById('modal-body').innerHTML = `
                            <div style="text-align: center; padding: 40px;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 40px; color: #dc3545;"></i>
                                <p>Failed to load request details</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('modal-body').innerHTML = `
                        <div style="text-align: center; padding: 40px;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 40px; color: #dc3545;"></i>
                            <p>Error loading details</p>
                        </div>
                    `;
                });
        }

        function displayRequestDetails(data) {
            const request = data.request;
            const items = data.items;
            
            let itemsHtml = '';
            let totalQty = 0;
            items.forEach(item => {
                totalQty += parseInt(item.quantity);
                itemsHtml += `
                    <tr>
                        <td>${item.name}${item.size ? ' <small style="color:#2ecc71;">(Size: ' + item.size + ')</small>' : ''}</td>
                        <td>${item.quantity}</td>
                        <td>₱${parseFloat(item.price).toFixed(2)}</td>
                        <td>₱${parseFloat(item.subtotal).toFixed(2)}</td>
                    </tr>
                `;
            });
            
            let statusIcon = '';
            switch(request.status.toLowerCase()) {
                case 'pending': statusIcon = 'fa-clock'; break;
                case 'paid': statusIcon = 'fa-credit-card'; break;
                case 'approved': statusIcon = 'fa-check-circle'; break;
                case 'completed': statusIcon = 'fa-check-double'; break;
                case 'rejected': statusIcon = 'fa-times-circle'; break;
                default: statusIcon = 'fa-info-circle';
            }
            
            const modalBody = `
                <div class="request-info">
                    <p><strong><i class="fas fa-hashtag"></i> OR Number:</strong> ${request.or_number}</p>
                    <p><strong><i class="fas fa-calendar"></i> Date Requested:</strong> ${new Date(request.date_requested).toLocaleString()}</p>
                    <p><strong><i class="fas ${statusIcon}"></i> Status:</strong> <span class="status-badge status-${request.status.toLowerCase()}">${request.status}</span></p>
                    <p><strong><i class="fas fa-boxes"></i> Total Items:</strong> ${totalQty} item(s)</p>
                    <p><strong><i class="fas fa-money-bill"></i> Total Amount:</strong> ₱${parseFloat(request.total_amount).toFixed(2)}</p>
                </div>
                
                <h4 style="color: #2ecc71; margin-bottom: 10px;"><i class="fas fa-list"></i> Requested Items</h4>
                <div style="overflow-x: auto;">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${itemsHtml}
                        </tbody>
                    </table>
                </div>
                
                <div class="tracking-timeline">
                    <h4 style="color: #2ecc71; margin-bottom: 15px;"><i class="fas fa-chart-line"></i> Request Timeline</h4>
                    ${getTimelineHtml(request)}
                </div>
            `;
            
            document.getElementById('modal-body').innerHTML = modalBody;
        }
        
        function getTimelineHtml(request) {
            const statuses = [
                { status: 'Pending', icon: 'fa-clock', description: 'Request submitted and waiting for approval' },
                { status: 'Paid', icon: 'fa-credit-card', description: 'Payment has been made' },
                { status: 'Approved', icon: 'fa-check-circle', description: 'Request has been approved' },
                { status: 'Completed', icon: 'fa-check-double', description: 'Request has been completed' },
                { status: 'Rejected', icon: 'fa-times-circle', description: 'Request was rejected' }
            ];
            
            let currentStatusFound = false;
            let timelineHtml = '';
            
            for (let status of statuses) {
                let isActive = request.status.toLowerCase() === status.status.toLowerCase();
                let isCompleted = false;
                
                if (!currentStatusFound && !isActive) {
                    isCompleted = true;
                }
                
                if (isActive) currentStatusFound = true;
                
                if (request.status.toLowerCase() === 'rejected' && status.status !== 'Rejected') {
                    continue;
                }
                
                timelineHtml += `
                    <div class="timeline-item">
                        <div class="timeline-icon" style="background: ${isActive ? '#2ecc71' : (isCompleted ? '#3a4046' : '#2c3136')}">
                            <i class="fas ${status.icon}" style="color: ${isActive ? '#2c3136' : '#aaa'}"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="status" style="color: ${isActive ? '#2ecc71' : (isCompleted ? '#fff' : '#666')}">
                                ${status.status}
                            </div>
                            <div class="date">${status.description}</div>
                            ${isActive ? `<div class="date" style="color: #2ecc71; margin-top: 5px;">📅 ${new Date(request.date_requested).toLocaleDateString()}</div>` : ''}
                        </div>
                        <div class="timeline-line"></div>
                    </div>
                `;
            }
            
            return timelineHtml;
        }

        function closeModal() {
            document.getElementById('requestModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('requestModal');
            const productModal = document.getElementById('productModal');
            if (event.target === modal) {
                closeModal();
            }
            if (event.target === productModal) {
                closeProductModal();
            }
        }
    </script>
</body>
</html>