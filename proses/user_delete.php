<?php
include "../db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id    = intval($_POST['id']);
    $nama  = mysqli_real_escape_string($conn, $_POST['nama']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    $query = "UPDATE users SET nama='$nama', email='$email' WHERE id=$id";
    if (mysqli_query($conn, $query)) {
        echo "success";
    } else {
        echo "error: " . mysqli_error($conn);
    }
}
