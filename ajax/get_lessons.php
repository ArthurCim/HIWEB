<?php
// ajax/get_lessons.php
include "../db.php";
header('Content-Type: application/json; charset=utf-8');
session_start();

// optional filter by course
$id_courses = isset($_GET['id_courses']) && $_GET['id_courses'] !== '' ? intval($_GET['id_courses']) : 0;

if ($id_courses) {
    $stmt = $conn->prepare("SELECT id_lesson, nama_lesson FROM lesson WHERE id_courses = ? ORDER BY nama_lesson ASC");
    $stmt->bind_param('i', $id_courses);
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
