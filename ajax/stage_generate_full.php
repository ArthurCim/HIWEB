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
function genid($prefix = 'id') {
    return $prefix . '_' . bin2hex(random_bytes(6));
}

// 🔧 Direktori upload
$uploadDir = __DIR__ . '/../uploads/materi/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// 🔧 Fungsi upload file media
function handleFileUpload($file, $uploadDir) {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) return null;
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $name = bin2hex(random_bytes(8)) . ($ext ? ('.' . $ext) : '');
    $dest = rtrim($uploadDir, '/') . '/' . $name;
    if (move_uploaded_file($file['tmp_name'], $dest)) return $name;
    return null;
}

$conn->begin_transaction();

try {
    // 🧱 Ambil semua stage dari FormData
    $stages = $_POST['stages'] ?? [];
    
    if (empty($stages)) {
        throw new Exception('Tidak ada stage yang dikirim');
    }

    foreach ($stages as $i => $s) {
        $id_stage = $s['id_stage'] ?? genid('st');
        $nama_stage = $s['nama_stage'] ?? ('Stage ' . ($i + 1));
        $deskripsi = $s['deskripsi'] ?? '';
        $type = $s['type'] ?? 'materi';
        $id_materi = null;

        // 🧠 Kalau stage = materi → buat dulu data materi
        if ($type === 'materi') {
            // Gabungkan semua detail materi menjadi satu konten
            $all_content = '';
            $first_media = null;
            
            if (!empty($s['details']) && is_array($s['details'])) {
                foreach ($s['details'] as $dIdx => $d) {
                    $judul = $d['judul'] ?? '';
                    $isi = $d['isi'] ?? '';
                    
                    // Buat HTML content dengan wrapper yang rapi
                    if ($judul || $isi) {
                        $all_content .= '<div class="materi-section">';
                        if ($judul) {
                            $all_content .= '<h4>' . htmlspecialchars($judul) . '</h4>';
                        }
                        if ($isi) {
                            $all_content .= '<div class="materi-content">' . $isi . '</div>';
                        }
                        $all_content .= '</div>';
                    }
                    
                    // Ambil file upload pertama yang ada
                    if ($first_media === null && isset($_FILES['stages'])) {
                        // Coba beberapa format nama field yang mungkin
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
            
            // Insert ke tabel materi
            $id_materi = genid('mt');
            $created_at = date('Y-m-d H:i:s');
            
            $stmt = $conn->prepare("INSERT INTO materi (id_materi, konten, file_url, created_at) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $id_materi, $all_content, $first_media, $created_at);
            $stmt->execute();
            $stmt->close();
        }

        // 🧩 Insert stage utama (dengan id_materi jika type=materi)
        $stmt = $conn->prepare("INSERT INTO stage (id_stage, id_lesson, nama_stage, deskripsi, type, id_materi) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $id_stage, $id_lesson, $nama_stage, $deskripsi, $type, $id_materi);
        $stmt->execute();
        $stmt->close();

        // 🧩 Kalau stage = quiz → simpan question & options
        if ($type === 'quiz' && !empty($s['details']) && is_array($s['details'])) {
            // Ambil detail pertama sebagai question
            $detail = $s['details'][0] ?? [];
            $pertanyaan = $detail['isi'] ?? '';
            $quiz_type = $detail['quiz_type'] ?? 'pilihan_ganda';
            $id_question = genid('qz');

            // Insert question
            $stmt = $conn->prepare("INSERT INTO question (id_question, id_stage, content, answers_type) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $id_question, $id_stage, $pertanyaan, $quiz_type);
            $stmt->execute();
            $stmt->close();

            // Insert options (untuk pilihan ganda)
            if ($quiz_type === 'pilihan_ganda' && !empty($detail['options']) && is_array($detail['options'])) {
                foreach ($detail['options'] as $opt) {
                    $text = trim($opt['text'] ?? '');
                    
                    // Skip opsi yang kosong
                    if (empty($text)) continue;
                    
                    $id_option = genid('op');
                    // Checkbox name format: stage[i][details][0][options][j][is_correct]
                    // Jika checked, nilainya 'on' atau '1'
                    $is_correct = (isset($opt['is_correct']) && ($opt['is_correct'] === 'on' || $opt['is_correct'] === '1')) ? 1 : 0;

                    $stmt = $conn->prepare("INSERT INTO question_option (id_question_option, id_question, option_text, is_correct) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("sssi", $id_option, $id_question, $text, $is_correct);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    }

    $conn->commit();
    $total = count($stages);
    echo json_encode([
        'status' => 'success', 
        'message' => "Berhasil menyimpan {$total} stage dengan detail lengkap!"
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    
    // Log detail error ke file
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