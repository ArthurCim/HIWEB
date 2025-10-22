<?php
include "../db.php";
header('Content-Type: application/json; charset=utf-8');

$id_stage = isset($_POST['id_stage']) ? intval($_POST['id_stage']) : 0;
$nama_stage = isset($_POST['nama_stage']) ? trim($_POST['nama_stage']) : '';
$deskripsi = isset($_POST['deskripsi']) ? trim($_POST['deskripsi']) : '';
$type = isset($_POST['type']) && in_array($_POST['type'], ['materi','quiz']) ? $_POST['type'] : 'materi';

if (!$id_stage || !$nama_stage) { echo json_encode(['status'=>'error','message'=>'Data tidak lengkap']); exit; }

$stmt = $conn->prepare("UPDATE stage SET nama_stage = ?, deskripsi = ?, type = ? WHERE id_stage = ?");
$stmt->bind_param("sssi", $nama_stage, $deskripsi, $type, $id_stage);
$ok = $stmt->execute();
if ($ok) echo json_encode(['status'=>'success','message'=>'Stage berhasil diperbarui']);
else echo json_encode(['status'=>'error','message'=>'Gagal update: '.$conn->error]);
