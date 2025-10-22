<?php
include "../db.php";
header('Content-Type: application/json; charset=utf-8');

$id_stage = isset($_GET['id_stage']) ? intval($_GET['id_stage']) : 0;
if (!$id_stage) { echo json_encode(['status'=>'error','message'=>'ID kosong']); exit; }

$stmt = $conn->prepare("SELECT s.id_stage, s.nama_stage, s.deskripsi, s.type, s.id_lesson, l.id_courses
                        FROM stage s LEFT JOIN lesson l ON s.id_lesson = l.id_lesson
                        WHERE s.id_stage = ? LIMIT 1");
$stmt->bind_param("i", $id_stage);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
if (!$res) { echo json_encode(['status'=>'error','message'=>'Tidak ditemukan']); exit; }
echo json_encode(['status'=>'success','data'=>$res]);
