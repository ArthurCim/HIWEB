<?php

/**
 * Check payment status and verify/settle if needed
 * Called by dashboard or success page to verify payment completion
 */

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

include __DIR__ . '/../db.php';
include __DIR__ . '/../includes/midtrans_helper.php';

try {
    $order_id = $_GET['order_id'] ?? $_POST['order_id'] ?? null;

    if (!$order_id) {
        throw new Exception('order_id parameter required');
    }

    $id_user = $_SESSION['id_user'];

    // Check transaction status in DB
    $tx_check = $conn->prepare(
        "SELECT id_transaction, id_user, id_plan, amount, status FROM transactions 
         WHERE order_id = ? AND id_user = ?"
    );
    $tx_check->bind_param("ss", $order_id, $id_user);
    $tx_check->execute();
    $tx_result = $tx_check->get_result();

    if ($tx_result->num_rows === 0) {
        throw new Exception('Transaction not found');
    }

    $transaction = $tx_result->fetch_assoc();
    $tx_check->close();

    // If transaction is already SETTLEMENT, just return success
    if ($transaction['status'] === 'SETTLEMENT') {
        echo json_encode([
            'status' => 'success',
            'payment_status' => 'settled',
            'message' => 'Payment already settled'
        ]);
        exit;
    }

    // If still PENDING, try to check Midtrans status and settle if payment succeeded
    if ($transaction['status'] === 'PENDING') {
        try {
            // Check status from Midtrans API
            $midtrans_status = checkTransactionStatus($order_id);

            if ($midtrans_status['success']) {
                $payment_status = $midtrans_status['status'];

                // If Midtrans shows payment succeeded, settle it locally
                if (in_array($payment_status, ['settlement', 'capture'])) {
                    // Get plan duration
                    $id_plan = $transaction['id_plan'];
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

                    // Settle the payment
                    $settlement_result = updateSubscriptionAfterPayment($conn, $id_user, $duration_months, $order_id);

                    if ($settlement_result) {
                        echo json_encode([
                            'status' => 'success',
                            'payment_status' => 'settled',
                            'message' => 'Payment verified and settled successfully'
                        ]);
                    } else {
                        throw new Exception('Settlement failed');
                    }
                } else {
                    echo json_encode([
                        'status' => 'pending',
                        'payment_status' => $payment_status,
                        'message' => 'Payment still pending at Midtrans'
                    ]);
                }
            } else {
                throw new Exception('Could not verify payment status from Midtrans');
            }
        } catch (Exception $e) {
            // If Midtrans check fails, still allow manual settlement
            // This handles cases where Midtrans API is unavailable
            echo json_encode([
                'status' => 'error',
                'message' => 'Could not verify with Midtrans: ' . $e->getMessage(),
                'suggestion' => 'Payment may still be pending. Please wait or contact support.'
            ]);
        }
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
