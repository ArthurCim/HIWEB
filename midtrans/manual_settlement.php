<?php

/**
 * Manual payment settlement trigger for testing
 * This simulates what happens when Midtrans sends a webhook notification
 */

header('Content-Type: application/json');

include __DIR__ . '/../db.php';
include __DIR__ . '/../includes/midtrans_helper.php';

try {
    // Get the order_id from query parameter
    $order_id = $_GET['order_id'] ?? $_POST['order_id'] ?? null;

    if (!$order_id) {
        throw new Exception('order_id parameter required');
    }

    echo "Processing settlement for order: $order_id\n";

    // Find the transaction
    $txq = $conn->prepare("SELECT id_transaction, id_user, id_plan, amount FROM transactions WHERE order_id = ? LIMIT 1");
    $txq->bind_param("s", $order_id);
    $txq->execute();
    $txres = $txq->get_result();

    if ($txres && $txres->num_rows > 0) {
        $tx = $txres->fetch_assoc();
        $id_user = $tx['id_user'];
        $id_plan = $tx['id_plan'];
        echo "Found transaction for user: $id_user, plan: $id_plan\n";
    } else {
        throw new Exception('Transaction not found');
    }
    $txq->close();

    // Get plan duration
    $duration_months = 1; // default
    if (!empty($id_plan)) {
        $pstmt = $conn->prepare("SELECT durasi_bulan FROM subscription_plans WHERE id_plan = ? LIMIT 1");
        $pstmt->bind_param("s", $id_plan);
        $pstmt->execute();
        $pres = $pstmt->get_result();
        if ($pres && $pres->num_rows > 0) {
            $prow = $pres->fetch_assoc();
            $duration_months = (int)$prow['durasi_bulan'];
            echo "Plan duration: $duration_months months\n";
        }
        $pstmt->close();
    }

    // Call updateSubscriptionAfterPayment to settle
    echo "Calling updateSubscriptionAfterPayment...\n";
    $result = updateSubscriptionAfterPayment($conn, $id_user, $duration_months, $order_id);

    if ($result) {
        echo "✓ Settlement successful\n";

        // Verify results
        $verify = $conn->prepare("SELECT id_user, is_premium FROM users WHERE id_user = ?");
        $verify->bind_param("s", $id_user);
        $verify->execute();
        $verify_res = $verify->get_result()->fetch_assoc();

        echo "\nVerification:\n";
        echo "  User is_premium: " . $verify_res['is_premium'] . "\n";

        $tx_verify = $conn->prepare("SELECT status FROM transactions WHERE order_id = ?");
        $tx_verify->bind_param("s", $order_id);
        $tx_verify->execute();
        $tx_verify_res = $tx_verify->get_result()->fetch_assoc();

        echo "  Transaction status: " . $tx_verify_res['status'] . "\n";

        echo json_encode(['status' => 'success', 'message' => 'Settlement processed']);
    } else {
        throw new Exception('Settlement failed');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
