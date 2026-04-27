<?php
$conn = new mysqli("localhost", "root", "1234", "plmun_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__.'/PHPMailer/src/PHPMailer.php';
require __DIR__.'/PHPMailer/src/SMTP.php';
require __DIR__.'/PHPMailer/src/Exception.php';

function sendNotificationEmail($or_number, $email, $status, $note, $resStatus) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'plmunselfservicerequest@gmail.com';
        $mail->Password   = 'gsbo yseb hzxg lyri';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('plmunselfservicerequest@gmail.com', 'PLMUN Request System - Notification');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Request ' . $resStatus . ' - ' . $or_number;

        $mail->Body = "
            <h3>Request was " . $resStatus . "</h3>
            <p><strong>OR Number:</strong> " . $or_number . "</p>
            <p><strong>Status:</strong> " . $status . "</p> 
            <p>" . $note . "</p>
        ";

        $mail->send();

    } catch (Exception $e) {
        error_log($mail->ErrorInfo);
    }
}