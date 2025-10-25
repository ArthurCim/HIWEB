<?php
include "../db.php";
header('Content-Type: application/json; charset=utf-8');

$id_lesson = $_GET['id_lesson'] ?? '';

if (!$id_lesson) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT id_stage, nama_stage, deskripsi, type, id_lesson, id_materi FROM stage WHERE id_lesson = ?");
$stmt->bind_param('s', $id_lesson);
$stmt->execute();
$res = $stmt->get_result();

$stages = [];
while ($r = $res->fetch_assoc()) {
    $stage = $r;
    $stage['details'] = [];

    // Get materi details if type is materi
    if ($r['type'] === 'materi') {
        try {
            // Newer flow: stage stores id_materi (single combined materi record)
            if (!empty($r['id_materi'])) {
                $materi_stmt = $conn->prepare("SELECT id_materi, konten as isi, file_url, created_at, id_materi FROM materi WHERE id_materi = ? LIMIT 1");
                if ($materi_stmt) {
                    $materi_stmt->bind_param('s', $r['id_materi']);
                    $materi_stmt->execute();
                    $materi_res = $materi_stmt->get_result();
                    if ($m = $materi_res->fetch_assoc()) {
                        $stage['details'][] = [
                            'id_detail' => $m['id_materi'],
                            'judul' => '',
                            'isi' => $m['isi'],
                            'media' => $m['file_url']
                        ];
                    }
                    $materi_stmt->close();
                }
            } else {
                // Legacy flow: materi rows reference id_stage directly
                $materi_stmt = $conn->prepare("SELECT id_materi, judul, isi, file_url FROM materi WHERE id_stage = ?");
                if ($materi_stmt) {
                    $materi_stmt->bind_param('s', $r['id_stage']);
                    $materi_stmt->execute();
                    $materi_res = $materi_stmt->get_result();
                    while ($m = $materi_res->fetch_assoc()) {
                        $stage['details'][] = [
                            'id_detail' => $m['id_materi'],
                            'judul' => $m['judul'],
                            'isi' => $m['isi'],
                            'media' => $m['file_url']
                        ];
                    }
                    $materi_stmt->close();
                }
            }
        } catch (Exception $e) {
            // If query fails, we'll just return empty details
            error_log("Failed to get materi details: " . $e->getMessage());
        }
    }
    // Get quiz details if type is quiz
    else if ($r['type'] === 'quiz') {
        try {
            $quiz_stmt = $conn->prepare("
                SELECT q.id_question, q.content as question_text,
                       q.id_stage, q.answer_type,
                       qo.id_option, qo.option_text, qo.is_correct
                FROM question q
                LEFT JOIN question_option qo ON qo.id_question = q.id_question
                WHERE q.id_stage = ?
            ");
            if ($quiz_stmt) {
                $quiz_stmt->bind_param('s', $r['id_stage']);
                $quiz_stmt->execute();
                $quiz_res = $quiz_stmt->get_result();

                $quiz_details = [];
                while ($q = $quiz_res->fetch_assoc()) {
                    if (!isset($quiz_details[$q['id_question']])) {
                        $quiz_details[$q['id_question']] = [
                            'id_detail' => $q['id_question'],
                            'isi' => $q['question_text'],
                            'quiz_type' => $q['answer_type'] ?? 'pilihan_ganda',
                            'options' => []
                        ];
                    }
                    if ($q['id_option']) {
                        $quiz_details[$q['id_question']]['options'][] = [
                            'id' => $q['id_option'],
                            'text' => $q['option_text'],
                            'is_correct' => $q['is_correct']
                        ];
                    }
                }
                $stage['details'] = array_values($quiz_details);
                $quiz_stmt->close();
            }
        } catch (Exception $e) {
            // If query fails, we'll just return empty details
            error_log("Failed to get quiz details: " . $e->getMessage());
        }
    }

    $stages[] = $stage;
}

echo json_encode($stages);
