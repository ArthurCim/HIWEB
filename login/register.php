<?php
// register.php
session_start();
// kalau sudah login, langsung ke index
if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register</title>
  <link rel="stylesheet" href="assets/register.css">
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
  <div class="left-side">
    <img class= "bg-left-side" src="../assets/Group 103 (1).png" alt="Illustration" />
    <img class= "icon-left-side" src="../assets/logo putih.svg">
    <div class="left-caption">
      <p>© 2025 Hijauteam - All Rights Reserved</p>
    </div>
  </div>

  <div class="right-side">
    <div class="form-box">
      <div class="icon">
        <img src="../assets/ikonakunlogin.svg" alt="user-icon">
      </div>
      <h2>Register</h2>

      <form action="register_process.php" method="post">
        <div class="input-group">
          <input type="text" id="username" name="username" required>
          <label for="username">Username</label>
        </div>

        <div class="input-group">
          <input type="email" id="email" name="email" required>
          <label for="email">Email</label>
        </div>

        <!-- Password -->
        <div class="input-group pw-wrap">
          <input id="password" name="password" type="password" required>
          <label for="password">Password</label>
          <button type="button" class="pw-toggle" data-target="password">👁</button>
        </div>

        <!-- Confirm Password -->
        <div class="input-group pw-wrap">
          <input id="confirm_password" name="confirm_password" type="password" required>
          <label for="confirm_password">Confirm Password</label>
          <button type="button" class="pw-toggle" data-target="confirm_password">👁</button>
        </div>

        <button type="submit" class="btn">Register</button>
      </form>

      <p class="switch">Sudah punya akun? <a href="login.php">Login</a></p>
    </div>
  </div>

  <?php if (isset($_SESSION['register_error'])): ?>
    <script>
      Swal.fire({
        icon: 'error',
        title: 'Registrasi Gagal',
        text: '<?php echo $_SESSION['register_error']; ?>',
        confirmButtonColor: '#d33'
      });
    </script>
    <?php unset($_SESSION['register_error']); ?>
  <?php endif; ?>

  <script>
    // Toggle password visibility
    document.querySelectorAll('.pw-toggle').forEach(button => {
      button.addEventListener('click', () => {
        const targetId = button.getAttribute('data-target');
        const input = document.getElementById(targetId);
        input.type = input.type === 'password' ? 'text' : 'password';
      });
    });
  </script>
</body>
</html>
    