<?php

/**
 * Guide untuk testing payment di Midtrans Sandbox
 */

echo "=== CARA TESTING PAYMENT DI MIDTRANS SANDBOX ===\n\n";

echo "1. Ketika user klik 'Upgrade' dan memilih paket:\n";
echo "   - Sistem akan membuat payment token dan membuka Midtrans Snap\n";
echo "   - Di sandbox, Midtrans akan show payment methods\n\n";

echo "2. Pilih payment method (e.g., Credit Card atau E-wallet):\n";
echo "   Untuk Credit Card, gunakan test card:\n";
echo "   - Card Number: 4111111111111111\n";
echo "   - CVV: 123\n";
echo "   - Exp Month: 12\n";
echo "   - Exp Year: 2025\n\n";

echo "3. Klik 'Complete': \n";
echo "   - Midtrans akan show confirmation\n";
echo "   - Klik 'Continue' atau 'Confirm'\n\n";

echo "4. Status berhasil:\n";
echo "   - Payment akan berhasil\n";
echo "   - Midtrans akan trigger webhook ke notification.php\n";
echo "   - Webhook akan:\n";
echo "     a) Create subscription record (user_subscriptions)\n";
echo "     b) Update transaction status ke SETTLEMENT\n";
echo "     c) Set users.is_premium = 1\n\n";

echo "5. Dashboard akan:\n";
echo "   - Reload dengan sync_premium_status.php\n";
echo "   - Fetch updated is_premium dari database\n";
echo "   - Display 'Premium' status\n\n";

echo "=== JIKA PAYMENT STUCK DI PENDING ===\n\n";

echo "Kemungkinan:\n";
echo "1. User tidak menyelesaikan payment flow di Midtrans\n";
echo "2. Payment gateway timed out\n";
echo "3. Webhook configuration tidak benar\n\n";

echo "Solusi:\n";
echo "1. Pastikan 'Midtrans Notification URL' sudah dikonfigurasi di Midtrans Dashboard:\n";
echo "   http://localhost:8080/HIWEB/midtrans/notification.php\n\n";

echo "2. Untuk manual testing (jika payment gagal), bisa gunakan:\n";
echo "   /midtrans/manual_settlement.php?order_id=ORDER_ID\n";
echo "   atau CLI: php test_manual_settlement.php\n\n";

echo "CATATAN: Di production, webhook HARUS dikonfigurasi dengan benar\n";
echo "dan payment HARUS diselesaikan dari Midtrans, bukan manual.\n";
