<?php
include "../db.php";
header('Content-Type: application/json; charset=utf-8');

$id_question = isset($_GET['id_question']) ? $_GET['id_question'] : '';
if (!$id_question) { echo json_encode(['error'=>'no id']); exit; }

// question
$stmt = $conn->prepare("SELECT id_question, content, answers_type, id_stage FROM question WHERE id_question = ? LIMIT 1");
$stmt->bind_param("s", $id_question);
$stmt->execute();
$qres = $stmt->get_result()->fetch_assoc();
if (!$qres) { echo json_encode(['error'=>'not found']); exit; }

// resolve stage -> lesson -> course ids
$stage_id = $qres['id_stage'];
$lesson_id = null; $course_id = null;
if ($stage_id) {
    $s = $conn->prepare("SELECT id_lesson FROM stage WHERE id_stage = ? LIMIT 1");
    $s->bind_param("s",$stage_id); $s->execute(); $r = $s->get_result()->fetch_assoc();
    if ($r) $lesson_id = $r['id_lesson'];
}
if ($lesson_id) {
    $l = $conn->prepare("SELECT id_courses FROM lesson WHERE id_lesson = ? LIMIT 1");
    $l->bind_param("s",$lesson_id); $l->execute(); $r2 = $l->get_result()->fetch_assoc();
    if ($r2) $course_id = $r2['id_courses'];
}

// options (if any)
$options = [];
if ($qres['answers_type'] === 'multiple_choice') {
    $opt = $conn->prepare("SELECT id_question_option, option_text, is_correct FROM question_option WHERE id_question = ? ORDER BY id_question_option ASC");
    $opt->bind_param("s",$id_question);
    $opt->execute();
    $ors = $opt->get_result();
    // try to map labels A,B,C... based on order
    $labels = ['A','B','C','D','E','F'];
    $i = 0;
    while ($o = $ors->fetch_assoc()) {
        $options[] = [
            'id_question_option' => $o['id_question_option'],
            'option_text' => $o['option_text'],
            'is_correct' => intval($o['is_correct']),
            'label' => $labels[$i] ?? chr(65+$i)
        ];
        $i++;
    }
}

echo json_encode([
    'question' => $qres,
    'options' => $options,
    'stage_id' => $stage_id,
    'lesson_id' => $lesson_id,
    'course_id' => $course_id
]);
