<?php
include "../db.php";
header('Content-Type: application/json; charset=utf-8');

$id_stage = isset($_POST['id_stage']) ? $_POST['id_stage'] : '';
$content = isset($_POST['content']) ? trim($_POST['content']) : '';
$answers_type = isset($_POST['answers_type']) ? $_POST['answers_type'] : 'multiple_choice';

// minimal validation
if (!$id_stage || !$content) {
    echo json_encode(['status'=>'error','message'=>'Stage dan pertanyaan wajib diisi.']); exit;
}

try {
    $conn->begin_transaction();

    // insert question
    $id_question = bin2hex(random_bytes(18)); // 36 chars
    $stmt = $conn->prepare("INSERT INTO question (id_question, content, answers_type, id_stage) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $id_question, $content, $answers_type, $id_stage);
    $stmt->execute();

    if ($answers_type === 'multiple_choice') {
        $options = isset($_POST['options']) ? $_POST['options'] : [];
        $correct = isset($_POST['correct_answer']) ? $_POST['correct_answer'] : null; // label A/B/C/D
        if (!is_array($options) || count($options) < 2) {
            $conn->rollback();
            echo json_encode(['status'=>'error','message'=>'Opsi tidak valid.']); exit;
        }

        // insert options with label mapping A,B,C...
        $labels = ['A','B','C','D','E'];
        for ($i=0;$i<count($options);$i++){
            $opt_text = trim($options[$i]);
            if ($opt_text === '') continue;
            $id_opt = bin2hex(random_bytes(12));
            $is_correct = ($labels[$i] === $correct) ? 1 : 0;
            $ins = $conn->prepare("INSERT INTO question_option (id_question_option, id_question, option_text, is_correct) VALUES (?, ?, ?, ?)");
            $ins->bind_param("sssi", $id_opt, $id_question, $opt_text, $is_correct);
            $ins->execute();
        }
        // also store in question_answer table a record with correct answer label (optional)
        if ($correct !== null) {
            $id_ans = bin2hex(random_bytes(12));
            $ins2 = $conn->prepare("INSERT INTO question_answer (id_question_answer, id_question, correct_answer) VALUES (?, ?, ?)");
            $ins2->bind_param("sss", $id_ans, $id_question, $correct);
            $ins2->execute();
        }
    } else {
        // essay: nothing in options. You may still create an empty question_answer row if needed.
    }

    $conn->commit();
    echo json_encode(['status'=>'success','message'=>'Question berhasil ditambahkan.']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status'=>'error','message'=>'Terjadi kesalahan: '.$e->getMessage()]);
}
