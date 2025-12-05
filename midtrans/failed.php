<?php
session_start();
include __DIR__ . '/../db.php';

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../login/login.php");
    exit();
}

$order_id = $_GET['order_id'] ?? '';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pembayaran Gagal - CodePlay</title>
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

        .error-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }

        h1 {
            font-size: 28px;
            margin-bottom: 10px;
            color: #ef4444;
        }

        .subtitle {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 30px;
            font-size: 14px;
        }

        .message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
            color: rgba(255, 255, 255, 0.8);
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
            margin: 0 5px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.15);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.25);
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="error-icon">✕</div>
        <h1>Pembayaran Gagal</h1>
        <p class="subtitle">Silakan coba kembali</p>

        <div class="message">
            Pembayaran Anda tidak berhasil diproses. Silakan periksa metode pembayaran Anda dan coba lagi.
            <?php if (!empty($order_id)): ?>
                <br><br>Order ID: <strong><?= htmlspecialchars($order_id); ?></strong>
            <?php endif; ?>
        </div>

        <div>
            <a href="../dashboard_user.php" class="btn">Kembali ke Dashboard</a>
            <a href="../dashboard_user.php" class="btn btn-secondary">Coba Lagi</a>
        </div>
    </div>
</body>

</html>