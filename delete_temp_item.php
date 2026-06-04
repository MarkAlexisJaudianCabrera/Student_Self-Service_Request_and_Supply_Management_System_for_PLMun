<?php
session_start();

if (!isset($_SESSION['validated']) || $_SESSION['validated'] !== true) {
    echo "unauthorized";
    exit();
}

include('config/db.php');

$temp_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($temp_id > 0) {
    $stmt = $conn->prepare("DELETE FROM tempreqitemtb WHERE temp_id = ?");
    $stmt->bind_param("i", $temp_id);
    $stmt->execute();
    echo "success";
} else {
    echo "failed";
}

$conn->close();
?>