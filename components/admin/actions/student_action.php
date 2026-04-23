<?php
    $conn = new mysqli("localhost", "root", "1234", "plmun_db");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    /* ADD STUDENT  */
    if (isset($_POST['add'])) {
        $id   = rand(100000,999999);
        $no   = $_POST['student_no'];
        $email= $_POST['instiemail'];
        $name = $_POST['fullname'];
        $course = $_POST['course'];
        $year   = $_POST['year'];

        $stmt = $conn->prepare("
            INSERT INTO students(id,student_no,instiemail,fullname,course,year)
            VALUES(?,?,?,?,?,?)
        ");
        $stmt->bind_param("ssssss", $id,$no,$email,$name,$course,$year);
        $stmt->execute();
    }

    /* EDIT STUDENT */
    if (isset($_POST['edit'])) {

        $id   = $_POST['id'];
        $no   = $_POST['student_no'];
        $email= $_POST['instiemail'];
        $name = $_POST['fullname'];
        $course = $_POST['course'];
        $year   = $_POST['year'];

        $stmt = $conn->prepare("
            UPDATE students
            SET student_no=?, instiemail=?, fullname=?, course=?, year=?
            WHERE id=?
        ");
        $stmt->bind_param("sssssi", $no,$email,$name,$course,$year,$id);
        $stmt->execute();
    }

    /* DELETE STUDENT */
    if (isset($_GET['delete'])) {

        $id = $_GET['delete'];

        $stmt = $conn->prepare("DELETE FROM students WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    header("Location: ../users-student.php");