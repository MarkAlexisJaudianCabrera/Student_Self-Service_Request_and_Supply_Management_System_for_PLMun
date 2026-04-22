<?php
session_start(); 
include('./config/db.php'); 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__.'/PHPMailer/src/PHPMailer.php';
require __DIR__.'/PHPMailer/src/SMTP.php';
require __DIR__.'/PHPMailer/src/Exception.php';
// ✅ EMAIL FUNCTION
function sendNotificationEmail($to, $or_number) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'plmunselfservicerequest@gmail.com';
        $mail->Password   = 'gsbo yseb hzxg lyri';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('plmunselfservicerequest@gmail.com', 'PLMUN Request System');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = 'Request Submitted - OR ' . $or_number;

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

// ✅ CALL IT HERE (BEFORE JSON OUTPUT)
sendNotificationEmail($email, $or_number);
?>