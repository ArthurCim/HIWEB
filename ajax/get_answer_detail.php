<?php
// ajax/get_answer_detail.php
include "../db.php";
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['login'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$id_answer = $_GET['id_answer'] ?? '';

if (!$id_answer) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid answer ID']);
    exit;
}

// Get answer with full details
$stmt = $conn->prepare("
    SELECT 
        sa.id_stage_answer,
        sa.answer as user_answer,
        sa.is_correct,
        sa.submitted_at,
        u.nama as user_name,
        u.email as user_email,
        s.id_stage,
        s.nama_stage,
        q.id_question,
        q.content as question_text
    FROM stage_answer sa
    JOIN users u ON sa.id_user = u.id_user
    JOIN stage s ON sa.id_stage = s.id_stage
    LEFT JOIN question q ON s.id_question = q.id_question
    WHERE sa.id_stage_answer = ?
");
$stmt->bind_param("s", $id_answer);
$stmt->execute();
$result = $stmt->get_result();
$answer = $result->fetch_assoc();
$stmt->close();

if (!$answer) {
    echo json_encode(['status' => 'error', 'message' => 'Answer not found']);
    exit;
}

// Get correct answer(s) from question_answer
$correctAnswers = [];
if ($answer['id_question']) {
    $correctStmt = $conn->prepare("
        SELECT qa.correct_answer, qo.option_text
        FROM question_answer qa
        JOIN question_option qo ON qa.correct_answer = qo.id_question_option
        WHERE qa.id_question = ?
    ");
    $correctStmt->bind_param("s", $answer['id_question']);
    $correctStmt->execute();
    $correctResult = $correctStmt->get_result();
    
    while ($row = $correctResult->fetch_assoc()) {
        $correctAnswers[] = $row['option_text'];
    }
    $correctStmt->close();
}

// Get all options for this question
$allOptions = [];
if ($answer['id_question']) {
    $optionsStmt = $conn->prepare("
        SELECT 
            qo.id_question_option,
            qo.option_text,
            qo.is_correct
        FROM question_option qo
        WHERE qo.id_question = ?
        ORDER BY qo.id_question_option
    ");
    $optionsStmt->bind_param("s", $answer['id_question']);
    $optionsStmt->execute();
    $optionsResult = $optionsStmt->get_result();
    
    while ($row = $optionsResult->fetch_assoc()) {
        $allOptions[] = $row;
    }
    $optionsStmt->close();
}

echo json_encode([
    'status' => 'success',
    'data' => [
        'user_name' => $answer['user_name'],
        'user_email' => $answer['user_email'],
        'question_text' => $answer['question_text'],
        'user_answer' => $answer['user_answer'],
        'is_correct' => $answer['is_correct'],
        'submitted_at' => $answer['submitted_at'],
        'correct_answer' => implode(', ', $correctAnswers),
        'all_options' => $allOptions,
        'stage_name' => $answer['nama_stage']
    ]
]);