<?php
// ajax/get_user_progress_detail.php
include "../db.php";
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['login'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$id_user = $_GET['id_user'] ?? '';
$id_lesson = $_GET['id_lesson'] ?? '';

if (!$id_user || !$id_lesson) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
    exit;
}

// Get user info
$userStmt = $conn->prepare("SELECT nama, email FROM users WHERE id_user = ?");
$userStmt->bind_param("s", $id_user);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();
$userStmt->close();

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
    exit;
}

// Get lesson and course info
$lessonStmt = $conn->prepare("
    SELECT l.nama_lesson, c.nama_courses 
    FROM lesson l 
    JOIN courses c ON l.id_courses = c.id_courses 
    WHERE l.id_lesson = ?
");
$lessonStmt->bind_param("s", $id_lesson);
$lessonStmt->execute();
$lessonResult = $lessonStmt->get_result();
$lesson = $lessonResult->fetch_assoc();
$lessonStmt->close();

// Get all stages with progress
$stagesStmt = $conn->prepare("
    SELECT 
        s.id_stage,
        s.nama_stage,
        s.type,
        s.deskripsi,
        usp.is_completed,
        usp.score,
        usp.completion_date
    FROM stage s
    LEFT JOIN user_stage_progress usp ON s.id_stage = usp.id_stage AND usp.id_user = ?
    WHERE s.id_lesson = ?
    ORDER BY CAST(REGEXP_REPLACE(s.nama_stage, '[^0-9]', '') AS UNSIGNED) ASC
");
$stagesStmt->bind_param("ss", $id_user, $id_lesson);
$stagesStmt->execute();
$stagesResult = $stagesStmt->get_result();

$stages = [];
while ($row = $stagesResult->fetch_assoc()) {
    $stages[] = $row;
}
$stagesStmt->close();

echo json_encode([
    'status' => 'success',
    'data' => [
        'user_name' => $user['nama'],
        'email' => $user['email'],
        'course_name' => $lesson['nama_courses'],
        'lesson_name' => $lesson['nama_lesson'],
        'stages' => $stages
    ]
]);