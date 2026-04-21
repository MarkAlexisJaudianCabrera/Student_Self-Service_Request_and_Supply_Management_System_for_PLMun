<?php
session_start();
header("Content-Type: application/json");

// DATABASE CONNECTION
$host = "localhost";
$user = "root";
$pass = "1234";
$db   = "plmun_db"; 

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    echo json_encode(["error" => "Database connection failed"]);
    exit();
}

// GET POST DATA
$student_no = $_POST['student_no'] ?? '';
$instiemail = $_POST['instiemail'] ?? '';




// VALIDATION (basic)
if (empty($student_no) || empty($instiemail)) {
    echo json_encode(["error" => "Missing input"]);
    exit();
}

// PREPARED STATEMENT (SAFE)
$sql = "SELECT fullname, course FROM students WHERE student_no = ? AND instiemail = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $student_no, $instiemail);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    $_SESSION['validated'] = true;
    $_SESSION['student_no'] = $student_no;
    $_SESSION['fullname'] = $row['fullname'];
    $_SESSION['course'] = $row['course'];

    echo json_encode([
        "success" => true,
        "fullname" => $row['fullname'],
        "course" => $row['course']
    ]);
} else {
    $_SESSION['validated'] = false;

    echo json_encode([
        "success" => false,
        "fullname" => "Student not found",
        "course" => "Invalid credentials"
    ]);
}

$stmt->close();
$conn->close();
?>

