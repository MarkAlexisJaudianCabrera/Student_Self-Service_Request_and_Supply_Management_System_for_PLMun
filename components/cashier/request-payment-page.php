<?php
    session_start();

    if (!isset($_SESSION['staffvalidated']) || $_SESSION['staffvalidated'] !== true) {
        header("Location: /404.php");
        exit();
    }
?>
<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Cashier - Student Self-Service Request and Supply Management System for PLMUN</title>
        <link rel="stylesheet" href="/assets/styles/allstyles.css">
        <link rel="stylesheet" href="/assets/styles/navbar.css">
        <link rel="icon" type="image/x-icon" href="/assets/ico/logo16ico.ico" >
        <link rel="icon" type="image/x-icon" href="/assets/ico/logo32ico.ico" >
        <link rel="icon" type="image/x-icon" href="/assets/ico/logo96ico.ico" >
        <link rel="icon" type="image/x-icon" href="/assets/ico/logo192ico.ico" >
    </head>
    <body>
        <nav class="navbar">
            <a href="/landingpage.html"><img src="/assets/img/schl_logo-1.png" alt="Logo"></a>
        </nav>
    </body>
</html>