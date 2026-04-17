<?php
include("config/db.php");

$res = $conn->query("SELECT COUNT(*) as total 
FROM requests WHERE status='Pending'");

$row = $res->fetch_assoc();

echo $row['total'];
?>