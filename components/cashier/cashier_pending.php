<?php
//session_start();
$conn = new mysqli("localhost", "root", "1234", "plmun_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$query = "
SELECT 
    r.request_id,
    r.or_number,
    r.student_no,
    r.fullname,
    r.course,
    r.status,
    i.name AS item_name,
    ri.quantity,
    i.price
FROM requesttb r
JOIN request_items ri ON r.request_id = ri.request_id
JOIN itemtb i ON ri.itemtbID = i.itemtbID
WHERE r.status = 'Unpaid'
ORDER BY r.request_id DESC
";

$result = $conn->query($query);

$requests = [];

while ($row = $result->fetch_assoc()) {

    $id = $row['request_id'];

    if (!isset($requests[$id])) {
        $requests[$id] = [
            "or_number" => $row['or_number'],
            "student_no" => $row['student_no'],
            "fullname" => $row['fullname'],
            "course" => $row['course'],
            "status" => $row['status'],
            "items" => [],
            "total" => 0
        ];
    }

    $subtotal = $row['price'] * $row['quantity'];

    $requests[$id]["items"][] = [
        "name" => $row['item_name'],
        "qty" => $row['quantity'],
        "subtotal" => $subtotal
    ];

    $requests[$id]["total"] += $subtotal;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cashier Dashboard</title>
    <link rel="stylesheet" href="/assets/styles/cashier.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
<div class="cashier-pending-container">
    <h2 id="reg-h2-style">Pending Requests - Cashier</h2>
    <p>Select a request to view its details.</p>
    <!-- REJECT MODAL -->
    <div id="rejectModal" style="display:none;">
        <textarea id="rejectNote" placeholder="Optional reason..."></textarea>
        <br>
        <button onclick="process('reject')">Confirm Reject</button>
        <button onclick="closeReject()">Cancel</button>
    </div>
    <hr class="border-top">
    <!-- TABLE -->
    <table border="1" width="100%">
        <tr>
            <th class="grn-font">Oficcial Receipt</th>
            <th>Student Number</th>
            <th>Name</th>
            <th>Course</th>
            <th>Status</th>
            <th>Items</th>
            <th>Total</th>
        </tr>

        <?php foreach ($requests as $id => $r): ?>
        <tr class="request-row" data-id="<?= $id; ?>">
            <td class="grn-font"><?= $r['or_number']; ?></td>
            <td><?= $r['student_no']; ?></td>
            <td><?= $r['fullname']; ?></td>
            <td><?= $r['course']; ?></td>
            <td><?= $r['status']; ?></td>

            <td>
                <div class="items-box grn-font">
                    <?php foreach ($r['items'] as $item): ?>
                        <?= $item['name']; ?> (x<?= $item['qty']; ?>) - ₱<?= number_format($item['subtotal'],2); ?><br>
                    <?php endforeach; ?>
                </div>
            </td>

            <td><b>₱<?= number_format($r['total'],2); ?></b></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <br>

    <button class="btn-default-style" onclick="process('accept')">Accept Payment</button>
    <button class="btn-default-style" onclick="openReject()">Reject</button>
</div>
<script>
let selected = [];

document.querySelectorAll(".request-row").forEach(row => {
    row.addEventListener("click", () => {

        let id = row.dataset.id;

        row.classList.toggle("selected");

        if (selected.includes(id)) {
            selected = selected.filter(x => x !== id);
        } else {
            selected.push(id);
        }
    });
});

function openReject() {
    document.getElementById("rejectModal").style.display = "block";
}

function closeReject() {
    document.getElementById("rejectModal").style.display = "none";
}

function process(action) {

    if (selected.length === 0) {
        Swal.fire({ title: "No Requests Selected", text: "Please select at least one request.", confirmButtonText: "OK" });
        return;
    }

    let note = document.getElementById("rejectNote").value;

    fetch("process_request.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            ids: selected,
            action: action,
            note: note
        })
    })
    .then(res => res.json())
    .then(data => {
        Swal.fire({ title: "Request Processed", text: data.message, confirmButtonText: "OK" }).then(() => {
            location.reload();
        });
    });
}
</script>

</body>
</html>