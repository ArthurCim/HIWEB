<?php
include "../db.php";
header('Content-Type: application/json; charset=utf-8');

$id_lesson = $_GET['id_lesson'] ?? '';

if (!$id_lesson) {
    echo json_encode([]);
    exit;
}

// Return list of stages for the lesson (frontend expects an array)
$stmt = $conn->prepare("SELECT id_stage, nama_stage, deskripsi, type FROM stage WHERE id_lesson = ? ORDER BY nama_stage ASC");
$stmt->bind_param('s', $id_lesson);
$stmt->execute();
$res = $stmt->get_result();

$out = [];
while ($r = $res->fetch_assoc()) {
    $out[] = $r;
}

echo json_encode($out);
