<?php
include "../db.php";

$nama = $_POST['nama_lesson'];
$deskripsi = $_POST['deskripsi'];
$id_courses = $_POST['id_courses'];
$id = uniqid('LES');

mysqli_query($conn, "INSERT INTO lesson (id_lesson, nama_lesson, deskripsi, id_courses) 
                     VALUES ('$id', '$nama', '$deskripsi', '$id_courses')");
?>
