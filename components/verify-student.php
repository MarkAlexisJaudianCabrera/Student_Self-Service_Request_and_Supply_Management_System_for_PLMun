<?php
session_start();

// Clear previous verification
unset($_SESSION['verified_for_past']);
unset($_SESSION['debug_otp']);

$error = '';
$success = '';
$showOtpForm = false;
$student_no = '';
$email = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_otp'])) {
        $student_no = trim($_POST['student_no']);
        $email = trim($_POST['email']);
        
        if (empty($student_no) || empty($email)) {
            $error = 'Please enter Student Number and Email';
        } elseif (!preg_match('/@plmun\.edu\.ph$/', $email)) {
            $error = 'Please use your PLMUN email address (@plmun.edu.ph)';
        } else {
            // Include the OTP sending logic directly
            include_once '../send_verification_otp_direct.php';
            
            // Call function to send OTP
            $result = sendVerificationOTP($student_no, $email);
            
            if ($result['success']) {
                $success = $result['message'];
                $showOtpForm = true;
                $_SESSION['verification_email'] = $email;
            } else {
                $error = $result['message'];
            }
        }
    }
    
    elseif (isset($_POST['verify_otp'])) {
        $entered_otp = trim($_POST['otp_code']);
        
        if (empty($entered_otp)) {
            $error = 'Please enter the OTP code';
        } else {
            $email = $_SESSION['verification_email'] ?? '';
            
            if (empty($email)) {
                $error = 'Session expired. Please request a new code.';
                $showOtpForm = false;
            } else {
                // Include verification logic directly
                include_once '../verify_otp_direct.php';
                
                $result = verifyOTP($email, $entered_otp);
                
                if ($result['success']) {
                    header('Location: student-past-requests.php');
                    exit();
                } else {
                    $error = $result['message'];
                    $showOtpForm = true;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Identity - PLMUN</title>
    <link rel="stylesheet" href="/assets/styles/allstyles.css">
    <link rel="stylesheet" href="/assets/styles/navbar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #1a1a2e; min-height: 100vh; }
        .verify-container {
            max-width: 500px; width: 90%; margin: 100px auto; padding: 40px;
            background: #2c3136; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            text-align: center;
        }
        .verify-header { margin-bottom: 30px; }
        .verify-header h1 { color: #2ecc71; font-size: 28px; margin-bottom: 10px; }
        .verify-header h1 i { margin-right: 10px; }
        .verify-header p { color: #aaa; font-size: 14px; }
        .verify-icon { font-size: 60px; color: #2ecc71; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; margin-bottom: 8px; color: #fff; font-weight: 500; font-size: 14px; }
        .form-group label i { color: #2ecc71; margin-right: 8px; }
        .form-group input {
            width: 100%; padding: 12px 15px; background: #3a4046;
            border: 1px solid #4a5056; border-radius: 8px; color: #fff; font-size: 14px;
        }
        .form-group input:focus { outline: none; border-color: #2ecc71; }
        .btn {
            width: 100%; padding: 12px; background: #2ecc71; color: #2c3136;
            border: none; border-radius: 8px; font-size: 16px; font-weight: bold;
            cursor: pointer; transition: all 0.3s ease;
        }
        .btn:hover { background: #45a049; transform: translateY(-2px); }
        .btn-secondary { background: #3a4046; color: #fff; margin-top: 10px; }
        .error-message {
            background: rgba(220,53,69,0.2); color: #dc3545; padding: 10px;
            border-radius: 8px; margin-bottom: 20px; font-size: 14px;
            border-left: 3px solid #dc3545; text-align: left;
        }
        .success-message {
            background: rgba(46,204,113,0.2); color: #2ecc71; padding: 10px;
            border-radius: 8px; margin-bottom: 20px; font-size: 14px;
            border-left: 3px solid #2ecc71; text-align: left;
        }
        .info-note {
            margin-top: 20px; padding: 12px; background: rgba(46,204,113,0.1);
            border-radius: 8px; font-size: 12px; color: #aaa;
            border-left: 3px solid #2ecc71; text-align: left;
        }
        .back-link { display: inline-block; margin-top: 20px; color: #2ecc71; text-decoration: none; font-size: 14px; }
        footer { text-align: center; padding: 20px; color: #888; font-size: 12px; margin-top: 20px; }
    </style>
</head>
<body>
    <nav class="navbar"><a href="/landingpage.html"><img src="/assets/img/schl_logo-1.png" alt="Logo"></a></nav>
    <div class="verify-container">
        <div class="verify-header">
            <div class="verify-icon"><i class="fas fa-shield-alt"></i></div>
            <h1><i class="fas fa-user-check"></i> Verify Identity</h1>
            <p>Please verify your identity to access your past requests</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if (!$showOtpForm): ?>
            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-id-card"></i> Student Number</label>
                    <input type="text" name="student_no" placeholder="Enter your student number" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Institutional Email</label>
                    <input type="email" name="email" placeholder="username@plmun.edu.ph" required>
                    <small style="color: #888; font-size: 11px; display: block; margin-top: 5px;">
                        <i class="fas fa-info-circle"></i> Use your PLMUN email address (@plmun.edu.ph)
                    </small>
                </div>
                <button type="submit" name="send_otp" class="btn"><i class="fas fa-paper-plane"></i> Send Verification Code</button>
            </form>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-key"></i> Enter OTP Code</label>
                    <input type="text" name="otp_code" placeholder="Enter 6-digit code" maxlength="6" required autocomplete="off">
                </div>
                <button type="submit" name="verify_otp" class="btn"><i class="fas fa-check-circle"></i> Verify & Continue</button>
                <button type="submit" name="send_otp" class="btn btn-secondary"><i class="fas fa-redo"></i> Resend Code</button>
            </form>
        <?php endif; ?>
        
        <div class="info-note"><i class="fas fa-info-circle"></i> A 6-digit verification code will be sent to your institutional email. The code expires in 5 minutes.</div>
        <a href="/landingpage.html" class="back-link"><i class="fas fa-arrow-left"></i> Back to Home</a>
        <footer><p>© 2025 PLMUN Student Self-Service Request System</p></footer>
    </div>
</body>
</html>