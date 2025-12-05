<?php
include "../../db.php";
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['login'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$id_plan = isset($_POST['id_plan']) ? trim($_POST['id_plan']) : '';
$nama = isset($_POST['nama']) ? trim($_POST['nama']) : '';
$deskripsi = isset($_POST['deskripsi']) ? trim($_POST['deskripsi']) : '';
$durasi_bulan = isset($_POST['durasi_bulan']) ? (int)$_POST['durasi_bulan'] : 0;
$harga = isset($_POST['harga']) ? (float)$_POST['harga'] : 0;

if (!$id_plan || !$nama || !$deskripsi || $durasi_bulan <= 0 || $harga < 0) {
    echo json_encode(['status' => 'error', 'message' => 'Semua field harus diisi dengan benar']);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE subscription_plans SET nama = ?, deskripsi = ?, durasi_bulan = ?, harga = ? WHERE id_plan = ?");
    $stmt->bind_param("sssds", $nama, $deskripsi, $durasi_bulan, $harga, $id_plan);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Plan berhasil diupdate']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal update plan: ' . $stmt->error]);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
