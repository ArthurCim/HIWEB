<?php
include "../db.php";
header('Content-Type: application/json; charset=utf-8');

$id_lesson = $_GET['id_lesson'] ?? '';

if (!$id_lesson) {
    echo json_encode([]);
    exit;
}

// ✅ Select stages WITH id_question for quiz lookup
$stmt = $conn->prepare("SELECT id_stage, nama_stage, deskripsi, type, id_lesson, id_materi, id_question 
                       FROM stage 
                       WHERE id_lesson = ? 
                       ORDER BY CAST(REGEXP_REPLACE(nama_stage, '[^0-9]', '') AS UNSIGNED)");
$stmt->bind_param('s', $id_lesson);
$stmt->execute();
$res = $stmt->get_result();

$stages = [];
$counter = 1;

while ($r = $res->fetch_assoc()) {
    $stage = $r;
    // Ensure stage names are sequential
    $stage['nama_stage'] = "Stage " . $counter++;
    $stage['details'] = [];

    // Get materi details if type is materi
    if ($r['type'] === 'materi') {
        try {
            // Newer flow: stage stores id_materi (single combined materi record)
            if (!empty($r['id_materi'])) {
                $materi_stmt = $conn->prepare("SELECT id_materi, konten as isi, file_url, created_at FROM materi WHERE id_materi = ? LIMIT 1");
                if ($materi_stmt) {
                    $materi_stmt->bind_param('s', $r['id_materi']);
                    $materi_stmt->execute();
                    $materi_res = $materi_stmt->get_result();
                    if ($m = $materi_res->fetch_assoc()) {
                        $stage['details'][] = [
                            'id_detail' => $m['id_materi'],
                            'judul' => '',
                            'isi' => $m['isi'] ?? '',
                            'media' => $m['file_url'] ?? ''
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
                            'judul' => $m['judul'] ?? '',
                            'isi' => $m['isi'] ?? '',
                            'media' => $m['file_url'] ?? ''
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
    // ✅ FIXED: Get quiz details if type is quiz
    else if ($r['type'] === 'quiz' && !empty($r['id_question'])) {
        try {
            // ✅ Query menggunakan stage.id_question, bukan q.id_stage
            $quiz_stmt = $conn->prepare("
                SELECT 
                    q.id_question, 
                    q.content as question_text,
                    qo.id_question_option, 
                    qo.option_text, 
                    qo.is_correct
                FROM question q
                LEFT JOIN question_option qo ON qo.id_question = q.id_question
                WHERE q.id_question = ?
                ORDER BY qo.id_question_option ASC
            ");
            
            if ($quiz_stmt) {
                $quiz_stmt->bind_param('s', $r['id_question']);
                $quiz_stmt->execute();
                $quiz_res = $quiz_stmt->get_result();

                $quiz_data = null;
                $options = [];

                while ($q = $quiz_res->fetch_assoc()) {
                    // Build quiz data from first row
                    if ($quiz_data === null) {
                        $quiz_data = [
                            'id_detail' => $q['id_question'],
                            'isi' => $q['question_text'] ?? '',
                            'quiz_type' => 'pilihan_ganda', // Default to multiple choice
                            'options' => []
                        ];
                    }
                    
                    // Collect options
                    if (!empty($q['id_question_option'])) {
                        $options[] = [
                            'id' => $q['id_question_option'],
                            'text' => $q['option_text'] ?? '',
                            'is_correct' => intval($q['is_correct'] ?? 0)
                        ];
                    }
                }

                // Add options to quiz data
                if ($quiz_data !== null) {
                    $quiz_data['options'] = $options;
                    $stage['details'][] = $quiz_data;
                    
                    // Debug log
                    error_log("✅ Quiz loaded for stage {$stage['nama_stage']}: " . json_encode($quiz_data));
                }

                $quiz_stmt->close();
            }
        } catch (Exception $e) {
            // If query fails, we'll just return empty details
            error_log("❌ Failed to get quiz details: " . $e->getMessage());
        }
    }

    $stages[] = $stage;
}

// Debug log final output
error_log("📦 Returning " . count($stages) . " stages for lesson {$id_lesson}");

echo json_encode($stages, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);