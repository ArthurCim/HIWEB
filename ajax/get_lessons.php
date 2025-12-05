<?php
// ajax/get_lessons.php
include "../db.php";
header('Content-Type: application/json; charset=utf-8');
session_start();

// optional filter by course (id_courses is VARCHAR like id_lesson, not integer)
$id_courses = isset($_GET['id_courses']) && $_GET['id_courses'] !== '' ? trim($_GET['id_courses']) : '';

if ($id_courses) {
    $stmt = $conn->prepare("SELECT id_lesson, nama_lesson FROM lesson WHERE id_courses = ? ORDER BY nama_lesson ASC");
    $stmt->bind_param('s', $id_courses);
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
