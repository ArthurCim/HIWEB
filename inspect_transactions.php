<?php
// Check transactions table structure
include "db.php";

$result = mysqli_query($conn, "DESCRIBE transactions");
echo "=== TRANSACTIONS TABLE STRUCTURE ===\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . " (" . $row['Type'] . ") - " . ($row['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . " - Default: " . ($row['Default'] ?? 'NONE') . "\n";
}
