<?php
include "../db.php";
header('Content-Type: application/json; charset=utf-8');

$id_stage = isset($_POST['id_stage']) ? intval($_POST['id_stage']) : 0;
if (!$id_stage) { echo json_encode(['status'=>'error','message'=>'ID kosong']); exit; }

// cek dependensi question
$chk = $conn->prepare("SELECT COUNT(*) AS cnt FROM question WHERE id_stage = ?");
$chk->bind_param("i", $id_stage);
$chk->execute();
$r = $chk->get_result()->fetch_assoc();
if ($r && intval($r['cnt']) > 0) {
    echo json_encode(['status'=>'error','message'=>'Tidak dapat menghapus: terdapat question yang terkait. Hapus question terlebih dahulu.']); exit;
}

$stmt = $conn->prepare("DELETE FROM stage WHERE id_stage = ?");
$stmt->bind_param("i", $id_stage);
$ok = $stmt->execute();
if ($ok) echo json_encode(['status'=>'success','message'=>'Stage berhasil dihapus']);
else echo json_encode(['status'=>'error','message'=>'Gagal hapus: '.$conn->error]);
