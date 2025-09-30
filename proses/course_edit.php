<?php
include "../db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id_courses']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama_courses']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);

    $sql = "UPDATE courses SET nama_courses='$nama', deskripsi='$deskripsi' WHERE id_courses=$id";
    if (mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        echo "error: " . mysqli_error($conn);
    }
}
