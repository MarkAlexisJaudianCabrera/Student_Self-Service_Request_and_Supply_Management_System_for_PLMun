<?php
session_start();

if (!isset($_SESSION['validated']) || $_SESSION['validated'] !== true) {
    echo "unauthorized";
    exit();
}

include('config/db.php');

$itemtbID = isset($_POST['itemtbID']) ? $_POST['itemtbID'] : '';
$qty = isset($_POST['qty']) ? intval($_POST['qty']) : 1;
$size = isset($_POST['size']) ? trim($_POST['size']) : null;

if (empty($itemtbID)) {
    echo "invalid_id";
    exit();
}

$studentNo = $_SESSION['student_no'] ?? $_SESSION['student_number'] ?? null;

if (!$studentNo) {
    echo "no_session";
    exit();
}

// Generate session_id for cart
if (!isset($_SESSION['temp_session_id'])) {
    $_SESSION['temp_session_id'] = session_id() . '_' . time();
}
$session_id = $_SESSION['temp_session_id'];

// Check if item exists
$checkItem = $conn->prepare("SELECT * FROM itemtb WHERE itemtbID = ? AND stock_quantity >= ?");
$checkItem->bind_param("si", $itemtbID, $qty);
$checkItem->execute();
$itemResult = $checkItem->get_result();

if ($itemResult->num_rows === 0) {
    echo "item_not_found";
    exit();
}

// Check if item already exists with same size
$checkExisting = $conn->prepare("SELECT * FROM tempreqitemtb WHERE session_id = ? AND itemtbID = ? AND (size = ? OR (size IS NULL AND ? IS NULL))");
$checkExisting->bind_param("ssss", $session_id, $itemtbID, $size, $size);
$checkExisting->execute();
$existingResult = $checkExisting->get_result();

if ($existingResult->num_rows > 0) {
    $existing = $existingResult->fetch_assoc();
    $newQty = $existing['quantity'] + $qty;
    $update = $conn->prepare("UPDATE tempreqitemtb SET quantity = ? WHERE temp_id = ?");
    $update->bind_param("ii", $newQty, $existing['temp_id']);
    $update->execute();
    echo "success";
} else {
    $insert = $conn->prepare("INSERT INTO tempreqitemtb (session_id, itemtbID, quantity, size) VALUES (?, ?, ?, ?)");
    $insert->bind_param("ssis", $session_id, $itemtbID, $qty, $size);
    $insert->execute();
    echo "success";
}

$conn->close();
?>