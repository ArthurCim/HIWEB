<?php
include "db.php";

$id_user = '5ad04b1f-6f66-400e-a727-e5269184a2c3'; // azizaan

$result = $conn->prepare("SELECT order_id FROM transactions WHERE id_user = ? AND status = 'PENDING' ORDER BY created_at DESC LIMIT 1");
$result->bind_param("s", $id_user);
$result->execute();
$row = $result->get_result()->fetch_assoc();

if ($row) {
    echo $row['order_id'];
} else {
    echo "No pending transactions found";
}
