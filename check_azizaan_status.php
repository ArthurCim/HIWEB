<?php
// Test dashboard premium status logic for AZIZAAN (the actual logged-in user)
include "db.php";

// Get azizaan user
$user_query = $conn->prepare("SELECT id_user, nama, email, is_premium FROM users WHERE nama = 'azizaan'");
$user_query->execute();
$user_result = $user_query->get_result();

echo "=== CHECKING AZIZAAN USER STATUS ===\n\n";

if ($user_result->num_rows === 0) {
    echo "No user named 'azizaan' found\n";
    exit;
}

$users = [];
while ($row = $user_result->fetch_assoc()) {
    $users[] = $row;
}
$user_query->close();

echo "Found " . count($users) . " user(s) named 'azizaan':\n\n";

foreach ($users as $idx => $user) {
    $id_user = $user['id_user'];

    echo "User #" . ($idx + 1) . ":\n";
    echo "  ID: " . $id_user . "\n";
    echo "  Name: " . $user['nama'] . "\n";
    echo "  Email: " . $user['email'] . "\n";
    echo "  is_premium: " . $user['is_premium'] . "\n\n";

    // Check subscriptions for this user
    echo "  Subscriptions:\n";
    $sub_query = $conn->prepare(
        "SELECT id_subscription, id_plan, payment_status, start_date, end_date 
         FROM user_subscriptions 
         WHERE id_user = ? 
         ORDER BY start_date DESC"
    );
    $sub_query->bind_param("s", $id_user);
    $sub_query->execute();
    $sub_result = $sub_query->get_result();

    if ($sub_result->num_rows === 0) {
        echo "    (None)\n";
    } else {
        $sub_count = 0;
        while ($sub = $sub_result->fetch_assoc()) {
            $sub_count++;
            $is_active = $sub['end_date'] > date('Y-m-d H:i:s') ? "ACTIVE" : "EXPIRED";
            echo "    $sub_count) Plan: " . $sub['id_plan'] . " | Status: " . $sub['payment_status'] . " | $is_active | End: " . $sub['end_date'] . "\n";
        }
    }
    $sub_query->close();

    // Check transactions
    echo "\n  Transactions:\n";
    $trans_query = $conn->prepare(
        "SELECT id_transaction, id_plan, amount, status, created_at 
         FROM transactions 
         WHERE id_user = ? 
         ORDER BY created_at DESC LIMIT 3"
    );
    $trans_query->bind_param("s", $id_user);
    $trans_query->execute();
    $trans_result = $trans_query->get_result();

    if ($trans_result->num_rows === 0) {
        echo "    (None)\n";
    } else {
        $trans_count = 0;
        while ($trans = $trans_result->fetch_assoc()) {
            $trans_count++;
            echo "    $trans_count) Plan: " . $trans['id_plan'] . " | Amount: " . number_format($trans['amount'], 0, ',', '.') . " | Status: " . $trans['status'] . " | Created: " . $trans['created_at'] . "\n";
        }
    }
    $trans_query->close();

    echo "\n" . str_repeat("-", 60) . "\n\n";
}
