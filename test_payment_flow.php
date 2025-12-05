<?php
// Test script to check payment flow and user premium status
include "db.php";

// Check if user exists and their current status
$id_user = '2024002'; // Replace with test user ID if needed
$user_query = mysqli_query($conn, "SELECT id_user, nama, email, is_premium FROM users WHERE id_user = '$id_user'");
if ($user = mysqli_fetch_assoc($user_query)) {
    echo "=== USER INFO ===\n";
    echo "ID: " . $user['id_user'] . "\n";
    echo "Nama: " . $user['nama'] . "\n";
    echo "Email: " . $user['email'] . "\n";
    echo "Is Premium: " . $user['is_premium'] . "\n\n";
}

// Check subscription plans in DB
echo "=== SUBSCRIPTION PLANS ===\n";
$plans_query = mysqli_query($conn, "SELECT id_plan, nama, durasi_bulan, harga FROM subscription_plans ORDER BY durasi_bulan ASC");
$count = 0;
while ($plan = mysqli_fetch_assoc($plans_query)) {
    echo ($count + 1) . ". ID: " . $plan['id_plan'] . " | Nama: " . $plan['nama'] . " | Durasi: " . $plan['durasi_bulan'] . " bln | Harga: Rp" . number_format($plan['harga'], 0, ',', '.') . "\n";
    $count++;
}
echo "Total Plans: $count\n\n";

// Check user subscriptions
echo "=== USER SUBSCRIPTIONS ===\n";
$subs_query = mysqli_query($conn, "SELECT us.id_subscription, us.id_user, us.id_plan, us.start_date, us.end_date, us.payment_status FROM user_subscriptions us WHERE us.id_user = '$id_user' ORDER BY us.start_date DESC LIMIT 5");
$subs_count = 0;
while ($sub = mysqli_fetch_assoc($subs_query)) {
    echo ($subs_count + 1) . ". Plan: " . $sub['id_plan'] . " | Status: " . $sub['payment_status'] . " | Start: " . $sub['start_date'] . " | End: " . $sub['end_date'] . "\n";
    $subs_count++;
}
if ($subs_count == 0) {
    echo "No subscriptions found for this user\n";
}
echo "\n";

// Check transactions
echo "=== RECENT TRANSACTIONS ===\n";
$trans_query = mysqli_query($conn, "SELECT order_id, id_user, id_plan, amount, status, created_at FROM transactions WHERE id_user = '$id_user' ORDER BY created_at DESC LIMIT 5");
$trans_count = 0;
while ($trans = mysqli_fetch_assoc($trans_query)) {
    echo ($trans_count + 1) . ". Order: " . $trans['order_id'] . " | Plan: " . $trans['id_plan'] . " | Amount: Rp" . number_format($trans['amount'], 0, ',', '.') . " | Status: " . $trans['status'] . " | Created: " . $trans['created_at'] . "\n";
    $trans_count++;
}
if ($trans_count == 0) {
    echo "No transactions found for this user\n";
}
