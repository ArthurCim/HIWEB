<?php
session_start();
include '../db.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: register.php");
    exit();
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if ($username === '' || $email === '' || $password === '' || $confirm === '') {
    $_SESSION['register_error'] = "Semua field harus diisi";
    header("Location: register.php");
    exit();
}

if ($password !== $confirm) {
    $_SESSION['register_error'] = "Password tidak sama";
    header("Location: register.php");
    exit();
}

// Cek apakah email sudah digunakan
$stmt = $conn->prepare("SELECT id_user FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->close();
    $_SESSION['register_error'] = "Email sudah digunakan";
    header("Location: register.php");
    exit();
}
$stmt->close();

// Ambil ID terakhir
$result = $conn->query("SELECT id_user FROM users ORDER BY id_user DESC LIMIT 1");
$lastId = $result->fetch_assoc();

if ($lastId) {
    // ✅ gunakan 'id_user' bukan 'id'
    $num = (int) substr($lastId['id_user'], 6);
    $num++;
    $newId = "users_" . str_pad($num, 3, "0", STR_PAD_LEFT);
} else {
    $newId = "users_001";
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (id_user, nama, email, PASSWORD) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $newId, $username, $email, $hashedPassword);

if ($stmt->execute()) {
    $_SESSION['register_success'] = "Registrasi berhasil, silakan login!";
    header("Location: login.php");
    exit();
} else {
    $_SESSION['register_error'] = "Gagal menyimpan data: " . $stmt->error;
    header("Location: register.php");
    exit();
}
