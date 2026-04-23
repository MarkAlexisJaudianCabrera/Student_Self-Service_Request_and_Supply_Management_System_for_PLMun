<?php
    $conn = new mysqli("localhost", "root", "1234", "plmun_db");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    /* ===== UPDATE STATUS ===== */
    if (isset($_POST['update'])) {

        $id = $_POST['id'];
        $status = $_POST['status'];

        $stmt = $conn->prepare("
            UPDATE requesttb
            SET status=?
            WHERE request_id=?
        ");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
    }

    /* ===== DELETE REQUEST ===== */
    if (isset($_GET['delete'])) {

        $id = $_GET['delete'];

        // delete items first (FK safety)
        $stmt = $conn->prepare("DELETE FROM request_items WHERE request_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM requesttb WHERE request_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    header("Location: ../requests.php");
    exit;