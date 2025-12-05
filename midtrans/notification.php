<?php

/**
 * Midtrans Notification Handler
 * Handle notifikasi dari Midtrans untuk setiap transaksi
 */

header('Content-Type: application/json');

include __DIR__ . '/../db.php';
include __DIR__ . '/../includes/midtrans_helper.php';

try {
    // Get notification body
    $notif = file_get_contents('php://input');
    $notif = json_decode($notif);

    // Validate signature
    $order_id = $notif->order_id;
    $status_code = $notif->status_code;
    $gross_amount = $notif->gross_amount;
    $signature = $notif->signature_key;

    if (!validateMidtransSignature($order_id, $status_code, $gross_amount, $signature)) {
        throw new Exception('Invalid signature');
    }

    // Log notification
    error_log("Midtrans Notification - Order: $order_id, Status: " . $notif->transaction_status);

    // Handle transaction status
    $transaction_status = $notif->transaction_status;
    $payment_type = $notif->payment_type;

    // Cari transaksi di database berdasarkan order_id
    $txq = $conn->prepare("SELECT id_transaction, id_user, id_plan, amount FROM transactions WHERE order_id = ? LIMIT 1");
    $txq->bind_param("s", $order_id);
    $txq->execute();
    $txres = $txq->get_result();

    if ($txres && $txres->num_rows > 0) {
        $tx = $txres->fetch_assoc();
        $id_user = $tx['id_user'];
        $id_plan = $tx['id_plan'];
        $tx_amount = $tx['amount'];
    } else {
        $id_user = null;
        $id_plan = null;
        $tx_amount = $notif->gross_amount;
    }

    // Jika ada id_plan, ambil durasi dari subscription_plans
    $duration_months = 1;
    if (!empty($id_plan)) {
        $pstmt = $conn->prepare("SELECT durasi_bulan FROM subscription_plans WHERE id_plan = ? LIMIT 1");
        $pstmt->bind_param("s", $id_plan);
        $pstmt->execute();
        $pres = $pstmt->get_result();
        if ($pres && $pres->num_rows > 0) {
            $prow = $pres->fetch_assoc();
            $duration_months = (int)$prow['durasi_bulan'];
        }
        $pstmt->close();
    } else {
        // fallback berdasarkan amount
        $amount = intval($notif->gross_amount);
        if ($amount >= 799000) {
            $duration_months = 12;
        } elseif ($amount >= 249000) {
            $duration_months = 3;
        } else {
            $duration_months = 1;
        }
    }

    switch ($transaction_status) {
        case 'capture':
        case 'settlement':
            // Pembayaran sukses
            error_log("Payment Success - Order: $order_id, User: $id_user");

            // Update subscription and mark transaction settled
            if (!empty($id_user)) {
                updateSubscriptionAfterPayment($conn, $id_user, $duration_months, $order_id);
                $upd = $conn->prepare("UPDATE transactions SET status = 'SETTLEMENT', updated_at = NOW(), payment_type = ? WHERE order_id = ?");
                $upd->bind_param("ss", $payment_type, $order_id);
                $upd->execute();
                $upd->close();
            }
            break;

        case 'pending':
            // Pembayaran pending
            error_log("Payment Pending - Order: $order_id, User: $id_user");
            $upd = $conn->prepare("UPDATE transactions SET status = 'PENDING', updated_at = NOW(), payment_type = ? WHERE order_id = ?");
            $upd->bind_param("ss", $payment_type, $order_id);
            $upd->execute();
            $upd->close();
            break;

        case 'deny':
            // Pembayaran ditolak
            error_log("Payment Denied - Order: $order_id, User: $id_user");
            $upd = $conn->prepare("UPDATE transactions SET status = 'DENY', updated_at = NOW(), payment_type = ? WHERE order_id = ?");
            $upd->bind_param("ss", $payment_type, $order_id);
            $upd->execute();
            $upd->close();
            break;

        case 'expire':
            // Pembayaran expired
            error_log("Payment Expired - Order: $order_id, User: $id_user");
            $upd = $conn->prepare("UPDATE transactions SET status = 'EXPIRE', updated_at = NOW(), payment_type = ? WHERE order_id = ?");
            $upd->bind_param("ss", $payment_type, $order_id);
            $upd->execute();
            $upd->close();
            break;

        case 'cancel':
            // Pembayaran dibatalkan
            error_log("Payment Cancelled - Order: $order_id, User: $id_user");
            $upd = $conn->prepare("UPDATE transactions SET status = 'CANCEL', updated_at = NOW(), payment_type = ? WHERE order_id = ?");
            $upd->bind_param("ss", $payment_type, $order_id);
            $upd->execute();
            $upd->close();
            break;

        default:
            error_log("Unknown transaction status: $transaction_status");
    }

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Notification received']);
} catch (Exception $e) {
    http_response_code(400);
    error_log("Notification Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
