<?php
include "../db.php";
header('Content-Type: application/json; charset=utf-8');

$id_stage = isset($_POST['id_stage']) ? trim($_POST['id_stage']) : '';
$id_lesson = isset($_POST['id_lesson']) ? trim($_POST['id_lesson']) : '';
$nama_stage = isset($_POST['nama_stage']) ? trim($_POST['nama_stage']) : '';
$deskripsi = isset($_POST['deskripsi']) ? trim($_POST['deskripsi']) : '';

if (!$id_stage || !$id_lesson || !$nama_stage) {
    echo json_encode(['status'=>'error','message'=>'Data tidak lengkap']); exit;
}

try {
    $stmt = $conn->prepare("UPDATE stage SET nama_stage = ?, deskripsi = ?, id_lesson = ? WHERE id_stage = ?");
    $stmt->bind_param("ssii", $nama_stage, $deskripsi, $id_lesson, $id_stage);
    $ok = $stmt->execute();
    if ($ok) echo json_encode(['status'=>'success','message'=>'Stage berhasil diperbarui']);
    else echo json_encode(['status'=>'error','message'=>'Gagal update: '.$conn->error]);
} catch (Exception $e){
    echo json_encode(['status'=>'error','message'=>'Exception: '.$e->getMessage()]);
}
