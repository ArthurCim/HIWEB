<?php
// ajax/get_stages.php
include "../db.php";
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['login'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$id_lesson = $_GET['id_lesson'] ?? '';
$type = $_GET['type'] ?? ''; // optional: filter by type (quiz/materi)

if (!$id_lesson) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT id_stage, nama_stage, type, deskripsi 
        FROM stage 
        WHERE id_lesson = ?";

if ($type) {
    $sql .= " AND type = ?";
}

$sql .= " ORDER BY CAST(REGEXP_REPLACE(nama_stage, '[^0-9]', '') AS UNSIGNED) ASC";

$stmt = $conn->prepare($sql);

if ($type) {
    $stmt->bind_param("ss", $id_lesson, $type);
} else {
    $stmt->bind_param("s", $id_lesson);
}

$stmt->execute();
$result = $stmt->get_result();

$stages = [];
while ($row = $result->fetch_assoc()) {
    $stages[] = $row;
}

$stmt->close();

echo json_encode($stages);