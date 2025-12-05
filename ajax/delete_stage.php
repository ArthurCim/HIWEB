<?php
include "../db.php";
header('Content-Type: application/json; charset=utf-8');

$id_stage = $_POST['id_stage'] ?? 0;

if (!$id_stage) {
    echo json_encode(['success' => false, 'message' => 'ID stage tidak ditemukan']);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM stage WHERE id_stage = ?");
    $stmt->bind_param('i', $id_stage);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Stage tidak ditemukan']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}