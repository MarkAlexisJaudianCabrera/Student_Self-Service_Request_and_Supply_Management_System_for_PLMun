<?php 
    ob_clean();
    header('Content-Type: application/json');
    ini_set('display_errors', 0);
    error_reporting(E_ALL);   
    session_start(); 

    include('./config/db.php'); 
    include('./components/sendNotifMail.php');
    
    // Check if user is validated
    if (!isset($_SESSION['validated']) || $_SESSION['validated'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    // Use the stored session_id from cart
    $session_id = $_SESSION['temp_session_id'] ?? session_id();
    
    // Check if cart has items
    $checkCart = $conn->query("SELECT COUNT(*) as count FROM tempreqitemtb WHERE session_id = '$session_id'");
    $cartCount = $checkCart->fetch_assoc()['count'];
    
    if ($cartCount == 0) {
        echo json_encode(['success' => false, 'message' => 'No items in cart']);
        exit();
    }
    
    // generate OR number 
    $or_number = "OR-" . time() . rand(100000, 999999); 

    // Get student info from session
    $fullname = $_SESSION['fullname'] ?? 'N/A'; 
    $course = $_SESSION['course'] ?? 'N/A'; 
    $student_no = $_SESSION['student_no'] ?? 'N/A'; 
    $email = $_SESSION['email'] ?? 'N/A';

    // compute total
    $total = 0;
    $result = $conn->query("SELECT t.quantity, i.price FROM tempreqitemtb t JOIN itemtb i ON t.itemtbID = i.itemtbID WHERE t.session_id = '$session_id'");
    while ($row = $result->fetch_assoc()) {
        $total += $row['quantity'] * $row['price'];
    }
    
    // Insert into requesttb with total_amount
    $insertRequest = $conn->prepare("INSERT INTO requesttb (or_number, student_no, fullname, course, total_amount, status, date_requested) VALUES (?, ?, ?, ?, ?, 'Pending', NOW())");
    $insertRequest->bind_param("ssssd", $or_number, $student_no, $fullname, $course, $total);
    
    if (!$insertRequest->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to create request: ' . $conn->error]);
        exit();
    }
    
    $request_id = $conn->insert_id; 

    // move items from tempreqitemtb to request_items
    $moveItems = $conn->query("INSERT INTO request_items (request_id, itemtbID, quantity, price, subtotal) SELECT $request_id, t.itemtbID, t.quantity, i.price, (t.quantity * i.price) FROM tempreqitemtb t JOIN itemtb i ON t.itemtbID = i.itemtbID WHERE t.session_id = '$session_id'");
    
    if (!$moveItems) {
        echo json_encode(['success' => false, 'message' => 'Failed to move items: ' . $conn->error]);
        exit();
    }
    
    // Clear the cart
    $conn->query("DELETE FROM tempreqitemtb WHERE session_id = '$session_id'");
    
    // Clear session cart ID
    unset($_SESSION['temp_session_id']);
    
    // Send notification email
    if (function_exists('sendNotificationEmail')) {
        sendNotificationEmail($or_number, $email, 'PENDING', 'Your request has been submitted and is waiting for approval.', 'SUBMITTED');
    }
    
    ob_clean();
    echo json_encode([
        "success" => true,
        "or_number" => $or_number,
        "total_amount" => $total,
        "message" => "Request submitted successfully"
    ]);
    exit();
?>