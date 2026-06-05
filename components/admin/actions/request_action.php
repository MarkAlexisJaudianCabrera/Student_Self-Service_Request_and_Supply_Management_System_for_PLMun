<?php
session_start();
include __DIR__ . '/ssendNotifMail.php';

// Check if user is authenticated
if (!isset($_SESSION['staffvalidated']) || $_SESSION['staffvalidated'] !== true) {
    header("Location: /404.php");
    exit();
}

$conn = new mysqli("localhost", "root", "1234", "plmun_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* UPDATE STATUS */
if (isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $status = $_POST['status'];
    
    // Get request details before updating
    $stmt1 = $conn->prepare("SELECT a.or_number, b.instiemail FROM requesttb a JOIN students b ON a.student_no = b.student_no WHERE a.request_id=?");
    $stmt1->bind_param("i", $id);    
    $stmt1->execute();
    $result = $stmt1->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $or_number = $row['or_number'];
        $email = $row['instiemail'];
        
        switch ($status){
            case 'Pending':
                $note = '⏳ YOUR REQUEST IS PENDING.\n\nPlease wait for approval from the Business Center. You will receive another notification once your request is processed.';
                break;
            case 'Unpaid':
                $note = '💰 YOUR REQUEST HAS BEEN ACCEPTED!\n\n'
                      . 'OR Number: ' . $or_number . '\n\n'
                      . 'You may now proceed to the University Treasury Office or Cashier to make your payment.\n\n'
                      . 'Please bring your OR number as reference.\n\n'
                      . 'Thank you for using PLMUN Student Service Request System.';
                break;
            case 'Paid':
                $note = '✅ PAYMENT CONFIRMED!\n\n'
                      . 'Your payment has been successfully processed.\n\n'
                      . 'Please wait for notification when your request is ready for claiming.\n\n'
                      . 'Thank you for your patience.';
                break;
            case 'Completed':
                $note = '🎉 YOUR REQUEST IS NOW COMPLETE!\n\n'
                      . 'OR Number: ' . $or_number . '\n\n'
                      . 'You may now claim your items at the designated office.\n\n'
                      . 'Please bring your OR number as reference.\n\n'
                      . 'Thank you for using PLMUN Student Service Request System.';
                break;
            case 'Rejected':
                $note = '❌ YOUR REQUEST HAS BEEN REJECTED.\n\n'
                      . 'OR Number: ' . $or_number . '\n\n'
                      . 'Reason: The request does not meet the requirements.\n\n'
                      . 'Please contact the Registrar\'s Office for more information or submit a new request.\n\n'
                      . 'We apologize for any inconvenience.';
                break;
            default:
                $note = 'Your request status has been updated to: ' . $status;
                break;
        }
        
        // Update the request status
        $stmt = $conn->prepare("UPDATE requesttb SET status=? WHERE request_id=?");
        $stmt->bind_param("si", $status, $id);
        
        if ($stmt->execute()) {
            sendNotificationEmail($or_number, $email, $status, $note, $status);
            header("Location: ../requests.php?success=updated");
        } else {
            header("Location: ../requests.php?error=update_failed");
        }
    } else {
        header("Location: ../requests.php?error=request_not_found");
    }
    exit();
}

/* DELETE REQUEST */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Get request details before deleting
    $stmt1 = $conn->prepare("SELECT a.or_number, b.instiemail FROM requesttb a JOIN students b ON a.student_no = b.student_no WHERE a.request_id=?");
    $stmt1->bind_param("i", $id);    
    $stmt1->execute();
    $result = $stmt1->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $or_number = $row['or_number'];
        $email = $row['instiemail'];
        $status = "DELETED";
        
        $note = "🗑️ YOUR REQUEST HAS BEEN DELETED.\n\n"
              . "OR Number: " . $or_number . "\n\n"
              . "The request has been removed from the system.\n\n"
              . "If you believe this is a mistake, please contact the Registrar's Office.\n\n"
              . "You may submit a new request if needed.\n\n"
              . "We apologize for any inconvenience.";
        
        // Delete items first (FK safety)
        $stmt = $conn->prepare("DELETE FROM request_items WHERE request_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // Then delete the request
        $stmt = $conn->prepare("DELETE FROM requesttb WHERE request_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        sendNotificationEmail($or_number, $email, $status, $note, "DELETED BY ADMIN");
        header("Location: ../requests.php?success=deleted");
    } else {
        header("Location: ../requests.php?error=request_not_found");
    }
    exit();
}

header("Location: ../requests.php");
exit();
?>