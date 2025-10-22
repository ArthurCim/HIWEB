<?php
include "../db.php";
header('Content-Type: application/json; charset=utf-8');

// Input
$id_lesson = isset($_POST['id_lesson']) ? trim($_POST['id_lesson']) : '';
$count = isset($_POST['count']) ? intval($_POST['count']) : 0;
$default_type = isset($_POST['default_type']) && in_array($_POST['default_type'], ['materi','quiz']) ? $_POST['default_type'] : 'materi';

if (!$id_lesson || $count <= 0) {
    echo json_encode(['status'=>'error','message'=>'Lesson dan jumlah stage wajib diisi valid']); 
    exit;
}

try {
    $conn->begin_transaction();

    // Ambil nomor terakhir dari id_stage (misal: stage_010)
    $q = $conn->query("SELECT MAX(CAST(SUBSTRING(id_stage, 7) AS UNSIGNED)) AS maxnum FROM stage");
    $row = $q->fetch_assoc();
    $start_num = $row && $row['maxnum'] ? intval($row['maxnum']) : 0;

    $stmt = $conn->prepare("INSERT INTO stage (id_stage, nama_stage, deskripsi, type, id_lesson) VALUES (?, ?, ?, ?, ?)");

    for ($i = 1; $i <= $count; $i++) {
        $next_num = $start_num + $i;
        $id_stage = 'stage_' . str_pad($next_num, 3, '0', STR_PAD_LEFT);
        $name = "Stage " . $next_num;
        $desc = "Materi untuk " . $name;
        $stmt->bind_param("ssssi", $id_stage, $name, $desc, $default_type, $id_lesson);

        if (!$stmt->execute()) {
            throw new Exception("Gagal insert: " . $stmt->error);
        }
    }

    $conn->commit();
    echo json_encode(['status'=>'success','message'=>"Berhasil generate {$count} stage baru."]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status'=>'error','message'=>'Error: '.$e->getMessage()]);
}
