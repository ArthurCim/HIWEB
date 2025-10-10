<?php
include "../../db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = mysqli_real_escape_string($conn, $_POST['id_users']);
    $nama   = mysqli_real_escape_string($conn, $_POST['nama']);
    $email  = mysqli_real_escape_string($conn, $_POST['email']);
    $pass   = $_POST['password'] ?? '';
    $confirm= $_POST['confirm_password'] ?? '';

    if ($nama === '' || $email === '') {
        echo "error: nama & email wajib diisi";
        exit();
    }

    if ($pass !== '' && $pass !== $confirm) {
        echo "error: password tidak sama";
        exit();
    }

    if ($pass !== '') {
        $hashedPassword = password_hash($pass, PASSWORD_DEFAULT);
        $query = "UPDATE users SET nama='$nama', email='$email', password='$hashedPassword' WHERE id_user='$id'";
    } else {
        $query = "UPDATE users SET nama='$nama', email='$email' WHERE id_user='$id'";
    }

    if (mysqli_query($conn, $query)) {
        echo "success";
    } else {
        echo "error: " . mysqli_error($conn);
    }
}
