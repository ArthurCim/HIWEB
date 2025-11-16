<?php
// ajax/get_user_progress_detail.php
include "../db.php";
session_start();

header('Content-Type: application/json');

// Enable error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Jangan tampilkan error di output
ini_set('log_errors', 1);

if (!isset($_SESSION['login'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$id_user = $_GET['id_user'] ?? '';
$id_lesson = $_GET['id_lesson'] ?? '';

if (!$id_user || !$id_lesson) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters. User ID and Lesson ID are required.']);
    exit;
}

try {
    // Get user info
    $userStmt = $conn->prepare("SELECT nama, email FROM users WHERE id_user = ?");
    if (!$userStmt) {
        throw new Exception("Prepare failed for user query: " . $conn->error);
    }
    
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
    
    if (!$lessonStmt) {
        throw new Exception("Prepare failed for lesson query: " . $conn->error);
    }
    
    $lessonStmt->bind_param("s", $id_lesson);
    $lessonStmt->execute();
    $lessonResult = $lessonStmt->get_result();
    $lesson = $lessonResult->fetch_assoc();
    $lessonStmt->close();

    if (!$lesson) {
        echo json_encode(['status' => 'error', 'message' => 'Lesson not found']);
        exit;
    }

    // Get all stages with progress
    $stagesStmt = $conn->prepare("
        SELECT 
            s.id_stage,
            s.nama_stage,
            s.type,
            s.deskripsi,
            COALESCE(usp.is_completed, 0) as is_completed,
            usp.score,
            usp.completion_date
        FROM stage s
        LEFT JOIN user_stage_progress usp ON s.id_stage = usp.id_stage AND usp.id_user = ?
        WHERE s.id_lesson = ?
        ORDER BY 
            CASE 
                WHEN s.nama_stage REGEXP '^[0-9]+$' THEN CAST(s.nama_stage AS UNSIGNED)
                ELSE 999999
            END ASC,
            s.nama_stage ASC
    ");
    
    if (!$stagesStmt) {
        throw new Exception("Prepare failed for stages query: " . $conn->error);
    }
    
    $stagesStmt->bind_param("ss", $id_user, $id_lesson);
    $stagesStmt->execute();
    $stagesResult = $stagesStmt->get_result();

    $stages = [];
    while ($row = $stagesResult->fetch_assoc()) {
        $stages[] = [
            'id_stage' => $row['id_stage'],
            'nama_stage' => $row['nama_stage'],
            'type' => $row['type'],
            'deskripsi' => $row['deskripsi'],
            'is_completed' => (int)$row['is_completed'],
            'score' => $row['score'] ? (int)$row['score'] : null,
            'completion_date' => $row['completion_date']
        ];
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
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>