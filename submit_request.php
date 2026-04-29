<?php 
    ob_clean();
    header('Content-Type: application/json');
    ini_set('display_errors', 0);
    error_reporting(E_ALL);   
    session_start(); 

    include('./config/db.php'); 
    include('./components/sendNotifMail.php');
    $session_id = session_id(); 
    
    // generate OR number 
    $or_number = "OR-" . time() . rand(100000, 999999); 

    // insert into request table 
    $fullname = $_SESSION['fullname']; 
    $course = $_SESSION['course']; 
    $student_no = $_SESSION['student_no']; 

    $total = 0;
    
    // compute total
    $result = $conn->query("SELECT t.quantity, i.price FROM tempreqitemtb t JOIN itemtb i ON t.itemtbID = i.itemtbID WHERE t.session_id = '$session_id'");
    while ($row = $result->fetch_assoc()) {
        $total += $row['quantity'] * $row['price'];
    }
    $conn->query("INSERT INTO requesttb (or_number, student_no, fullname, course, total_amount)VALUES ('$or_number', '$student_no', '$fullname', '$course', '$total')");
    $request_id = $conn->insert_id; 

    // move items from tempreqitemtb to request_items
    $conn->query("INSERT INTO request_items (request_id, itemtbID, quantity, price, subtotal) SELECT $request_id, t.itemtbID, t.quantity, i.price, (t.quantity * i.price) FROM tempreqitemtb t JOIN itemtb i ON t.itemtbID = i.itemtbID WHERE t.session_id = '$session_id'");
    
    $conn->query("DELETE FROM tempreqitemtb WHERE session_id = '$session_id'"); 
    
    sendNotificationEmail($or_number, $_SESSION['email'], 'PENDING', 'WAIT FOR NOTIFICATION THAT ACCEPTS THIS REQUEST', 'SUBMITTED');

    ob_clean();
    echo json_encode([
        "success" => true,
        "or_number" => $or_number,
        "qr_data" => $or_number
    ]);
    exit();