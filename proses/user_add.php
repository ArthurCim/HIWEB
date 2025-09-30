<?php
include "../db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama  = mysqli_real_escape_string($conn, $_POST['nama']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    $query = "INSERT INTO users (nama, email) VALUES ('$nama', '$email')";
    if (mysqli_query($conn, $query)) {
        echo "success";
    } else {
        echo "error: " . mysqli_error($conn);
    }
}
