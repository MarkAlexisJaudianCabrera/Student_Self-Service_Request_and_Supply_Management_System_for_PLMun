<?php
include('./config/db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    $res = $conn->query("SELECT * FROM users 
    WHERE username='$user' AND password='$pass'");

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();

        if ($row['role'] == "registrar") {
            header("Location: /components/registrar-home-page.php");
        }
        if ($row['role'] == "cashier") {
            header("Location: /components/cashier-home-page.php");
        }
        if ($row['role'] == "business") {
            header("Location: /components/business-center-home-page.php");
        }
        if ($row['role'] == "admin") {
            header("Location: /components/admin-home-page.php");
        }
    } else {
        echo "Invalid login";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - Student Self-Service Request and Supply Management System for PLMUN</title>
    <link rel="stylesheet" href="/assets/styles/allstyles.css">
    <link rel="stylesheet" href="/assets/styles/navbar.css">
    <link rel="stylesheet" href="/assets/styles/login.css">
    <link rel="icon" type="image/x-icon" href="/assets/ico/logo16ico.ico" >
    <link rel="icon" type="image/x-icon" href="/assets/ico/logo32ico.ico" >
    <link rel="icon" type="image/x-icon" href="/assets/ico/logo96ico.ico" >
    <link rel="icon" type="image/x-icon" href="/assets/ico/logo192ico.ico" >
</head>
<body>
    <nav class="navbar">
        <img src="/assets/img/schl_logo-1.png" alt="Logo">
    </nav>

    <div class="login-container">
        <div class="login-container-title">
            Supply Management
        </div>
        <div class="login-container-subtitle">
            <p>Staff Login</p>  
        </div>
        <form class="login_form" method="POST">
            <br>
            <div class="input-group">
                <label>Staff ID :</label><br>
                <input id="textboxx" type="text" placeholder="Enter your staff ID" name="username" required>
            </div>
            <div class="input-group">
                <label>Pin Number :</label><br>
                <input id="textboxx" type="password" placeholder="Enter your pin number" name="password" required>
            </div>
            <br>
            <button type="submit" class="login-btn">Sign In</button>
        </form>
        <br>
    <div class="footer-text">
        © 2026 All rights reserved
    </div>
  </div>
</body>
</html>