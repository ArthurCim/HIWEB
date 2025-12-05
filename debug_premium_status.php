<?php
session_start();
include __DIR__ . '/db.php';

$id_user = $_SESSION['id_user'] ?? null;

if (!$id_user) {
    echo "Not logged in\n";
    exit;
}

echo "=== PREMIUM STATUS DIAGNOSTIC ===\n";
echo "User ID: $id_user\n\n";

// Check 1: users.is_premium column
$user_query = $conn->prepare("SELECT id_user, nama, email, is_premium FROM users WHERE id_user = ?");
$user_query->bind_param("s", $id_user);
$user_query->execute();
$user_result = $user_query->get_result();

if ($user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
    echo "1. users.is_premium = " . $user['is_premium'] . " (0=Free, 1=Premium)\n";
} else {
    echo "1. User not found\n";
}
$user_query->close();

// Check 2: user_subscriptions with valid end_date
$sub_query = $conn->prepare(
    "SELECT id_subscription, id_user, id_plan, start_date, end_date, payment_status 
     FROM user_subscriptions 
     WHERE id_user = ? AND payment_status = 'PAID' AND end_date > NOW()
     ORDER BY end_date DESC LIMIT 1"
);
$sub_query->bind_param("s", $id_user);
$sub_query->execute();
$sub_result = $sub_query->get_result();

if ($sub_result->num_rows > 0) {
    $sub = $sub_result->fetch_assoc();
    echo "2. Active subscription found:\n";
    echo "   - ID: " . $sub['id_subscription'] . "\n";
    echo "   - Plan: " . $sub['id_plan'] . "\n";
    echo "   - Status: " . $sub['payment_status'] . "\n";
    echo "   - Start: " . $sub['start_date'] . "\n";
    echo "   - End: " . $sub['end_date'] . "\n";
    echo "   - End > NOW(): YES (is_premium = true)\n";
} else {
    echo "2. No active subscription found with valid end_date\n";
}
$sub_query->close();

// Check 3: All user_subscriptions (including expired)
echo "\n3. ALL user_subscriptions:\n";
$all_subs = $conn->prepare("SELECT id_subscription, id_plan, payment_status, start_date, end_date FROM user_subscriptions WHERE id_user = ? ORDER BY start_date DESC");
$all_subs->bind_param("s", $id_user);
$all_subs->execute();
$all_res = $all_subs->get_result();

if ($all_res->num_rows > 0) {
    $count = 0;
    while ($s = $all_res->fetch_assoc()) {
        $count++;
        echo "   $count) Plan=" . $s['id_plan'] . " | Status=" . $s['payment_status'] . " | End=" . $s['end_date'] . " | Valid=" . ($s['end_date'] > date('Y-m-d H:i:s') ? "YES" : "NO") . "\n";
    }
} else {
    echo "   (None)\n";
}
$all_subs->close();

// Check 4: Recent transactions
echo "\n4. Recent transactions:\n";
$trans = $conn->prepare("SELECT id_transaction, id_plan, amount, status, created_at FROM transactions WHERE id_user = ? ORDER BY created_at DESC LIMIT 3");
$trans->bind_param("s", $id_user);
$trans->execute();
$trans_res = $trans->get_result();

if ($trans_res->num_rows > 0) {
    $count = 0;
    while ($t = $trans_res->fetch_assoc()) {
        $count++;
        echo "   $count) Plan=" . $t['id_plan'] . " | Status=" . $t['status'] . " | Amount=" . $t['amount'] . " | Created=" . $t['created_at'] . "\n";
    }
} else {
    echo "   (None)\n";
}
$trans->close();
