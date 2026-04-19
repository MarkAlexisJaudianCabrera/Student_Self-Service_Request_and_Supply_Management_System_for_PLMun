<?php
    session_start();
    $_SESSION['staffvalidated'] = false;

    include('./config/db.php');

    $error = false;
    $isPost = ($_SERVER["REQUEST_METHOD"] === "POST");

    if ($isPost) {

        $user = trim($_POST['username'] ?? '');
        $pass = trim($_POST['password'] ?? '');

        if ($user === "" || $pass === "") {
            $error = true;
        } else {

            $res = $conn->query("SELECT * FROM users 
            WHERE username='$user' AND password='$pass'");

            if ($res->num_rows > 0) {
                $row = $res->fetch_assoc();

                if ($row['role'] == "registrar") {
                    $_SESSION['staffvalidated'] = true;
                    header("Location: /components/registrar/registrar-home-page.php");
                    exit();
                }
                if ($row['role'] == "cashier") {
                    $_SESSION['staffvalidated'] = true;
                    header("Location: /components/cashier/request-payment-page.php");
                    exit();
                }
                if ($row['role'] == "business") {
                    $_SESSION['staffvalidated'] = true;
                    header("Location: /components/businesscenter/business-center-home-page.php");
                    exit();
                }
                if ($row['role'] == "admin") {
                    $_SESSION['staffvalidated'] = true;
                    header("Location: /components/admin/admin-home-page.php");
                    exit();
                }
            } else {
                $_SESSION['staffvalidated'] = false;
                $error = true; // wrong login
            }
        }
    }
?>
<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Staff Login</title>
        <link rel="icon" href="./assets/ico/logo16ico.ico" >
        <link rel="icon" href="./assets/ico/logo32ico.ico" >
        <link rel="icon" href="./assets/ico/logo96ico.ico" >
        <link rel="icon" href="./assets/ico/logo192ico.ico">
        <link rel="stylesheet" href="/assets/styles/allstyles.css">
        <link rel="stylesheet" href="/assets/styles/navbar.css">
        <link rel="stylesheet" href="/assets/styles/login.css">
    </head>
    <body>
        <nav class="navbar">
            <a href="/landingpage.html"><img src="/assets/img/schl_logo-1.png" alt="Logo"></a>
        </nav>
        <div class="login-container">
            <div class="login-container-title">
                Supply Management
            </div>
            <div class="login-container-subtitle">
                <p>Staff Login</p>  
            </div>
            <form class="login_form" method="POST">
                <p class="error-box <?php echo ($isPost && $error) ? 'show' : ''; ?>">
                    <br>
                    Invalid Staff ID or Pin Number. <br>Please try again.<br><br>
                </p>
                <br>
                <div class="input-group">
                    <label>Staff ID :</label><br>
                    <input type="text" placeholder="Enter your staff ID" name="username" required>
                </div>
                <div class="input-group">
                    <label>Pin Number :</label><br>
                    <input type="password" placeholder="Enter your pin number" name="password" required>
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