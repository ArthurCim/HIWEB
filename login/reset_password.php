<?php
session_start();
include __DIR__ . '/../db.php';
include __DIR__ . '/../includes/otp_helper_clean.php';

// If POST -> process reset
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $otp = trim($_POST['otp'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (empty($email) || empty($otp) || empty($password) || empty($password2)) {
        $_SESSION['rp_message'] = 'Semua field wajib diisi.';
        header('Location: reset_password.php');
        exit;
    }
    if ($password !== $password2) {
        $_SESSION['rp_message'] = 'Konfirmasi password tidak cocok.';
        header('Location: reset_password.php');
        exit;
    }

    // Find latest unused reset for this email
    $stmt = $conn->prepare('SELECT id, id_user, otp_hash, expires_at, used FROM password_resets WHERE email = ? ORDER BY created_at DESC LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        $_SESSION['rp_message'] = 'Permintaan reset tidak ditemukan. Minta kode OTP terlebih dahulu.';
        header('Location: reset_password.php');
        exit;
    }
    $row = $res->fetch_assoc();
    if ((int)$row['used'] === 1) {
        $_SESSION['rp_message'] = 'Kode OTP sudah digunakan.';
        header('Location: reset_password.php');
        exit;
    }
    if (new DateTime() > new DateTime($row['expires_at'])) {
        $_SESSION['rp_message'] = 'Kode OTP sudah kadaluarsa.';
        header('Location: reset_password.php');
        exit;
    }

    if (!password_verify($otp, $row['otp_hash'])) {
        $_SESSION['rp_message'] = 'Kode OTP salah.';
        header('Location: reset_password.php');
        exit;
    }

    // Update user's password
    $new_hash = password_hash($password, PASSWORD_DEFAULT);
    $upd = $conn->prepare('UPDATE users SET password = ? WHERE id_user = ?');
    $upd->bind_param('ss', $new_hash, $row['id_user']);
    if (!$upd->execute()) {
        $_SESSION['rp_message'] = 'Gagal mengubah password. Coba lagi.';
        header('Location: reset_password.php');
        exit;
    }

    // Mark reset used
    $mark = $conn->prepare('UPDATE password_resets SET used = 1 WHERE id = ?');
    $mark->bind_param('i', $row['id']);
    $mark->execute();

    $_SESSION['rp_message'] = 'Password berhasil diperbarui. Silakan login.';
    header('Location: login.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reset Password</title>
    <link rel="stylesheet" href="assets/login.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="right-side">
        <div class="form-box">
            <div class="icon">
                <img src="../assets/ikonakunlogin.svg" alt="Icon" />
            </div>
            <h1>Reset Password</h1>

            <?php if (!empty($_SESSION['rp_message'])): ?>
                <div class="alert"><?php echo htmlspecialchars($_SESSION['rp_message']);
                                    unset($_SESSION['rp_message']); ?></div>
            <?php endif; ?>

            <form method="post" action="reset_password.php">
                <div class="input-group">
                    <input type="email" id="email" name="email" placeholder=" " required />
                    <label for="email" class="floating-label">Email</label>
                </div>

                <div class="input-group">
                    <input type="text" id="otp" name="otp" placeholder=" " required />
                    <label for="otp" class="floating-label">Kode OTP</label>
                </div>

                <div class="input-group pw-wrap">
                    <input type="password" id="password" name="password" placeholder=" " required />
                    <label for="password" class="floating-label">Password Baru</label>
                    <button type="button" class="pw-toggle" data-target="password">
                        <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                        <svg class="eye-off-icon" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none">
                            <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.65 21.65 0 0 1 5.1-6.36M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.65 21.65 0 0 1-4.21 5.64" />
                            <line x1="1" y1="1" x2="23" y2="23" />
                        </svg>
                    </button>
                </div>

                <div class="input-group pw-wrap">
                    <input type="password" id="password2" name="password2" placeholder=" " required />
                    <label for="password2" class="floating-label">Konfirmasi Password</label>
                    <button type="button" class="pw-toggle" data-target="password2">
                        <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                        <svg class="eye-off-icon" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none">
                            <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.65 21.65 0 0 1 5.1-6.36M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.65 21.65 0 0 1-4.21 5.64" />
                            <line x1="1" y1="1" x2="23" y2="23" />
                        </svg>
                    </button>
                </div>

                <button type="submit">Reset Password</button>
            </form>

            <div class="register-link" style="margin-top:10px">
                <a href="login.php">Kembali ke login</a>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.pw-toggle').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = document.getElementById(btn.dataset.target);
                const eye = btn.querySelector('.eye-icon');
                const eyeOff = btn.querySelector('.eye-off-icon');
                if (input.type === 'password') {
                    input.type = 'text';
                    eye.style.display = 'none';
                    eyeOff.style.display = 'block';
                } else {
                    input.type = 'password';
                    eye.style.display = 'block';
                    eyeOff.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>