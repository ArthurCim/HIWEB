<?php
// Test script to simulate Midtrans webhook and check subscription flow
include "db.php";
include "includes/midtrans_helper.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test data
$id_user = '4b31f8b6-28bb-4400-bb2d-a3804597d645'; // Use existing user (Affan)
$order_id = 'ORD' . substr(md5('test' . time()), 0, 20); // Generate test order ID
$id_plan = 'PLAN_1M'; // Test with 1 month plan

echo "=== PAYMENT FLOW TEST ===\n";
echo "User ID: $id_user\n";
echo "Order ID: $order_id\n";
echo "Plan ID: $id_plan\n\n";

// 1. Create transaction record (simulating payment creation)
echo "1. Creating transaction record...\n";
$id_transaction = 'TRX-' . substr(md5(microtime(true)), 0, 12);
$amount = 99000;
$id_trans_dup = $id_transaction; // duplicate for id_transactions field
$insert_tx = $conn->prepare("INSERT INTO transactions (id_transaction, id_user, id_plan, amount, status, order_id, id_transactions) VALUES (?, ?, ?, ?, 'PENDING', ?, ?)");
$insert_tx->bind_param("sssdss", $id_transaction, $id_user, $id_plan, $amount, $order_id, $id_trans_dup);

if ($insert_tx->execute()) {
    echo "✓ Transaction created: $id_transaction\n";
} else {
    echo "✗ Error creating transaction: " . $insert_tx->error . "\n";
}
$insert_tx->close();

// 2. Get transaction back from DB to verify
echo "\n2. Verifying transaction in database...\n";
$verify_tx = $conn->prepare("SELECT id_transaction, id_user, id_plan, amount, status, order_id FROM transactions WHERE order_id = ? LIMIT 1");
$verify_tx->bind_param("s", $order_id);
$verify_tx->execute();
$verify_res = $verify_tx->get_result();

if ($verify_res && $verify_res->num_rows > 0) {
    $tx = $verify_res->fetch_assoc();
    echo "✓ Transaction found in DB:\n";
    echo "  - ID: " . $tx['id_transaction'] . "\n";
    echo "  - User: " . $tx['id_user'] . "\n";
    echo "  - Plan: " . $tx['id_plan'] . "\n";
    echo "  - Amount: Rp" . number_format($tx['amount'], 0, ',', '.') . "\n";
    echo "  - Status: " . $tx['status'] . "\n";
    echo "  - Order ID: " . $tx['order_id'] . "\n";
} else {
    echo "✗ Transaction not found in database!\n";
}
$verify_tx->close();

// 3. Get plan details
echo "\n3. Getting plan details...\n";
$plan_query = $conn->prepare("SELECT id_plan, nama, durasi_bulan, harga FROM subscription_plans WHERE id_plan = ? LIMIT 1");
$plan_query->bind_param("s", $id_plan);
$plan_query->execute();
$plan_res = $plan_query->get_result();

if ($plan_res && $plan_res->num_rows > 0) {
    $plan = $plan_res->fetch_assoc();
    echo "✓ Plan found:\n";
    echo "  - ID: " . $plan['id_plan'] . "\n";
    echo "  - Name: " . $plan['nama'] . "\n";
    echo "  - Duration: " . $plan['durasi_bulan'] . " months\n";
    echo "  - Price: Rp" . number_format($plan['harga'], 0, ',', '.') . "\n";
    $duration_months = (int)$plan['durasi_bulan'];
} else {
    echo "✗ Plan not found! ID: $id_plan\n";
    $duration_months = 1;
}
$plan_query->close();

// 4. Test updateSubscriptionAfterPayment() function
echo "\n4. Calling updateSubscriptionAfterPayment()...\n";
$result = updateSubscriptionAfterPayment($conn, $id_user, $duration_months, $order_id);

if ($result) {
    echo "✓ updateSubscriptionAfterPayment() returned TRUE\n";
} else {
    echo "✗ updateSubscriptionAfterPayment() returned FALSE\n";
}

// 5. Check if user_subscriptions was created
echo "\n5. Checking user_subscriptions...\n";
$subs_query = $conn->prepare("SELECT id_subscription, id_user, id_plan, payment_status, start_date, end_date FROM user_subscriptions WHERE id_user = ? ORDER BY start_date DESC LIMIT 1");
$subs_query->bind_param("s", $id_user);
$subs_query->execute();
$subs_res = $subs_query->get_result();

if ($subs_res && $subs_res->num_rows > 0) {
    $sub = $subs_res->fetch_assoc();
    echo "✓ Latest subscription found:\n";
    echo "  - ID: " . $sub['id_subscription'] . "\n";
    echo "  - User: " . $sub['id_user'] . "\n";
    echo "  - Plan: " . $sub['id_plan'] . "\n";
    echo "  - Status: " . $sub['payment_status'] . "\n";
    echo "  - Start: " . $sub['start_date'] . "\n";
    echo "  - End: " . $sub['end_date'] . "\n";
} else {
    echo "✗ No subscription found for user!\n";
}
$subs_query->close();

// 6. Check user premium status
echo "\n6. Checking user premium status...\n";
$user_query = $conn->prepare("SELECT id_user, nama, email, is_premium FROM users WHERE id_user = ?");
$user_query->bind_param("s", $id_user);
$user_query->execute();
$user_res = $user_query->get_result();

if ($user_res && $user_res->num_rows > 0) {
    $user = $user_res->fetch_assoc();
    echo "✓ User found:\n";
    echo "  - ID: " . $user['id_user'] . "\n";
    echo "  - Name: " . $user['nama'] . "\n";
    echo "  - Email: " . $user['email'] . "\n";
    echo "  - Premium: " . ($user['is_premium'] ? "YES (1)" : "NO (0)") . "\n";
} else {
    echo "✗ User not found!\n";
}
$user_query->close();

// 7. Check transaction status update
echo "\n7. Verifying transaction status was updated to SETTLEMENT...\n";
$final_tx = $conn->prepare("SELECT id_transaction, status, order_id FROM transactions WHERE order_id = ? LIMIT 1");
$final_tx->bind_param("s", $order_id);
$final_tx->execute();
$final_res = $final_tx->get_result();

if ($final_res && $final_res->num_rows > 0) {
    $tx = $final_res->fetch_assoc();
    echo "✓ Final transaction status: " . $tx['status'] . "\n";
} else {
    echo "✗ Transaction not found!\n";
}
$final_tx->close();

echo "\n=== TEST COMPLETE ===\n";
