<?php
    session_start();
    include('../../config/db.php');
    $result = $conn->query("SELECT * FROM students");
?>
<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin - Student Self-Service Request and Supply Management System for PLMUN</title>
        <link rel="stylesheet" href="/assets/styles/allstyles.css">
        <link rel="stylesheet" href="/assets/styles/navbar.css">
        <link rel="stylesheet" href="/assets/styles/adminstyles/adminusrstud.css">
        <link rel="icon" type="image/x-icon" href="/assets/ico/logo16ico.ico" >
        <link rel="icon" type="image/x-icon" href="/assets/ico/logo32ico.ico" >
        <link rel="icon" type="image/x-icon" href="/assets/ico/logo96ico.ico" >
        <link rel="icon" type="image/x-icon" href="/assets/ico/logo192ico.ico" >
    </head>
    <body>
        <nav class="navbar">
            <a href="/landingpage.html"><img src="/assets/img/schl_logo-1.png" alt="Logo"></a>
        </nav>
        <?php include('../left-navbar.php'); ?>
        <div class="adminusr-megacontainer">
            <h2>Students</h2>
            <p>Manage student accounts</p>
            <form method="POST" action="actions/student_action.php">
                <input name="student_no" placeholder="Student Number" required>
                <input name="instiemail" placeholder="Institutional Email" required>
                <input name="fullname" placeholder="Fullname" required>
                <input name="course" placeholder="Course" required>
                <input name="year" placeholder="Year" required>
                <button name="add">Add</button>
            </form>

            <table border="1">
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['student_no'] ?></td>
                    <td><?= $row['fullname'] ?></td>
                    <td><?= $row['instiemail'] ?></td>
                    <td><?= $row['course'] ?></td>
                    <td><?= $row['year'] ?></td>
                    <td>
                        <a href="actions/student_action.php?delete=<?= $row['id'] ?>">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </table>
        </div>
    </body>
</html>