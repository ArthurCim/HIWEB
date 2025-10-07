<?php
session_start();
include '../db.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit();
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if ($email === '' || $password === '') {
    $_SESSION['login_error'] = "Email dan Password wajib diisi!";
    header("Location: login.php");
    exit();
}

$stmt = $conn->prepare("SELECT id_users, email, password, nama FROM users WHERE email = ?");
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

    if (password_verify($password, $user['password'])) {
        $_SESSION['login'] = true;
        $_SESSION['user_id'] = $user['id_users'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_nama'] = $user['nama'];

        header("Location: ../index.php");
        exit();
    }
}

$_SESSION['login_error'] = "Email atau password salah!";
header("Location: login.php");
exit();
