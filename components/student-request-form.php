<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Self-Service Request - Student Self-Service Request and Supply Management System for PLMUN</title>
    <link rel="stylesheet" href="/assets/styles/allstyles.css">
    <link rel="stylesheet" href="/assets/styles/navbar.css">
    <link rel="stylesheet" href="/assets/styles/request.css">
    <link rel="icon" type="image/x-icon" href="/assets/ico/logo16ico.ico" >
    <link rel="icon" type="image/x-icon" href="/assets/ico/logo32ico.ico" >
    <link rel="icon" type="image/x-icon" href="/assets/ico/logo96ico.ico" >
    <link rel="icon" type="image/x-icon" href="/assets/ico/logo192ico.ico" >
</head>
<body>
    <nav class="navbar">
        <img src="/assets/img/schl_logo-1.png" alt="Logo">
    </nav>

    <div class="request-container">
        <div class="title">
            <h3>Self-Service Request</h3>
        </div>
        <div class="subtitle">
            <p>This is the self-service request page. Here, students can submit their requests for various services and supplies. Please fill out the form below to check validity.</p>
            <br>
        </div>
        <form class="checkinput">
            <label for="student-id">Student ID:</label>
            <input type="text" id="student-id" name="student-id" required>
            <label for="email">Institutional Email:</label>
            <input type="email" id="email" name="email" required>
            <input type="submit" value="Check Validity">
        </form>
        <br>
        <form class="checkresult">
            <label for="text">Full Name:</label>
            <input type="text" id="fnresult" name="fnresult" readonly>
            <label for="text">Course:</label>
            <input type="text" id="courseresult" name="courseresult" readonly>
        </form>
        <br>
        <!-- <form class="">
            <label for="text">Request Type:</label>
            <select id="request-type" name="request-type" required>
                <option value="">Select a request type</option>
                <option value="id-card">ID Card Replacement</option>
                <option value="transcript">Transcript Request</option>
                <option value="enrollment-cert">Enrollment Certificate</option>
                <option value="other">Other Requests</option>
            </select>
            <input type="submit" value="Submit Request">
        </form> -->
        
        <div class="footer">
            <br><br>
            © 2026 All rights reserved
        </div>
    </div>
</body>
</html>