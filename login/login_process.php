<?php
session_start();
include __DIR__ . '/../db.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION['login_error'] = "Metode tidak valid.";
    header("Location: login.php");
    exit();
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$stmt = $conn->prepare("SELECT id_user, nama, password, role FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $_SESSION['login_error'] = "Email tidak ditemukan.";
    header("Location: login.php");
    exit();
}

$stmt->bind_result($id_user, $nama, $hashedPassword, $role);
$stmt->fetch();

if (!password_verify($password, $hashedPassword)) {
    $_SESSION['login_error'] = "Password salah.";
    header("Location: login.php");
    exit();
}

$_SESSION['login'] = true;
$_SESSION['id_user'] = $id_user;
$_SESSION['nama'] = $nama;
$_SESSION['role'] = $role;

if ($role === "admin") {
    header("Location: ../index.php"); // halaman admin
    exit();
} elseif ($role === "user") {
    header("Location: ../landing.php"); // halaman landing user
    exit();
} else {
    $_SESSION['login_error'] = "Role tidak dikenali.";
    header("Location: login.php");
    exit();
}
?>
