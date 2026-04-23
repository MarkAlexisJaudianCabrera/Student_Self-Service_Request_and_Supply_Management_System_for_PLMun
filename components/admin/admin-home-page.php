<?php
    session_start();
    include('../../config/db.php');
    if (!isset($_SESSION['staffvalidated']) || $_SESSION['staffvalidated'] !== true) {
        header("Location: /404.php");
        exit();
    }

    $totalRequests = $conn->query("SELECT COUNT(*) as c FROM requesttb")->fetch_assoc()['c'];
    $totalStudents = $conn->query("SELECT COUNT(*) as c FROM students")->fetch_assoc()['c'];
    $totalItems    = $conn->query("SELECT COUNT(*) as c FROM itemtb")->fetch_assoc()['c'];

    $category = $_GET['category'] ?? 'acaditem';

    $stmt = $conn->prepare("
    SELECT i.name, SUM(ri.quantity) as total_qty
    FROM request_items ri
    JOIN itemtb i ON ri.itemtbID = i.itemtbID
    WHERE i.category = ?
    GROUP BY i.name
    ");

    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
?>
<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin - Student Self-Service Request and Supply Management System for PLMUN</title>
        <link rel="stylesheet" href="/assets/styles/allstyles.css">
        <link rel="stylesheet" href="/assets/styles/navbar.css">
        <link rel="stylesheet" href="/assets/styles/adminstyles/adminhp.css">
        <link rel="icon" type="image/x-icon" href="/assets/ico/logo16ico.ico" >
        <link rel="icon" type="image/x-icon" href="/assets/ico/logo32ico.ico" >
        <link rel="icon" type="image/x-icon" href="/assets/ico/logo96ico.ico" >
        <link rel="icon" type="image/x-icon" href="/assets/ico/logo192ico.ico" >
    </head>
    <body>
        <nav class="navbar">
            <a href="/landingpage.html"><img src="/assets/img/schl_logo-1.png" alt="Logo"></a>
        </nav>
        <?php include('../left-navbar.php'); ?>
        <div class="adminhp-megacontainer">
            <div class="adminhp-container">
                <h2>Admin Dashboard</h2>
                <h4 class="border-bt">Welcome to the Admin Dashboard</h4>
                <div>
                    <p>Total Submitted Requests: <?= $totalRequests ?></p>
                    <p>Total Students Enrolled: <?= $totalStudents ?></p>
                    <p>Total Items Registered: <?= $totalItems ?></p>
                </div>
            </div>
                <br>
            <div class="analytics-container">
                <h2>Analytics (<?= $category ?>)</h2>
                <p class="border-bt">Analysis of <?= $category ?> items</p><br>
                <a class="btn-default-style adminhp-btn" href="?category=acaditem">Academic</a> 
                <a class="btn-default-style adminhp-btn" href="?category=suppitem">Supply</a>
                <br> <br>
                <div class="analytics-table">
                    <table border="1">
                        <tr>
                            <th>Item Name</th>
                            <th>Total Quantity Requested</th>
                        </tr>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['name'] ?></td>
                        <td><?= $row['total_qty'] ?></td>
                    </tr>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </body>
</html>