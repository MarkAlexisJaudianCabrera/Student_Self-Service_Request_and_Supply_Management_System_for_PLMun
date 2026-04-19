<?php
session_start();
$_SESSION['validated'] = false;
$_SESSION['student_no'] = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Validation - Student Self-Service Request and Supply Management System for PLMUN</title>  
    <script src="/assets/function/validatestudent.js"></script>
    <script src="/assets/function/secure_validate.js"></script>
    <link rel="stylesheet" href="/assets/styles/allstyles.css">
    <link rel="stylesheet" href="/assets/styles/navbar.css">
    <link rel="stylesheet" href="/assets/styles/request.css">
    <link rel="icon" href="/assets/ico/logo16ico.ico" >
    <link rel="icon" href="/assets/ico/logo32ico.ico" >
    <link rel="icon" href="/assets/ico/logo96ico.ico" >
    <link rel="icon" href="/assets/ico/logo192ico.ico"> 
</head>
<body>
   <nav class="navbar">
        <a href="/landingpage.html"><img src="/assets/img/schl_logo-1.png" alt="Logo"></a>
    </nav>

    <div class="request-container">
        <div class="title">
            <h3>Self-Service Request | Student Validation</h3>
        </div>
        <div class="subtitle">
            <p>This is the self-service request page. Here, students can submit their requests for various services and supplies. Please fill out the form below to check validity.</p>
            <br>
        </div>
        <br><br><br>
        <form class="checkinput" onsubmit="return validateStudent(event)">    
            <label for="student-id">Student ID:</label>
            <input type="text" id="student_no" name="student_no" placeholder="ex.24175161" required>

            <label for="email">Institutional Email:</label>
            <input type="email" id="instiemail" name="instiemail" placeholder="ex.yourname@plmun.edu.ph" required>

            <button type="submit" id="checkBtn">Check Validity</button>
        </form>
        <br>
        <form class="checkresult">
            <label for="text">Full Name:</label>
            <input type="text" id="fnresult" name="fnresult" placeholder="" readonly>
            <label for="text">Course:</label>
            <input type="text" id="courseresult" name="courseresult" placeholder="" readonly>
        </form>
        <br>
        <form action="">
            <button type="button" id="proceedBtn">
                Proceed
            </button>
        </form>
        <div class="footer">
            <br><br>
            © 2026 All rights reserved
        </div>
    </div>
</body>
</html>