<?php
include "../db.php";
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['login'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$id_stage = $_POST['id_stage'] ?? '';
$type = $_POST['type'] ?? '';
if (!$id_stage || !$type) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
    exit;
}

function handleFileUpload($fieldName, $uploadDir)
{
    if (!isset($_FILES[$fieldName])) return null;
    $f = $_FILES[$fieldName];
    if ($f['error'] !== UPLOAD_ERR_OK) return null;
    $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
    $name = bin2hex(random_bytes(8)) . ($ext ? ('.' . $ext) : '');
    $dest = rtrim($uploadDir, '/') . '/' . $name;
    if (move_uploaded_file($f['tmp_name'], $dest)) return $name;
    return null;
}

$uploadDir = __DIR__ . '/../uploads/stage_images/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$conn->begin_transaction();

try {
    // update basic info
    $nama_stage = $_POST['nama_stage'] ?? '';
    $deskripsi = $_POST['deskripsi'] ?? '';
    $stmt = $conn->prepare("UPDATE stage SET nama_stage=?, deskripsi=?, type=? WHERE id_stage=?");
    // id_stage treated as string (ids like 'st_xxx')
    $stmt->bind_param("ssss", $nama_stage, $deskripsi, $type, $id_stage);
    $stmt->execute();
    $stmt->close();

    // update materi
    // Only update materi details if materi-specific fields or files were provided
    if ($type === 'materi' && (isset($_POST['judul']) || isset($_POST['isi']) || isset($_FILES['media']))) {
        $judul = $_POST['judul'] ?? '';
        $isi = $_POST['isi'] ?? '';
        $media = handleFileUpload('media', $uploadDir);

        $stmt = $conn->prepare("UPDATE stage_detail SET judul=?, isi=?" . ($media ? ", media=?" : "") . " WHERE id_stage=?");
        if ($media) {
            // judul, isi, media, id_stage (id_stage string)
            $stmt->bind_param("ssss", $judul, $isi, $media, $id_stage);
        } else {
            // judul, isi, id_stage
            $stmt->bind_param("sss", $judul, $isi, $id_stage);
        }
        $stmt->execute();
        $stmt->close();
    }

    // update quiz
    // Only update quiz details if quiz-specific fields provided
    if ($type === 'quiz' && (isset($_POST['pertanyaan']) || isset($_POST['options']) || isset($_POST['tipe']))) {
        $pertanyaan = $_POST['pertanyaan'] ?? '';
        $tipe = $_POST['tipe'] ?? 'pilihan_ganda';

        $stmt = $conn->prepare("UPDATE question SET pertanyaan=?, tipe=? WHERE id_stage=?");
        // pertanyaan, tipe, id_stage (id_stage treated as string)
        $stmt->bind_param("sss", $pertanyaan, $tipe, $id_stage);
        $stmt->execute();
        $stmt->close();

        // hapus opsi lama dan masukkan ulang (use prepared statement)
        $delStmt = $conn->prepare("DELETE qo FROM question_option qo INNER JOIN question q ON q.id_question = qo.id_question WHERE q.id_stage = ?");
        $delStmt->bind_param('s', $id_stage);
        $delStmt->execute();
        $delStmt->close();

        if (!empty($_POST['options']) && is_array($_POST['options'])) {
            $selStmt = $conn->prepare("SELECT id_question FROM question WHERE id_stage = ? LIMIT 1");
            $selStmt->bind_param('s', $id_stage);
            $selStmt->execute();
            $resQ = $selStmt->get_result();
            $getQid = $resQ ? $resQ->fetch_assoc()['id_question'] : null;
            $selStmt->close();
            if ($getQid) {
                foreach ($_POST['options'] as $opt) {
                    $id_option = bin2hex(random_bytes(6));
                    $text = $opt['text'] ?? '';
                    $is_correct = isset($opt['is_correct']) ? 1 : 0;

                    $stmt = $conn->prepare("INSERT INTO question_option (id_option, id_question, text_option, is_correct) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("sssi", $id_option, $getQid, $text, $is_correct);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    }

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Stage berhasil diperbarui']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Gagal: ' . $e->getMessage()]);
}
