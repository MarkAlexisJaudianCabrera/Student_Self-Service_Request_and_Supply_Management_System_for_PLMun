<?php
include('../config/db.php'); 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__.'/PHPMailer/src/PHPMailer.php';
require __DIR__.'/PHPMailer/src/SMTP.php';
require __DIR__.'/PHPMailer/src/Exception.php';
// ✅ EMAIL FUNCTION
function sendNotificationEmail($or_number) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'plmunselfservicerequest@gmail.com';
        $mail->Password   = 'gsbo yseb hzxg lyri';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('plmunselfservicerequest@gmail.com', 'PLMUN Request System - Submitted Request');
        $mail->addAddress($_SESSION['email']);

        $mail->isHTML(true);
        $mail->Subject = 'Request Submitted - ' . $or_number;

        $mail->Body = "
            <h3>Request Submitted Successfully</h3>
            <p><strong>OR Number:</strong> $or_number</p>
            <p><strong>Status:</strong> PENDING</p>
            <p>WAIT FOR NOTIFICATION THAT ACCEPTS THIS REQUEST</p>
        ";

        $mail->send();

    } catch (Exception $e) {
        error_log($mail->ErrorInfo);
    }
}

function sendApprovalEmail($or_number, $email) {

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'plmunselfservicerequest@gmail.com';
        $mail->Password  = 'gsbo yseb hzxg lyri';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('plmunselfservicerequest@gmail.com', 'PLMUN Request System - Registrar');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "Approved - OR $or_number";

        $mail->Body = "
            <h3>Your request is approved</h3>
            <p>OR: $or_number</p>
            <p>Status: UNPAID</p>
        ";

        $mail->send();

    } catch (Exception $e) {
        error_log($mail->ErrorInfo);
    }
}

function sendRejectionEmail($or_number, $email, $note) {

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'plmunselfservicerequest@gmail.com';
        $mail->Password  = 'gsbo yseb hzxg lyri';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('plmunselfservicerequest@gmail.com', 'PLMUN Request System - Registrar');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "Rejected - OR $or_number";

        $mail->Body = "
            <h3>Request Rejected</h3>
            <p>OR: $or_number</p>
            <p>Reason: $note</p>
        ";

        $mail->send();

    } catch (Exception $e) {
        error_log($mail->ErrorInfo);
    }
}