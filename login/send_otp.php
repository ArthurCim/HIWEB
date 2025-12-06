<?php
session_start();
include __DIR__ . '/../db.php';
include __DIR__ . '/../includes/otp_helper_clean.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed';
    exit;
}

$email = trim($_POST['email'] ?? '');
if (empty($email)) {
    $_SESSION['fp_message'] = 'Email wajib diisi.';
    header('Location: forgot_password.php');
    exit;
}

// Check user exists
$stmt = $conn->prepare('SELECT id_user FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    $_SESSION['fp_message'] = 'Email tidak ditemukan.';
    header('Location: forgot_password.php');
    exit;
}
$user = $res->fetch_assoc();
$id_user = $user['id_user'];

// Ensure table exists
create_password_resets_table($conn);

// Generate OTP and store
$otp = generate_otp(6);
$otp_hash = password_hash($otp, PASSWORD_DEFAULT);
$expires_at = date('Y-m-d H:i:s', time() + 600); // 10 minutes

$ins = $conn->prepare('INSERT INTO password_resets (id_user, email, otp_hash, expires_at, used) VALUES (?, ?, ?, ?, 0)');
$ins->bind_param('ssss', $id_user, $email, $otp_hash, $expires_at);
if (!$ins->execute()) {
    $_SESSION['fp_message'] = 'Gagal membuat permintaan reset.';
    header('Location: forgot_password.php');
    exit;
}

// Send via sandi app (placeholder)
$sent = sendOtpWithSandiApp($email, $otp);

if ($sent) {
    $_SESSION['fp_message'] = 'Kode OTP telah dikirim. Periksa email Anda (atau channel sandi aplikasi).';
} else {
    $_SESSION['fp_message'] = 'Gagal mengirim kode. Silakan coba lagi atau hubungi admin.';
}

header('Location: forgot_password.php');
exit;
