<?php
session_start();
include('../../../config/db.php');

// Send email notification using PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';
require __DIR__ . '/PHPMailer/src/Exception.php';

if (!isset($_SESSION['staffvalidated']) || $_SESSION['staffvalidated'] !== true) {
    header("Location: /404.php"); 
    exit();
}

if (isset($_POST['set_schedule'])) {
    $request_id = intval($_POST['request_id']);
    $pickup_schedule = $_POST['pickup_schedule'];
    $pickup_notes = $_POST['pickup_notes'] ?? '';
    
    // Update the request with pickup schedule
    $stmt = $conn->prepare("UPDATE requesttb SET pickup_schedule = ?, pickup_notes = ? WHERE request_id = ?");
    $stmt->bind_param("ssi", $pickup_schedule, $pickup_notes, $request_id);
    
    if ($stmt->execute()) {
        // Get student email for notification
        $emailStmt = $conn->prepare("
            SELECT b.instiemail, a.or_number, a.fullname
            FROM requesttb a 
            JOIN students b ON a.student_no = b.student_no 
            WHERE a.request_id = ?
        ");
        $emailStmt->bind_param("i", $request_id);
        $emailStmt->execute();
        $result = $emailStmt->get_result();
        $row = $result->fetch_assoc();
        
        // Format date for display
        $formatted_date = date('F d, Y h:i A', strtotime($pickup_schedule));
        
        $mail = new PHPMailer(true);
        
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'plmunselfservicerequest@gmail.com';
            $mail->Password   = 'gsbo yseb hzxg lyri';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            
            $mail->setFrom('plmunselfservicerequest@gmail.com', 'PLMUN Student Service');
            $mail->addAddress($row['instiemail'], $row['fullname']);
            
            $mail->isHTML(true);
            $mail->Subject = 'Pickup Schedule Set - ' . $row['or_number'];
            
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
                        <h3>Your Pickup Schedule Has Been Set!</h3>
                        <p>Dear <strong>" . htmlspecialchars($row['fullname']) . "</strong>,</p>
                        <p><strong>OR Number:</strong> " . $row['or_number'] . "</p>
                        <p><strong>📅 Pickup Date & Time:</strong> " . $formatted_date . "</p>
                        <p><strong>📝 Notes:</strong> " . nl2br(htmlspecialchars($pickup_notes)) . "</p>
                        <p>Please come on time and bring your OR number as reference.</p>
                        <p>Thank you for using PLMUN Student Service Request System!</p>
                    </div>
                    <div class='footer'>
                        <p>&copy; " . date('Y') . " PLMUN Student Service Request System</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $mail->send();
        } catch (Exception $e) {
            error_log("Mail Error: " . $mail->ErrorInfo);
        }
        
        header("Location: ../requests.php?success=schedule_set");
    } else {
        header("Location: ../requests.php?error=schedule_failed");
    }
    exit();
}

header("Location: ../requests.php");
exit();
?>