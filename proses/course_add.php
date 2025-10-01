<?php
include "../db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_courses']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);

    // Ambil id_courses terakhir
    $result = mysqli_query($conn, "SELECT id_courses FROM courses ORDER BY id_courses DESC LIMIT 1");
    $lastId = mysqli_fetch_assoc($result);

    if ($lastId) {
        // ambil angka dari id_courses (contoh: courses_001 -> 001)
        $num = (int) substr($lastId['id_courses'], 8); 
        $num++;
        $newId = "courses_" . str_pad($num, 3, "0", STR_PAD_LEFT);
    } else {
        // jika belum ada data
        $newId = "courses_001";
    }

    // Insert data dengan id_courses baru
    $sql = "INSERT INTO courses (id_courses, nama_courses, deskripsi) 
            VALUES ('$newId', '$nama', '$deskripsi')";
    if (mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        echo "error: " . mysqli_error($conn);
    }
}
