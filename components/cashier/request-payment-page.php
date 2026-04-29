<?php
session_start();
include('../../config/db.php');

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
        i.name AS item_name,
        ri.quantity,
        ri.subtotal
    FROM requesttb r 
    JOIN request_items ri ON r.request_id = ri.request_id
    JOIN itemtb i ON ri.itemtbID = i.itemtbID
    WHERE r.status = 'Pending'
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
            "items" => []
        ];
    }

    $requests[$id]["items"][] = [
        "name" => $row['item_name'],
        "qty" => $row['quantity'],
        "subtotal" => $row['subtotal']
    ];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Treasury Dashboard - Student Self-Service Request and Supply Management System for PLMUN</title>
    <link rel="stylesheet" href="/assets/styles/cashier.css">
    <link rel="stylesheet" href="/assets/styles/allstyles.css">
    <link rel="stylesheet" href="/assets/styles/navbar.css">
    <link rel="icon" type="image/x-icon" href="/assets/ico/logo16ico.ico" >
    <link rel="icon" type="image/x-icon" href="/assets/ico/logo32ico.ico" >
    <link rel="icon" type="image/x-icon" href="/assets/ico/logo96ico.ico" >
    <link rel="icon" type="image/x-icon" href="/assets/ico/logo192ico.ico" >
    
    <!-- QR Libraries -->
    <script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>
    <script src="https://unpkg.com/jsqr/dist/jsQR.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* MODAL for message */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            justify-content: center;
            align-items: center;
            z-index: 999;
        }

        .modal-content {
            background: #2f2f2f;
            padding: 20px;
            border-radius: 12px;
            width: 420px;
            color: white;
        }

        textarea {
            width: 95%;
            height: 120px;
            border-radius: 8px;
            padding: 10px;
            margin: 10px 0;
        }

        .modal-buttons {
            display: flex;
            justify-content: space-between;
        }
        
        .send-btn {
            background: green;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 8px;
        }
        
        .send-btn:hover {
            transform: translateY(-5px);
            transition: 0.3s;
            cursor: pointer;
        }
        
        .cancel-btn {
            background: red;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 8px;
        }
        
        .cancel-btn:hover {
            transform: translateY(-5px);
            transition: 0.3s;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <a href="/landingpage.html"><img src="/assets/img/schl_logo-1.png" alt="Logo"></a>
    </nav>
    
    <?php include('../left-navbar.php'); ?>
    
    <div class="cashier-pending-container">
        <h2 id="reg-h2-style">Pay Requests - Treasury</h2>
        <p>This page displays all items, categorized by "Academic and Supply Items". Mark Requests as Paid.</p>
        <hr class="border-top">
        
        <!-- Search Form with QR -->
        <form method="GET" class="search-form" id="searchForm">
            <input 
                type="text" 
                name="search" 
                id="searchInput"
                placeholder="Search OR, Name, Student No, Status..."
                value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>"
            >
            <button type="submit" class="searchBtn">Search</button>
            
            <!-- QR Dropdown Button -->
            <div class="qr-dropdown">
                <button type="button" id="scanCameraBtn" class="qr-scan-btn">
                    Scan QR Code
                </button>
            </div>
        </form>
        
        <!-- QR Scanner Modal -->
        <div id="qrModal" class="qr-modal">
            <div class="qr-modal-content">
                <span class="qr-close">&times;</span>
                <h3 style="margin-top: 0;">Scan QR Code</h3>
                
                <!-- Camera Scan View -->
                <div id="cameraScanView">
                    <div id="qr-reader" style="width: 100%; max-width: 500px; margin: 0 auto;"></div>
                    <div id="qr-result"></div>
                    <button type="button" id="stopCameraBtn" class="qr-action-btn">Stop Camera</button>
                </div>
                
                <!-- Upload View -->
                <div id="uploadView" style="display: none;">
                    <input type="file" id="qrImageUpload" accept="image/*">
                    <div id="uploadPreview"></div>
                    <button type="button" id="decodeImageBtn" class="qr-action-btn">Decode QR Code</button>
                </div>
                
                <div class="qr-switch-buttons">
                    <button type="button" id="switchToCamera" class="switch-btn">📸 Use Camera</button>
                    <button type="button" id="switchToUpload" class="switch-btn">📁 Upload Image</button>
                </div>
            </div>
        </div>
        
        <div class="cashier-pending-table">
            <table border="1" width="100%">
                <thead>
                    <tr>
                        <th class="grn-font">Official Receipt</th>
                        <th>Student Number</th>
                        <th>Name</th>
                        <th>Course</th>
                        <th>Status</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Update Status</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($requests) > 0): ?>
                        <?php foreach($requests as $row): ?>
                        <tr class="HOVER">
                            <td class="a"><?= htmlspecialchars($row['or_number']); ?></td>
                            <td><?= htmlspecialchars($row['student_no']); ?></td>
                            <td><?= htmlspecialchars($row['fullname']); ?></td>
                            <td><?= htmlspecialchars($row['course']); ?></td>
                            <td><?= htmlspecialchars($row['status']); ?></td>
                            <td>
                                <div class="items-box a">
                                    <?php foreach ($row['items'] as $item): ?>
                                        <?= htmlspecialchars($item['name']) ?> (x<?= $item['qty']; ?>) - ₱<?= number_format($item['subtotal'],2); ?><br>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td><b>₱<?= number_format($row['total_amount'],2); ?></b></td>
                            <td>
                                <div class="inline">
                                    <form method="POST" action="actions/req-action.php">
                                        <input type="hidden" name="id" value="<?= $row['request_id'] ?>">
                                        <input type="hidden" name="status" value="Paid">
                                        <button class="btn-default-style acpt" type="submit" name="update">
                                            Mark as Paid
                                        </button>
                                    </form>
                                </div>
                            </td>
                            <td>
                                <button class="openModalBtn"
                                        data-id="<?= $row['request_id'] ?>"
                                        data-or="<?= $row['or_number'] ?>">
                                    Send Message
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">
                                No pending requests found
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
            <h3>SEND MESSAGE TO <span id="orText"></span></h3>
            <form method="POST" action="actions/send_message.php">
                <input type="hidden" name="request_id" id="request_id">
                <textarea name="message" id="messageBox" placeholder="Enter your message..." required></textarea>
                <div class="modal-buttons">
                    <button type="submit" class="send-btn" name="send">Send</button>
                    <button type="button" class="cancel-btn" id="cancelBtn">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        // Message Modal functions
        const messageModal = document.getElementById("messageModal");
        const orText = document.getElementById("orText");
        const requestInput = document.getElementById("request_id");
        const messageBox = document.getElementById("messageBox");

        /* OPEN MODAL */
        document.querySelectorAll(".openModalBtn").forEach(btn => {
            btn.addEventListener("click", function() {
                requestInput.value = this.dataset.id;
                orText.innerText = this.dataset.or;
                messageModal.style.display = "flex";
            });
        });

        /* CANCEL */
        document.getElementById("cancelBtn").onclick = () => {
            messageModal.style.display = "none";
            messageBox.value = "";
        };
        // QR SCANNER FUNCTIONS
        let html5QrCode;
        
        // Get DOM elements
        const qrModal = document.getElementById('qrModal');
        const cameraView = document.getElementById('cameraScanView');
        const uploadView = document.getElementById('uploadView');
        const qrResult = document.getElementById('qr-result');
        
        // Function to handle QR result
        function handleQRResult(decodedText) {
            console.log("QR Scanned:", decodedText);
            
            if (qrResult) {
                qrResult.innerHTML = `<div style="background: #4CAF50; padding: 10px; border-radius: 5px;">
                                        ✅ QR Code Detected: ${decodedText}<br>
                                        Searching...
                                       </div>`;
            }
            
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.value = decodedText;
                
                setTimeout(() => {
                    qrModal.style.display = 'none';
                    document.getElementById('searchForm').submit();
                }, 1000);
            }
            
            if (html5QrCode) {
                html5QrCode.stop();
            }
        }
        
        // Start camera function
        function startCamera() {
            if (html5QrCode) {
                html5QrCode.stop();
            }
            
            const readerDiv = document.getElementById('qr-reader');
            if (!readerDiv) {
                console.error("qr-reader div not found");
                return;
            }
            
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
                Swal.fire({
                    icon: 'error',
                    title: 'Camera Error',
                    text: 'Cannot access camera. Please check permissions or use upload option.'
                });
                if (qrResult) {
                    qrResult.innerHTML = '<div style="background: #f44336; padding: 10px; border-radius: 5px;">❌ Cannot access camera.</div>';
                }
            });
        }
        
        // Event Listeners
        if (qrModal) {
            const scanCameraBtn = document.getElementById('scanCameraBtn');
            if (scanCameraBtn) {
                scanCameraBtn.onclick = function(e) {
                    e.preventDefault();
                    qrModal.style.display = 'flex';
                    if (cameraView) cameraView.style.display = 'block';
                    if (uploadView) uploadView.style.display = 'none';
                    startCamera();
                };
            }
            
            const switchToCamera = document.getElementById('switchToCamera');
            if (switchToCamera) {
                switchToCamera.onclick = function() {
                    if (cameraView) cameraView.style.display = 'block';
                    if (uploadView) uploadView.style.display = 'none';
                    startCamera();
                };
            }
            
            const switchToUpload = document.getElementById('switchToUpload');
            if (switchToUpload) {
                switchToUpload.onclick = function() {
                    if (cameraView) cameraView.style.display = 'none';
                    if (uploadView) uploadView.style.display = 'block';
                    if (html5QrCode) html5QrCode.stop();
                };
            }
            
            const qrClose = document.querySelector('.qr-close');
            if (qrClose) {
                qrClose.onclick = function() {
                    qrModal.style.display = 'none';
                    if (html5QrCode) html5QrCode.stop();
                };
            }
            
            const stopCameraBtn = document.getElementById('stopCameraBtn');
            if (stopCameraBtn) {
                stopCameraBtn.onclick = function() {
                    if (html5QrCode) html5QrCode.stop();
                    qrModal.style.display = 'none';
                };
            }
            
            const decodeImageBtn = document.getElementById('decodeImageBtn');
            if (decodeImageBtn) {
                decodeImageBtn.onclick = function() {
                    const fileInput = document.getElementById('qrImageUpload');
                    const file = fileInput.files[0];
                    
                    if (!file) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'No Image Selected',
                            text: 'Please select an image first'
                        });
                        return;
                    }
                    
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
                                handleQRResult(code.data);
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'No QR Code Found',
                                    text: 'No QR Code found in this image. Please try another.'
                                });
                            }
                        };
                        img.src = e.target.result;
                        const preview = document.getElementById('uploadPreview');
                        if (preview) preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 200px;">`;
                    };
                    reader.readAsDataURL(file);
                };
            }
            
            const qrImageUpload = document.getElementById('qrImageUpload');
            if (qrImageUpload) {
                qrImageUpload.onchange = function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const preview = document.getElementById('uploadPreview');
                            if (preview) preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 200px;">`;
                        };
                        reader.readAsDataURL(file);
                    }
                };
            }
            
            // Close modal when clicking outside
            window.onclick = function(event) {
                if (event.target == qrModal) {
                    qrModal.style.display = 'none';
                    if (html5QrCode) html5QrCode.stop();
                }
            };
        }
    </script>
</body>
</html>