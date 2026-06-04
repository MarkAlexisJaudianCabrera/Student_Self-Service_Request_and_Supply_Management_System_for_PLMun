<?php
    session_start();
    include('../config/db.php'); 

    if (
        !isset($_SESSION['validated']) || 
        $_SESSION['validated'] !== true
    ) {
        header("Location: /404.php");
        exit();
    }

    $fullname = $_SESSION['fullname'] ?? 'N/A';
    $course = $_SESSION['course'] ?? 'N/A';
    $student_no = $_SESSION['student_no'] ?? $_SESSION['student_number'] ?? 'N/A';
    $email = $_SESSION['email'] ?? 'N/A'; 

    // Use the same session_id that was used for cart items
    $session_id = $_SESSION['temp_session_id'] ?? session_id() . '_' . time();
    
    // If no temp_session_id exists, create one
    if (!isset($_SESSION['temp_session_id'])) {
        $_SESSION['temp_session_id'] = $session_id;
    }

    // Get temp items with size information
    $stmt = $conn->prepare("
        SELECT t.quantity, t.size, i.name, i.price, i.itemtbID
        FROM tempreqitemtb t
        JOIN itemtb i ON t.itemtbID = i.itemtbID
        WHERE t.session_id = ?
    ");
    $stmt->bind_param("s", $session_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $total = 0;
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $subtotal = $row['price'] * $row['quantity'];
        $total += $subtotal;
        $items[] = $row;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Summary & Submit - Student Self-Service Request and Supply Management System for PLMUN</title>
    <link rel="stylesheet" href="/assets/styles/allstyles.css">
    <link rel="stylesheet" href="/assets/styles/navbar.css">
    <link rel="stylesheet" href="/assets/styles/summary.css">
    <link rel="icon" href="/assets/ico/logo16ico.ico">
    <link rel="icon" href="/assets/ico/logo32ico.ico">
    <link rel="icon" href="/assets/ico/logo96ico.ico">
    <link rel="icon" href="/assets/ico/logo192ico.ico">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .summary-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background: #2c3136;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            color: #fff;
        }
        
        .summary-container h2 {
            text-align: center;
            color: #2ecc71;
            margin-bottom: 30px;
        }
        
        .student-info {
            background: #3a4046;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .student-info p {
            margin: 8px 0;
        }
        
        .items-list {
            background: #3a4046;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #4a5056;
        }
        
        .item-row:last-child {
            border-bottom: none;
        }
        
        .item-name {
            flex: 2;
            font-weight: 500;
        }
        
        .item-size {
            background: #2ecc71;
            color: #2c3136;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .item-qty {
            flex: 1;
            text-align: center;
        }
        
        .item-subtotal {
            flex: 1;
            text-align: right;
            color: #2ecc71;
            font-weight: bold;
        }
        
        .total-box {
            background: #2ecc71;
            color: #2c3136;
            padding: 15px 20px;
            border-radius: 10px;
            text-align: right;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }
        
        .cancel-btn, .submit-btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .cancel-btn {
            background: #dc3545;
            color: white;
        }
        
        .cancel-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        .submit-btn {
            background: #2ecc71;
            color: #2c3136;
        }
        
        .submit-btn:hover {
            background: #45a049;
            transform: translateY(-2px);
        }
        
        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .empty-cart {
            text-align: center;
            padding: 40px;
            color: #888;
        }
        
        @media (max-width: 600px) {
            .summary-container {
                margin: 20px;
                padding: 20px;
            }
            
            .item-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .item-subtotal {
                text-align: left;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .cancel-btn, .submit-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="/landingpage.html"><img src="/assets/img/schl_logo-1.png" alt="Logo"></a>
    </nav>  

    <div class="summary-container">
        <h2>Request Summary</h2>

        <!-- Student Info -->
        <div class="student-info">
            <p><strong>👤 Name:</strong> <?= htmlspecialchars($fullname); ?></p>
            <p><strong>📚 Course:</strong> <?= htmlspecialchars($course); ?></p>
            <p><strong>🆔 Student No:</strong> <?= htmlspecialchars($student_no); ?></p>
            <p><strong>📧 Email:</strong> <?= htmlspecialchars($email); ?></p>
        </div>

        <!-- Items -->
        <div class="items-list">
            <h3 style="margin-bottom: 15px; color: #2ecc71;">📦 Requested Items</h3>
            <?php if (count($items) > 0): ?>
                <?php foreach ($items as $item): 
                    $subtotal = $item['price'] * $item['quantity'];
                ?>
                    <div class="item-row">
                        <div class="item-name">
                            <?= htmlspecialchars($item['name']); ?>
                            <?php if (!empty($item['size'])): ?>
                                <span class="item-size">Size: <?= htmlspecialchars($item['size']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="item-qty">Qty: <?= $item['quantity']; ?></div>
                        <div class="item-subtotal">₱<?= number_format($subtotal, 2); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-cart">
                    <i class="fa-solid fa-cart-shopping" style="font-size: 48px; opacity: 0.5;"></i>
                    <p>No items in your request</p>
                    <small>Please go back and add items to your request</small>
                </div>
            <?php endif; ?>
        </div>

        <!-- Total -->
        <div class="total-box">
            Total: ₱<?= number_format($total, 2); ?>
        </div>

        <!-- Buttons -->
        <div class="button-group">
            <button class="cancel-btn" onclick="cancelRequest()">Cancel</button>
            <button class="submit-btn" onclick="submitRequest(this)" <?= count($items) === 0 ? 'disabled' : ''; ?>>Submit</button>
        </div>
    </div>
    
    <script>
    function cancelRequest() {
        Swal.fire({
            title: "Cancel Request?",
            text: "All items in your cart will be cleared.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#dc3545",
            cancelButtonColor: "#2ecc71",
            confirmButtonText: "Yes, cancel",
            cancelButtonText: "No, go back",
            background: "#2c3136",
            color: "#fff"
        }).then((result) => {
            if (result.isConfirmed) {
                // Clear cart
                fetch("../clear_temp_items.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" }
                })
                .then(() => {
                    window.location.href = "/landingpage.html";
                });
            }
        });
    }

    function submitRequest(btn) {
        btn.disabled = true;
        btn.innerText = "Submitting...";
        
        fetch("../submit_request.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({})
        })
        .then(res => res.json())
        .then(data => { 
            if (!data.success) {
                throw new Error(data.message || "Server failed");
            }
            
            let qrUrl = "https://quickchart.io/qr?text=" + encodeURIComponent(data.or_number);
            
            Swal.fire({
                title: "✅ Request Submitted!",
                html: `
                    <div style="text-align: center;">
                        <p><strong>OR Number:</strong> ${data.or_number}</p>
                        <img src="${qrUrl}" style="width:200px;height:200px;margin-top:10px;border-radius:10px;">
                        <p style="margin-top:15px; font-size:12px; color:#aaa;">Screenshot this QR Code as your reference</p>
                    </div>
                `,
                confirmButtonText: "OK",
                background: "#2c3136",
                color: "#fff",
                confirmButtonColor: "#2ecc71"
            }).then(() => { 
                window.location.href = '/landingpage.html'; 
            });
        })
        .catch(err => {
            console.error("Fetch Error:", err);
            Swal.fire({ 
                title: "❌ Unable to submit request", 
                text: err.message || "Please try again.", 
                icon: "error",
                confirmButtonText: "OK",
                background: "#2c3136",
                color: "#fff"
            });
            
            btn.disabled = false;
            btn.innerText = "Submit";
        });
    }
    </script>
</body>
</html>