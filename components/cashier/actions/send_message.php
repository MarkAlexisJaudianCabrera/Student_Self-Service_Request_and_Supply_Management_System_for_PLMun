<?php
session_start();

// Include the mailer file from the same directory (actions folder)
include __DIR__ . '/sssssendNotifMail.php';

// Check if user is authenticated
if (!isset($_SESSION['staffvalidated']) || $_SESSION['staffvalidated'] !== true) {
    header("Location: /404.php");
    exit();
}

$conn = new mysqli("localhost", "root", "1234", "plmun_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['send'])) {
    $id = intval($_POST['request_id']);
    $message = $_POST['message'];
    
    if (empty($message)) {
        header("Location: ../request-payment-page.php?error=empty_message");
        exit();
    }
    
    $stmt = $conn->prepare("
        SELECT a.or_number, b.instiemail, a.status
        FROM requesttb a 
        JOIN students b ON a.student_no = b.student_no 
        WHERE a.request_id=?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $or_number = $row['or_number'];
        $email = $row['instiemail'];
        $status = $row['status'];
        
        $fullMessage = "📧 MESSAGE FROM TREASURY/CASHIER\n\n"
                     . "Regarding your request (OR: $or_number)\n\n"
                     . $message . "\n\n"
                     . "Please reply to this email or contact the Treasury Office for more information.\n\n"
                     . "Thank you.";
        
        sendNotificationEmail($or_number, $email, $status, $fullMessage, "MESSAGE FROM TREASURY");
        header("Location: ../request-payment-page.php?success=message_sent");
    } else {
        header("Location: ../request-payment-page.php?error=request_not_found");
    }
    exit();
}

header("Location: ../request-payment-page.php");
exit();
?>