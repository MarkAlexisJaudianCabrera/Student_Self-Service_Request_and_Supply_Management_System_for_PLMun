<?php
session_start();

if (!isset($_SESSION['validated']) || $_SESSION['validated'] !== true) {
    echo "unauthorized";
    exit();
}

include('config/db.php');

$session_id = $_SESSION['temp_session_id'] ?? null;

if ($session_id) {
    $stmt = $conn->prepare("DELETE FROM tempreqitemtb WHERE session_id = ?");
    $stmt->bind_param("s", $session_id);
    $stmt->execute();
    
    // Clear the session cart ID
    unset($_SESSION['temp_session_id']);
}

echo "success";
$conn->close();
?>