<?php
    session_start();
    include('../../config/db.php');
    $result = $conn->query("SELECT * FROM requesttb ORDER BY request_id DESC");
?>
<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin - Student Self-Service Request and Supply Management System for PLMUN</title>
        <link rel="stylesheet" href="/assets/styles/allstyles.css">
        <link rel="stylesheet" href="/assets/styles/navbar.css">
        <link rel="stylesheet" href="/assets/styles/adminstyles/adminreq.css">
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
        <div class="adminreq-megacontainer">
            <h2>Requests</h2>
            <p>Manage and update student requests</p>
            <div class="adminreq-table">
                <table border="1">
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['or_number'] ?></td>
                        <td><?= $row['status'] ?></td>

                        <td>
                            <form method="POST" action="actions/request_action.php">
                                <input type="hidden" name="id" value="<?= $row['request_id'] ?>">
                                <select name="status">
                                    <option>Pending</option>
                                    <option>Unpaid</option>
                                    <option>Paid</option>
                                    <option>Completed</option>
                                    <option>Rejected</option>
                                </select>
                                <button name="update">Update</button>
                            </form>

                            <a href="actions/request_action.php?delete=<?= $row['request_id'] ?>">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
            </div>
        </div>
     </body>
</html>   