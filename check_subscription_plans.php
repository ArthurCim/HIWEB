<?php
require_once 'db.php';
$res = $conn->query('DESCRIBE subscription_plans');
if (!$res) {
    echo 'Error: ' . $conn->error . PHP_EOL;
    exit;
}
while ($r = $res->fetch_assoc()) {
    echo $r['Field'] . ' (' . $r['Type'] . ')' . PHP_EOL;
}
