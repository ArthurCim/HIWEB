<?php
include "../db.php";
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['login'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

$id_lesson = $_POST['id_lesson'] ?? '';

if (!$id_lesson) {
    echo json_encode(['status' => 'error', 'message' => 'No lesson ID provided']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // 1. Get all stages for this lesson ordered by their number
    $stmt = $conn->prepare("SELECT id_stage, nama_stage 
                           FROM stage 
                           WHERE id_lesson = ? 
                           ORDER BY CAST(REGEXP_REPLACE(nama_stage, '[^0-9]', '') AS UNSIGNED)");
    $stmt->bind_param('s', $id_lesson);
    $stmt->execute();
    $result = $stmt->get_result();

    $stages = [];
    $counter = 1;

    while ($row = $result->fetch_assoc()) {
        $stages[] = [
            'id_stage' => $row['id_stage'],
            'new_name' => "Stage " . $counter++
        ];
    }

    // 2. Update each stage with its corrected name
    $update = $conn->prepare("UPDATE stage SET nama_stage = ? WHERE id_stage = ?");

    foreach ($stages as $stage) {
        $update->bind_param('ss', $stage['new_name'], $stage['id_stage']);
        $update->execute();
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Stages reordered successfully',
        'stages' => $stages
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to reorder stages: ' . $e->getMessage()
    ]);
}
