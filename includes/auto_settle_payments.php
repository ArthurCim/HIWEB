<?php

/**
 * Auto-settle any pending transactions that have actually been paid at Midtrans
 * This helps catch payments that didn't trigger webhooks
 */

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

include __DIR__ . '/../db.php';
include __DIR__ . '/midtrans_helper.php';

try {
    $id_user = $_SESSION['id_user'];
    $settled_count = 0;

    // Get all PENDING transactions for this user
    $pending_query = $conn->prepare(
        "SELECT order_id, id_plan FROM transactions 
         WHERE id_user = ? AND status = 'PENDING' 
         ORDER BY created_at DESC"
    );
    $pending_query->bind_param("s", $id_user);
    $pending_query->execute();
    $pending_result = $pending_query->get_result();

    while ($pending_tx = $pending_result->fetch_assoc()) {
        $order_id = $pending_tx['order_id'];
        $id_plan = $pending_tx['id_plan'];

        try {
            // Try to check status at Midtrans (but don't fail if it can't connect)
            try {
                $midtrans_status = checkTransactionStatus($order_id);
                $payment_succeeded = $midtrans_status['success'] && in_array($midtrans_status['status'], ['settlement', 'capture']);
            } catch (Exception $e) {
                // If Midtrans check fails, skip this transaction (leave it pending)
                error_log("Could not check Midtrans status for $order_id: " . $e->getMessage());
                continue;
            }

            if ($payment_succeeded) {
                // Payment succeeded at Midtrans, settle it locally
                $duration_months = 1;
                if (!empty($id_plan)) {
                    $plan_check = $conn->prepare("SELECT durasi_bulan FROM subscription_plans WHERE id_plan = ?");
                    $plan_check->bind_param("s", $id_plan);
                    $plan_check->execute();
                    $plan_result = $plan_check->get_result();
                    if ($plan_result->num_rows > 0) {
                        $plan_row = $plan_result->fetch_assoc();
                        $duration_months = (int)$plan_row['durasi_bulan'];
                    }
                    $plan_check->close();
                }

                if (updateSubscriptionAfterPayment($conn, $id_user, $duration_months, $order_id)) {
                    $settled_count++;
                }
            }
        } catch (Exception $e) {
            // Log but continue with other transactions
            error_log("Error settling transaction $order_id: " . $e->getMessage());
        }
    }
    $pending_query->close();

    // Get updated user premium status
    $user_check = $conn->prepare("SELECT is_premium FROM users WHERE id_user = ?");
    $user_check->bind_param("s", $id_user);
    $user_check->execute();
    $user_result = $user_check->get_result()->fetch_assoc();
    $user_check->close();

    echo json_encode([
        'status' => 'success',
        'settled_count' => $settled_count,
        'is_premium' => (int)$user_result['is_premium'],
        'message' => $settled_count > 0 ? "$settled_count pending payment(s) have been settled" : 'No pending payments found'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
