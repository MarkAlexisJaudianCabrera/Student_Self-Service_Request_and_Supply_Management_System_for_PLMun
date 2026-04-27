<?php
    include __DIR__ . '/sssssendNotifMail.php';

    $conn = new mysqli("localhost", "root", "1234", "plmun_db");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    /* UPDATE STATUS */
    if (isset($_POST['update'])) {

        $id = $_POST['id'];
        $status = $_POST['status'];

        $stmt = $conn->prepare("
            UPDATE requesttb
            SET status=?
            WHERE request_id=?
        ");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();

        $stmt1 = $conn->prepare("SELECT a.or_number, b.instiemail FROM requesttb a JOIN students b ON a.student_no = b.student_no WHERE a.request_id=?");
        $stmt1->bind_param("i", $id);    
        $stmt1->execute();
        $result = $stmt1->get_result();
        $row = $result->fetch_assoc();
        $or_number = $row['or_number'];
        $email = $row['instiemail'];
        switch ($status){
        
            case 'Paid':
                $note = 'THIS REQUEST WAS PAID AT CASHIER, YOU MAY NOW PROCEED FOR THE PAYMENT AT UNIVERSITY CASHIER';
                break;
        }

        sendNotificationEmail($or_number, $email, $status, $note, $status); 

    }

    header("Location: ../request-payment-page.php");
    exit;