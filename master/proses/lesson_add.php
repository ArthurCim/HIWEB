<?php
include "../../db.php";

$nama = $_POST['nama_lesson'];
$deskripsi = $_POST['deskripsi'];
$id_courses = $_POST['id_courses'];

// Ambil ID terakhir
$query = mysqli_query($conn, "SELECT id_lesson FROM lesson ORDER BY id_lesson DESC LIMIT 1");
$data = mysqli_fetch_assoc($query);

if ($data && preg_match('/lesson_(\d+)/', $data['id_lesson'], $matches)) {
    $lastNumber = (int)$matches[1];
    $newNumber = $lastNumber + 1;
} else {
    $newNumber = 1;
}

// Buat ID baru dengan format lesson_001
$newId = 'lesson_' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

// Simpan ke database
$stmt = $conn->prepare("INSERT INTO lesson (id_lesson, nama_lesson, deskripsi, id_courses) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $newId, $nama, $deskripsi, $id_courses);
$stmt->execute();

$stmt->close();
$conn->close();
?>
