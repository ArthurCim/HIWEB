<?php
session_start();
include __DIR__ . '/../db.php';

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../login/login.php");
    exit();
}

$order_id = $_GET['order_id'] ?? '';
$id_user = $_SESSION['id_user'];

// Ambil detail transaksi dari database
if (!empty($order_id)) {
    $query = $conn->prepare(
        "SELECT order_id, amount, status, payment_type, created_at 
         FROM transactions 
         WHERE order_id = ? AND id_user = ?"
    );
    $query->bind_param("si", $order_id, $id_user);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $transaction = $result->fetch_assoc();
    }
    $query->close();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pembayaran Berhasil - CodePlay</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Inter, system-ui;
            background: #000;
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            text-align: center;
            backdrop-filter: blur(10px);
        }

        .success-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }

        h1 {
            font-size: 28px;
            margin-bottom: 10px;
            color: #10b981;
        }

        .subtitle {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 30px;
            font-size: 14px;
        }

        .details {
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: left;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .detail-label {
            color: rgba(255, 255, 255, 0.6);
        }

        .detail-value {
            font-weight: 600;
            color: #fff;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.15);
            margin-left: 10px;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.25);
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="success-icon">✓</div>
        <h1>Pembayaran Berhasil!</h1>
        <p class="subtitle">Premium Anda telah diaktifkan</p>

        <?php if (!empty($transaction)): ?>
            <div class="details">
                <div class="detail-row">
                    <span class="detail-label">Order ID:</span>
                    <span class="detail-value"><?= htmlspecialchars($transaction['order_id']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Jumlah:</span>
                    <span class="detail-value">Rp <?= number_format($transaction['amount'], 0, ',', '.'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Metode Pembayaran:</span>
                    <span class="detail-value"><?= ucfirst($transaction['payment_type']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Waktu:</span>
                    <span class="detail-value"><?= date('d M Y H:i', strtotime($transaction['created_at'])); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <div>
            <a href="../dashboard_user.php" class="btn">Kembali ke Dashboard</a>
            <a href="../landing.php" class="btn btn-secondary">Belajar Sekarang</a>
        </div>
    </div>
</body>

</html>