<?php
include 'db.php';
$res = $conn->query("SELECT id_answer FROM stage_answer LIMIT 1");
if ($res) {
    $a = $res->fetch_assoc();
    if ($a) echo $a['id_answer'];
}
