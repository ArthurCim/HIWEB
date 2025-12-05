<?php
// Test auto_settle_payments.php endpoint
session_start();
$_SESSION['login'] = true;
$_SESSION['id_user'] = '5ad04b1f-6f66-400e-a727-e5269184a2c3'; // azizaan user

echo "=== TESTING AUTO-SETTLE ENDPOINT ===\n\n";

include __DIR__ . '/includes/auto_settle_payments.php';
