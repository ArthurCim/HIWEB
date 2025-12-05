<?php

/**
 * Helper functions untuk Midtrans Payment Integration
 */

require_once __DIR__ . '/../config/midtrans_config.php';

/**
 * Buat transaction token untuk Midtrans Snap
 * 
 * @param string $id_user ID User
 * @param string $id_subscription ID Subscription
 * @param string $package_name Nama paket
 * @param int $amount Jumlah pembayaran
 * @param string $email Email user
 * @param string $customer_name Nama customer
 * 
 * @return array Token dan URL jika berhasil, false jika gagal
 */
function createMidtransToken($id_user, $id_subscription, $package_name, $amount, $email, $customer_name)
{
    try {
        // Generate unique order ID (max 50 chars for Midtrans)
        // Format: ORD + 20 char hash = total 23 chars
        $order_id = "ORD" . substr(md5($id_user . time() . uniqid()), 0, 20);

        // Transaction details
        $transaction_details = array(
            "order_id" => $order_id,
            "gross_amount" => $amount
        );

        // Customer details
        $customer_details = array(
            "first_name" => $customer_name,
            "email" => $email,
            "phone" => "",
        );

        // Item details
        $item_details = array(
            array(
                "id" => $id_subscription,
                "price" => $amount,
                "quantity" => 1,
                "name" => $package_name . " - CodePlay Premium"
            )
        );

        // Prepare transaction parameter
        $transaction = array(
            'transaction_details' => $transaction_details,
            'customer_details' => $customer_details,
            'item_details' => $item_details,
            'usage_limit' => 1,
            'expiry_time' => 1440 // 24 jam dalam menit
        );

        // Get Snap Token - ini yang digunakan di frontend
        $snap_token = \Midtrans\Snap::getSnapToken($transaction);

        return array(
            'success' => true,
            'token' => $snap_token,
            'order_id' => $order_id
        );
    } catch (\Exception $e) {
        error_log("Midtrans Error: " . $e->getMessage());
        return array(
            'success' => false,
            'message' => $e->getMessage()
        );
    }
}

/**
 * Cek status transaksi di Midtrans
 * 
 * @param string $order_id Order ID dari Midtrans
 * 
 * @return array Status transaksi
 */
function checkTransactionStatus($order_id)
{
    try {
        $status = \Midtrans\Transaction::status($order_id);
        return array(
            'success' => true,
            'status' => $status->transaction_status,
            'payment_type' => $status->payment_type ?? '',
            'data' => $status
        );
    } catch (\Exception $e) {
        error_log("Midtrans Status Check Error: " . $e->getMessage());
        return array(
            'success' => false,
            'message' => $e->getMessage()
        );
    }
}

/**
 * Approve transaction di Midtrans
 * 
 * @param string $order_id Order ID dari Midtrans
 * 
 * @return array Hasil approve
 */
function approveTransaction($order_id)
{
    try {
        $status = \Midtrans\Transaction::approve($order_id);
        return array(
            'success' => true,
            'message' => 'Transaction approved',
            'data' => $status
        );
    } catch (\Exception $e) {
        error_log("Midtrans Approve Error: " . $e->getMessage());
        return array(
            'success' => false,
            'message' => $e->getMessage()
        );
    }
}

/**
 * Deny transaction di Midtrans
 * 
 * @param string $order_id Order ID dari Midtrans
 * 
 * @return array Hasil deny
 */
function denyTransaction($order_id)
{
    try {
        $status = \Midtrans\Transaction::deny($order_id);
        return array(
            'success' => true,
            'message' => 'Transaction denied',
            'data' => $status
        );
    } catch (\Exception $e) {
        error_log("Midtrans Deny Error: " . $e->getMessage());
        return array(
            'success' => false,
            'message' => $e->getMessage()
        );
    }
}

/**
 * Update subscription setelah pembayaran berhasil
 * 
 * @param mysqli $conn Database connection
 * @param string $id_user ID User
 * @param int $duration_months Durasi subscription (bulan)
 * @param string $order_id Order ID dari Midtrans
 * 
 * @return bool Berhasil atau tidak
 */
function updateSubscriptionAfterPayment($conn, $id_user, $duration_months, $order_id)
{
    try {
        // Generate unique subscription ID
        $id_subscription = 'SUB-' . substr(md5($id_user . time()), 0, 12);

        // Default plan (bisa diubah sesuai kebutuhan)
        $id_plan = 'PLAN_' . $duration_months . 'M';

        // Hitung start dan end date
        $start_date = date('Y-m-d H:i:s');
        $end_date = date('Y-m-d H:i:s', strtotime("+{$duration_months} months"));

        // Insert ke user_subscriptions
        $insert_query = $conn->prepare(
            "INSERT INTO user_subscriptions (id_subscription, id_user, id_plan, start_date, end_date, payment_status) 
             VALUES (?, ?, ?, ?, ?, 'PAID')"
        );

        $insert_query->bind_param("sssss", $id_subscription, $id_user, $id_plan, $start_date, $end_date);
        $insert_result = $insert_query->execute();
        $insert_query->close();

        if (!$insert_result) {
            throw new Exception("Failed to insert subscription");
        }

        // Update existing transaction record (set to settlement/success)
        $update_tx = $conn->prepare(
            "UPDATE transactions SET status = 'SETTLEMENT', updated_at = NOW() WHERE order_id = ?"
        );
        $update_tx->bind_param("s", $order_id);
        $update_result = $update_tx->execute();
        $update_tx->close();

        return $insert_result && $update_result;
    } catch (\Exception $e) {
        error_log("Update Subscription Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Validasi signature dari Midtrans webhook
 * 
 * @param string $order_id Order ID
 * @param string $status_code Status code dari Midtrans
 * @param string $gross_amount Gross amount
 * @param string $signature Signature dari Midtrans
 * 
 * @return bool Valid atau tidak
 */
function validateMidtransSignature($order_id, $status_code, $gross_amount, $signature)
{
    $server_key = \Midtrans\Config::$serverKey;

    // Generate signature yang benar
    $data = $order_id . $status_code . $gross_amount . $server_key;
    $generated_signature = hash('sha512', $data);

    // Bandingkan dengan signature yang diterima
    return hash_equals($generated_signature, $signature);
}
