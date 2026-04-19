<?php
    session_start();

    if (!isset($_SESSION['validated']) || $_SESSION['validated'] !== true) {
        header("Location: /404.php");
        exit();
    }

    include('../config/db.php');

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $result = $conn->query("SELECT * FROM itemtb");
?>
<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Request Items - Student Self-Service Request and Supply Management System for PLMUN</title>
        <link rel="stylesheet" href="/assets/styles/allstyles.css">
        <link rel="stylesheet" href="/assets/styles/navbar.css">
        <link rel="icon" href="/assets/ico/logo16ico.ico" >
        <link rel="icon" href="/assets/ico/logo32ico.ico" >
        <link rel="icon" href="/assets/ico/logo96ico.ico" >
        <link rel="icon" href="/assets/ico/logo192ico.ico">
    </head>
    <body>
        <nav class="navbar">
            <a href="/landingpage.html"><img src="/assets/img/schl_logo-1.png" alt="Logo"></a>
        </nav>
        <div class="selectitems-container">
            <div class="title">
                <h3>Self-Service Request | Student Validation</h3>
            </div>
            <div class="subtitle">
                <p>This is the self-service request page. Here, students can submit their requests for various services and supplies. Please fill out the form below to check validity.</p>
                <br>
            </div>
            <br><br><br>
            <form action="">
                 <?php while ($row = $result->fetch_assoc()) : ?>
                    <button class="item-btn"
                        data-id="<?= $row['itemtbID']; ?>"
                        data-name="<?= htmlspecialchars($row['name']); ?>"
                        data-price="<?= $row['price']; ?>"
                        data-itemrole="<?= htmlspecialchars($row['category']); ?>"
                    >
                        <div class="item-header">
                            <h4><?= htmlspecialchars($row['name']); ?></h4>
                            <p><?= htmlspecialchars($row['description']); ?></p>
                        </div>
                    </button>
                <?php endwhile; ?>
            </form>
        </div>
    </body>
</html>