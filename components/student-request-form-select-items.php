<?php
session_start();

$session_id = session_id() . '_' . time();
if (!isset($_SESSION['temp_session_id'])) {
    $_SESSION['temp_session_id'] = $session_id;
}

if (!isset($_SESSION['validated']) || $_SESSION['validated'] !== true) {
    header("Location: /404.php");
    exit();
}

include('../config/db.php');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get student number from session
$studentNo = $_SESSION['student_no'] ?? $_SESSION['student_number'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Items - Student Self-Service Request and Supply Management System for PLMUN</title>
    <link rel="stylesheet" href="/assets/styles/allstyles.css">
    <link rel="stylesheet" href="/assets/styles/selectitems.css">
    <link rel="stylesheet" href="/assets/styles/navbar.css">
    
    <!-- For different sizes -->
    <link rel="icon" type="image/x-icon" sizes="16x16" href="/assets/ico/logo16ico.ico">
    <link rel="icon" type="image/x-icon" sizes="32x32" href="/assets/ico/logo32ico.ico">
    <link rel="icon" type="image/x-icon" sizes="96x96" href="/assets/ico/logo96ico.ico">
    <link rel="icon" type="image/x-icon" sizes="192x192" href="/assets/ico/logo192ico.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <nav class="navbar">
        <a href="/landingpage.html"><img src="/assets/img/schl_logo-1.png" alt="Logo"></a>
    </nav>
    
    <div class="selectitems-container">
        <div class="title">
            <h3>Self-Service Request | Request Items</h3>
        </div>
        <div class="subtitle">
            <p>Select the items you would like to request.</p>
        </div>
        
        <div id="selected-items"></div>
        
        <!-- School Uniforms Section -->
        <div class="section-title">
            <i class="fas fa-tshirt"></i> School Uniforms
        </div>
        <div class="item-grid">
            <?php 
            $uniformResult = $conn->query("SELECT * FROM itemtb WHERE stock_quantity > 0 AND (name LIKE '%Uniform%' OR name LIKE '%uniform%' OR category LIKE '%Uniform%')");
            if ($uniformResult && $uniformResult->num_rows > 0) {
                while ($row = $uniformResult->fetch_assoc()): 
            ?>
            <button type="button" class="item-btn uniform-item"
                data-id="<?= $row['itemtbID']; ?>"
                data-name="<?= htmlspecialchars($row['name']); ?>"
                data-price="<?= $row['price']; ?>"
                data-description="<?= htmlspecialchars($row['description']); ?>"
                data-isuniform="true">
                <div class="item-header">
                    <div class="text">
                        <h4><?= htmlspecialchars($row['name']); ?></h4>
                        <p>₱<?= number_format($row['price'], 2); ?></p>
                    </div>
                    <i class="fa-solid fa-square-plus"></i>
                </div>
                <div class="size-display">
                    <small>📏 Select size (XS-XXXL)</small>
                </div>
            </button>
            <?php 
                endwhile;
            } else {
                echo '<div class="no-items">No uniform items available.</div>';
            }
            ?>
        </div>
        
        <!-- Other Items Section -->
        <div class="section-title">
            <i class="fas fa-box"></i> Other Items
        </div>
        <div class="item-grid">
            <?php 
            $otherResult = $conn->query("SELECT * FROM itemtb WHERE stock_quantity > 0 AND (name NOT LIKE '%Uniform%' AND name NOT LIKE '%uniform%' AND category NOT LIKE '%Uniform%')");
            if ($otherResult && $otherResult->num_rows > 0) {
                while ($row = $otherResult->fetch_assoc()): 
            ?>
            <button type="button" class="item-btn"
                data-id="<?= $row['itemtbID']; ?>"
                data-name="<?= htmlspecialchars($row['name']); ?>"
                data-price="<?= $row['price']; ?>"
                data-description="<?= htmlspecialchars($row['description']); ?>"
                data-isuniform="false">
                <div class="item-header">
                    <div class="text">
                        <h4><?= htmlspecialchars($row['name']); ?></h4>
                        <p>₱<?= number_format($row['price'], 2); ?></p>
                    </div>
                    <i class="fa-solid fa-square-plus"></i>
                </div>
            </button>
            <?php 
                endwhile;
            } else {
                echo '<div class="no-items">No other items available.</div>';
            }
            ?>
        </div>
        
        <label id="temp-items-list-label">Requested Items:</label>
        <div class="cart-wrapper">
            <div id="temp-items-list"></div>
        </div>
        <button id="request-all-btn">Request All Item(s)</button>
    </div>
    
    <script>
    const uniformSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];
    
    document.getElementById("request-all-btn").onclick = function() {
        const container = document.getElementById("temp-items-list");
        if (container.innerHTML.includes("No items requested") || container.children.length === 0 || container.innerHTML.trim() === "") {
            Swal.fire({ 
                title: "No Items Selected", 
                text: "Please select at least one item.", 
                icon: "warning",
                background: "#2c3136",
                color: "#fff"
            });
            return;
        }
        window.location.href = "student-request-form-summary.php";
    };
    
    function loadTempItems() {
        fetch("/load_temp_items.php")
            .then(res => res.text())
            .then(data => {
                document.getElementById("temp-items-list").innerHTML = data;
                attachDeleteEvents();
            })
            .catch(error => {
                console.error("Error loading temp items:", error);
            });
    }
    
    function attachDeleteEvents() {
        document.querySelectorAll(".delete-btn").forEach(btn => {
            btn.onclick = function(e) {
                e.preventDefault();
                const id = this.getAttribute('data-id');
                if (!id) return;
                
                fetch("/delete_temp_item.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `id=${id}`
                })
                .then(res => res.text())
                .then(data => {
                    if (data.trim() === "success") {
                        loadTempItems();
                    } else {
                        Swal.fire({ 
                            title: "Error", 
                            text: "Failed to delete item", 
                            icon: "error",
                            background: "#2c3136",
                            color: "#fff"
                        });
                    }
                });
            };
        });
    }
    
    function showItemModal(id, name, description, isUniform, price) {
        const existingOverlay = document.querySelector('.overlay');
        const existingModal = document.querySelector('.selected-item');
        if (existingOverlay) existingOverlay.remove();
        if (existingModal) existingModal.remove();
        
        const overlay = document.createElement('div');
        overlay.className = 'overlay';
        overlay.onclick = () => {
            overlay.remove();
            const modal = document.querySelector('.selected-item');
            if (modal) modal.remove();
        };
        document.body.appendChild(overlay);
        
        const modal = document.createElement('div');
        modal.className = 'selected-item';
        
        let sizeHTML = '';
        if (isUniform === 'true') {
            sizeHTML = `
                <div class="uniform-selection">
                    <label>Select Size:</label>
                    <select class="uniform-size-select" id="size-select">
                        <option value="">-- Choose size --</option>
                        ${uniformSizes.map(s => `<option value="${s}">${s}</option>`).join('')}
                    </select>
                </div>
            `;
        }
        
        modal.innerHTML = `
            <div class="dsply">
                <div class="top">
                    <span class="item-name"><strong>${name}</strong></span>
                    <div class="qty-control">
                        <i class="fa-solid fa-minus minus"></i>
                        <span class="qty">1</span>
                        <i class="fa-solid fa-plus plus"></i>
                    </div>
                    <button class="ok-btn">Add to Request</button>
                </div>
                <div class="bottom">
                    <h4 class="grn-font">💰 Price: ₱${parseFloat(price).toFixed(2)}</h4>
                    <p class="item-description">${description || 'No description available'}</p>
                    ${sizeHTML}
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        let qty = 1;
        const qtySpan = modal.querySelector('.qty');
        
        modal.querySelector('.plus').onclick = () => {
            qty++;
            qtySpan.textContent = qty;
        };
        
        modal.querySelector('.minus').onclick = () => {
            if (qty > 1) {
                qty--;
                qtySpan.textContent = qty;
            }
        };
        
        modal.querySelector('.ok-btn').onclick = () => {
            const selectedSize = isUniform === 'true' ? document.getElementById('size-select')?.value : null;
            
            if (isUniform === 'true' && (!selectedSize || selectedSize === '')) {
                Swal.fire({ 
                    title: "Size Required", 
                    text: "Please select a size for the uniform.", 
                    icon: "warning",
                    background: "#2c3136",
                    color: "#fff"
                });
                return;
            }
            
            let body = `itemtbID=${id}&qty=${qty}`;
            if (selectedSize) body += `&size=${selectedSize}`;
            
            fetch("/add_temp_item.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: body
            })
            .then(res => res.text())
            .then(data => {
                if (data.trim() === "success") {
                    Swal.fire({ 
                        title: "Success!", 
                        text: `${name}${selectedSize ? ' (Size: ' + selectedSize + ')' : ''} added to your request.`, 
                        icon: "success", 
                        timer: 1500, 
                        showConfirmButton: false,
                        background: "#2c3136",
                        color: "#fff"
                    });
                    loadTempItems();
                    overlay.remove();
                    modal.remove();
                } else {
                    Swal.fire({ 
                        title: "Error", 
                        text: "Failed to add item: " + data, 
                        icon: "error",
                        background: "#2c3136",
                        color: "#fff"
                    });
                }
            })
            .catch(error => {
                console.error("Fetch error:", error);
                Swal.fire({ 
                    title: "Error", 
                    text: "Network error occurred", 
                    icon: "error",
                    background: "#2c3136",
                    color: "#fff"
                });
            });
        };
    }
    
    document.addEventListener("DOMContentLoaded", function() {
        loadTempItems();
        
        document.querySelectorAll(".item-btn").forEach(btn => {
            btn.addEventListener("click", function(e) {
                e.preventDefault();
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const description = this.getAttribute('data-description') || '';
                const isUniform = this.getAttribute('data-isuniform');
                const price = this.getAttribute('data-price');
                
                if (!id) {
                    Swal.fire({ 
                        title: "Error", 
                        text: "Invalid item ID", 
                        icon: "error",
                        background: "#2c3136",
                        color: "#fff"
                    });
                    return;
                }
                
                showItemModal(id, name, description, isUniform, price);
            });
        });
    });
    </script>
</body>
</html>