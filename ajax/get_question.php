<?php
// ajax/get_question.php
include "../db.php";
header('Content-Type: application/json; charset=utf-8');

$id_question = $_GET['id_question'] ?? '';

if (!$id_question) {
    echo json_encode(['error' => 'No question ID provided']);
    exit;
}

try {
    // Get question data
    $stmt = $conn->prepare("SELECT id_question, content, id_stage FROM question WHERE id_question = ? LIMIT 1");
    $stmt->bind_param("s", $id_question);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result || $result->num_rows === 0) {
        echo json_encode(['error' => 'Question not found']);
        exit;
    }
    
    $question = $result->fetch_assoc();
    $stmt->close();
    
    // Get stage -> lesson -> course hierarchy
    $stage_id = $question['id_stage'];
    $lesson_id = null;
    $course_id = null;
    
    if ($stage_id) {
        $stageStmt = $conn->prepare("SELECT id_lesson FROM stage WHERE id_stage = ? LIMIT 1");
        $stageStmt->bind_param("s", $stage_id);
        $stageStmt->execute();
        $stageResult = $stageStmt->get_result();
        
        if ($stageResult && $stageResult->num_rows > 0) {
            $stageData = $stageResult->fetch_assoc();
            $lesson_id = $stageData['id_lesson'];
        }
        $stageStmt->close();
    }
    
    if ($lesson_id) {
        $lessonStmt = $conn->prepare("SELECT id_courses FROM lesson WHERE id_lesson = ? LIMIT 1");
        $lessonStmt->bind_param("s", $lesson_id);
        $lessonStmt->execute();
        $lessonResult = $lessonStmt->get_result();
        
        if ($lessonResult && $lessonResult->num_rows > 0) {
            $lessonData = $lessonResult->fetch_assoc();
            $course_id = $lessonData['id_courses'];
        }
        $lessonStmt->close();
    }
    
    // Get question options
    $options = [];
    $optStmt = $conn->prepare("SELECT id_question_option, option_text, is_correct FROM question_option WHERE id_question = ? ORDER BY id_question_option ASC");
    $optStmt->bind_param("s", $id_question);
    $optStmt->execute();
    $optResult = $optStmt->get_result();
    
    // Generate labels A, B, C, etc.
    $labels = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
    $idx = 0;
    
    while ($opt = $optResult->fetch_assoc()) {
        $options[] = [
            'id_question_option' => $opt['id_question_option'],
            'option_text' => $opt['option_text'],
            'is_correct' => (int)$opt['is_correct'],
            'label' => $labels[$idx] ?? chr(65 + $idx)
        ];
        $idx++;
    }
    $optStmt->close();
    
    echo json_encode([
        'status' => 'success',
        'question' => [
            'id_question' => $question['id_question'],
            'content' => $question['content'],
            'id_stage' => $stage_id
        ],
        'options' => $options,
        'stage_id' => $stage_id,
        'lesson_id' => $lesson_id,
        'course_id' => $course_id
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to load question data',
        'error' => $e->getMessage()
    ]);
}
?>