<?php
session_start();
include('./config/db.php');

$session_id = session_id();

// generate OR number
$or_number = "OR-" . rand(100000, 999999);

// insert into request table
$fullname = $_SESSION['fullname'];
$course = $_SESSION['course'];
$student_no = $_SESSION['student_no'];

$conn->query("
INSERT INTO requesttb (or_number, student_no, fullname, course, total_amount)
VALUES ('$or_number', '$student_no', '$fullname', '$course', 0)
");

$request_id = $conn->insert_id;

// move items
$conn->query("
INSERT INTO request_items (request_id, itemtbID, quantity)
SELECT $request_id, itemtbID, quantity
FROM tempreqitemtb
WHERE session_id = '$session_id'
");

// clear temp
$conn->query("DELETE FROM tempreqitemtb WHERE session_id = '$session_id'");

echo $or_number;
?>