<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

function sendVerificationOTP($student_no, $email) {
    //session_start();
    include('config/db.php');
    
    // Verify student exists
    $query = "SELECT * FROM students WHERE student_no = ? AND instiemail = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $student_no, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Student not found. Please check your Student Number and Email.'];
    }
    
    $student = $result->fetch_assoc();
    
    // Generate OTP
    $otp = sprintf("%06d", mt_rand(1, 999999));
    
    // Store in session
    $_SESSION['verification_otp'] = $otp;
    $_SESSION['verification_email'] = $email;
    $_SESSION['verification_student_no'] = $student_no;
    $_SESSION['verification_fullname'] = $student['fullname'];
    $_SESSION['verification_course'] = $student['course'];
    $_SESSION['verification_expiry'] = time() + 300;
    
    // Send email using PHPMailer
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'plmunselfservicerequest@gmail.com';
        $mail->Password   = 'gsbo yseb hzxg lyri'; // Your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->SMTPDebug = 0; // Set to 2 for debugging
        
        // Recipients
        $mail->setFrom('plmunselfservicerequest@gmail.com', 'PLMUN Request System - Verification Code');
        $mail->addAddress($email, $student['fullname']);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'PLMUN Request System - Your Verification Code';
        $mail->Body    = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
                .container { max-width: 500px; margin: 20px auto; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background: #2ecc71; padding: 25px; text-align: center; }
                .header h2 { color: #2c3136; margin: 0; font-size: 24px; }
                .content { padding: 30px; text-align: center; }
                .greeting { font-size: 16px; color: #333; margin-bottom: 20px; }
                .otp-code { font-size: 42px; font-weight: bold; color: #2ecc71; letter-spacing: 8px; background: #f0f0f0; padding: 20px; border-radius: 10px; display: inline-block; margin: 20px 0; font-family: monospace; }
                .message { color: #666; line-height: 1.6; margin: 15px 0; }
                .footer { background: #f5f5f5; padding: 15px; text-align: center; font-size: 12px; color: #888; }
                .warning { color: #e74c3c; font-size: 12px; margin-top: 15px; padding: 10px; background: #ffeaea; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>PLMUN Request System</h2>
                </div>
                <div class="content">
                    <div class="greeting">Dear <strong>' . htmlspecialchars($student['fullname']) . '</strong>,</div>
                    <div class="message">Your verification code for accessing past requests is:</div>
                    <div class="otp-code">' . $otp . '</div>
                    <div class="message">This code will expire in <strong>5 minutes</strong>.</div>
                    <div class="warning">
                        ⚠️ Never share this code with anyone.
                    </div>
                </div>
                <div class="footer">
                    <p>&copy; ' . date('Y') . ' PLMUN Student Service Request System</p>
                    <p>This is an automated message, please do not reply.</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        $mail->AltBody = "Dear " . $student['fullname'] . ",\n\nYour verification code is: " . $otp . "\n\nThis code will expire in 5 minutes.\n\nNever share this code with anyone.\n\n---\nPLMUN Student Service Request System";
        
        $mail->send();
        $conn->close();
        return ['success' => true, 'message' => 'Verification code sent to your email!'];
        
    } catch (Exception $e) {
        $conn->close();
        return ['success' => false, 'message' => 'Failed to send email. Error: ' . $mail->ErrorInfo];
    }
}
?>