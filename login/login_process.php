<?php
session_start();
include '../db.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit();
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    $_SESSION['login_error'] = "Email dan Password wajib diisi!";
    header("Location: login.php");
    exit();
}

$stmt = $conn->prepare("SELECT id_user, email, PASSWORD, nama FROM users WHERE email = ?");
if (!$stmt) {
    $_SESSION['login_error'] = "Terjadi kesalahan pada server!";
    header("Location: login.php");
    exit();
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Gunakan kolom 'PASSWORD' sesuai nama di database
    if (password_verify($password, $user['PASSWORD'])) {
        $_SESSION['login'] = true;
        $_SESSION['user_id'] = $user['id_user'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_nama'] = $user['nama'];

        header("Location: ../index.php");
        exit();
    }
}

$_SESSION['login_error'] = "Email atau password salah!";
header("Location: login.php");
exit();
