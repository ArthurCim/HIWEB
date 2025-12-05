<?php
// Matikan semua output error agar tidak ganggu JSON response
error_reporting(0);
ini_set('display_errors', 0);

include "../db.php";
session_start();

// Bersihkan output buffer yang mungkin ada
ob_clean();

header('Content-Type: application/json; charset=utf-8');

// 🧱 Cek login
if (!isset($_SESSION['login'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// 🧱 Input utama
$id_lesson = $_POST['id_lesson'] ?? '';
if (!$id_lesson) {
    echo json_encode(['status' => 'error', 'message' => 'Lesson invalid']);
    exit;
}

// 🔧 Helper buat ID acak
function genid($prefix = 'id')
{
    return $prefix . '_' . bin2hex(random_bytes(6));
}

// 🔧 Direktori upload
$uploadDir = __DIR__ . '/../uploads/materi/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// 🔧 Fungsi upload file media
function handleFileUpload($file, $uploadDir)
{
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) return null;
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $name = bin2hex(random_bytes(8)) . ($ext ? ('.' . $ext) : '');
    $dest = rtrim($uploadDir, '/') . '/' . $name;
    if (move_uploaded_file($file['tmp_name'], $dest)) return $name;
    return null;
}

$conn->begin_transaction();

try {
    // try to accept structured JSON payload (preferred) or fall back to POST arrays
    $rawStagesJson = $_POST['stages_json'] ?? '';
    if ($rawStagesJson) {
        $stages = json_decode($rawStagesJson, true);
        if (!is_array($stages)) $stages = [];
    } else {
        // Fallback: if stages provided as nested POST fields, the structure may be complex.
        // For now, prefer stages_json. If absent, try to use simple POST['stages'] if present.
        $stages = $_POST['stages'] ?? [];
    }

    if (empty($stages)) {
        throw new Exception('Tidak ada stage yang dikirim');
    }

    // Deleted stages list (from builder deletedStages)
    $deleted_raw = $_POST['deleted_stages'] ?? '';
    $deleted_stages = [];
    if ($deleted_raw) {
        $d = json_decode($deleted_raw, true);
        if (is_array($d)) $deleted_stages = $d;
    }

    // Process deletions first
    if (!empty($deleted_stages)) {
        foreach ($deleted_stages as $ds) {
            // normalize
            $ds = trim($ds);
            if ($ds === '') continue;

            // remove question options and questions
            $delOptions = $conn->prepare("DELETE qo FROM question_option qo INNER JOIN question q ON q.id_question = qo.id_question WHERE q.id_stage = ?");
            $delOptions->bind_param('s', $ds);
            $delOptions->execute();
            $delOptions->close();

            $delQuestions = $conn->prepare("DELETE FROM question WHERE id_stage = ?");
            $delQuestions->bind_param('s', $ds);
            $delQuestions->execute();
            $delQuestions->close();

            // fetch linked materi id (if any) and delete
            $selMat = $conn->prepare("SELECT id_materi FROM stage WHERE id_stage = ? LIMIT 1");
            $selMat->bind_param('s', $ds);
            $selMat->execute();
            $mr = $selMat->get_result()->fetch_assoc();
            $selMat->close();
            if (!empty($mr['id_materi'])) {
                $delMat = $conn->prepare("DELETE FROM materi WHERE id_materi = ?");
                $delMat->bind_param('s', $mr['id_materi']);
                $delMat->execute();
                $delMat->close();
            }

            // delete stage
            $delStage = $conn->prepare("DELETE FROM stage WHERE id_stage = ?");
            $delStage->bind_param('s', $ds);
            $delStage->execute();
            $delStage->close();
        }
    }

    // Now process stages (create or update)
    $processed = 0;
    foreach ($stages as $i => $s) {
        $id_stage = $s['id_stage'] ?? genid('st');
        $nama_stage = $s['nama_stage'] ?? ('Stage ' . ($i + 1));
        $deskripsi = $s['deskripsi'] ?? '';
        $type = $s['type'] ?? 'materi';
        $isExisting = isset($s['isExisting']) && ($s['isExisting'] === true || $s['isExisting'] === '1' || $s['isExisting'] === 1);

        $id_materi = null;

        // If existing, check current row
        $exists = false;
        if ($isExisting) {
            $check = $conn->prepare("SELECT id_stage, id_materi FROM stage WHERE id_stage = ? LIMIT 1");
            $check->bind_param('s', $id_stage);
            $check->execute();
            $cres = $check->get_result()->fetch_assoc();
            $check->close();
            if ($cres) {
                $exists = true;
                $old_id_materi = $cres['id_materi'] ?? null;
            }
        }

        // If materi, build content and insert materi record
        if ($type === 'materi') {
            $all_content = '';
            $first_media = null;
            if (!empty($s['details']) && is_array($s['details'])) {
                foreach ($s['details'] as $dIdx => $d) {
                    $judul = $d['judul'] ?? '';
                    $isi = $d['isi'] ?? '';
                    if ($judul || $isi) {
                        $all_content .= '<div class="materi-section">';
                        if ($judul) $all_content .= '<h4>' . htmlspecialchars($judul) . '</h4>';
                        if ($isi) $all_content .= '<div class="materi-content">' . $isi . '</div>';
                        $all_content .= '</div>';
                    }
                    // file upload detection (best-effort)
                    if ($first_media === null && isset($_FILES['stages'])) {
                        $possibleKeys = [
                            "stages_{$i}_details_{$dIdx}_media",
                            "stage[{$i}][details][{$dIdx}][media]"
                        ];
                        foreach ($possibleKeys as $fileKey) {
                            if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                                $first_media = handleFileUpload($_FILES[$fileKey], $uploadDir);
                                break 2;
                            }
                        }
                    }
                }
            }

            // If updating and old materi existed, delete it to avoid orphan duplicates
            if (!empty($old_id_materi)) {
                $delMatOld = $conn->prepare("DELETE FROM materi WHERE id_materi = ?");
                $delMatOld->bind_param('s', $old_id_materi);
                $delMatOld->execute();
                $delMatOld->close();
            }

            $id_materi = genid('mt');
            $created_at = date('Y-m-d H:i:s');
            $stmt = $conn->prepare("INSERT INTO materi (id_materi, konten, file_url, created_at) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $id_materi, $all_content, $first_media, $created_at);
            $stmt->execute();
            $stmt->close();
        }

        if ($exists) {
            // update existing stage
            $update = $conn->prepare("UPDATE stage SET nama_stage = ?, deskripsi = ?, type = ?, id_materi = ? WHERE id_stage = ?");
            $update->bind_param('sssss', $nama_stage, $deskripsi, $type, $id_materi, $id_stage);
            $update->execute();
            $update->close();

            // remove old quiz data if switching to quiz
            if ($type === 'quiz') {
                // delete existing question/options for this stage
                $delOptions = $conn->prepare("DELETE qo FROM question_option qo INNER JOIN question q ON q.id_question = qo.id_question WHERE q.id_stage = ?");
                $delOptions->bind_param('s', $id_stage);
                $delOptions->execute();
                $delOptions->close();

                $delQuestions = $conn->prepare("DELETE FROM question WHERE id_stage = ?");
                $delQuestions->bind_param('s', $id_stage);
                $delQuestions->execute();
                $delQuestions->close();
            }
        } else {
            // insert new stage
            $ins = $conn->prepare("INSERT INTO stage (id_stage, id_lesson, nama_stage, deskripsi, type, id_materi) VALUES (?, ?, ?, ?, ?, ?)");
            $ins->bind_param('ssssss', $id_stage, $id_lesson, $nama_stage, $deskripsi, $type, $id_materi);
            $ins->execute();
            $ins->close();
        }

        // handle quiz details (insert question + options)
        if ($type === 'quiz' && !empty($s['details']) && is_array($s['details'])) {
            $detail = $s['details'][0] ?? [];
            $pertanyaan = $detail['isi'] ?? '';
            $quiz_type = $detail['quiz_type'] ?? 'pilihan_ganda';

            $id_question = genid('qz');
            $stmt = $conn->prepare("INSERT INTO question (id_question, id_stage, content, answers_type) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('ssss', $id_question, $id_stage, $pertanyaan, $quiz_type);
            $stmt->execute();
            $stmt->close();

            if ($quiz_type === 'pilihan_ganda' && !empty($detail['options']) && is_array($detail['options'])) {
                foreach ($detail['options'] as $opt) {
                    $text = trim($opt['text'] ?? '');
                    if ($text === '') continue;
                    $id_option = genid('op');
                    $is_correct = (isset($opt['is_correct']) && ($opt['is_correct'] === 'on' || $opt['is_correct'] === '1' || $opt['is_correct'] === 1)) ? 1 : 0;
                    $stmt = $conn->prepare("INSERT INTO question_option (id_question_option, id_question, option_text, is_correct) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param('sssi', $id_option, $id_question, $text, $is_correct);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        $processed++;
    }

    $conn->commit();
    echo json_encode([
        'status' => 'success',
        'message' => "Berhasil menyimpan {$processed} stage dengan detail!",
        'processed' => $processed
    ]);
} catch (Exception $e) {
    $conn->rollback();
    $logFile = __DIR__ . '/debug_stage_generate.log';
    $logMsg = date('Y-m-d H:i:s') . " ERROR:\n";
    $logMsg .= "Message: " . $e->getMessage() . "\n";
    $logMsg .= "POST: " . print_r($_POST, true) . "\n";
    $logMsg .= "FILES: " . print_r($_FILES, true) . "\n";
    $logMsg .= str_repeat('-', 80) . "\n";
    file_put_contents($logFile, $logMsg, FILE_APPEND);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => basename($e->getFile())
    ]);
}
