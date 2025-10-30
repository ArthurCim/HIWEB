<?php
// stage_generate_full.php - FIXED WITH PROPER CASCADE DELETE
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
function stripHtmlTags($html) {
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

// 🔧 Fungsi untuk hapus stage dengan validasi dan cascade delete
function deleteStageWithValidation($conn, $stageId) {
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

        // 2️⃣ VALIDASI: Cek apakah ada user progress terkait (opsional, sesuaikan dengan table Anda)
        // Uncomment jika ada table user_progress atau sejenisnya
        /*
        $progressStmt = $conn->prepare("SELECT COUNT(*) as total FROM user_progress WHERE id_stage = ?");
        $progressStmt->bind_param('s', $stageId);
        $progressStmt->execute();
        $progressResult = $progressStmt->get_result();
        $progressCount = $progressResult->fetch_assoc()['total'];
        $progressStmt->close();

        if ($progressCount > 0) {
            return [
                'success' => false, 
                'message' => "Stage '$nama_stage' tidak dapat dihapus karena sudah ada $progressCount user yang mengaksesnya"
            ];
        }
        */

        // 3️⃣ HAPUS CHILD RECORDS DULU (CASCADE DELETE MANUAL)
        
        // Hapus question options jika ada
        if ($id_question) {
            $delOptStmt = $conn->prepare("DELETE FROM question_option WHERE id_question = ?");
            $delOptStmt->bind_param('s', $id_question);
            $delOptStmt->execute();
            $delOptStmt->close();

            // Hapus question
            $delQStmt = $conn->prepare("DELETE FROM question WHERE id_question = ?");
            $delQStmt->bind_param('s', $id_question);
            $delQStmt->execute();
            $delQStmt->close();
        }

        // 4️⃣ SET NULL FOREIGN KEY DULU sebelum hapus materi
        // Ini menghindari foreign key constraint error
        $nullifyStmt = $conn->prepare("UPDATE stage SET id_materi = NULL, id_question = NULL WHERE id_stage = ?");
        $nullifyStmt->bind_param('s', $stageId);
        $nullifyStmt->execute();
        $nullifyStmt->close();

        // 5️⃣ Hapus materi jika ada
        if ($id_materi) {
            $delMateriStmt = $conn->prepare("DELETE FROM materi WHERE id_materi = ?");
            $delMateriStmt->bind_param('s', $id_materi);
            $delMateriStmt->execute();
            $delMateriStmt->close();
        }

        // 6️⃣ TERAKHIR: Hapus stage
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

$conn->begin_transaction();

try {
    // 🗑️ FIX: PROSES DELETED STAGES DENGAN VALIDASI
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
            
            // 🎯 AUTO-GENERATE NAMA STAGE berdasarkan urutan
            $nama_stage = 'Stage ' . $stageCounter;
            $stageCounter++;
            
            $deskripsi = $s['deskripsi'] ?? '';
            $type = $s['type'] ?? 'materi';
            $id_materi = null;
            $id_question = null;

            // Check whether this stage already exists in DB
            $isExisting = false;
            $old_id_materi = null;
            $old_id_question = null;
            
            $chkStmt = $conn->prepare("SELECT id_stage, id_materi, id_question FROM stage WHERE id_stage = ? LIMIT 1");
            $chkStmt->bind_param('s', $id_stage);
            $chkStmt->execute();
            $chkRes = $chkStmt->get_result();
            if ($chkRes && $chkRes->num_rows) {
                $rowChk = $chkRes->fetch_assoc();
                $isExisting = true;
                $old_id_materi = $rowChk['id_materi'];
                $old_id_question = $rowChk['id_question'];
            }
            $chkStmt->close();

            // 🧠 Kalau stage = materi → buat atau update materi
            if ($type === 'materi') {
                // Gabungkan semua detail materi menjadi satu konten TEKS BIASA
                $all_content = '';
                $first_media = null;

                if (!empty($s['details']) && is_array($s['details'])) {
                    foreach ($s['details'] as $dIdx => $d) {
                        $judul = $d['judul'] ?? '';
                        $isi = $d['isi'] ?? '';

                        // Strip HTML dari judul dan isi - simpan sebagai plain text
                        $judul = stripHtmlTags($judul);
                        $isi = stripHtmlTags($isi);

                        // Gabungkan sebagai plain text dengan pemisah sederhana
                        if ($judul || $isi) {
                            if ($judul) {
                                $all_content .= $judul . "\n\n";
                            }
                            if ($isi) {
                                $all_content .= $isi . "\n\n";
                            }
                            $all_content .= "---\n\n"; // Pemisah antar sub-materi
                        }

                        // Ambil file upload pertama yang ada
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

                // Trim trailing separator
                $all_content = rtrim($all_content, "\n-");

                // UPDATE materi yang sudah ada, bukan buat baru
                if ($isExisting && $old_id_materi) {
                    $id_materi = $old_id_materi;
                    $updated_at = date('Y-m-d H:i:s');
                    
                    if ($first_media) {
                        $stmt = $conn->prepare("UPDATE materi SET konten = ?, file_url = ?, created_at = ? WHERE id_materi = ?");
                        $stmt->bind_param("ssss", $all_content, $first_media, $updated_at, $id_materi);
                    } else {
                        $stmt = $conn->prepare("UPDATE materi SET konten = ?, created_at = ? WHERE id_materi = ?");
                        $stmt->bind_param("sss", $all_content, $updated_at, $id_materi);
                    }
                    $stmt->execute();
                    $stmt->close();
                } else {
                    // Insert materi baru
                    $id_materi = genid('mt');
                    $created_at = date('Y-m-d H:i:s');

                    $stmt = $conn->prepare("INSERT INTO materi (id_materi, konten, file_url, created_at) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssss", $id_materi, $all_content, $first_media, $created_at);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            // 🧩 Insert or Update stage utama
            if ($isExisting) {
                $upStmt = $conn->prepare("UPDATE stage SET id_lesson = ?, nama_stage = ?, deskripsi = ?, type = ?, id_materi = ?, id_question = ? WHERE id_stage = ?");
                $upStmt->bind_param("sssssss", $id_lesson, $nama_stage, $deskripsi, $type, $id_materi, $id_question, $id_stage);
                $upStmt->execute();
                $upStmt->close();

                // HAPUS old_question jika ada dan berbeda
                if ($old_id_question && $old_id_question !== $id_question) {
                    $delOpt = $conn->prepare("DELETE FROM question_option WHERE id_question = ?");
                    $delOpt->bind_param('s', $old_id_question);
                    $delOpt->execute();
                    $delOpt->close();

                    $delQ = $conn->prepare("DELETE FROM question WHERE id_question = ?");
                    $delQ->bind_param('s', $old_id_question);
                    $delQ->execute();
                    $delQ->close();
                }

                $actions[] = [
                    'id_stage' => $id_stage,
                    'action' => 'updated',
                    'id_materi' => $id_materi
                ];
            } else {
                $stmt = $conn->prepare("INSERT INTO stage (id_stage, id_lesson, nama_stage, deskripsi, type, id_materi, id_question) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $id_stage, $id_lesson, $nama_stage, $deskripsi, $type, $id_materi, $id_question);
                $stmt->execute();
                $stmt->close();

                $actions[] = [
                    'id_stage' => $id_stage,
                    'action' => 'inserted',
                    'id_materi' => $id_materi
                ];
            }

            // 🧩 Kalau stage = quiz → simpan question & options
            if ($type === 'quiz' && !empty($s['details']) && is_array($s['details'])) {
                $detail = $s['details'][0] ?? [];
                $pertanyaan = $detail['isi'] ?? '';
                $quiz_type = $detail['quiz_type'] ?? 'pilihan_ganda';

                // Strip HTML dari pertanyaan
                $pertanyaan = stripHtmlTags($pertanyaan);

                // UPDATE question yang sudah ada
                if ($isExisting && $old_id_question) {
                    $id_question = $old_id_question;
                    
                    $stmt = $conn->prepare("UPDATE question SET content = ?, answers_type = ? WHERE id_question = ?");
                    $stmt->bind_param("sss", $pertanyaan, $quiz_type, $id_question);
                    $stmt->execute();
                    $stmt->close();
                    
                    $delOpt = $conn->prepare("DELETE FROM question_option WHERE id_question = ?");
                    $delOpt->bind_param('s', $id_question);
                    $delOpt->execute();
                    $delOpt->close();
                } else {
                    $id_question = genid('qz');
                    
                    $stmt = $conn->prepare("INSERT INTO question (id_question, content, answers_type) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $id_question, $pertanyaan, $quiz_type);
                    $stmt->execute();
                    $stmt->close();
                }

                // Update stage with id_question
                $stmt = $conn->prepare("UPDATE stage SET id_question = ? WHERE id_stage = ?");
                $stmt->bind_param("ss", $id_question, $id_stage);
                $stmt->execute();
                $stmt->close();

                // Insert new options
                if ($quiz_type === 'pilihan_ganda' && !empty($detail['options']) && is_array($detail['options'])) {
                    foreach ($detail['options'] as $opt) {
                        $text = trim($opt['text'] ?? '');
                        if (empty($text)) continue;

                        // Strip HTML dari option text
                        $text = stripHtmlTags($text);

                        $id_option = genid('op');
                        $is_correct = (isset($opt['is_correct']) && ($opt['is_correct'] === 'on' || $opt['is_correct'] === '1')) ? 1 : 0;

                        $stmt = $conn->prepare("INSERT INTO question_option (id_question_option, id_question, option_text, is_correct) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("sssi", $id_option, $id_question, $text, $is_correct);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
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