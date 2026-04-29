<?php
    session_start();
    include('../../config/db.php');
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Student Self-Service Request and Supply Management System for PLMUN</title>
    <link rel="stylesheet" href="/assets/styles/allstyles.css">
    <link rel="stylesheet" href="/assets/styles/navbar.css">
    <link rel="stylesheet" href="/assets/styles/adminstyles/adminreq.css">
    <link rel="icon" type="image/x-icon" href="/assets/ico/logo16ico.ico">
    <link rel="icon" type="image/x-icon" href="/assets/ico/logo32ico.ico">
    <link rel="icon" type="image/x-icon" href="/assets/ico/logo96ico.ico">
    <link rel="icon" type="image/x-icon" href="/assets/ico/logo192ico.ico">
    
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
    
    <div class="adminreq-megacontainer">
        <h2>Requests</h2>
        <p>Manage and update student requests</p>
        
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

        <!-- Include QR Scanner Modal -->
        <?php include('../../components/qrcodeScanner.php'); ?>

        <div class="adminreq-table">
            <table border="1">
                <tr>
                    <th class="grn-font">Official Receipt</th>
                    <th>Status</th>
                    <th>Student Number</th>
                    <th>Full Name</th>
                    <th>Course</th>
                    <th>Item Name(s)</th>
                    <th>Category</th>
                    <th>Total</th>
                    <th>Update Status</th>
                    <th>Message</th>
                </tr>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['or_number'] ?></td>
                    <td><?= $row['status'] ?></td>
                    <td><?= $row['student_no'] ?></td>
                    <td><?= $row['fullname'] ?></td>
                    <td><?= $row['course'] ?></td>
                    <td><?= $row['item_names'] ?></td>
                    <td><?= $row['categories'] ?></td>
                    <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                    <td>
                        <form method="POST" action="actions/request_action.php">
                            <input type="hidden" name="id" value="<?= $row['request_id'] ?>">
                            <select name="status">
                                <option>Pending</option>
                                <option>Unpaid</option>
                                <option>Paid</option>
                                <option>Completed</option>
                                <option>Rejected</option>
                            </select>
                            <button name="update">Update</button>
                        </form>
                        <a href="actions/request_action.php?delete=<?= $row['request_id'] ?>">Delete</a>
                    </td>
                    <td>
                        <button class="openModalBtn"
                                data-id="<?= $row['request_id'] ?>"
                                data-or="<?= $row['or_number'] ?>">
                            Send Message
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
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

        /* QR SCANNER FUNCTIONS */
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
                if (qrResult) {
                    qrResult.innerHTML = '<div style="background: #f44336; padding: 10px; border-radius: 5px;">❌ Cannot access camera. Please check permissions or use upload option.</div>';
                }
            });
        }

        // Event Listeners with error checking
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

            const uploadQRBtn = document.getElementById('uploadQRBtn');
            if (uploadQRBtn) {
                uploadQRBtn.onclick = function(e) {
                    e.preventDefault();
                    qrModal.style.display = 'flex';
                    if (cameraView) cameraView.style.display = 'none';
                    if (uploadView) uploadView.style.display = 'block';
                    if (html5QrCode) html5QrCode.stop();
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
                            text: 'Please select an image first',
                            confirmButtonColor: '#3085d6',
                            confirmButtonText: 'OK'
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
                            } else if (qrResult) {
                                qrResult.innerHTML = '<div style="background: #f44336; padding: 10px; border-radius: 5px;">❌ No QR Code found in this image.</div>';
                            }
                        };
                        img.src = e.target.result;
                        const preview = document.getElementById('uploadPreview');
                        if (preview) preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 100px;">`;
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
                            if (preview) preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 100px;">`;
                        };
                        reader.readAsDataURL(file);
                    }
                };
            }

            window.onclick = function(event) {
                if (event.target == qrModal) {
                    qrModal.style.display = 'none';
                    if (html5QrCode) html5QrCode.stop();
                }
                if (event.target == messageModal) {
                    messageModal.style.display = "none";
                    if (messageBox) messageBox.value = "";
                }
            };
        }
    </script>
</body>
</html>