<?php
include "../db.php";
header('Content-Type: application/json; charset=utf-8');
session_start();

// ambil parameter apa adanya (string)
$id_courses = isset($_GET['id_courses']) && $_GET['id_courses'] !== '' ? $_GET['id_courses'] : '';

if ($id_courses) {
    $stmt = $conn->prepare("SELECT id_lesson, nama_lesson FROM lesson WHERE id_courses = ? ORDER BY nama_lesson ASC");
    $stmt->bind_param('s', $id_courses); // 🟢 pakai 's' karena id_courses adalah string
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query("SELECT id_lesson, nama_lesson FROM lesson ORDER BY nama_lesson ASC");
}

$out = [];
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $out[] = $r;
    }
}

echo json_encode($out);
