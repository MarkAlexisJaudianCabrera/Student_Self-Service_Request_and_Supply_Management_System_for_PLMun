<?php
session_start();
include('./config/db.php');

$_SESSION['staffvalidated'] = false;
$_SESSION['user_type'] = null;

$error = false;
$isPost = ($_SERVER["REQUEST_METHOD"] === "POST");

if ($isPost) {

    $user = trim($_POST['username'] ?? '');
    $pass = trim($_POST['password'] ?? '');

    if ($user === "" || $pass === "") {
        $error = true;
    } else {

        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $result = $stmt->get_result();

        $row = $result->fetch_assoc();

        if ($row && password_verify($pass, $row['password'])) {

            $_SESSION['staffvalidated'] = true;
            $_SESSION['user_type'] = $row['role'];

            switch ($row['role']) {
                case 'registrar':
                    header("Location: /components/registrar/registrar-home-page.php");
                    break;

                case 'cashier':
                    header("Location: /components/cashier/request-payment-page.php");
                    break;

                case 'business':
                    header("Location: /components/businesscenter/business-center-home-page.php");
                    break;

                case 'admin':
                    header("Location: /components/admin/admin-home-page.php");
                    break;

                default:
                    $_SESSION['staffvalidated'] = false;
                    $error = true;
            }
            exit();

        } else {
            $error = true;
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