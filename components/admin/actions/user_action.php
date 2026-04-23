<?php
    $conn = new mysqli("localhost", "root", "1234", "plmun_db");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    if (isset($_POST['add'])) {
        $u = $_POST['username'];
        $p = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $r = $_POST['role'];

        $stmt = $conn->prepare("INSERT INTO users(username,password,role) VALUES(?,?,?)");
        $stmt->bind_param("sss", $u,$p,$r);
        $stmt->execute();
    }

    if (isset($_GET['delete'])) {
        $id = $_GET['delete'];
        $conn->query("DELETE FROM users WHERE id=$id");
    }

    header("Location: ../users-staff.php");