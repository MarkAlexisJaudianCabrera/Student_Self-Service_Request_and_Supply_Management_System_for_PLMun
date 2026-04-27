<?php
session_start();
include('../../config/db.php');

    $result = $conn->query("
        SELECT 
            r.request_id,
            r.or_number,
            r.student_no,
            r.fullname,
            r.course,
            r.status,
            r.total_amount,
            i.name AS item_name,
            ri.quantity,
            ri.subtotal
        FROM requesttb r 
        JOIN request_items ri ON r.request_id = ri.request_id
        JOIN itemtb i ON ri.itemtbID = i.itemtbID
        WHERE r.status = 'Pending'
        ORDER BY r.request_id DESC"
    );
    
    $requests = [];

    while ($row = $result->fetch_assoc()) {
        $id = $row['request_id'];

        if (!isset($requests[$id])) {
            $requests[$id] = [
                "request_id" => $row['request_id'],
                "or_number" => $row['or_number'],
                "student_no" => $row['student_no'],
                "fullname" => $row['fullname'],
                "course" => $row['course'],
                "status" => $row['status'],
                "total_amount" => $row['total_amount'],
                "items" => []
            ];
        }

        $requests[$id]["items"][] = [
            "name" => $row['item_name'],
            "qty" => $row['quantity'],
            "subtotal" => $row['subtotal']
        ];
    }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cashier Dashboard - Student Self-Service Request and Supply Management System for PLMUN</title>
    <link rel="stylesheet" href="/assets/styles/cashier.css">
    <link rel="stylesheet" href="/assets/styles/allstyles.css">
    <link rel="stylesheet" href="/assets/styles/navbar.css">
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
    <div class="cashier-pending-container">
        <h2 id="reg-h2-style">Pay Requests - Cashier</h2>
        <p>This page displays all items, categorized by "Academic and Supply Items". Mark Requests as Paid.</p>
        <hr class="border-top">
        <div class="cashier-pending-table">
            <table border="1" width="100%">
                <tr>
                    <th class="grn-font">Oficcial Receipt</th>
                    <th>Student Number</th>
                    <th>Name</th>
                    <th>Course</th>
                    <th>Status</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Update Status</th>
                </tr>
                <?php foreach($requests as $row): ?>
                <tr class="HOVER">
                    <td class="a" name="$row['request_id']"><?= $row['or_number']; ?></td>
                    <td><?= $row['student_no']; ?></td>
                    <td><?= $row['fullname']; ?></td>
                    <td><?= $row['course']; ?></td>
                    <td><?= $row['status']; ?></td>

                    <td>
                        <div class="items-box a">
                            <?php foreach ($row['items'] as $item): ?>
                                <?= $item['name'] ?> (x<?= $item['qty']; ?>) - ₱<?= number_format($item['subtotal'],2); ?><br>
                            <?php endforeach; ?>
                        </div>
                    </td>

                    <td><b>₱<?= number_format($row['total_amount'],2); ?></b></td>
                    <td>
                        <div class="inline">
                           <form method="POST" action="actions/req-action.php">
                                <input type="hidden" name="id" value="<?= $row['request_id'] ?>">
                                <input type="hidden" name="status" value="Paid">
                                <button class="btn-default-style acpt" type="submit" name="update">
                                    Mark as Paid
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>