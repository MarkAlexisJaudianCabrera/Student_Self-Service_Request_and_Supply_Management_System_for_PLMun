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
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        $mail->setFrom('plmunselfservicerequest@gmail.com', 'PLMUN Request System - Notification');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Request ' . $resStatus . ' - ' . $or_number;

        $mail->Body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #2ecc71; padding: 20px; text-align: center; color: #2c3136; }
                    .content { padding: 20px; background: #f5f5f5; }
                    .footer { font-size: 12px; text-align: center; color: #888; margin-top: 20px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>PLMUN Student Service Request System</h2>
                    </div>
                    <div class='content'>
                        <h3>Request was " . $resStatus . "</h3>
                        <p><strong>OR Number:</strong> " . $or_number . "</p>
                        <p><strong>Status:</strong> " . $status . "</p>
                        <p>" . nl2br(htmlspecialchars($note)) . "</p>
                    </div>
                    <div class='footer'>
                        <p>&copy; " . date('Y') . " PLMUN Student Service Request System</p>
                    </div>
                </div>
            </body>
            </html>
        ";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Mail Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>