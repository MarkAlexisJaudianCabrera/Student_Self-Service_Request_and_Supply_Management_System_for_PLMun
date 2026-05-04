<?php
session_start();
include('../../config/db.php');
$result = $conn->query("SELECT * FROM itemtb");

$editData = null;

if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM itemtb WHERE itemtbID=?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $resultEdit = $stmt->get_result();
    $editData = $resultEdit->fetch_assoc();
}
?>
<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin - Student Self-Service Request and Supply Management System for PLMUN</title>
        <link rel="stylesheet" href="/assets/styles/allstyles.css">
        <link rel="stylesheet" href="/assets/styles/navbar.css">
        <link rel="stylesheet" href="/assets/styles/adminstyles/adminitems.css">
        <link rel="icon" type="image/x-icon" href="/assets/ico/logo16ico.ico" >
        <link rel="icon" type="image/x-icon" href="/assets/ico/logo32ico.ico" >
        <link rel="icon" type="image/x-icon" href="/assets/ico/logo96ico.ico" >
        <link rel="icon" type="image/x-icon" href="/assets/ico/logo192ico.ico" >
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
        <nav class="navbar">
            <a href="/landingpage.html"><img src="/assets/img/schl_logo-1.png" alt="Logo"></a>
        </nav>
        <?php include('../left-navbar.php'); ?>
        <div class="adminitems-megacontainer">
            <h2>Manage Academic Items and Supply Items</h2>
            <p>Add, edit, or delete academic and supply items from the inventory.</p>
            <form method="POST" action="actions/item_action.php">
                <input name="itemtbID" placeholder="ID (REG001)" required>
                <input name="name" placeholder="Name" required>
                <input name="price" placeholder="Price (0-999.99)" type="number" step="0.01" required>
                <input name="stock_quantity" placeholder="Stock Quantity (0-999)" type="number" required>
                <select name="category" required>
                    <option value="">Select Category</option>
                    <option value="acaditem">Academic</option>
                    <option value="suppitem">Supply</option>
                </select>
                <button name="add">Add</button>
            </form>

            <table border="1">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Stock Quantity</th>
                    <th>Actions</th>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['itemtbID'] ?></td>
                    <td><?= $row['name'] ?></td>
                    <td><?= $row['category'] ?></td>
                    <td><?= $row['description'] ?? 'N/A' ?></td>
                    <td><?= $row['price'] ?></td>
                    <td><?= $row['stock_quantity'] ?></td>
                    <td>
                        <a href="actions/item_action.php?delete=<?= $row['itemtbID'] ?>">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
     </body>
</html>   