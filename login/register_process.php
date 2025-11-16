<?php
session_start();
include __DIR__ . '/../db.php';

function generateUUIDv4() {
    return sprintf(
        '%08s-%04s-%04x-%04x-%12s',
        bin2hex(random_bytes(4)),
        bin2hex(random_bytes(2)),
        random_int(0, 0x0fff) | 0x4000,
        random_int(0, 0x3fff) | 0x8000,
        bin2hex(random_bytes(6))
    );
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION['register_error'] = "Metode tidak valid.";
    header("Location: register.php");
    exit();
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if ($username === '' || $email === '' || $password === '' || $confirm === '') {
    $_SESSION['register_error'] = "Semua field wajib diisi.";
    header("Location: register.php");
    exit();
}

if ($password !== $confirm) {
    $_SESSION['register_error'] = "Password tidak sama.";
    header("Location: register.php");
    exit();
}

// Check email exist
$stmt = $conn->prepare("SELECT id_user FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $_SESSION['register_error'] = "Email sudah terdaftar.";
    header("Location: register.php");
    exit();
}
$stmt->close();

$newId = generateUUIDv4();
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$role = "user";

$stmt = $conn->prepare("INSERT INTO users (id_user, nama, email, password, role) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $newId, $username, $email, $hashedPassword, $role);

if ($stmt->execute()) {
    $_SESSION['register_success'] = "Registrasi berhasil! Silakan login.";
    header("Location: register.php");
    exit();
} else {
    $_SESSION['register_error'] = "Gagal menyimpan data.";
    header("Location: register.php");
    exit();
}
?>
