<?php
include "../db.php";
header('Content-Type: application/json; charset=utf-8');

$id_question = isset($_POST['id_question']) ? $_POST['id_question'] : '';
if (!$id_question) { echo json_encode(['status'=>'error','message'=>'ID tidak ditemukan']); exit; }

try {
    $conn->begin_transaction();

    $d1 = $conn->prepare("DELETE FROM question_option WHERE id_question = ?");
    $d1->bind_param("s",$id_question); $d1->execute();

    $d2 = $conn->prepare("DELETE FROM question_answer WHERE id_question = ?");
    $d2->bind_param("s",$id_question); $d2->execute();

    $d3 = $conn->prepare("DELETE FROM question WHERE id_question = ?");
    $d3->bind_param("s",$id_question); $d3->execute();

    $conn->commit();
    echo json_encode(['status'=>'success','message'=>'Question berhasil dihapus.']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status'=>'error','message'=>'Terjadi kesalahan: '.$e->getMessage()]);
}
