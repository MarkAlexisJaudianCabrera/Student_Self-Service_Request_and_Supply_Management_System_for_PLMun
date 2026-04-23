<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

ob_start();

session_start();
include('../config/db.php');
include('./sendNotifMail.php');

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$ids = $data['ids'];
$action = $data['action'];
$note = $data['note'] ?? "";

foreach ($ids as $id) {

    $stmt = $conn->prepare("
        SELECT r.or_number, s.instiemail
        FROM requesttb r
        JOIN students s ON r.student_no = s.student_no
        WHERE r.request_id = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    if (!$res) continue;

    $or = $res['or_number'];
    $email = $res['instiemail'];

    if ($action === "accept") {

        $q = $conn->prepare("
            UPDATE requesttb 
            SET status = 'Unpaid'
            WHERE request_id = ?
        ");
        $q->bind_param("i", $id);
        $q->execute();

        try {
            sendApprovalEmail($or, $email);
        } catch (Exception $e) {
            error_log("Email failed: " . $e->getMessage());
        }

    } else if ($action === "reject") {

        $q = $conn->prepare("
            UPDATE requesttb 
            SET status = 'Rejected'
            WHERE request_id = ?
        ");
        $q->bind_param("i", $id);
        $q->execute();

        sendRejectionEmail($or, $email, $note);
    }
}

if (!$data || !isset($data['ids'])) {
    ob_clean();
    echo json_encode(["error" => "Invalid request"]);
    exit;
}