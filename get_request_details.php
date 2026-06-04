<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['validated']) || $_SESSION['validated'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include('config/db.php');

$request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;

if ($request_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
    exit();
}

$student_no = $_SESSION['verified_student_no'] ?? $_SESSION['student_no'] ?? null;

$query = "SELECT * FROM requesttb WHERE request_id = ? AND student_no = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $request_id, $student_no);
$stmt->execute();
$requestResult = $stmt->get_result();

if ($requestResult->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Request not found']);
    exit();
}

$request = $requestResult->fetch_assoc();

$itemsQuery = "
    SELECT ri.*, i.name, i.category 
    FROM request_items ri
    JOIN itemtb i ON ri.itemtbID = i.itemtbID
    WHERE ri.request_id = ?
";
$stmt = $conn->prepare($itemsQuery);
$stmt->bind_param("i", $request_id);
$stmt->execute();
$itemsResult = $stmt->get_result();

$items = [];
while ($row = $itemsResult->fetch_assoc()) {
    $items[] = [
        'name' => $row['name'],
        'quantity' => $row['quantity'],
        'price' => $row['price'],
        'subtotal' => $row['subtotal'],
        'size' => $row['size'] ?? null
    ];
}

echo json_encode([
    'success' => true,
    'request' => $request,
    'items' => $items
]);

$conn->close();
?>