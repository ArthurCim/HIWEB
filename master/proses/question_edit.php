<?php
include "../db.php";
header('Content-Type: application/json; charset=utf-8');

$id_question = isset($_POST['id_question']) ? $_POST['id_question'] : '';
$id_stage = isset($_POST['id_stage']) ? $_POST['id_stage'] : '';
$content = isset($_POST['content']) ? trim($_POST['content']) : '';
$answers_type = isset($_POST['answers_type']) ? $_POST['answers_type'] : 'multiple_choice';

if (!$id_question || !$id_stage || !$content) {
    echo json_encode(['status'=>'error','message'=>'Data tidak lengkap']); exit;
}

try {
    $conn->begin_transaction();

    // update main question
    $upd = $conn->prepare("UPDATE question SET content = ?, answers_type = ?, id_stage = ? WHERE id_question = ?");
    $upd->bind_param("ssss", $content, $answers_type, $id_stage, $id_question);
    $upd->execute();

    // remove existing options & answers for this question, then re-insert if multi-choice
    $delOpt = $conn->prepare("DELETE FROM question_option WHERE id_question = ?");
    $delOpt->bind_param("s",$id_question); $delOpt->execute();

    $delAns = $conn->prepare("DELETE FROM question_answer WHERE id_question = ?");
    $delAns->bind_param("s",$id_question); $delAns->execute();

    if ($answers_type === 'multiple_choice') {
        $options = isset($_POST['options']) ? $_POST['options'] : [];
        $correct = isset($_POST['correct_answer']) ? $_POST['correct_answer'] : null;

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
        if ($correct !== null) {
            $id_ans = bin2hex(random_bytes(12));
            $ins2 = $conn->prepare("INSERT INTO question_answer (id_question_answer, id_question, correct_answer) VALUES (?, ?, ?)");
            $ins2->bind_param("sss", $id_ans, $id_question, $correct);
            $ins2->execute();
        }
    }

    $conn->commit();
    echo json_encode(['status'=>'success','message'=>'Question berhasil diperbarui.']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status'=>'error','message'=>'Terjadi kesalahan: '.$e->getMessage()]);
}
