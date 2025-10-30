<?php
// ajax/stage_generate.php
include "../db.php";
header('Content-Type: application/json; charset=utf-8');
session_start();
if (!isset($_SESSION['login'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

$id_lesson = isset($_POST['id_lesson']) ? intval($_POST['id_lesson']) : 0;
$count = isset($_POST['count']) ? intval($_POST['count']) : 0;
$default_type = isset($_POST['default_type']) && in_array($_POST['default_type'], ['materi', 'quiz']) ? $_POST['default_type'] : 'materi';

if (!$id_lesson || $count <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Parameter invalid']);
    exit;
}
if ($count > 200) {
    echo json_encode(['status' => 'error', 'message' => 'Count terlalu besar (max 200)']);
    exit;
}

// cek lesson exist
$stmt = $conn->prepare("SELECT id_lesson FROM lesson WHERE id_lesson = ?");
$stmt->bind_param('i', $id_lesson);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Lesson tidak ditemukan']);
    exit;
}
$stmt->close();

// mulai transaction
$conn->begin_transaction();
try {
    // Hitung jumlah stage yang sudah ada
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM stage WHERE id_lesson = ?");
    $stmt->bind_param('i', $id_lesson);
    $stmt->execute();
    $res = $stmt->get_result();
    $start = 1;
    if ($res && $res->num_rows) {
        $row = $res->fetch_assoc();
        $start = $row['total'] + 1; // Mulai dari nomor setelah stage terakhir
    }
    $stmt->close();

    $ins = $conn->prepare("INSERT INTO stage (id_lesson, nama_stage, deskripsi, type) VALUES (?, ?, ?, ?)");
    for ($i = 0; $i < $count; $i++) {
        $num = $start + $i;
        $nama = "Stage {$num}";
        $desc = "";
        $type = $default_type;
        $ins->bind_param('isss', $id_lesson, $nama, $desc, $type);
        if (!$ins->execute()) throw new Exception("DB insert failed: " . $ins->error);
    }
    $ins->close();
    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => "Berhasil membuat {$count} stage untuk lesson."]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Gagal membuat stage: ' . $e->getMessage()]);
}
