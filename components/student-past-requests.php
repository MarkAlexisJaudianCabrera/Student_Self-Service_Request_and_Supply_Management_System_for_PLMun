<?php
session_start();

// Only check if verified for past requests (OTP verification)
if (!isset($_SESSION['verified_for_past']) || $_SESSION['verified_for_past'] !== true) {
    header("Location: verify-student.php");
    exit();
}

include('../config/db.php');

$student_no = $_SESSION['verified_student_no'] ?? null;
$fullname = $_SESSION['verified_fullname'] ?? 'Student';

if (!$student_no) {
    header("Location: verify-student.php");
    exit();
}

// Fetch all past requests with pickup schedule
$query = "
    SELECT 
        r.request_id,
        r.or_number,
        r.total_amount,
        r.status,
        r.date_requested,
        r.pickup_schedule,
        r.pickup_notes,
        COUNT(DISTINCT ri.request_item_id) as item_count,
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Past Requests - Student Self-Service Request System for PLMUN</title>
    <link rel="stylesheet" href="/assets/styles/allstyles.css">
    <link rel="stylesheet" href="/assets/styles/navbar.css">
    <link rel="stylesheet" href="/assets/styles/studpastreq.css">
    <!-- For different sizes -->
    <link rel="icon" type="image/x-icon" sizes="16x16" href="/assets/ico/logo16ico.ico">
    <link rel="icon" type="image/x-icon" sizes="32x32" href="/assets/ico/logo32ico.ico">
    <link rel="icon" type="image/x-icon" sizes="96x96" href="/assets/ico/logo96ico.ico">
    <link rel="icon" type="image/x-icon" sizes="192x192" href="/assets/ico/logo192ico.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <!-- Top Navbar -->
    <nav class="navbar">
        <a href="/landingpage.html"><img src="/assets/img/schl_logo-1.png" alt="Logo"></a>
    </nav>

    <!-- Main Content -->
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
            <a href="/landingpage.html" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Home</a>
        </div>

        <div class="stats-row">
            <div class="stat-card"><i class="fas fa-clipboard-list"></i><div class="stat-number"><?php echo $totalRequests; ?></div><div class="stat-label">Total</div></div>
            <div class="stat-card"><i class="fas fa-clock"></i><div class="stat-number"><?php echo $pendingCount; ?></div><div class="stat-label">Pending</div></div>
            <div class="stat-card"><i class="fas fa-credit-card"></i><div class="stat-number"><?php echo $paidCount; ?></div><div class="stat-label">Paid</div></div>
            <div class="stat-card"><i class="fas fa-check-circle"></i><div class="stat-number"><?php echo $approvedCount; ?></div><div class="stat-label">Approved</div></div>
            <div class="stat-card"><i class="fas fa-check-double"></i><div class="stat-number"><?php echo $completedCount; ?></div><div class="stat-label">Completed</div></div>
        </div>

        <div class="filter-tabs">
            <button class="filter-tab active" data-filter="all">All</button>
            <button class="filter-tab" data-filter="pending">Pending</button>
            <button class="filter-tab" data-filter="paid">Paid</button>
            <button class="filter-tab" data-filter="approved">Approved</button>
            <button class="filter-tab" data-filter="completed">Completed</button>
            <button class="filter-tab" data-filter="rejected">Rejected</button>
        </div>

        <div class="table-container">
            <table class="requests-table" id="requestsTable">
                <thead>
                    <tr>
                        <th>OR Number</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Pickup Schedule</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($requests) > 0): ?>
                        <?php foreach ($requests as $request): ?>
                            <tr data-status="<?php echo strtolower($request['status']); ?>">
                                <td><strong><?php echo htmlspecialchars($request['or_number']); ?></strong></td>
                                <td><?php echo date('M d, Y h:i A', strtotime($request['date_requested'])); ?></td>
                                <td><?php echo $request['item_count']; ?> item(s)</td>
                                <td class="revenue-col">₱<?php echo number_format($request['total_amount'], 2); ?></td>
                                <td><span class="status-badge status-<?php echo strtolower($request['status']); ?>"><?php echo $request['status']; ?></span></td>
                                <td class="pickup-schedule">
                                    <?php if (!empty($request['pickup_schedule']) && (strtolower($request['status']) == 'paid' || strtolower($request['status']) == 'completed')): ?>
                                        <span class="pickup-scheduled"><i class="fas fa-calendar-check"></i> <?php echo date('M d, Y h:i A', strtotime($request['pickup_schedule'])); ?></span>
                                    <?php elseif (strtolower($request['status']) == 'paid'): ?>
                                        <span class="pickup-pending"><i class="fas fa-clock"></i> Pending</span>
                                    <?php else: ?>
                                        <span style="color:#aaa;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><button class="view-details-btn" onclick="viewRequestDetails(<?php echo $request['request_id']; ?>)"><i class="fas fa-eye"></i> View</button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="no-data">
                                <i class="fas fa-inbox"></i>
                                <p>No requests found</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div id="requestModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-receipt"></i> Request Details</h3>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modal-body">
                <div style="text-align:center;padding:40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size:40px;color:#2ecc71;"></i>
                    <p>Loading...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Filter tabs
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                const filter = this.dataset.filter;
                document.querySelectorAll('#requestsTable tbody tr').forEach(row => {
                    if (filter === 'all' || row.dataset.status === filter) row.style.display = '';
                    else row.style.display = 'none';
                });
            });
        });

        function viewRequestDetails(requestId) {
            document.getElementById('requestModal').style.display = 'flex';
            fetch(`../get_request_details.php?request_id=${requestId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) displayRequestDetails(data);
                    else document.getElementById('modal-body').innerHTML = '<div style="text-align:center;padding:40px;"><i class="fas fa-exclamation-triangle" style="font-size:40px;color:#dc3545;"></i><p>Failed to load</p></div>';
                })
                .catch(() => { 
                    document.getElementById('modal-body').innerHTML = '<div style="text-align:center;padding:40px;"><i class="fas fa-exclamation-triangle" style="font-size:40px;color:#dc3545;"></i><p>Error loading</p></div>'; 
                });
        }

        function displayRequestDetails(data) {
            const request = data.request, items = data.items;
            let itemsHtml = '', totalQty = 0;
            items.forEach(item => { 
                totalQty += parseInt(item.quantity); 
                itemsHtml += `<tr><td><strong>${item.name}</strong>${item.size ? ' <small style="color:#2ecc71;">(Size: ' + item.size + ')</small>' : ''}</td><td>${item.quantity}</td><td>₱${parseFloat(item.price).toFixed(2)}</td><td>₱${parseFloat(item.subtotal).toFixed(2)}</td></tr>`; 
            });
            
            let statusIcon = { pending: 'fa-clock', paid: 'fa-credit-card', approved: 'fa-check-circle', completed: 'fa-check-double', rejected: 'fa-times-circle' }[request.status.toLowerCase()] || 'fa-info-circle';
            
            let pickupHtml = '';
            if ((request.status.toLowerCase() == 'paid' || request.status.toLowerCase() == 'completed') && request.pickup_schedule) {
                pickupHtml = `<div class="pickup-info"><h4 style="color:#2ecc71;"><i class="fas fa-calendar-check"></i> Pickup Schedule</h4><p><strong>📅 Date & Time:</strong> ${new Date(request.pickup_schedule).toLocaleString()}</p>${request.pickup_notes ? `<p><strong>📝 Notes:</strong> ${request.pickup_notes}</p>` : ''}<p style="margin-top:10px;font-size:12px;color:#aaa;"><i class="fas fa-info-circle"></i> Please bring your OR number as reference.</p></div>`;
            } else if (request.status.toLowerCase() == 'paid') {
                pickupHtml = `<div class="pickup-info" style="background:rgba(255,193,7,0.15);border-color:#ffc107;"><h4 style="color:#ffc107;"><i class="fas fa-clock"></i> Awaiting Pickup Schedule</h4><p>Your payment has been confirmed. Please wait for the pickup schedule.</p></div>`;
            }
            
            document.getElementById('modal-body').innerHTML = `
                <div class="request-info">
                    <p><strong><i class="fas fa-hashtag"></i> OR Number:</strong> ${request.or_number}</p>
                    <p><strong><i class="fas fa-calendar"></i> Date Requested:</strong> ${new Date(request.date_requested).toLocaleString()}</p>
                    <p><strong><i class="fas ${statusIcon}"></i> Status:</strong> <span class="status-badge status-${request.status.toLowerCase()}">${request.status}</span></p>
                    <p><strong><i class="fas fa-boxes"></i> Total Items:</strong> ${totalQty} item(s)</p>
                    <p><strong><i class="fas fa-money-bill"></i> Total Amount:</strong> ₱${parseFloat(request.total_amount).toFixed(2)}</p>
                </div>
                <h4 style="color:#2ecc71;"><i class="fas fa-list"></i> Requested Items</h4>
                <div style="overflow-x:auto;">
                    <table class="items-table">
                        <thead><tr><th>Item Name</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
                        <tbody>${itemsHtml}</tbody>
                    </table>
                </div>
                ${pickupHtml}
                <div class="tracking-timeline">
                    <h4 style="color:#2ecc71;"><i class="fas fa-chart-line"></i> Request Timeline</h4>
                    ${getTimelineHtml(request)}
                </div>
            `;
        }

        function getTimelineHtml(request) {
            const statuses = [
                { status: 'Pending', icon: 'fa-clock', desc: 'Request submitted' },
                { status: 'Paid', icon: 'fa-credit-card', desc: 'Payment confirmed' },
                { status: 'Approved', icon: 'fa-check-circle', desc: 'Request approved' },
                { status: 'Completed', icon: 'fa-check-double', desc: 'Request completed' },
                { status: 'Rejected', icon: 'fa-times-circle', desc: 'Request rejected' }
            ];
            let currentStatusFound = false, html = '';
            for (let s of statuses) {
                let isActive = request.status.toLowerCase() === s.status.toLowerCase();
                let isCompleted = !currentStatusFound && !isActive;
                if (isActive) currentStatusFound = true;
                if (request.status.toLowerCase() === 'rejected' && s.status !== 'Rejected') continue;
                html += `<div class="timeline-item">
                            <div class="timeline-icon" style="background: ${isActive ? '#2ecc71' : (isCompleted ? '#3a4046' : '#2c3136')}">
                                <i class="fas ${s.icon}" style="color: ${isActive ? '#2c3136' : '#aaa'}"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="status" style="color: ${isActive ? '#2ecc71' : (isCompleted ? '#fff' : '#666')}">${s.status}</div>
                                <div class="date">${s.desc}</div>
                                ${isActive ? `<div class="date" style="color:#2ecc71;">📅 ${new Date(request.date_requested).toLocaleDateString()}</div>` : ''}
                            </div>
                            <div class="timeline-line"></div>
                        </div>`;
            }
            return html;
        }

        function closeModal() { 
            document.getElementById('requestModal').style.display = 'none'; 
        }
        
        window.onclick = function(e) { 
            if (e.target === document.getElementById('requestModal')) closeModal(); 
        }
    </script>
</body>
</html>