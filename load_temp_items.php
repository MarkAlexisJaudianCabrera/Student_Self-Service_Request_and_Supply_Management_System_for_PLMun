<?php
    session_start();
    include('./config/db.php');

    $session_id = session_id();

    $stmt = $conn->prepare("
        SELECT t.temp_id, t.quantity, i.name, i.price
        FROM tempreqitemtb t
        JOIN itemtb i ON t.itemtbID = i.itemtbID
        WHERE t.session_id = ?
    ");
    $stmt->bind_param("s", $session_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $subtotal = $row['price'] * $row['quantity'];

        echo "
        <div class='temp-item'>
            <span class='item-name'>{$row['name']}</span>

            <div class='right-side'>
                <span class='qty'>Qty: {$row['quantity']}</span>
                <span class='subtotal'>₱" . number_format($subtotal, 2) . "</span>
                <i class='fa-solid fa-trash delete-btn' data-id='{$row['temp_id']}'></i>
            </div>
        </div>
        ";
    }
?>