<?php
session_start();

if (!isset($_SESSION['validated']) || $_SESSION['validated'] !== true) {
    echo "";
    exit();
}

include('config/db.php');

$studentNo = $_SESSION['student_no'] ?? $_SESSION['student_number'] ?? null;

if (!$studentNo) {
    echo '<div class="empty-cart">
            <i class="fa-solid fa-cart-shopping"></i>
            <p>Please login to continue</p>
          </div>';
    exit();
}

$session_id = $_SESSION['temp_session_id'] ?? session_id() . '_' . time();
$_SESSION['temp_session_id'] = $session_id;

$query = "SELECT t.*, i.name, i.price 
          FROM tempreqitemtb t
          JOIN itemtb i ON t.itemtbID = i.itemtbID
          WHERE t.session_id = ?
          ORDER BY t.date_added DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $session_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="empty-cart">
            <i class="fa-solid fa-cart-shopping"></i>
            <p>No items requested yet</p>
            <small>Click on any item above to add to your request</small>
          </div>';
    echo '<div class="cart-footer" style="display: none;"></div>';
} else {
    $total = 0;
    while ($row = $result->fetch_assoc()) {
        $subtotal = $row['price'] * $row['quantity'];
        $total += $subtotal;
        ?>
        <div class="temp-item">
            <div class="item-info">
                <div class="item-title">
                    <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                    <?php if (!empty($row['size'])): ?>
                        <span class="size-tag"><?php echo htmlspecialchars($row['size']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="item-details">
                    <span class="item-qty">
                        <i class="fa-solid fa-cube"></i> Qty: <?php echo $row['quantity']; ?>
                    </span>
                    <span class="item-price">
                        <i class="fa-solid fa-tag"></i> ₱<?php echo number_format($row['price'], 2); ?>
                    </span>
                </div>
            </div>
            <div class="right-side">
                <span class="subtotal">₱<?php echo number_format($subtotal, 2); ?></span>
                <i class="fa-solid fa-trash-can delete-btn" data-id="<?php echo $row['temp_id']; ?>" title="Remove item"></i>
            </div>
        </div>
        <?php
    }
    ?>
    <div class="cart-footer">
        <div class="total-amount">
            <span>Total Amount:</span>
            <strong>₱<?php echo number_format($total, 2); ?></strong>
        </div>
    </div>
    <?php
}
$conn->close();
?>