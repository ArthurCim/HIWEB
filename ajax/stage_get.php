<?php
// ajax/stage_get.php
include "../db.php";
header('Content-Type: application/json; charset=utf-8');
session_start();
if (!isset($_SESSION['login'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}
$id_stage = isset($_GET['id_stage']) ? $_GET['id_stage'] : '';
if ($id_stage === '') {
    echo json_encode(['status' => 'error', 'message' => 'Parameter invalid']);
    exit;
}

$stmt = $conn->prepare("SELECT id_stage, id_lesson, nama_stage, deskripsi, type FROM stage WHERE id_stage = ? LIMIT 1");
$stmt->bind_param('s', $id_stage);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows) {
    $row = $res->fetch_assoc();
    echo json_encode(['status' => 'success', 'data' => $row]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan']);
}
$stmt->close();
