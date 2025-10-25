<?php
// ajax/stage_delete.php
include "../db.php";
header('Content-Type: application/json; charset=utf-8');
session_start();
if (!isset($_SESSION['login'])) {
    echo json_encode(['status'=>'error','message'=>'Not authenticated']); exit;
}
$id_stage = isset($_POST['id_stage']) ? intval($_POST['id_stage']) : 0;
if (!$id_stage) { echo json_encode(['status'=>'error','message'=>'Parameter invalid']); exit; }

// jika ada foreign key constraint (soal/dll), kamu mungkin ingin cek atau hapus cascade. Di sini kita coba hapus langsung.
$stmt = $conn->prepare("DELETE FROM stage WHERE id_stage = ?");
$stmt->bind_param('i', $id_stage);
if ($stmt->execute()) {
    echo json_encode(['status'=>'success','message'=>'Stage berhasil dihapus']);
} else {
    echo json_encode(['status'=>'error','message'=>'Hapus gagal: '.$stmt->error]);
}
$stmt->close();
