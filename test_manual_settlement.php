<?php
// Direct test of manual settlement
include "db.php";
include "includes/midtrans_helper.php";

$order_id = 'ORD2fdcadfb87286856fdf7';

echo "=== MANUAL SETTLEMENT TEST ===\n";
echo "Order ID: $order_id\n\n";

// Find transaction
$txq = $conn->prepare("SELECT id_transaction, id_user, id_plan, amount, status FROM transactions WHERE order_id = ? LIMIT 1");
$txq->bind_param("s", $order_id);
$txq->execute();
$txres = $txq->get_result();

if ($txres && $txres->num_rows > 0) {
    $tx = $txres->fetch_assoc();
    $id_user = $tx['id_user'];
    $id_plan = $tx['id_plan'];
    echo "Transaction found:\n";
    echo "  User: $id_user\n";
    echo "  Plan: $id_plan\n";
    echo "  Current status: " . $tx['status'] . "\n\n";
} else {
    echo "Transaction not found\n";
    exit;
}
$txq->close();

// Get plan duration
$duration_months = 1;
if (!empty($id_plan)) {
    $pstmt = $conn->prepare("SELECT durasi_bulan FROM subscription_plans WHERE id_plan = ? LIMIT 1");
    $pstmt->bind_param("s", $id_plan);
    $pstmt->execute();
    $pres = $pstmt->get_result();
    if ($pres && $pres->num_rows > 0) {
        $prow = $pres->fetch_assoc();
        $duration_months = (int)$prow['durasi_bulan'];
        echo "Plan duration: $duration_months months\n\n";
    }
    $pstmt->close();
}

// Call settlement function
echo "Calling updateSubscriptionAfterPayment...\n";
$result = updateSubscriptionAfterPayment($conn, $id_user, $duration_months, $order_id);

if ($result) {
    echo "✓ Settlement successful\n\n";

    // Verify
    echo "Verification after settlement:\n";
    $verify = $conn->prepare("SELECT id_user, is_premium FROM users WHERE id_user = ?");
    $verify->bind_param("s", $id_user);
    $verify->execute();
    $verify_res = $verify->get_result()->fetch_assoc();
    echo "  User is_premium: " . $verify_res['is_premium'] . "\n";

    $tx_verify = $conn->prepare("SELECT status FROM transactions WHERE order_id = ?");
    $tx_verify->bind_param("s", $order_id);
    $tx_verify->execute();
    $tx_verify_res = $tx_verify->get_result()->fetch_assoc();
    echo "  Transaction status: " . $tx_verify_res['status'] . "\n";

    $sub_verify = $conn->prepare("SELECT id_subscription, payment_status FROM user_subscriptions WHERE id_user = ? ORDER BY start_date DESC LIMIT 1");
    $sub_verify->bind_param("s", $id_user);
    $sub_verify->execute();
    $sub_verify_res = $sub_verify->get_result()->fetch_assoc();
    echo "  Subscription created: " . ($sub_verify_res ? "YES (ID: " . $sub_verify_res['id_subscription'] . ", Status: " . $sub_verify_res['payment_status'] . ")" : "NO") . "\n";
} else {
    echo "✗ Settlement failed\n";
}
