<?php
include __DIR__ . '/ssssendNotifMail.php';

$conn = new mysqli("localhost", "root", "1234", "plmun_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['send'])) {

    $id = $_POST['request_id'];
    $message = $_POST['message'];

    $stmt = $conn->prepare("
        SELECT a.or_number, b.instiemail, a.status
        FROM requesttb a 
        JOIN students b ON a.student_no = b.student_no 
        WHERE a.request_id=?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $or_number = $row['or_number'];
    $email = $row['instiemail'];
    $status = $row['status'];

    sendNotificationEmail(
        $or_number,
        $email,
        $status,
        $message,
        "checked by Business Center and sent a message"
    );
}

header("Location: ../business-center-home-page.php");
exit;