<!-- QR Scanner Modal -->
<div id="qrModal" class="qr-modal">
    <div class="qr-modal-content">
        <div class="qr-modal-header">
            <h3><i class="fas fa-qrcode"></i> Scan QR Code</h3>
            <button class="qr-close" onclick="closeQRModal()">&times;</button>
        </div>
        
        <div class="qr-modal-body">
            <div id="qr-message" class="qr-result" style="margin-bottom: 10px;"></div>
            
            <!-- Camera Scan View -->
            <div id="cameraScanView" class="qr-scan-view">
                <div class="qr-scanner-container">
                    <div id="qr-reader" style="width: 100%; max-width: 500px; min-height: 300px;"></div>
                </div>
                <button type="button" id="stopCameraBtn" class="qr-action-btn" style="margin-top: 10px;">
                    <i class="fas fa-stop"></i> Stop Camera
                </button>
            </div>
            
            <!-- Upload View -->
            <div id="uploadView" class="qr-upload-view" style="display: none;">
                <div class="qr-upload-area">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Upload QR Code Image</p>
                    <input type="file" id="qrImageUpload" accept="image/*" class="qr-file-input">
                    <label for="qrImageUpload" class="qr-file-label">
                        <i class="fas fa-folder-open"></i> Choose Image
                    </label>
                    <div id="uploadPreview" class="qr-preview"></div>
                </div>
                <button type="button" id="decodeImageBtn" class="qr-action-btn qr-decode-btn" style="margin-top: 10px;">
                    <i class="fas fa-qrcode"></i> Decode QR Code
                </button>
            </div>
        </div>
        
        <div class="qr-switch-buttons">
            <button type="button" id="switchToCamera" class="switch-btn active">
                <i class="fas fa-camera"></i> Use Camera
            </button>
            <button type="button" id="switchToUpload" class="switch-btn">
                <i class="fas fa-image"></i> Upload Image
            </button>
        </div>
    </div>
</div>

<!-- Load QR Libraries -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="https://unpkg.com/jsqr/dist/jsQR.js"></script>

<script>
    // QR Scanner Variables
    let html5QrCode = null;
    let isScanning = false;
    
    // Get DOM elements
    const qrModal = document.getElementById('qrModal');
    const scanCameraBtn = document.getElementById('scanCameraBtn');
    const qrMessage = document.getElementById('qr-message');
    
    // Function to show message
    function showQRMessage(message, isError = false) {
        if (qrMessage) {
            const color = isError ? '#e74c3c' : '#2ecc71';
            qrMessage.innerHTML = `<div style="background: ${color}; padding: 10px; border-radius: 5px; color: ${isError ? '#fff' : '#2c3136'};">
                ${isError ? '❌' : '✅'} ${message}
            </div>`;
            setTimeout(() => {
                if (qrMessage.innerHTML.includes(message)) {
                    qrMessage.innerHTML = '';
                }
            }, 3000);
        }
    }
    
    // Start camera function
    async function startCamera() {
        const qrReaderDiv = document.getElementById('qr-reader');
        
        if (!qrReaderDiv) {
            console.error("qr-reader div not found");
            showQRMessage("Scanner element not found", true);
            return;
        }
        
        // Clear previous scanner
        qrReaderDiv.innerHTML = '';
        
        // Check if library is loaded
        if (typeof Html5Qrcode === 'undefined') {
            console.error("Html5Qrcode library not loaded");
            showQRMessage("QR library not loaded. Please refresh the page.", true);
            return;
        }
        
        try {
            showQRMessage("Requesting camera access...");
            
            html5QrCode = new Html5Qrcode("qr-reader");
            
            const config = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0
            };
            
            await html5QrCode.start(
                { facingMode: "environment" },
                config,
                (decodedText, decodedResult) => {
                    console.log("QR Code detected:", decodedText);
                    showQRMessage(`QR Code detected: ${decodedText}`);
                    
                    // Set search input and submit form
                    const searchInput = document.getElementById('searchInput');
                    if (searchInput) {
                        searchInput.value = decodedText;
                        setTimeout(() => {
                            closeQRModal();
                            document.getElementById('searchForm').submit();
                        }, 1500);
                    }
                },
                (errorMessage) => {
                    // Silently handle scanning errors
                    // console.log("Scanning...");
                }
            );
            
            isScanning = true;
            console.log("Camera started successfully");
            showQRMessage("Camera is ready. Position QR code in frame.");
            
        } catch (err) {
            console.error("Camera error:", err);
            let errorMsg = "Cannot access camera. ";
            if (err.message.includes("NotFoundError")) {
                errorMsg += "No camera found on this device.";
            } else if (err.message.includes("NotAllowedError")) {
                errorMsg += "Please grant camera permission.";
            } else if (err.message.includes("NotReadableError")) {
                errorMsg += "Camera is already in use by another application.";
            } else {
                errorMsg += err.message;
            }
            showQRMessage(errorMsg, true);
        }
    }
    
    // Stop camera function
    async function stopCamera() {
        if (html5QrCode && isScanning) {
            try {
                await html5QrCode.stop();
                isScanning = false;
                console.log("Camera stopped");
            } catch (err) {
                console.error("Error stopping camera:", err);
            }
        }
        html5QrCode = null;
    }
    
    // Close modal function
    function closeQRModal() {
        qrModal.style.display = 'none';
        stopCamera();
        if (qrMessage) qrMessage.innerHTML = '';
    }
    
    // Open modal function
    function openQRModal() {
        qrModal.style.display = 'flex';
        setTimeout(() => {
            startCamera();
        }, 500);
    }
    
    // Switch to camera
    function switchToCamera() {
        const cameraView = document.getElementById('cameraScanView');
        const uploadView = document.getElementById('uploadView');
        const switchCamera = document.getElementById('switchToCamera');
        const switchUpload = document.getElementById('switchToUpload');
        
        if (cameraView) cameraView.style.display = 'block';
        if (uploadView) uploadView.style.display = 'none';
        if (switchCamera) switchCamera.classList.add('active');
        if (switchUpload) switchUpload.classList.remove('active');
        
        stopCamera();
        setTimeout(() => {
            startCamera();
        }, 300);
    }
    
    // Switch to upload
    function switchToUpload() {
        const cameraView = document.getElementById('cameraScanView');
        const uploadView = document.getElementById('uploadView');
        const switchCamera = document.getElementById('switchToCamera');
        const switchUpload = document.getElementById('switchToUpload');
        
        if (cameraView) cameraView.style.display = 'none';
        if (uploadView) uploadView.style.display = 'block';
        if (switchCamera) switchCamera.classList.remove('active');
        if (switchUpload) switchUpload.classList.add('active');
        
        stopCamera();
    }
    
    // Preview uploaded image
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
    
    // Decode QR from image
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
                    const searchInput = document.getElementById('searchInput');
                    if (searchInput) {
                        searchInput.value = code.data;
                        setTimeout(() => {
                            closeQRModal();
                            document.getElementById('searchForm').submit();
                        }, 1500);
                    }
                } else {
                    showQRMessage("No QR Code found in this image", true);
                }
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
    
    // Initialize event listeners when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        console.log("DOM loaded, initializing QR scanner...");
        console.log("Html5Qrcode available:", typeof Html5Qrcode !== 'undefined');
        console.log("jsQR available:", typeof jsQR !== 'undefined');
        
        // QR Scan Button
        if (scanCameraBtn) {
            scanCameraBtn.onclick = openQRModal;
            console.log("Scan button initialized");
        } else {
            console.error("scanCameraBtn not found");
        }
        
        // Modal close button
        const qrClose = document.querySelector('.qr-close');
        if (qrClose) {
            qrClose.onclick = closeQRModal;
        }
        
        // Switch buttons
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
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const messageModal = document.getElementById('messageModal');
            if (event.target === messageModal) {
                if (typeof closeMessageModal === 'function') closeMessageModal();
            }
            if (event.target === qrModal) {
                closeQRModal();
            }
        }
    });
    
    // Clean up on page unload
    window.addEventListener('beforeunload', function() {
        if (html5QrCode && isScanning) {
            html5QrCode.stop();
        }
    });
</script>