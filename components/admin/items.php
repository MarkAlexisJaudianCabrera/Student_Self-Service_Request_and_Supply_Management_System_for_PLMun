<?php
session_start();
include('../../config/db.php');

if (!isset($_SESSION['staffvalidated']) || $_SESSION['staffvalidated'] !== true) {
    header("Location: /404.php");
    exit();
}

$result = $conn->query("SELECT * FROM itemtb ORDER BY itemtbID ASC");

$editData = null;

if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM itemtb WHERE itemtbID=?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $resultEdit = $stmt->get_result();
    $editData = $resultEdit->fetch_assoc();
}

// Handle success/error messages
$message = '';
$messageType = '';
if (isset($_GET['success'])) {
    $message = $_GET['success'] == 'added' ? 'Item added successfully!' : ($_GET['success'] == 'updated' ? 'Item updated successfully!' : 'Item deleted successfully!');
    $messageType = 'success';
}
if (isset($_GET['error'])) {
    $message = 'Operation failed. Please try again.';
    $messageType = 'error';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Admin - Manage Items | PLMUN</title>
    <link rel="icon" type="image/x-icon" sizes="16x16" href="/assets/ico/logo16ico.ico">
    <link rel="icon" type="image/x-icon" sizes="32x32" href="/assets/ico/logo32ico.ico">
    <link rel="icon" type="image/x-icon" sizes="96x96" href="/assets/ico/logo96ico.ico">
    <link rel="icon" type="image/x-icon" sizes="192x192" href="/assets/ico/logo192ico.ico">
    <link rel="stylesheet" href="/assets/styles/allstyles.css">
    <link rel="stylesheet" href="/assets/styles/navbar.css">
    <link rel="stylesheet" href="/assets/styles/adminstyles/adminitems.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="/assets/ico/logo16ico.ico">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <!-- Top Navbar -->
    <nav class="navbar">
        <a href="/landingpage.html"><img src="/assets/img/schl_logo-1.png" alt="Logo"></a>
    </nav>

    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn" id="mobileMenuBtn">
        <i class="fas fa-bars"></i>
    </button>

    <div class="overlay" id="overlay"></div>

    <!-- Left Navbar -->
    <div class="left-navbar" id="leftNavbar">
        <?php include('../left-navbar.php'); ?>
    </div>

    <div class="adminitems-megacontainer">
        <!-- Header -->
        <div class="page-header">
            <h2><i class="fas fa-boxes"></i> Manage Items</h2>
            <p>Add, edit, or delete academic and supply items from the inventory</p>
        </div>

        <!-- Alert Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Form Section -->
        <div class="form-section">
            <h3><i class="fas <?php echo $editData ? 'fa-edit' : 'fa-plus-circle'; ?>"></i> <?php echo $editData ? 'Edit Item' : 'Add New Item'; ?></h3>
            <form method="POST" action="actions/item_action.php" id="itemForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label><i class="fas fa-barcode"></i> Item ID</label>
                        <input type="text" name="itemtbID" id="itemtbID" value="<?php echo htmlspecialchars($editData['itemtbID'] ?? ''); ?>" placeholder="e.g., ITEM001" required <?php echo $editData ? 'readonly' : ''; ?>>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Item Name</label>
                        <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($editData['name'] ?? ''); ?>" placeholder="Enter item name" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-list"></i> Category</label>
                        <select name="category" id="category" required>
                            <option value="">Select Category</option>
                            <option value="acaditem" <?php echo (isset($editData['category']) && $editData['category'] == 'acaditem') ? 'selected' : ''; ?>>📚 Academic Item</option>
                            <option value="suppitem" <?php echo (isset($editData['category']) && $editData['category'] == 'suppitem') ? 'selected' : ''; ?>>✏️ Supply Item</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-align-left"></i> Description</label>
                        <input type="text" name="description" id="description" value="<?php echo htmlspecialchars($editData['description'] ?? ''); ?>" placeholder="Enter description" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-dollar-sign"></i> Price</label>
                        <input type="number" step="0.01" name="price" id="price" value="<?php echo htmlspecialchars($editData['price'] ?? ''); ?>" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-cubes"></i> Stock Quantity</label>
                        <input type="number" name="stock_quantity" id="stock_quantity" value="<?php echo htmlspecialchars($editData['stock_quantity'] ?? ''); ?>" placeholder="0" required>
                    </div>
                </div>
                <div class="form-actions">
                    <?php if ($editData): ?>
                        <button type="submit" name="edit" class="btn-primary"><i class="fas fa-save"></i> Update Item</button>
                        <a href="items.php" class="btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="add" class="btn-primary"><i class="fas fa-plus"></i> Add Item</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Table Section -->
        <div class="table-section">
            <div class="table-header">
                <h3><i class="fas fa-table"></i> Items List</h3>
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search by name or ID...">
                    <button onclick="searchTable()"><i class="fas fa-search"></i></button>
                </div>
            </div>
            <div class="table-container">
                <table class="items-table" id="itemsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr class="clickable-row"
                                data-id="<?= htmlspecialchars($row['itemtbID']) ?>"
                                data-name="<?= htmlspecialchars($row['name']) ?>"
                                data-desc="<?= htmlspecialchars($row['description']) ?>"
                                data-price="<?= $row['price'] ?>"
                                data-stock="<?= $row['stock_quantity'] ?>"
                                data-cat="<?= $row['category'] ?>">
                                <td><?= htmlspecialchars($row['itemtbID']) ?></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= $row['category'] == 'acaditem' ? '📚 Academic' : '✏️ Supply' ?></td>
                                <td title="<?= htmlspecialchars($row['description']) ?>"><?= htmlspecialchars(substr($row['description'], 0, 50)) . (strlen($row['description']) > 50 ? '...' : '') ?></td>
                                <td>₱<?= number_format($row['price'], 2) ?></td>
                                <td><?= $row['stock_quantity'] ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a class="edit-btn" href="items.php?edit=<?= $row['itemtbID'] ?>"><i class="fas fa-edit"></i> Edit</a>
                                        <a class="del-btn" href="javascript:void(0)" onclick="confirmDelete('<?= $row['itemtbID'] ?>', '<?= htmlspecialchars($row['name']) ?>')"><i class="fas fa-trash"></i> Delete</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="no-data">
                                    <i class="fas fa-inbox"></i>
                                    <p>No items found</p>
                                    <small>Click "Add New Item" to create one</small>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const leftNavbar = document.getElementById('leftNavbar');
        const overlay = document.getElementById('overlay');

        function toggleMobileMenu() {
            leftNavbar.classList.toggle('open');
            overlay.classList.toggle('active');
            document.body.style.overflow = leftNavbar.classList.contains('open') ? 'hidden' : '';
        }

        function closeMobileMenu() {
            leftNavbar.classList.remove('open');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', toggleMobileMenu);
        }

        if (overlay) {
            overlay.addEventListener('click', closeMobileMenu);
        }

        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                closeMobileMenu();
            }
        });

        // Search function
        function searchTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('itemsTable');
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                const id = rows[i].getElementsByTagName('td')[0];
                const name = rows[i].getElementsByTagName('td')[1];
                if (id || name) {
                    const idValue = id ? id.textContent || id.innerText : '';
                    const nameValue = name ? name.textContent || name.innerText : '';
                    if (idValue.toLowerCase().indexOf(filter) > -1 || nameValue.toLowerCase().indexOf(filter) > -1) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            }
        }

        document.getElementById('searchInput').addEventListener('keyup', searchTable);

        // Confirm delete with SweetAlert
        function confirmDelete(itemId, itemName) {
            Swal.fire({
                title: 'Delete Item?',
                text: `Are you sure you want to delete "${itemName}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#2ecc71',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                background: '#2c3136',
                color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `actions/item_action.php?delete=${itemId}`;
                }
            });
        }

        // Populate form when clicking on table row
        document.querySelectorAll(".clickable-row").forEach(row => {
            row.addEventListener("click", (e) => {
                if (e.target.tagName === "A" || e.target.tagName === "BUTTON" || e.target.closest('.action-buttons')) return;
                
                document.getElementById("itemtbID").value = row.dataset.id;
                document.getElementById("name").value = row.dataset.name;
                document.getElementById("description").value = row.dataset.desc;
                document.getElementById("price").value = row.dataset.price;
                document.getElementById("stock_quantity").value = row.dataset.stock;
                document.getElementById("category").value = row.dataset.cat;
                
                // Change form to edit mode
                const form = document.getElementById('itemForm');
                const buttons = document.querySelector('.form-actions');
                buttons.innerHTML = `
                    <button type="submit" name="edit" class="btn-primary"><i class="fas fa-save"></i> Update Item</button>
                    <a href="items.php" class="btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                `;
                document.getElementById("itemtbID").readOnly = true;
                
                // Scroll to form
                document.querySelector('.form-section').scrollIntoView({ behavior: 'smooth' });
            });
        });
    </script>
</body>
</html>