<?php
session_start();
include('./config/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemtbID = $_POST['itemtbID'];
    $qty = $_POST['qty'];

    // optional: track per user/session
    $session_id = session_id();

    $stmt = $conn->prepare("
    INSERT INTO tempreqitemtb (session_id, itemtbID, quantity)
    VALUES (?, ?, ?)
    ");
    $stmt->bind_param("ssi", $session_id, $itemtbID, $qty);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
}
?>