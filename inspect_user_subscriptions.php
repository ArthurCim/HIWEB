<?php
include 'db.php';
$res = $conn->query("SHOW COLUMNS FROM user_subscriptions");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        echo $r['Field'] . "\t" . $r['Type'] . "\n";
    }
}
