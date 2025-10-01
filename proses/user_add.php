<?php
include "../db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = mysqli_real_escape_string($conn, $_POST['nama']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'] ?? '';

    if ($nama === '' || $email === '' || $password === '') {
        echo "error: semua field harus diisi";
        exit();
    }

    // cek email unik
    $check = $conn->prepare("SELECT id_users FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        echo "error: email sudah digunakan";
        $check->close();
        exit();
    }
    $check->close();

    // ambil id terakhir
    $result = $conn->query("SELECT id_users FROM users ORDER BY id_users DESC LIMIT 1");
    $lastId = $result->fetch_assoc();

    if ($lastId) {
        $num = (int) substr($lastId['id_users'], 6); // ambil angka setelah "users_"
        $num++;
        $newId = "users_" . str_pad($num, 3, "0", STR_PAD_LEFT);
    } else {
        $newId = "users_001";
    }

    // hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (id_users, nama, email, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $newId, $nama, $email, $hashedPassword);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error: " . $stmt->error;
    }
    $stmt->close();
}
