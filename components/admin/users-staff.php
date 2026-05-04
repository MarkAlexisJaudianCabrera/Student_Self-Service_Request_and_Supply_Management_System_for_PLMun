<?php
    session_start();
    include('../../config/db.php');
    $result = $conn->query("SELECT * FROM users WHERE role != 'student'");
?>
<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin - Student Self-Service Request and Supply Management System for PLMUN</title>
        <link rel="stylesheet" href="/assets/styles/allstyles.css">
        <link rel="stylesheet" href="/assets/styles/navbar.css">
        <link rel="stylesheet" href="/assets/styles/adminstyles/adminusrstaff.css">
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
            <h2>Staff Users</h2>
            <p>Manage staff user accounts</p>
            <form method="POST" action="actions/user_action.php">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <select name="role">
                    <option value="admin">Admin</option>
                    <option value="registrar">Registrar</option>
                    <option value="business">Business Center</option>
                    <option value="cashier">Cashier</option>
                </select>
                <button name="add">Add</button>
            </form>
            <div class="adminusr-table">
                <table border="1">
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['username'] ?></td>
                        <td><?= $row['role'] ?></td>
                        <td><?= $row['password'] ?></td>
                        <td>
                            <a href="actions/user_action.php?delete=<?= $row['id'] ?>">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>
    </body>
</html>