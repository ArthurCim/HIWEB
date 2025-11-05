<?php
// stage_generate_full.php - FIXED WITH PROPER QUIZ HANDLING
error_reporting(0);
ini_set('display_errors', 0);

include "../db.php";
session_start();

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

// 🔧 Strip HTML tags - pastikan hanya text yang disimpan
function stripHtmlTags($html)
{
    // Remove all HTML tags
    $text = strip_tags($html);
    // Decode HTML entities
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // Trim whitespace
    $text = trim($text);
    return $text;
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

// 🔧 FUNGSI BARU: Hapus question dan semua data terkait
function deleteQuestionWithRelatedData($conn, $questionId)
{
    if (empty($questionId)) return false;
    
    try {
        // Hapus question_answer terlebih dahulu
        $delAns = $conn->prepare("DELETE FROM question_answer WHERE id_question = ?");
        $delAns->bind_param('s', $questionId);
        $delAns->execute();
        $delAns->close();

        // Hapus question_option
        $delOpt = $conn->prepare("DELETE FROM question_option WHERE id_question = ?");
        $delOpt->bind_param('s', $questionId);
        $delOpt->execute();
        $delOpt->close();

        // Hapus question
        $delQ = $conn->prepare("DELETE FROM question WHERE id_question = ?");
        $delQ->bind_param('s', $questionId);
        $delQ->execute();
        $delQ->close();

        return true;
    } catch (Exception $e) {
        error_log("Error deleting question {$questionId}: " . $e->getMessage());
        return false;
    }
}

// 🔧 FUNGSI BARU: Hapus materi
function deleteMateri($conn, $materiId)
{
    if (empty($materiId)) return false;
    
    try {
        $delStmt = $conn->prepare("DELETE FROM materi WHERE id_materi = ?");
        $delStmt->bind_param('s', $materiId);
        $success = $delStmt->execute();
        $delStmt->close();
        return $success;
    } catch (Exception $e) {
        error_log("Error deleting materi {$materiId}: " . $e->getMessage());
        return false;
    }
}

// 🔧 Fungsi untuk hapus stage dengan validasi dan cascade delete
function deleteStageWithValidation($conn, $stageId)
{
    if (empty($stageId)) {
        return ['success' => false, 'message' => 'Stage ID kosong'];
    }

    try {
        // 1️⃣ VALIDASI: Cek apakah stage ada
        $checkStmt = $conn->prepare("SELECT id_stage, id_materi, id_question, nama_stage FROM stage WHERE id_stage = ? LIMIT 1");
        $checkStmt->bind_param('s', $stageId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if (!$result || $result->num_rows === 0) {
            $checkStmt->close();
            return ['success' => false, 'message' => "Stage dengan ID $stageId tidak ditemukan"];
        }

        $stageData = $result->fetch_assoc();
        $id_materi = $stageData['id_materi'];
        $id_question = $stageData['id_question'];
        $nama_stage = $stageData['nama_stage'];
        $checkStmt->close();

        // 2️⃣ HAPUS DATA TERKAIT

        // Hapus question dan data terkait jika ada
        if ($id_question) {
            deleteQuestionWithRelatedData($conn, $id_question);
        }

        // Hapus materi jika ada
        if ($id_materi) {
            deleteMateri($conn, $id_materi);
        }

        // 3️⃣ Hapus stage
        $delStageStmt = $conn->prepare("DELETE FROM stage WHERE id_stage = ?");
        $delStageStmt->bind_param('s', $stageId);
        $success = $delStageStmt->execute();
        $delStageStmt->close();

        if ($success) {
            return [
                'success' => true,
                'message' => "Stage '$nama_stage' berhasil dihapus beserta materi/quiz terkait"
            ];
        } else {
            return ['success' => false, 'message' => 'Gagal menghapus stage dari database'];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

// 🔧 FUNGSI BARU: Handle quiz creation/update
function handleQuizOperations($conn, $stageData, $id_stage, $isExisting, $old_id_question)
{
    $actions = [];
    $id_question = null;

    try {
        $detail = $stageData['details'][0] ?? [];
        $pertanyaan = stripHtmlTags($detail['isi'] ?? '');

        // ⚠️ Validasi: Pertanyaan wajib ada
        if (empty(trim($pertanyaan))) {
            throw new Exception("Stage {$stageData['nama_stage']}: Pertanyaan quiz tidak boleh kosong!");
        }

        // ⚠️ Validasi: Harus ada minimal 2 opsi
        if (empty($detail['options']) || !is_array($detail['options']) || count($detail['options']) < 2) {
            throw new Exception("Stage {$stageData['nama_stage']}: Quiz harus memiliki minimal 2 opsi jawaban!");
        }

        // ⚠️ Validasi: Harus ada minimal 1 jawaban benar
        $hasCorrect = false;
        foreach ($detail['options'] as $opt) {
            if (isset($opt['is_correct']) && ($opt['is_correct'] === 'on' || $opt['is_correct'] === '1' || $opt['is_correct'] === 1 || $opt['is_correct'] === true)) {
                $hasCorrect = true;
                break;
            }
        }
        if (!$hasCorrect) {
            throw new Exception("Stage {$stageData['nama_stage']}: Quiz harus memiliki minimal 1 jawaban benar!");
        }

        // 1️⃣ HAPUS OLD QUESTION JIKA ADA (untuk update atau perubahan type)
        if ($isExisting && $old_id_question) {
            deleteQuestionWithRelatedData($conn, $old_id_question);
        }

        // 2️⃣ CREATE NEW QUESTION
        $id_question = genid('qz');
        $stmt = $conn->prepare("INSERT INTO question (id_question, content) VALUES (?, ?)");
        $stmt->bind_param("ss", $id_question, $pertanyaan);
        $stmt->execute();
        $stmt->close();

        // 3️⃣ INSERT OPTIONS & COLLECT CORRECT ANSWER IDs
        $correct_option_ids = [];
        $option_count = 0;

        foreach ($detail['options'] as $opt) {
            $text = trim(stripHtmlTags($opt['text'] ?? ''));
            if (empty($text)) continue; // Skip empty options

            $id_option = genid('op');
            $is_correct = (isset($opt['is_correct']) && ($opt['is_correct'] === 'on' || $opt['is_correct'] === '1' || $opt['is_correct'] === 1 || $opt['is_correct'] === true)) ? 1 : 0;

            // Insert option
            $stmt = $conn->prepare("INSERT INTO question_option (id_question_option, id_question, option_text, is_correct) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $id_option, $id_question, $text, $is_correct);
            $stmt->execute();
            $stmt->close();

            $option_count++;

            // Collect correct answer IDs
            if ($is_correct) {
                $correct_option_ids[] = $id_option;
            }
        }

        // ⚠️ Final validation: Pastikan ada opsi yang ter-insert
        if ($option_count === 0) {
            throw new Exception("Stage {$stageData['nama_stage']}: Tidak ada opsi valid yang bisa disimpan!");
        }

        // 4️⃣ INSERT QUESTION_ANSWER untuk setiap jawaban benar
        foreach ($correct_option_ids as $correct_id) {
            $id_answer = genid('ans');
            $stmt = $conn->prepare("INSERT INTO question_answer (id_question_answer, id_question, correct_answer) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $id_answer, $id_question, $correct_id);
            $stmt->execute();
            $stmt->close();
        }

        $actions = [
            'id_question' => $id_question,
            'options_count' => $option_count,
            'correct_answers' => count($correct_option_ids)
        ];

    } catch (Exception $e) {
        // Rollback question creation if any error
        if ($id_question) {
            deleteQuestionWithRelatedData($conn, $id_question);
        }
        throw $e;
    }

    return $actions;
}

// 🔧 FUNGSI BARU: Handle materi operations - FIXED COLUMN ISSUE
function handleMateriOperations($conn, $stageData, $isExisting, $old_id_materi, $uploadDir)
{
    $id_materi = $old_id_materi;
    
    // Gabungkan semua detail materi menjadi satu konten TEKS BIASA
    $all_content = '';
    $first_media = null;

    if (!empty($stageData['details']) && is_array($stageData['details'])) {
        foreach ($stageData['details'] as $dIdx => $d) {
            $isi = $d['isi'] ?? '';

            // Strip HTML dari isi - simpan sebagai plain text
            $isi = stripHtmlTags($isi);

            // Gabungkan sebagai plain text dengan pemisah sederhana
            if ($isi) {
                $all_content .= $isi . "\n\n";
                $all_content .= "---\n\n"; // Pemisah antar sub-materi
            }

            // Handle file upload jika ada
            if ($first_media === null && isset($_FILES['stages'])) {
                $possibleKeys = [
                    "stages_{$stageData['index']}_details_{$dIdx}_media",
                    "stage[{$stageData['index']}][details][{$dIdx}][media]"
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

    // Trim trailing separator
    $all_content = rtrim($all_content, "\n-");

    // UPDATE materi yang sudah ada - TANPA updated_at
    if ($isExisting && $old_id_materi) {
        if ($first_media) {
            $stmt = $conn->prepare("UPDATE materi SET konten = ?, file_url = ? WHERE id_materi = ?");
            $stmt->bind_param("sss", $all_content, $first_media, $id_materi);
        } else {
            $stmt = $conn->prepare("UPDATE materi SET konten = ? WHERE id_materi = ?");
            $stmt->bind_param("ss", $all_content, $id_materi);
        }
        $stmt->execute();
        $stmt->close();
    } else {
        // Insert materi baru - TANPA updated_at
        $id_materi = genid('mt');
        $created_at = date('Y-m-d H:i:s');

        $stmt = $conn->prepare("INSERT INTO materi (id_materi, konten, file_url, created_at) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $id_materi, $all_content, $first_media, $created_at);
        $stmt->execute();
        $stmt->close();
    }

    return $id_materi;
}

$conn->begin_transaction();

try {
    // 🗑️ PROSES DELETED STAGES DENGAN VALIDASI
    $deletedStages = [];
    if (!empty($_POST['deleted_stages'])) {
        $decoded = json_decode($_POST['deleted_stages'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $deletedStages = $decoded;
        }
    }

    $deletedCount = 0;
    $deletedMessages = [];

    if (!empty($deletedStages)) {
        foreach ($deletedStages as $stageId) {
            if (empty($stageId)) continue;

            // Gunakan fungsi validasi dan hapus
            $deleteResult = deleteStageWithValidation($conn, $stageId);

            if ($deleteResult['success']) {
                $deletedCount++;
                $deletedMessages[] = "✓ " . $deleteResult['message'];
            } else {
                // Jika gagal, rollback dan return error
                $conn->rollback();
                echo json_encode([
                    'status' => 'error',
                    'message' => $deleteResult['message']
                ]);
                exit;
            }
        }
    }

    // 🧱 Ambil semua stage dari FormData
    $stages = $_POST['stages'] ?? [];

    if (!empty($stages) && is_string($stages)) {
        $dec = json_decode($stages, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($dec)) {
            $stages = $dec;
        }
    }

    if (empty($stages) && !empty($_POST['stages_json'])) {
        $dec = json_decode($_POST['stages_json'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($dec)) {
            $stages = $dec;
        }
    }

    // Jika tidak ada stages sama sekali dan tidak ada yang dihapus
    if (empty($stages) && $deletedCount === 0) {
        $conn->rollback();
        echo json_encode([
            'status' => 'error',
            'message' => 'Tidak ada stage untuk diproses.',
            'total' => 0
        ]);
        exit;
    }

    $actions = [];
    $stageCounter = 1; // Counter untuk nama stage otomatis

    // Proses stages yang ada
    if (!empty($stages) && is_array($stages)) {
        foreach ($stages as $i => $s) {
            $id_stage = $s['id_stage'] ?? genid('st');
            $isExisting = ($s['isExisting'] ?? '0') === '1';

            // 🎯 AUTO-GENERATE NAMA STAGE berdasarkan urutan
            $nama_stage = 'Stage ' . $stageCounter;
            $stageCounter++;

            $deskripsi = $s['deskripsi'] ?? '';
            $type = $s['type'] ?? 'materi';
            
            // Tambahkan index untuk referensi file upload
            $s['index'] = $i;
            $s['nama_stage'] = $nama_stage;

            // Check existing stage data
            $old_id_materi = null;
            $old_id_question = null;

            if ($isExisting) {
                $chkStmt = $conn->prepare("SELECT id_stage, id_materi, id_question FROM stage WHERE id_stage = ? LIMIT 1");
                $chkStmt->bind_param('s', $id_stage);
                $chkStmt->execute();
                $chkRes = $chkStmt->get_result();
                if ($chkRes && $chkRes->num_rows) {
                    $rowChk = $chkRes->fetch_assoc();
                    $old_id_materi = $rowChk['id_materi'];
                    $old_id_question = $rowChk['id_question'];
                }
                $chkStmt->close();
            }

            $id_materi = null;
            $id_question = null;
            $stageActions = [];

            // 🧠 PROSES BERDASARKAN TYPE
            if ($type === 'materi') {
                // Handle materi operations
                $id_materi = handleMateriOperations($conn, $s, $isExisting, $old_id_materi, $uploadDir);
                
                // Hapus old question jika ada (perubahan dari quiz ke materi)
                if ($old_id_question) {
                    deleteQuestionWithRelatedData($conn, $old_id_question);
                }
                
                $stageActions['materi'] = $id_materi;

            } else if ($type === 'quiz') {
                // Handle quiz operations
                $quizResult = handleQuizOperations($conn, $s, $id_stage, $isExisting, $old_id_question);
                $id_question = $quizResult['id_question'];
                $stageActions['quiz'] = $quizResult;

                // Hapus old materi jika ada (perubahan dari materi ke quiz)
                if ($old_id_materi) {
                    deleteMateri($conn, $old_id_materi);
                }
            }

            // 🧩 INSERT OR UPDATE STAGE UTAMA
            if ($isExisting) {
                $upStmt = $conn->prepare("UPDATE stage SET id_lesson = ?, nama_stage = ?, deskripsi = ?, type = ?, id_materi = ?, id_question = ? WHERE id_stage = ?");
                $upStmt->bind_param("sssssss", $id_lesson, $nama_stage, $deskripsi, $type, $id_materi, $id_question, $id_stage);
                $upStmt->execute();
                $upStmt->close();

                $actionType = 'updated';
                if ($type === 'quiz') {
                    $actionType = 'updated_quiz';
                }
            } else {
                $stmt = $conn->prepare("INSERT INTO stage (id_stage, id_lesson, nama_stage, deskripsi, type, id_materi, id_question) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $id_stage, $id_lesson, $nama_stage, $deskripsi, $type, $id_materi, $id_question);
                $stmt->execute();
                $stmt->close();

                $actionType = 'inserted';
                if ($type === 'quiz') {
                    $actionType = 'created_quiz';
                }
            }

            // Track perubahan type
            if ($isExisting) {
                if ($type === 'materi' && $old_id_question) {
                    $stageActions['type_change'] = 'quiz_to_materi';
                } else if ($type === 'quiz' && $old_id_materi) {
                    $stageActions['type_change'] = 'materi_to_quiz';
                }
            }

            $actions[] = [
                'id_stage' => $id_stage,
                'action' => $actionType,
                'type' => $type,
                'details' => $stageActions
            ];
        }
    }

    $conn->commit();

    $total = count($stages);
    $message = "Berhasil menyimpan {$total} stage!";

    if ($deletedCount > 0) {
        $message = "Berhasil menghapus {$deletedCount} stage dan menyimpan {$total} stage!";
    }

    echo json_encode([
        'status' => 'success',
        'message' => $message,
        'actions' => $actions,
        'deleted_count' => $deletedCount,
        'deleted_details' => $deletedMessages
    ]);
} catch (Exception $e) {
    $conn->rollback();

    $logFile = __DIR__ . '/debug_stage_generate.log';
    $logMsg = date('Y-m-d H:i:s') . " ERROR:\n";
    $logMsg .= "Message: " . $e->getMessage() . "\n";
    $logMsg .= "Line: " . $e->getLine() . "\n";
    $logMsg .= "Trace: " . $e->getTraceAsString() . "\n";
    $logMsg .= str_repeat('-', 80) . "\n";
    file_put_contents($logFile, $logMsg, FILE_APPEND);

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => basename($e->getFile())
    ]);
}
?>