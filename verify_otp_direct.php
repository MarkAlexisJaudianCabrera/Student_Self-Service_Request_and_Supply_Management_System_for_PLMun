<?php
function verifyOTP($email, $otp) {
    
    // Check if OTP exists
    if (!isset($_SESSION['verification_otp'])) {
        return ['success' => false, 'message' => 'No verification code found. Please request a new code.'];
    }
    
    // Check expiry
    if (time() > $_SESSION['verification_expiry']) {
        unset($_SESSION['verification_otp']);
        unset($_SESSION['verification_expiry']);
        return ['success' => false, 'message' => 'Verification code has expired. Please request a new code.'];
    }
    
    // Check email match
    if ($_SESSION['verification_email'] !== $email) {
        return ['success' => false, 'message' => 'Email mismatch. Please try again.'];
    }
    
    // Check OTP match
    if ($_SESSION['verification_otp'] === $otp) {
        $_SESSION['verified_for_past'] = true;
        $_SESSION['verified_student_no'] = $_SESSION['verification_student_no'];
        $_SESSION['verified_email'] = $email;
        $_SESSION['verified_fullname'] = $_SESSION['verification_fullname'];
        $_SESSION['verified_course'] = $_SESSION['verification_course'];
        
        unset($_SESSION['verification_otp']);
        unset($_SESSION['verification_expiry']);
        
        return ['success' => true, 'message' => 'Verification successful'];
    } else {
        return ['success' => false, 'message' => 'Invalid verification code'];
    }
}
?>