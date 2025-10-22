<?php
include "../db.php";
header('Content-Type: application/json; charset=utf-8');

$id_courses = isset($_GET['id_courses']) ? trim($_GET['id_courses']) : '';
if (!$id_courses) { echo json_encode([]); exit; }

$stmt = $conn->prepare("SELECT id_lesson, nama_lesson FROM lesson WHERE id_courses = ? ORDER BY nama_lesson ASC");
$stmt->bind_param("s", $id_courses);
$stmt->execute();
$res = $stmt->get_result();
$out = [];
while ($r = $res->fetch_assoc()) $out[] = $r;
echo json_encode($out);
