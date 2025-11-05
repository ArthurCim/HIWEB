<?php
// ajax/get_quiz_by_stage.php
include "../db.php";
header('Content-Type: application/json; charset=utf-8');

$id_stage = $_GET['id_stage'] ?? '';

if (!$id_stage) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No stage ID provided'
    ]);
    exit;
}

try {
    // Get stage info to find question
    $stageStmt = $conn->prepare("SELECT id_stage, id_question, type FROM stage WHERE id_stage = ? LIMIT 1");
    $stageStmt->bind_param('s', $id_stage);
    $stageStmt->execute();
    $stageResult = $stageStmt->get_result();
    
    if (!$stageResult || $stageResult->num_rows === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Stage not found'
        ]);
        exit;
    }
    
    $stage = $stageResult->fetch_assoc();
    $stageStmt->close();
    
    // Check if stage is quiz type
    if ($stage['type'] !== 'quiz') {
        echo json_encode([
            'status' => 'error',
            'message' => 'Stage is not a quiz type'
        ]);
        exit;
    }
    
    $id_question = $stage['id_question'];
    
    if (!$id_question) {
        echo json_encode([
            'status' => 'success',
            'data' => []
        ]);
        exit;
    }
    
    // Get question data
    $qStmt = $conn->prepare("SELECT id_question, content FROM question WHERE id_question = ? LIMIT 1");
    $qStmt->bind_param('s', $id_question);
    $qStmt->execute();
    $qResult = $qStmt->get_result();
    
    if (!$qResult || $qResult->num_rows === 0) {
        echo json_encode([
            'status' => 'success',
            'data' => []
        ]);
        exit;
    }
    
    $question = $qResult->fetch_assoc();
    $qStmt->close();
    
    // Get question options
    $options = [];
    $optStmt = $conn->prepare("SELECT id_question_option, option_text, is_correct FROM question_option WHERE id_question = ? ORDER BY id_question_option ASC");
    $optStmt->bind_param('s', $id_question);
    $optStmt->execute();
    $optResult = $optStmt->get_result();
    
    while ($opt = $optResult->fetch_assoc()) {
        $options[] = [
            'id' => $opt['id_question_option'],
            'text' => $opt['option_text'],
            'is_correct' => (int)$opt['is_correct']
        ];
    }
    $optStmt->close();
    
    // Return data in expected format
    $quizData = [
        [
            'id_detail' => $id_question,  // Using question ID as detail ID for compatibility
            'isi' => $question['content'],
            'options' => $options
        ]
    ];
    
    echo json_encode([
        'status' => 'success',
        'data' => $quizData
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to load quiz data',
        'error' => $e->getMessage()
    ]);
}
?>