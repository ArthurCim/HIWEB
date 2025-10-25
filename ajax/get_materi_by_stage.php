<?php
include "../db.php";
header('Content-Type: application/json; charset=utf-8');

$id_stage = $_GET['id_stage'] ?? '';

if (!$id_stage) {
    echo json_encode([]);
    exit;
}

try {
    // Get stage_detail entries that are materi type for this stage
    $stmt = $conn->prepare("
        SELECT s.id_detail, s.type, m.id_materi, m.judul, m.isi, m.file_url 
        FROM stage_detail s 
        LEFT JOIN materi m ON m.id_materi = s.id_materi 
        WHERE s.id_stage = ? AND s.type = 'materi'
    ");
    $stmt->bind_param('s', $id_stage);
    $stmt->execute();
    $res = $stmt->get_result();

    $materi = [];
    while ($m = $res->fetch_assoc()) {
        $materi[] = [
            'id_detail' => $m['id_detail'],
            'judul' => $m['judul'],
            'isi' => $m['isi'],
            'media' => $m['file_url']
        ];
    }
    $stmt->close();

    // Get questions and options for this stage
    $stmt = $conn->prepare("
        SELECT q.id_question, q.content as question_text, q.answer_type,
               qo.id_option, qo.option_text, qo.is_correct
        FROM question q
        LEFT JOIN question_option qo ON qo.id_question = q.id_question
        WHERE q.id_stage = ?
    ");
    $stmt->bind_param('s', $id_stage);
    $stmt->execute();
    $res = $stmt->get_result();

    $questions = [];
    while ($q = $res->fetch_assoc()) {
        $questions[] = [
            'id_question' => $q['id_question'],
            'question_text' => $q['question_text'],
            'answer_type' => $q['answer_type'],
            'options' => isset($q['id_option']) ? [
                'id_option' => $q['id_option'],
                'option_text' => $q['option_text'],
                'is_correct' => $q['is_correct']
            ] : null
        ];
    }
    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'data' => [
            'materi' => $materi,
            'questions' => $questions
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal mengambil data materi dan pertanyaan'
    ]);
}
