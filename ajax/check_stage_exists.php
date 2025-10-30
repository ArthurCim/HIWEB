<?php
include "../db.php";
header('Content-Type: application/json; charset=utf-8');

$id_lesson = $_GET['id_lesson'] ?? '';

if (!$id_lesson) {
    echo json_encode(['has_stage' => false]);
    exit;
}

$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM stage WHERE id_lesson = ?");
$stmt->bind_param('s', $id_lesson);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

echo json_encode(['has_stage' => ($res['cnt'] > 0)]);
