<?php
require_once 'db.php';
$result = $conn->query('DESCRIBE transactions');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' (' . $row['Type'] . ")\n";
}
