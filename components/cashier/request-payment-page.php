<?php
session_start();
include('../../config/db.php');

if (!isset($_SESSION['staffvalidated']) || $_SESSION['staffvalidated'] !== true) {
    header("Location: /404.php");
    exit();
}

// Get search parameter
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Build query with search
$sql = "
    SELECT 
        r.request_id,
        r.or_number,
        r.student_no,
        r.fullname,
        r.course,
        r.status,
        r.total_amount,
        r.date_requested,
        i.name AS item_name,
        ri.quantity,
        ri.subtotal
    FROM requesttb r 
    JOIN request_items ri ON r.request_id = ri.request_id
    JOIN itemtb i ON ri.itemtbID = i.itemtbID
    WHERE r.status = 'Unpaid'
";

// Add search conditions
if (!empty($search)) {
    $sql .= " AND (
        r.or_number LIKE '%$search%' OR
        r.student_no LIKE '%$search%' OR
        r.fullname LIKE '%$search%' OR
        i.name LIKE '%$search%'
    )";
}

$sql .= " ORDER BY r.request_id DESC";

$result = $conn->query($sql);

$requests = [];

while ($row = $result->fetch_assoc()) {
    $id = $row['request_id'];

    if (!isset($requests[$id])) {
        $requests[$id] = [
            "request_id" => $row['request_id'],
            "or_number" => $row['or_number'],
            "student_no" => $row['student_no'],
            "fullname" => $row['fullname'],
            "course" => $row['course'],
            "status" => $row['status'],
            "total_amount" => $row['total_amount'],
            "date_requested" => $row['date_requested'],
            "items" => []
        ];
    }

    $requests[$id]["items"][] = [
        "name" => $row['item_name'],
        "qty" => $row['quantity'],
        "subtotal" => $row['subtotal']
    ];
}

// Get stats (these are NOT affected by search - always show total unpaid count)
$totalUnpaid = $conn->query("SELECT COUNT(*) as c FROM requesttb WHERE status = 'Unpaid'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Treasury - Payment Requests | PLMUN</title>
    <link rel="stylesheet" href="/assets/styles/allstyles.css">
    <link rel="stylesheet" href="/assets/styles/navbar.css">
    <link rel="stylesheet" href="/assets/styles/requestpayment.css">
    <link rel="icon" type="image/x-icon" href="/assets/ico/logo16ico.ico">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="https://unpkg.com/jsqr/dist/jsQR.js"></script>
</head>
<body>
    <nav class="navbar">
        <a href="/landingpage.html"><img src="/assets/img/schl_logo-1.png" alt="Logo"></a>
    </nav>

    <button class="mobile-menu-btn" id="mobileMenuBtn">
        <i class="fas fa-bars"></i>
    </button>

    <div class="overlay" id="overlay"></div>

    <div class="left-navbar" id="leftNavbar">
        <?php include('../left-navbar.php'); ?>
    </div>

    <div class="cashier-container">
        <div class="page-header">
            <h2><i class="fas fa-credit-card"></i> Payment Requests</h2>
            <p>Process and confirm student payments</p>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> 
                <?php echo $_GET['success'] == 'paid' ? 'Payment confirmed successfully! Email notification sent to student.' : 'Message sent successfully!'; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> 
                <?php echo $_GET['error'] == 'update_failed' ? 'Failed to process payment.' : 'Failed to process request.'; ?>
            </div>
        <?php endif; ?>

        <!-- Stats Cards - NOT affected by search -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-value"><?php echo $totalUnpaid; ?></div>
                <div class="stat-label">Unpaid Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💳</div>
                <div class="stat-value">Pending</div>
                <div class="stat-label">Payment Status</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🏦</div>
                <div class="stat-value">Treasury</div>
                <div class="stat-label">Cashier Office</div>
            </div>
        </div>

        <!-- Search Bar - Independent, affects only table results -->
        <div class="search-bar">
            <form method="GET" class="search-form" id="searchForm">
                <input type="text" name="search" id="searchInput" placeholder="Search by OR Number, Student No, or Name..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
            </form>
            <button type="button" id="scanCameraBtn" class="qr-scan-btn"><i class="fas fa-qrcode"></i> Scan QR</button>
        </div>

        <!-- Table - Results are filtered by search, NOT the cards -->
        <div class="table-container">
            <table class="payments-table">
                <thead>
                    <tr>
                        <th>OR Number</th>
                        <th>Date</th>
                        <th>Student No</th>
                        <th>Student Name</th>
                        <th>Course</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </td>
                </thead>
                <tbody>
                    <?php if (count($requests) > 0): ?>
                        <?php foreach($requests as $row): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['or_number']); ?></strong></td>
                            <td><?php echo isset($row['date_requested']) ? date('M d, Y', strtotime($row['date_requested'])) : 'N/A'; ?></td>
                            <td><?php echo htmlspecialchars($row['student_no']); ?></td>
                            <td title="<?php echo htmlspecialchars($row['fullname']); ?>"><?php echo htmlspecialchars(substr($row['fullname'], 0, 25)) . (strlen($row['fullname']) > 25 ? '...' : ''); ?></td>
                            <td><?php echo htmlspecialchars($row['course']); ?></td>
                            <td>
                                <div class="items-box">
                                    <?php foreach ($row['items'] as $item): ?>
                                        <div>📦 <?php echo htmlspecialchars($item['name']); ?> (x<?php echo $item['qty']; ?>) - ₱<?php echo number_format($item['subtotal'],2); ?></div>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td class="total-amount">₱<?php echo number_format($row['total_amount'],2); ?></td>
                            <td><span class="status-badge status-unpaid"><?php echo $row['status']; ?></span></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="pay-btn" onclick="processPayment('<?php echo $row['request_id']; ?>', '<?php echo htmlspecialchars($row['or_number']); ?>', '<?php echo number_format($row['total_amount'],2); ?>')">
                                        <i class="fas fa-check-circle"></i> Mark as Paid
                                    </button>
                                    <button class="message-btn" onclick="openMessageModal('<?php echo $row['request_id']; ?>', '<?php echo htmlspecialchars($row['or_number']); ?>')">
                                        <i class="fas fa-envelope"></i> Message
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="no-data">
                                <i class="fas fa-inbox"></i>
                                <p>No unpaid requests found</p>
                                <small>Try a different search term</small>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Message Modal -->
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeMessageModal()">&times;</button>
            <h3><i class="fas fa-envelope"></i> Send Message to <span id="orText"></span></h3>
            <form method="POST" action="actions/send_message.php">
                <input type="hidden" name="request_id" id="request_id">
                <textarea name="message" id="messageBox" placeholder="Type your message here..." required></textarea>
                <div class="modal-buttons">
                    <button type="submit" name="send" class="send-btn"><i class="fas fa-paper-plane"></i> Send</button>
                    <button type="button" class="cancel-btn" onclick="closeMessageModal()"><i class="fas fa-times"></i> Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- QR Scanner Modal -->
    <div id="qrModal" class="qr-modal">
        <div class="qr-modal-content">
            <div class="qr-modal-header">
                <h3><i class="fas fa-qrcode"></i> Scan QR Code</h3>
                <button class="qr-close" onclick="closeQRModal()">&times;</button>
            </div>
            <div id="cameraScanView">
                <div id="qr-reader"></div>
                <div id="qr-result" class="qr-result"></div>
                <button type="button" id="stopCameraBtn" class="qr-action-btn">Stop Camera</button>
            </div>
            <div id="uploadView" style="display: none;">
                <div class="upload-area">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Upload an image containing a QR code</p>
                    <label class="file-label">
                        <i class="fas fa-folder-open"></i> Choose Image
                        <input type="file" id="qrImageUpload" accept="image/*" class="file-input">
                    </label>
                    <div id="uploadPreview"></div>
                </div>
                <button type="button" id="decodeImageBtn" class="qr-action-btn qr-decode-btn">
                    <i class="fas fa-qrcode"></i> Decode QR Code
                </button>
            </div>
            <div class="qr-switch-buttons">
                <button type="button" id="switchToCamera" class="switch-btn">📸 Camera</button>
                <button type="button" id="switchToUpload" class="switch-btn">📁 Upload</button>
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

        // Message Modal
        const messageModal = document.getElementById('messageModal');
        const orText = document.getElementById('orText');
        const requestInput = document.getElementById('request_id');
        const messageBox = document.getElementById('messageBox');

        function openMessageModal(id, orNumber) {
            requestInput.value = id;
            orText.innerText = orNumber;
            messageModal.style.display = 'flex';
        }

        function closeMessageModal() {
            messageModal.style.display = 'none';
            if (messageBox) messageBox.value = '';
        }

        // Process Payment
        function processPayment(requestId, orNumber, amount) {
            Swal.fire({
                title: 'Confirm Payment?',
                html: `Process payment for <strong>${orNumber}</strong> with amount <strong>₱${amount}</strong>?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2ecc71',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, confirm payment!',
                cancelButtonText: 'Cancel',
                background: '#2c3136',
                color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'actions/req-action.php';
                    
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'id';
                    idInput.value = requestId;
                    
                    const statusInput = document.createElement('input');
                    statusInput.type = 'hidden';
                    statusInput.name = 'status';
                    statusInput.value = 'Paid';
                    
                    const updateInput = document.createElement('input');
                    updateInput.type = 'hidden';
                    updateInput.name = 'update';
                    updateInput.value = 'true';
                    
                    form.appendChild(idInput);
                    form.appendChild(statusInput);
                    form.appendChild(updateInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // QR Scanner Variables
        let html5QrCode;
        let isScanning = false;
        const qrModal = document.getElementById('qrModal');
        const scanCameraBtn = document.getElementById('scanCameraBtn');
        const qrResult = document.getElementById('qr-result');

        function handleQRResult(decodedText) {
            console.log("QR Scanned:", decodedText);
            if (qrResult) {
                qrResult.innerHTML = `<div style="background: #2ecc71; padding: 10px; border-radius: 5px; color: #2c3136;">
                                        ✅ QR Code Detected: ${decodedText}<br> Searching...
                                    </div>`;
            }
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.value = decodedText;
                setTimeout(() => {
                    closeQRModal();
                    document.getElementById('searchForm').submit();
                }, 1000);
            }
        }

        function startCamera() {
            if (html5QrCode) html5QrCode.stop();
            const readerDiv = document.getElementById('qr-reader');
            if (!readerDiv) return;
            readerDiv.innerHTML = '';
            if (qrResult) qrResult.innerHTML = '';
            html5QrCode = new Html5Qrcode("qr-reader");
            html5QrCode.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: { width: 250, height: 250 } },
                (decodedText) => handleQRResult(decodedText),
                (errorMessage) => {}
            ).catch(err => {
                console.error("Camera error:", err);
                if (qrResult) {
                    qrResult.innerHTML = '<div style="background: #e74c3c; padding: 10px; border-radius: 5px;">❌ Cannot access camera. Please check permissions or use upload option.</div>';
                }
            });
        }

        function previewImage() {
            const fileInput = document.getElementById('qrImageUpload');
            const preview = document.getElementById('uploadPreview');
            if (fileInput.files && fileInput.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 100px; border-radius: 5px; margin-top: 10px;">`;
                };
                reader.readAsDataURL(fileInput.files[0]);
            }
        }

        function decodeQRImage() {
            const fileInput = document.getElementById('qrImageUpload');
            const file = fileInput.files[0];
            const qrResultDiv = document.getElementById('qr-result');
            if (!file) {
                Swal.fire({ title: 'No Image Selected', text: 'Please select an image first', icon: 'warning', background: '#2c3136', color: '#fff', confirmButtonColor: '#2ecc71' });
                return;
            }
            if (qrResultDiv) qrResultDiv.innerHTML = '<div style="background: #3498db; padding: 10px; border-radius: 5px;">🔍 Decoding image...</div>';
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    canvas.width = img.width;
                    canvas.height = img.height;
                    ctx.drawImage(img, 0, 0, img.width, img.height);
                    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    const code = jsQR(imageData.data, imageData.width, imageData.height);
                    if (code) {
                        if (qrResultDiv) qrResultDiv.innerHTML = `<div style="background: #2ecc71; padding: 10px; border-radius: 5px; color: #2c3136;">✅ QR Code detected: ${code.data}<br> Searching...</div>`;
                        const searchInput = document.getElementById('searchInput');
                        if (searchInput) {
                            searchInput.value = code.data;
                            setTimeout(() => { closeQRModal(); document.getElementById('searchForm').submit(); }, 1000);
                        }
                    } else {
                        if (qrResultDiv) qrResultDiv.innerHTML = '<div style="background: #e74c3c; padding: 10px; border-radius: 5px;">❌ No QR Code found in this image.</div>';
                    }
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }

        function closeQRModal() {
            qrModal.style.display = 'none';
            if (html5QrCode) html5QrCode.stop();
            const fileInput = document.getElementById('qrImageUpload');
            const preview = document.getElementById('uploadPreview');
            const qrResultDiv = document.getElementById('qr-result');
            if (fileInput) fileInput.value = '';
            if (preview) preview.innerHTML = '';
            if (qrResultDiv) qrResultDiv.innerHTML = '';
        }

        function switchToCamera() {
            document.getElementById('cameraScanView').style.display = 'block';
            document.getElementById('uploadView').style.display = 'none';
            document.getElementById('switchToCamera').classList.add('active');
            document.getElementById('switchToUpload').classList.remove('active');
            startCamera();
        }

        function switchToUpload() {
            document.getElementById('cameraScanView').style.display = 'none';
            document.getElementById('uploadView').style.display = 'block';
            document.getElementById('switchToCamera').classList.remove('active');
            document.getElementById('switchToUpload').classList.add('active');
            if (html5QrCode) html5QrCode.stop();
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (scanCameraBtn) scanCameraBtn.onclick = function() { qrModal.style.display = 'flex'; startCamera(); };
            const qrClose = document.querySelector('.qr-close');
            if (qrClose) qrClose.onclick = closeQRModal;
            const stopCameraBtn = document.getElementById('stopCameraBtn');
            if (stopCameraBtn) stopCameraBtn.onclick = closeQRModal;
            const switchToCameraBtn = document.getElementById('switchToCamera');
            const switchToUploadBtn = document.getElementById('switchToUpload');
            const decodeImageBtn = document.getElementById('decodeImageBtn');
            const qrImageUpload = document.getElementById('qrImageUpload');
            if (switchToCameraBtn) switchToCameraBtn.onclick = switchToCamera;
            if (switchToUploadBtn) switchToUploadBtn.onclick = switchToUpload;
            if (decodeImageBtn) decodeImageBtn.onclick = decodeQRImage;
            if (qrImageUpload) qrImageUpload.onchange = previewImage;
        });

        window.onclick = function(event) {
            if (event.target === messageModal) closeMessageModal();
            if (event.target === qrModal) closeQRModal();
        }
    </script>
</body>
</html>