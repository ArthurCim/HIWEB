<?php
include "../db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_courses']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);

    $sql = "INSERT INTO courses (nama_courses, deskripsi) VALUES ('$nama', '$deskripsi')";
    if (mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        echo "error: " . mysqli_error($conn);
    }
}
