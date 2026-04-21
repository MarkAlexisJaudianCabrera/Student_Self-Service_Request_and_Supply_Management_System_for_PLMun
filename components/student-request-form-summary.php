<?php
    session_start();
    include('../config/db.php'); 

    if (
        !isset($_SESSION['validated']) || 
        $_SESSION['validated'] !== true ||
        !isset($_SESSION['fullname']) ||
        !isset($_SESSION['course'])
    ) {
        header("Location: /404.php");
        exit();
    }

    $fullname = $_SESSION['fullname'];
    $course = $_SESSION['course'];
    $student_no = $_SESSION['student_no'];

    $session_id = session_id();

    // get temp items
    $stmt = $conn->prepare("
        SELECT t.quantity, i.name, i.price
        FROM tempreqitemtb t
        JOIN itemtb i ON t.itemtbID = i.itemtbID
        WHERE t.session_id = ?
    ");
    $stmt->bind_param("s", $session_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $total = 0;
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
        <link rel="icon" href="/assets/ico/logo16ico.ico" >
        <link rel="icon" href="/assets/ico/logo32ico.ico" >
        <link rel="icon" href="/assets/ico/logo96ico.ico" >
        <link rel="icon" href="/assets/ico/logo192ico.ico">
    </head>
    <body>
        <nav class="navbar">
            <a href="/landingpage.html"><img src="/assets/img/schl_logo-1.png" alt="Logo"></a>
        </nav>
        <br>
        <br>
        <br>
        <br>
        <div class="summary-container">

            <h2>Request Summary</h2>

            <!-- Student Info -->
            <div class="card student-info">
                <p><strong>Name:</strong> <?= $fullname; ?></p>
                <p><strong>Course:</strong> <?= $course; ?></p>
                <p><strong>Student No:</strong> <?= $student_no; ?></p>
            </div>

            <!-- Items -->
            <div class="card items-list">
                <?php while ($row = $result->fetch_assoc()): 
                    $subtotal = $row['price'] * $row['quantity'];
                    $total += $subtotal;
                ?>
                    <div class="item-row">
                        <span><?= $row['name']; ?></span>
                        <span>Qty: <?= $row['quantity']; ?></span>
                        <span>₱<?= number_format($subtotal, 2); ?></span>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Total -->
            <div class="total-box">
                Total: ₱<?= number_format($total, 2); ?>
            </div>

            <!-- Buttons -->
            <div class="button-group">
                <button class="cancel-btn" onclick="cancelRequest()">Cancel</button>
                <button class="submit-btn" onclick="submitRequest()">Submit</button>
            </div>

        </div>
        <script>
        function cancelRequest() {
            if (confirm("Cancel request?")) {
                window.location.href = "/landingpage.html";
            }
        }

        function submitRequest() {
            fetch("../submit_request.php")
            .then(res => res.json())
            .then(data => { 
                if (!data.success) {
                    throw new Error("Server failed");
                }

                alert("Request Submitted!\nOR No: " + data.or_number);
                window.location.href = "/landingpage.html";
            })
            .catch(err => {
                console.error(err);
                alert("Error submitting request. Please try again.");
            });
        }
        </script>

    </body>
</html>