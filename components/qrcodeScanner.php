<!-- QR Code Scanner Modal -->
<div id="qrModal" class="qr-modal">
    <div class="qr-modal-content">
        <h3 style="margin-top: 0;">Scan QR Code</h3>
        
        <!-- Camera Scan View -->
        <div id="cameraScanView">
            <div id="qr-reader" style="width: 100%; max-width: 300px; margin: 0 auto;"></div>
            <div id="qr-result"></div>
            <button type="button" id="stopCameraBtn" class="qr-action-btn">Close QR Scan</button>
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