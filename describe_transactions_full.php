<?php
require_once 'db.php';
$res = $conn->query('DESCRIBE transactions');
while($r = $res->fetch_assoc()){
    echo $r['Field'].' ('.$r['Type'].') NULL='.$r['Null'].' DEFAULT='.$r['Default'].