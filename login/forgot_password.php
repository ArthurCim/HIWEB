<?php
session_start();
// keep db include if later needed
include __DIR__ . '/../db.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Lupa Password</title>
    <link rel="stylesheet" href="assets/login.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="right-side">
        <div class="form-box">
            <div class="icon">
                <img src="../assets/ikonakunlogin.svg" alt="Icon" />
            </div>
            <h1>Lupa Password</h1>

            <?php if (!empty($_SESSION['fp_message'])): ?>
                <div class="alert"><?php echo htmlspecialchars($_SESSION['fp_message']);
                                    unset($_SESSION['fp_message']); ?></div>
            <?php endif; ?>

            <form action="send_otp.php" method="post">
                <div class="input-group">
                    <input type="email" id="email" name="email" placeholder=" " required />
                    <label for="email" class="floating-label">Email terdaftar</label>
                </div>

                <button type="submit">Kirim Kode OTP</button>

                <div class="register-link" style="margin-top:12px">
                    Sudah dapat kode? <a href="reset_password.php">Reset password</a>
                </div>

                <div class="register-link">
                    <a href="login.php">Kembali ke login</a>
                </div>
            </form>
        </div>
    </div>

</body>

</html>