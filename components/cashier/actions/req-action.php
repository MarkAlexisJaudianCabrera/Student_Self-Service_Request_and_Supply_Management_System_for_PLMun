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

/* UPDATE STATUS (MARK AS PAID) */
if (isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $status = $_POST['status'];
    
    // Get request details before updating
    $stmt1 = $conn->prepare("SELECT a.or_number, b.instiemail, a.total_amount FROM requesttb a JOIN students b ON a.student_no = b.student_no WHERE a.request_id=?");
    $stmt1->bind_param("i", $id);    
    $stmt1->execute();
    $result = $stmt1->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: ../request-payment-page.php?error=request_not_found");
        exit();
    }
    
    $row = $result->fetch_assoc();
    $or_number = $row['or_number'];
    $email = $row['instiemail'];
    $total_amount = $row['total_amount'];
    
    // Update the request status
    $stmt = $conn->prepare("UPDATE requesttb SET status=? WHERE request_id=?");
    $stmt->bind_param("si", $status, $id);
    
    if ($stmt->execute()) {
        $note = "✅ PAYMENT CONFIRMED!\n\n"
              . "OR Number: " . $or_number . "\n"
              . "Amount Paid: ₱" . number_format($total_amount, 2) . "\n\n"
              . "Your payment has been successfully processed.\n\n"
              . "Please wait for notification when your request is ready for claiming.\n\n"
              . "Thank you for using PLMUN Student Service Request System.";
        
        sendNotificationEmail($or_number, $email, $status, $note, "PAYMENT CONFIRMED");
        header("Location: ../request-payment-page.php?success=paid");
    } else {
        header("Location: ../request-payment-page.php?error=update_failed");
    }
    exit();
}

header("Location: ../request-payment-page.php");
exit();
?>