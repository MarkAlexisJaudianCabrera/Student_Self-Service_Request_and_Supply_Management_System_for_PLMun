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
            <div class="adminitems-title">  
                <form method="POST" action="actions/item_action.php">
                    <input name="itemtbID" value="<?= $editData['itemtbID'] ?? '' ?>" required placeholder="ID (REG001)" id="itemtbID">
                    <input name="name" value="<?= $editData['name'] ?? '' ?>" required placeholder="Name" id="name">
                    <input name="description" value="<?= $editData['description'] ?? '' ?>" required placeholder="Description" id="description">
                    <input name="price" value="<?= $editData['price'] ?? '' ?>" type="number" step="0.01" required placeholder="Price (0-999.99)" id="price">
                    <input name="stock_quantity" value="<?= $editData['stock_quantity'] ?? '' ?>" type="number" required placeholder="Stock Quantity (0-999)" id="stock_quantity">
                    <select name="category" required id="category">
                        <option value="">Select Category</option>
                        <option value="acaditem">Academic</option>
                        <option value="suppitem">Supply</option>
                    </select>
                    <button name="<?= $editData ? 'edit' : 'add' ?>">
                        <?= $editData ? 'Update' : 'Add' ?>
                    </button>
                </form>
            </div>
            <div class="adminitems-table">
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
                    <tr class="clickable-row"
                        data-id="<?= $row['itemtbID'] ?>"
                        data-name="<?= $row['name'] ?>"
                        data-desc="<?= $row['description'] ?>"
                        data-price="<?= $row['price'] ?>"
                        data-stock="<?= $row['stock_quantity'] ?>"
                        data-cat="<?= $row['category'] ?>">
                        <td><?= $row['itemtbID'] ?></td>
                        <td><?= $row['name'] ?></td>
                        <td><?= $row['category'] ?></td>
                        <td><?= $row['description'] ?? 'N/A' ?></td>
                        <td>₱<?= number_format($row['price'], 2) ?></td>
                        <td><?= $row['stock_quantity'] ?></td>
                        <td class="inl-tb">
                            <a class="edit-btn" href="items.php?edit=<?= $row['itemtbID'] ?>">Edit</a>
                            <br><br>
                            <a class="del-btn" href="actions/item_action.php?delete=<?= $row['itemtbID'] ?>">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>
        <script>
            document.querySelectorAll(".clickable-row").forEach(row => {
                row.addEventListener("click", (e) => {
                    if (e.target.tagName === "A" || e.target.tagName === "BUTTON") return;

                    document.getElementById("itemtbID").value = row.dataset.id;
                    document.getElementById("name").value = row.dataset.name;
                    document.getElementById("description").value = row.dataset.desc;
                    document.getElementById("price").value = row.dataset.price;
                    document.getElementById("stock_quantity").value = row.dataset.stock;
                    document.getElementById("category").value = row.dataset.cat;
                    let btn = document.querySelector("button[name='add'], button[name='edit']");
                    btn.name = "edit";
                    btn.textContent = "Update";
                    document.getElementById("itemtbID").readOnly = true;
                });
            });
        </script>
     </body>
</html>   