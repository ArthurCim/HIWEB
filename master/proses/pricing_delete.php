<?php
include "../../db.php";
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['login'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$id_plan = isset($_POST['id_plan']) ? trim($_POST['id_plan']) : '';

if (!$id_plan) {
    echo json_encode(['status' => 'error', 'message' => 'ID Plan tidak valid']);
    exit;
}

try {
    // Soft check: prevent deletion if plan is used in transactions (optional safeguard)
    $checkStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM transactions WHERE id_plan = ?");
    $checkStmt->bind_param("s", $id_plan);
    $checkStmt->execute();
    $checkRes = $checkStmt->get_result();
    $checkRow = $checkRes->fetch_assoc();
    $checkStmt->close();

    if ($checkRow['cnt'] > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Tidak dapat menghapus plan yang sudah digunakan']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM subscription_plans WHERE id_plan = ?");
    $stmt->bind_param("s", $id_plan);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Plan berhasil dihapus']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal hapus plan: ' . $stmt->error]);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
