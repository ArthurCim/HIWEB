<?php
include "db.php";

$result = mysqli_query($conn, "SELECT id_user, nama, email, is_premium FROM users LIMIT 10");
echo "=== EXISTING USERS ===\n";
$count = 0;
while ($row = mysqli_fetch_assoc($result)) {
    echo ($count + 1) . ". ID: " . $row['id_user'] . " | Name: " . $row['nama'] . " | Email: " . $row['email'] . " | Premium: " . ($row['is_premium'] ? "YES" : "NO") . "\n";
    $count++;
}
