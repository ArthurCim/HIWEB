<?php
include "../db.php";
header('Content-Type: application/json; charset=utf-8');

$id_stage = isset($_POST['id_stage']) ? trim($_POST['id_stage']) : '';
if (!$id_stage) { echo json_encode(['status'=>'error','message'=>'ID kosong']); exit; }

// cek dependensi (contoh: question referencing stage) — jika ada, tolak hapus atau hapus cascade sesuai kebutuhan
// contoh: hitung jumlah question yg terkait
$chk = $conn->prepare("SELECT COUNT(*) as cnt FROM question WHERE id_stage = ?");
$chk->bind_param("i", $id_stage);
$chk->execute();
$res = $chk->get_result()->fetch_assoc();
if ($res && intval($res['cnt']) > 0) {
    echo json_encode(['status'=>'error','message'=>'Tidak bisa menghapus: ada question yang terkait dengan stage ini. Hapus question terlebih dahulu atau gunakan penghapusan cascade.']);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM stage WHERE id_stage = ?");
    $stmt->bind_param("i", $id_stage);
    $ok = $stmt->execute();
    if ($ok) echo json_encode(['status'=>'success','message'=>'Stage berhasil dihapus']);
    else echo json_encode(['status'=>'error','message'=>'Gagal menghapus: '.$conn->error]);
} catch (Exception $e){
    echo json_encode(['status'=>'error','message'=>'Exception: '.$e->getMessage()]);
}
