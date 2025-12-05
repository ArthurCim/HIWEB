<?php
include "../db.php";
header('Content-Type: application/json; charset=utf-8');

$id_stage = $_GET['id_stage'] ?? '';

if (!$id_stage) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT s.id_detail, s.type,
               q.id_question, q.content as question_text, q.answer_type,
               qo.id_option, qo.option_text, qo.is_correct
        FROM stage_detail s
        LEFT JOIN question q ON q.id_question = s.id_question
        LEFT JOIN question_option qo ON qo.id_question = q.id_question
        WHERE s.id_stage = ? AND s.type = 'quiz'
    ");
    $stmt->bind_param('s', $id_stage);
    $stmt->execute();
    $res = $stmt->get_result();

    $questions = [];
    while ($q = $res->fetch_assoc()) {
        if (!isset($questions[$q['id_question']])) {
            $questions[$q['id_question']] = [
                'id_detail' => $q['id_question'],  // tetap gunakan id_detail untuk kompatibilitas frontend
                'isi' => $q['question_text'],
                'quiz_type' => $q['answer_type'] ?? 'pilihan_ganda',
                'options' => []
            ];
        }
        if ($q['id_option']) {
            $questions[$q['id_question']]['options'][] = [
                'id' => $q['id_option'],
                'text' => $q['option_text'],
                'is_correct' => $q['is_correct']
            ];
        }
    }
    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'data' => array_values($questions)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal mengambil data quiz'
    ]);
}
