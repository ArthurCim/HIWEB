<?php
include "../../db.php";
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['login'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$nama = isset($_POST['nama']) ? trim($_POST['nama']) : '';
$deskripsi = isset($_POST['deskripsi']) ? trim($_POST['deskripsi']) : '';
$durasi_bulan = isset($_POST['durasi_bulan']) ? (int)$_POST['durasi_bulan'] : 0;
$harga = isset($_POST['harga']) ? (float)$_POST['harga'] : 0;

if (!$nama || !$deskripsi || $durasi_bulan <= 0 || $harga < 0) {
    echo json_encode(['status' => 'error', 'message' => 'Semua field harus diisi dengan benar']);
    exit;
}

try {
    // Generate id_plan
    $id_plan = 'PLAN_' . $durasi_bulan . 'M_' . bin2hex(random_bytes(4));

    $stmt = $conn->prepare("INSERT INTO subscription_plans (id_plan, nama, deskripsi, durasi_bulan, harga) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssid", $id_plan, $nama, $deskripsi, $durasi_bulan, $harga);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Plan berhasil ditambahkan']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menambah plan: ' . $stmt->error]);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
