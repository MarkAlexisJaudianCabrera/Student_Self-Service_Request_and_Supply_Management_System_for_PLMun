<?php
    include __DIR__ . '/sssendNotifMail.php';

    $conn = new mysqli("localhost", "root", "1234", "plmun_db");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    /* UPDATE STATUS */
    if (isset($_POST['update'])) {

        $id = $_POST['id'];
        $status = $_POST['status'];
        $message = $_POST['message'];

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
        
            case 'Unpaid':
                $note = 'THIS REQUEST WAS ACCEPTED BY REGISTRAR, YOU MAY NOW PROCEED FOR THE PAYMENT AT THE TREASURY OFFICE';
                break;
        }

        sendNotificationEmail($or_number, $email, $status, $note, $status); 

    }

    /* DELETE REQUEST */
    if (isset($_GET['delete'])) {

        $id = $_POST['id'];
        $id = $_GET['delete'];

        $stmt1 = $conn->prepare("SELECT a.or_number, b.instiemail FROM requesttb a JOIN students b ON a.student_no = b.student_no WHERE a.request_id=?");
        $stmt1->bind_param("i", $id);    
        $stmt1->execute();
        $result = $stmt1->get_result();
        $row = $result->fetch_assoc();
        $or_number = $row['or_number'];
        $email = $row['instiemail'];
        $status = "Rejected";

        // delete items first (FK safety)
        $stmt = $conn->prepare("DELETE FROM request_items WHERE request_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM requesttb WHERE request_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        sendNotificationEmail($or_number, $email, $status, 'UNFORTUNATELY THIS REQUEST WAS REJECTED BY REGISTRAR, CONTACT THE REGISTRAR OR REQUEST AGAIN', $status." BY REGISTRAR"); 
    }

    header("Location: ../registrar-home-page.php");
    exit;