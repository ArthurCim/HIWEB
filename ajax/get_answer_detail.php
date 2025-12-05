<?php
// ajax/get_answer_detail.php
// suppress notices in AJAX endpoints to avoid breaking JSON
error_reporting(0);
ini_set('display_errors', 0);

include "../db.php";
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['login'])) {
    // allow a one-time debug override when running on localhost (helps reproduce from browser)
    $allowDebug = false;
    if (isset($_GET['debug']) && $_GET['debug'] === '1') {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $remote = $_SERVER['REMOTE_ADDR'] ?? '';
        if (strpos($host, 'localhost') !== false || $remote === '127.0.0.1' || $remote === '::1') {
            $allowDebug = true;
        }
    }
    if (!$allowDebug) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }
}

$id_answer = $_GET['id_answer'] ?? '';

if (!$id_answer) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid answer ID']);
    exit;
}

// Get answer with full details (align with `stage_answer` columns used elsewhere)
$stmt = $conn->prepare(
    "SELECT 
        sa.id_answer,
        sa.jawaban_user AS user_answer,
        sa.is_correct,
        sa.submitted_at,
        sa.id_question,
        u.nama as user_name,
        u.email as user_email,
        q.content as question_text,
        s.id_stage,
        s.nama_stage
    FROM stage_answer sa
    JOIN users u ON sa.id_user = u.id_user
    LEFT JOIN question q ON sa.id_question = q.id_question
    LEFT JOIN stage s ON s.id_question = q.id_question
    WHERE sa.id_answer = ?"
);
$stmt->bind_param("s", $id_answer);
if (!$stmt->execute()) {
    $err = $stmt->error ?: $conn->error;
    file_put_contents(__DIR__ . '/debug_get_answer_detail.log', date('c') . " - execute failed: $err\nGET:" . print_r($_GET, true) . "\n\n", FILE_APPEND);
    echo json_encode(['status' => 'error', 'message' => 'Query execution failed']);
    exit;
}
$result = $stmt->get_result();
if (!$result) {
    file_put_contents(__DIR__ . '/debug_get_answer_detail.log', date('c') . " - get_result failed\nGET:" . print_r($_GET, true) . "\n\n", FILE_APPEND);
    echo json_encode(['status' => 'error', 'message' => 'No result from query']);
    exit;
}
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

// ensure no stray output before JSON
if (ob_get_length()) ob_clean();
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
], JSON_UNESCAPED_UNICODE);
exit;
