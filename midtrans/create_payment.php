<?php

/**
 * Create payment transaction - Endpoint untuk membuat transaksi pembayaran
 */

header('Content-Type: application/json');

if (!isset($_SESSION)) {
    session_start();
}

// Check session
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit();
}

include __DIR__ . '/../db.php';
include __DIR__ . '/../config/midtrans_config.php';
include __DIR__ . '/../includes/midtrans_helper.php';

try {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    $duration_months = intval($input['duration_months'] ?? 1);

    if ($duration_months < 1 || $duration_months > 12) {
        throw new Exception('Durasi tidak valid');
    }

    $id_user = $_SESSION['id_user'];
    $nama_user = $_SESSION['nama'];

    // Ambil email user
    $user_query = $conn->prepare("SELECT email FROM users WHERE id_user = ?");
    $user_query->bind_param("s", $id_user);
    $user_query->execute();
    $user_result = $user_query->get_result();

    if ($user_result->num_rows === 0) {
        throw new Exception('User tidak ditemukan');
    }

    $user = $user_result->fetch_assoc();
    $email_user = $user['email'];
    $user_query->close();

    // Ambil data plan dari subscription_plans untuk menentukan harga dasar
    // Kita akan menghitung harga berdasarkan harga bulanan dasar dan diskon untuk paket 3 dan 12 bulan
    $plan_query = $conn->prepare("SELECT id_plan, nama, durasi_bulan, harga FROM subscription_plans WHERE durasi_bulan IN (1, ?, ?) ");
    // bind untuk durasi 3 dan 12 (digunakan saat mencari harga 1 bulan atau plan yang cocok)
    $three = 3;
    $twelve = 12;
    $plan_query->bind_param("ii", $three, $twelve);
    $plan_query->execute();
    $plan_res = $plan_query->get_result();

    $monthly_price = null;
    $id_plan = null;
    $package_name = $duration_months . ' Bulan';

    if ($plan_res && $plan_res->num_rows > 0) {
        // Prefer plan with durasi_bulan = 1 jika ada, else use smallest-duration plan available
        $plans_found = [];
        while ($r = $plan_res->fetch_assoc()) {
            $plans_found[] = $r;
        }

        foreach ($plans_found as $p) {
            if ((int)$p['durasi_bulan'] === 1) {
                $monthly_price = (int)$p['harga'];
                break;
            }
        }

        if ($monthly_price === null) {
            // jika tidak ada plan 1 bulan, coba derive monthly dari plan dengan durasi sama atau smallest
            // cari plan dengan durasi == requested
            foreach ($plans_found as $p) {
                if ((int)$p['durasi_bulan'] === $duration_months) {
                    $monthly_price = (int)$p['harga'] / max(1, (int)$p['durasi_bulan']);
                    $id_plan = $p['id_plan'];
                    $package_name = $p['nama'];
                    break;
                }
            }
        }

        if ($monthly_price === null) {
            // use smallest durasi as fallback
            usort($plans_found, function ($a, $b) {
                return $a['durasi_bulan'] - $b['durasi_bulan'];
            });
            $p = $plans_found[0];
            $monthly_price = (int)$p['harga'] / max(1, (int)$p['durasi_bulan']);
            $id_plan = $p['id_plan'];
        }
    }

    $plan_query->close();

    // Fallback monthly price jika tidak ditemukan di DB
    if ($monthly_price === null) {
        $monthly_price = 99000;
    }

    // Hitung diskon berdasarkan durasi
    $discount = 0.0;
    if ($duration_months === 3) $discount = 0.05;
    if ($duration_months === 12) $discount = 0.15;

    // Hitung total amount
    $amount = (int) round($monthly_price * $duration_months * (1 - $discount));

    // Tentukan package name jika belum ditentukan
    if (empty($package_name)) {
        $package_name = $duration_months . ' Bulan';
    }

    // Pastikan kita memiliki id_plan yang valid (karena kolom id_plan tidak boleh NULL)
    // Coba cari plan dengan durasi yang sama terlebih dahulu
    $id_plan_found = null;
    $exact_q = $conn->prepare("SELECT id_plan, nama FROM subscription_plans WHERE durasi_bulan = ? LIMIT 1");
    $exact_q->bind_param("i", $duration_months);
    $exact_q->execute();
    $exact_res = $exact_q->get_result();
    if ($exact_res && $exact_res->num_rows > 0) {
        $r = $exact_res->fetch_assoc();
        $id_plan_found = $r['id_plan'];
        $package_name = $r['nama'];
    }
    $exact_q->close();

    // Jika tidak ada exact-duration plan, coba plan 1 bulan
    if ($id_plan_found === null) {
        $one_q = $conn->prepare("SELECT id_plan, nama FROM subscription_plans WHERE durasi_bulan = 1 LIMIT 1");
        $one_q->execute();
        $one_res = $one_q->get_result();
        if ($one_res && $one_res->num_rows > 0) {
            $r = $one_res->fetch_assoc();
            $id_plan_found = $r['id_plan'];
        }
        $one_q->close();
    }

    // Jika masih belum ada, ambil plan terkecil yang tersedia
    if ($id_plan_found === null) {
        $any_q = $conn->prepare("SELECT id_plan, nama FROM subscription_plans ORDER BY durasi_bulan ASC LIMIT 1");
        $any_q->execute();
        $any_res = $any_q->get_result();
        if ($any_res && $any_res->num_rows > 0) {
            $r = $any_res->fetch_assoc();
            $id_plan_found = $r['id_plan'];
            if (empty($package_name)) $package_name = $r['nama'];
        }
        $any_q->close();
    }

    // Jika masih tidak ada plan di DB (sangat jarang), gunakan string default 'PLAN_DEFAULT' agar tidak null
    if ($id_plan_found === null) {
        $id_plan_found = 'PLAN_DEFAULT';
    }

    $id_plan = $id_plan_found;

    // Generate subscription ID (UUID format)
    $id_subscription = 'SUB-' . $id_user . '-' . time() . '-' . bin2hex(random_bytes(3));

    // Create Midtrans Token
    $payment_result = createMidtransToken(
        $id_user,
        $id_subscription,
        $package_name,
        $amount,
        $email_user,
        $nama_user
    );

    if ($payment_result['success']) {
        // Log transaksi jika diperlukan
        error_log("Payment created - User: $id_user, Amount: $amount, Order ID: " . $payment_result['order_id']);

        // Simpan transaksi awal ke database (pending)
        $id_transaction = 'TRX-' . substr(md5($id_user . microtime(true)), 0, 12);
        $insert_tx = $conn->prepare("INSERT INTO transactions (id_transaction, id_user, id_plan, amount, status, snap_token, order_id, created_at, id_transactions) VALUES (?, ?, ?, ?, 'PENDING', ?, ?, NOW(), ?)");
        $snap = $payment_result['token'];
        $order_id = $payment_result['order_id'];
        $id_transactions = $id_transaction; // populate legacy column
        $insert_tx->bind_param("sssdsss", $id_transaction, $id_user, $id_plan, $amount, $snap, $order_id, $id_transactions);
        $insert_tx->execute();
        $insert_tx->close();

        echo json_encode([
            'success' => true,
            'token' => $payment_result['token'],
            'order_id' => $payment_result['order_id']
        ]);
    } else {
        throw new Exception($payment_result['message']);
    }
} catch (Exception $e) {
    http_response_code(400);
    error_log("Payment Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
