<?php
// ajax/stage_delete.php
include "../db.php";
header('Content-Type: application/json; charset=utf-8');
session_start();
if (!isset($_SESSION['login'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

$id_stage = isset($_POST['id_stage']) ? trim($_POST['id_stage']) : '';
if ($id_stage === '') {
    echo json_encode(['status' => 'error', 'message' => 'Parameter invalid']);
    exit;
}

try {
    // First, remove related question options and questions (if any)
    $delOptions = $conn->prepare("DELETE qo FROM question_option qo INNER JOIN question q ON q.id_question = qo.id_question WHERE q.id_stage = ?");
    $delOptions->bind_param('s', $id_stage);
    $delOptions->execute();
    $delOptions->close();

    $delQuestions = $conn->prepare("DELETE FROM question WHERE id_stage = ?");
    $delQuestions->bind_param('s', $id_stage);
    $delQuestions->execute();
    $delQuestions->close();

    // Delete materi records linked via stage.id_materi (if any)
    $selMat = $conn->prepare("SELECT id_materi FROM stage WHERE id_stage = ? LIMIT 1");
    $selMat->bind_param('s', $id_stage);
    $selMat->execute();
    $matRes = $selMat->get_result()->fetch_assoc();
    $selMat->close();
    if (!empty($matRes['id_materi'])) {
        $delMat = $conn->prepare("DELETE FROM materi WHERE id_materi = ?");
        $delMat->bind_param('s', $matRes['id_materi']);
        $delMat->execute();
        $delMat->close();
    }

    // Finally delete the stage
    $stmt = $conn->prepare("DELETE FROM stage WHERE id_stage = ?");
    $stmt->bind_param('s', $id_stage);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Stage berhasil dihapus']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Hapus gagal: ' . $stmt->error]);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Exception: ' . $e->getMessage()]);
}
