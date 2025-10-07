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

$stmt = $conn->prepare("SELECT id_users FROM users WHERE email = ?");
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

$result = $conn->query("SELECT id FROM users ORDER BY id DESC LIMIT 1");
$lastId = $result->fetch_assoc();

if ($lastId) {
    $num = (int) substr($lastId['id'], 6);
    $num++;
    $newId = "users_" . str_pad($num, 3, "0", STR_PAD_LEFT);
} else {
    $newId = "users_001";
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (id_users, nama, email, password) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $newId, $username, $email, $hashedPassword);

if ($stmt->execute()) {
    $_SESSION['register_success'] = "Registrasi berhasil, silakan login!";
    header("Location: login.php");
    exit();
} else {
    $_SESSION['register_error'] = "Gagal menyimpan data";
    header("Location: register.php");
    exit();
}
