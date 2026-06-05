<?php
    session_start();
    include('../../config/db.php');
    
    if (!isset($_SESSION['staffvalidated']) || $_SESSION['staffvalidated'] !== true) {
        header("Location: /404.php");
        exit();
    }
    
    $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

    $sql = "
        SELECT 
            a.request_id,
            a.or_number,
            a.student_no,
            a.fullname,
            a.course,
            a.status,
            a.total_amount,
            a.date_requested,
            a.pickup_schedule,
            a.pickup_notes,
            GROUP_CONCAT(
                CONCAT(c.name, ' (x', b.quantity, ')')
                SEPARATOR ', '
            ) AS item_names,
            GROUP_CONCAT(DISTINCT c.category SEPARATOR ', ') AS categories
        FROM requesttb a
        JOIN request_items b ON a.request_id = b.request_id
        JOIN itemtb c ON b.itemtbID = c.itemtbID
    ";

    if (!empty($search)) {
        $sql .= "
            WHERE 
                a.or_number LIKE '%$search%' OR
                a.student_no LIKE '%$search%' OR
                a.fullname LIKE '%$search%' OR
                a.status LIKE '%$search%' OR
                c.name LIKE '%$search%'
        ";
    }

    $sql .= "
        GROUP BY a.request_id
        ORDER BY a.request_id DESC
    ";

    $result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Admin - Manage Requests | PLMUN</title>
    <link rel="stylesheet" href="/assets/styles/allstyles.css">
    <link rel="stylesheet" href="/assets/styles/navbar.css">
    <link rel="stylesheet" href="/assets/styles/adminstyles/adminreq.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="/assets/ico/logo16ico.ico">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- QR Scanner Libraries -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="https://unpkg.com/jsqr/dist/jsQR.js"></script>
</head>
<body>
    <nav class="navbar">
        <a href="/landingpage.html"><img src="/assets/img/schl_logo-1.png" alt="Logo"></a>
    </nav>

    <?php include('../left-navbar.php'); ?>

    <div class="adminreq-megacontainer">
        <div class="page-header">
            <h2><i class="fas fa-clipboard-list"></i> Manage Requests</h2>
            <p>View, update, and manage all student requests</p>
        </div>

        <?php
        $totalRequests = $conn->query("SELECT COUNT(*) as c FROM requesttb")->fetch_assoc()['c'];
        $pendingCount = $conn->query("SELECT COUNT(*) as c FROM requesttb WHERE status = 'Pending'")->fetch_assoc()['c'];
        $paidCount = $conn->query("SELECT COUNT(*) as c FROM requesttb WHERE status = 'Paid'")->fetch_assoc()['c'];
        $completedCount = $conn->query("SELECT COUNT(*) as c FROM requesttb WHERE status = 'Completed'")->fetch_assoc()['c'];
        ?>
        <div class="stats-row">
            <div class="stat-card"><div class="stat-icon">📋</div><div class="stat-value"><?php echo $totalRequests; ?></div><div class="stat-label">Total Requests</div></div>
            <div class="stat-card"><div class="stat-icon">⏳</div><div class="stat-value"><?php echo $pendingCount; ?></div><div class="stat-label">Pending</div></div>
            <div class="stat-card"><div class="stat-icon">💰</div><div class="stat-value"><?php echo $paidCount; ?></div><div class="stat-label">Paid</div></div>
            <div class="stat-card"><div class="stat-icon">✅</div><div class="stat-value"><?php echo $completedCount; ?></div><div class="stat-label">Completed</div></div>
        </div>

        <div class="search-bar">
            <form method="GET" class="search-form" id="searchForm">
                <input type="text" name="search" id="searchInput" placeholder="Search by OR Number, Student No, Name, or Status..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
            </form>
            <button type="button" id="scanCameraBtn" class="qr-scan-btn"><i class="fas fa-qrcode"></i> Scan QR</button>
        </div>

        <div class="table-container">
            <table class="requests-table">
                <thead>
                    <tr>
                        <th>OR Number</th>
                        <th>Date</th>
                        <th>Student No</th>
                        <th>Student Name</th>
                        <th>Course</th>
                        <th>Items</th>
                        <th>Categories</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Pickup</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (isset($result) && $result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): 
                            $statusClass = '';
                            switch(strtolower($row['status'])) {
                                case 'pending': $statusClass = 'status-pending'; break;
                                case 'unpaid': $statusClass = 'status-unpaid'; break;
                                case 'paid': $statusClass = 'status-paid'; break;
                                case 'completed': $statusClass = 'status-completed'; break;
                                case 'rejected': $statusClass = 'status-rejected'; break;
                                default: $statusClass = 'status-pending';
                            }
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['or_number']); ?></strong></td>
                            <td><?php echo isset($row['date_requested']) ? date('M d, Y', strtotime($row['date_requested'])) : 'N/A'; ?></td>
                            <td><?php echo htmlspecialchars($row['student_no']); ?></td>
                            <td title="<?php echo htmlspecialchars($row['fullname']); ?>"><?php echo htmlspecialchars(substr($row['fullname'], 0, 25)) . (strlen($row['fullname']) > 25 ? '...' : ''); ?></td>
                            <td><?php echo htmlspecialchars($row['course']); ?></td>
                            <td title="<?php echo htmlspecialchars($row['item_names']); ?>"><?php echo htmlspecialchars(substr($row['item_names'], 0, 40)) . (strlen($row['item_names']) > 40 ? '...' : ''); ?></td>
                            <td>
                                <?php 
                                $cats = explode(',', $row['categories']);
                                foreach($cats as $cat):
                                    $catClass = trim($cat) == 'acaditem' ? 'category-acaditem' : 'category-suppitem';
                                    $catName = trim($cat) == 'acaditem' ? 'Academic' : 'Supply';
                                ?>
                                    <span class="category-badge <?php echo $catClass; ?>"><?php echo $catName; ?></span>
                                <?php endforeach; ?>
                            </td>
                            <td class="revenue-col">₱<?php echo number_format($row['total_amount'], 2); ?></td>
                            <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $row['status']; ?></span></td>
                            <td>
                                <?php if (!empty($row['pickup_schedule'])): ?>
                                    <span class="pickup-badge">
                                        <i class="fas fa-calendar-check"></i> <?php echo date('M d, h:i A', strtotime($row['pickup_schedule'])); ?>
                                    </span>
                                <?php elseif ($row['status'] == 'Paid'): ?>
                                    <span class="pickup-badge" style="background: #ffc107; color:#2c3136;">
                                        <i class="fas fa-clock"></i> Pending
                                    </span>
                                <?php else: ?>
                                    <span style="color:#aaa;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <form method="POST" action="actions/request_action.php" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo $row['request_id']; ?>">
                                        <select name="status" class="status-select">
                                            <option value="Pending" <?php echo $row['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="Unpaid" <?php echo $row['status'] == 'Unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                                            <option value="Paid" <?php echo $row['status'] == 'Paid' ? 'selected' : ''; ?>>Paid</option>
                                            <option value="Completed" <?php echo $row['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="Rejected" <?php echo $row['status'] == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                        </select>
                                        <button type="submit" name="update" class="update-btn"><i class="fas fa-save"></i> Update</button>
                                    </form>
                                    <?php if ($row['status'] == 'Paid'): ?>
                                        <button class="schedule-btn" onclick="openScheduleModal('<?php echo $row['request_id']; ?>', '<?php echo htmlspecialchars($row['or_number']); ?>')">
                                            <i class="fas fa-calendar-alt"></i> Set Schedule
                                        </button>
                                    <?php endif; ?>
                                    <button class="message-btn" onclick="openMessageModal('<?php echo $row['request_id']; ?>', '<?php echo htmlspecialchars($row['or_number']); ?>')">
                                        <i class="fas fa-envelope"></i> Message
                                    </button>
                                    <a href="actions/request_action.php?delete=<?php echo $row['request_id']; ?>" class="delete-btn" onclick="return confirmDelete('<?php echo htmlspecialchars($row['or_number']); ?>', '<?php echo $row['request_id']; ?>')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" class="no-data">
                                <i class="fas fa-inbox"></i>
                                <p>No requests found</p>
                                <small>Try adjusting your search or filter</small>
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

    <!-- Schedule Pickup Modal -->
    <div id="scheduleModal" class="modal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeScheduleModal()">&times;</button>
            <h3><i class="fas fa-calendar-alt"></i> Set Pickup Schedule</h3>
            <form method="POST" action="actions/set_pickup_schedule.php">
                <input type="hidden" name="request_id" id="schedule_request_id">
                <div class="form-group">
                    <label><i class="fas fa-calendar-day"></i> Pickup Date & Time:</label>
                    <input type="datetime-local" name="pickup_schedule" id="pickup_schedule" required style="width: 100%; padding: 10px; background: #3a4046; border: 1px solid #4a5056; border-radius: 8px; color: #fff;">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-sticky-note"></i> Additional Notes (Optional):</label>
                    <textarea name="pickup_notes" id="pickup_notes" placeholder="e.g., Bring OR number, Go to window 3, etc." style="width: 100%; padding: 10px; background: #3a4046; border: 1px solid #4a5056; border-radius: 8px; color: #fff; resize: vertical; min-height: 80px;"></textarea>
                </div>
                <div class="modal-buttons">
                    <button type="submit" name="set_schedule" class="send-btn"><i class="fas fa-save"></i> Set Schedule</button>
                    <button type="button" class="cancel-btn" onclick="closeScheduleModal()"><i class="fas fa-times"></i> Cancel</button>
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
            <div id="qr-message" class="qr-result" style="margin-bottom: 10px;"></div>
            <div id="cameraScanView">
                <div class="qr-scanner-container">
                    <div id="qr-reader" style="width: 100%; max-width: 500px; min-height: 300px;"></div>
                </div>
                <button type="button" id="stopCameraBtn" class="qr-action-btn">Stop Camera</button>
            </div>
            <div id="uploadView" style="display: none;">
                <div class="qr-upload-area">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Upload QR Code Image</p>
                    <input type="file" id="qrImageUpload" accept="image/*" class="qr-file-input">
                    <label for="qrImageUpload" class="qr-file-label">Choose Image</label>
                    <div id="uploadPreview" class="qr-preview"></div>
                </div>
                <button type="button" id="decodeImageBtn" class="qr-action-btn qr-decode-btn">Decode QR Code</button>
            </div>
            <div class="qr-switch-buttons">
                <button type="button" id="switchToCamera" class="switch-btn active">📸 Camera</button>
                <button type="button" id="switchToUpload" class="switch-btn">📁 Upload</button>
            </div>
        </div>
    </div>

    <script>
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

        // Schedule Modal
        const scheduleModal = document.getElementById('scheduleModal');
        
        function openScheduleModal(requestId, orNumber) {
            document.getElementById('schedule_request_id').value = requestId;
            scheduleModal.style.display = 'flex';
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            tomorrow.setHours(9, 0, 0);
            const minDateTime = tomorrow.toISOString().slice(0, 16);
            document.getElementById('pickup_schedule').min = minDateTime;
        }

        function closeScheduleModal() {
            scheduleModal.style.display = 'none';
        }

        // Confirm Delete
        function confirmDelete(orNumber, requestId) {
            Swal.fire({
                title: 'Delete Request?',
                text: `Are you sure you want to delete request ${orNumber}?`,
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
                    window.location.href = `actions/request_action.php?delete=${requestId}`;
                }
            });
            return false;
        }

        // QR Scanner Variables
        let html5QrCode = null;
        let isScanning = false;
        const qrModal = document.getElementById('qrModal');
        const scanCameraBtn = document.getElementById('scanCameraBtn');
        const qrMessage = document.getElementById('qr-message');

        function showQRMessage(message, isError = false) {
            if (qrMessage) {
                const color = isError ? '#e74c3c' : '#2ecc71';
                qrMessage.innerHTML = `<div style="background: ${color}; padding: 10px; border-radius: 5px; color: ${isError ? '#fff' : '#2c3136'};">${message}</div>`;
                setTimeout(() => {
                    if (qrMessage.innerHTML.includes(message)) qrMessage.innerHTML = '';
                }, 3000);
            }
        }

        function startCamera() {
            const readerDiv = document.getElementById('qr-reader');
            if (!readerDiv) {
                showQRMessage("Scanner element not found", true);
                return;
            }
            if (html5QrCode) html5QrCode.stop().then(() => startCameraInternal(readerDiv)).catch(() => startCameraInternal(readerDiv));
            else startCameraInternal(readerDiv);
        }

        function startCameraInternal(readerDiv) {
            readerDiv.innerHTML = '';
            if (typeof Html5Qrcode === 'undefined') {
                showQRMessage("QR library not loaded. Please refresh.", true);
                return;
            }
            showQRMessage("Requesting camera access...");
            html5QrCode = new Html5Qrcode("qr-reader");
            html5QrCode.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: { width: 250, height: 250 } },
                (decodedText) => {
                    showQRMessage(`QR Code detected: ${decodedText}`);
                    document.getElementById('searchInput').value = decodedText;
                    setTimeout(() => {
                        closeQRModal();
                        document.getElementById('searchForm').submit();
                    }, 1500);
                },
                (errorMessage) => {}
            ).then(() => {
                isScanning = true;
                showQRMessage("Camera is ready. Position QR code in frame.");
            }).catch(err => {
                let errorMsg = "Cannot access camera. ";
                if (err.message.includes("NotFoundError")) errorMsg += "No camera found on this device.";
                else if (err.message.includes("NotAllowedError")) errorMsg += "Please grant camera permission.";
                else errorMsg += err.message;
                showQRMessage(errorMsg, true);
            });
        }

        function stopCamera() {
            if (html5QrCode && isScanning) {
                html5QrCode.stop().then(() => { isScanning = false; }).catch(() => {});
            }
        }

        function closeQRModal() {
            qrModal.style.display = 'none';
            stopCamera();
            if (qrMessage) qrMessage.innerHTML = '';
            const fileInput = document.getElementById('qrImageUpload');
            const preview = document.getElementById('uploadPreview');
            if (fileInput) fileInput.value = '';
            if (preview) preview.innerHTML = '';
        }

        function openQRModal() {
            qrModal.style.display = 'flex';
            setTimeout(() => startCamera(), 500);
        }

        function switchToCamera() {
            document.getElementById('cameraScanView').style.display = 'block';
            document.getElementById('uploadView').style.display = 'none';
            document.getElementById('switchToCamera').classList.add('active');
            document.getElementById('switchToUpload').classList.remove('active');
            stopCamera();
            setTimeout(() => startCamera(), 300);
        }

        function switchToUpload() {
            document.getElementById('cameraScanView').style.display = 'none';
            document.getElementById('uploadView').style.display = 'block';
            document.getElementById('switchToCamera').classList.remove('active');
            document.getElementById('switchToUpload').classList.add('active');
            stopCamera();
        }

        function previewImage() {
            const fileInput = document.getElementById('qrImageUpload');
            const preview = document.getElementById('uploadPreview');
            if (fileInput.files && fileInput.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 150px; border-radius: 8px; margin-top: 10px;">`;
                };
                reader.readAsDataURL(fileInput.files[0]);
            }
        }

        function decodeQRImage() {
            const fileInput = document.getElementById('qrImageUpload');
            const file = fileInput.files[0];
            if (!file) {
                showQRMessage("Please select an image first", true);
                return;
            }
            showQRMessage("Decoding image...");
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
                        showQRMessage(`QR Code detected: ${code.data}`);
                        document.getElementById('searchInput').value = code.data;
                        setTimeout(() => { closeQRModal(); document.getElementById('searchForm').submit(); }, 1500);
                    } else {
                        showQRMessage("No QR Code found in this image", true);
                    }
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (scanCameraBtn) scanCameraBtn.onclick = openQRModal;
            const qrClose = document.querySelector('.qr-close');
            if (qrClose) qrClose.onclick = closeQRModal;
            const switchToCameraBtn = document.getElementById('switchToCamera');
            const switchToUploadBtn = document.getElementById('switchToUpload');
            const stopCameraBtn = document.getElementById('stopCameraBtn');
            const decodeImageBtn = document.getElementById('decodeImageBtn');
            const qrImageUpload = document.getElementById('qrImageUpload');
            if (switchToCameraBtn) switchToCameraBtn.onclick = switchToCamera;
            if (switchToUploadBtn) switchToUploadBtn.onclick = switchToUpload;
            if (stopCameraBtn) stopCameraBtn.onclick = closeQRModal;
            if (decodeImageBtn) decodeImageBtn.onclick = decodeQRImage;
            if (qrImageUpload) qrImageUpload.onchange = previewImage;
        });

        window.onclick = function(event) {
            if (event.target === messageModal) closeMessageModal();
            if (event.target === scheduleModal) closeScheduleModal();
            if (event.target === qrModal) closeQRModal();
        }
    </script>
</body>
</html>