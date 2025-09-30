<?php
// login/logout.php
session_start();

// Hapus semua data session
$_SESSION = [];
session_unset();
session_destroy();

// Arahkan kembali ke halaman login
header("Location: ../index.php"); // sesuaikan path file login Anda
exit();
