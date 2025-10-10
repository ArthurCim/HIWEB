<?php
include "../../db.php";

$id = $_POST['id_lesson'];
$nama = $_POST['nama_lesson'];
$deskripsi = $_POST['deskripsi'];
$id_courses = $_POST['id_courses'];

mysqli_query($conn, "UPDATE lesson SET 
    nama_lesson='$nama', 
    deskripsi='$deskripsi', 
    id_courses='$id_courses'
    WHERE id_lesson='$id'");
?>
