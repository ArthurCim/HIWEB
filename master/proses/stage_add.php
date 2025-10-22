<?php
include "../db.php";
header('Content-Type: application/json; charset=utf-8');

$id_lesson = isset($_POST['id_lesson']) ? trim($_POST['id_lesson']) : '';
$nama_stage = isset($_POST['nama_stage']) ? trim($_POST['nama_stage']) : '';
$deskripsi = isset($_POST['deskripsi']) ? trim($_POST['deskripsi']) : '';

if (!$id_lesson || !$nama_stage) {
    echo json_encode(['status'=>'error','message'=>'Lesson dan nama stage wajib diisi']); exit;
}

try {
    $stmt = $conn->prepare("INSERT INTO stage (nama_stage, deskripsi, id_lesson) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nama_stage, $deskripsi, $id_lesson);
    $ok = $stmt->execute();
    if ($ok) echo json_encode(['status'=>'success','message'=>'Stage berhasil ditambahkan']);
    else echo json_encode(['status'=>'error','message'=>'Gagal menambahkan: '.$conn->error]);
} catch (Exception $e){
    echo json_encode(['status'=>'error','message'=>'Exception: '.$e->getMessage()]);
}
