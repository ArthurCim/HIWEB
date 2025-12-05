<?php
require_once __DIR__ . '/../db.php';

// Add order_id column if not exists
$sql = "ALTER TABLE transactions ADD COLUMN IF NOT EXISTS order_id VARCHAR(100) DEFAULT NULL";
// Note: MySQL doesn't support IF NOT EXISTS for ALTER ADD COLUMN before 8.0. We'll check existence first.
$res = $conn->query("SHOW COLUMNS FROM transactions LIKE 'order_id'");
if ($res && $res->num_rows == 0) {
    $alter = $conn->query("ALTER TABLE transactions ADD COLUMN order_id VARCHAR(100) DEFAULT NULL");
    if ($alter) echo "Added order_id column\n";
    else echo "Failed to add column: " . $conn->error . "\n";
} else {
    echo "order_id column already exists\n";
}
