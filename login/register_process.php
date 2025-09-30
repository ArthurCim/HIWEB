<?php
// register_process.php
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

// validasi sederhana
if ($username === '' || $email === '' || $password === '' || $confirm === '') {
    header("Location: register.php?error=Semua field harus diisi");
    exit();
}

if ($password !== $confirm) {
    header("Location: register.php?error=Password tidak sama");
    exit();
}
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->close();
    header("Location: register.php?error=Email sudah digunakan");
    exit();
}
$stmt->close();

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (nama, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $username, $email, $hashedPassword);

if ($stmt->execute()) {
    // sukses → langsung ke login
    header("Location: login.php");
    exit();
} else {
    header("Location: register.php?error=Gagal menyimpan data");
    exit();
}
