<?php
session_start();
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
  <div class="right-side">
    <div class="form-box">
      <div class="icon">
        <img src="../assets/ikonakunlogin.svg" alt="user-icon">
      </div>
      <h2>Register</h2>

      <form action="register_process.php" method="post">
        <div class="input-group">
          <input type="text" id="username" name="username" placeholder=" " required>
          <label for="username" class="floating-label">Username</label>
        </div>

        <div class="input-group">
          <input type="email" id="email" name="email" placeholder=" " required>
          <label for="email" class="floating-label">Email</label>
        </div>

        <!-- Password -->
        <div class="input-group pw-wrap">
          <input id="password" name="password" type="password" placeholder=" " required>
          <label for="password" class="floating-label">Password</label>
          <button type="button" class="pw-toggle" data-target="password">
            <!-- Eye open -->
            <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" />
              <circle cx="12" cy="12" r="3" />
            </svg>
            <!-- Eye off -->
            <svg class="eye-off-icon" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none">
              <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.65 21.65 0 0 1 5.1-6.36M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.65 21.65 0 0 1-4.21 5.64" />
              <line x1="1" y1="1" x2="23" y2="23" />
            </svg>
          </button>
        </div>

        <!-- Confirm Password -->
        <div class="input-group pw-wrap">
          <input id="confirm_password" name="confirm_password" type="password" placeholder=" " required>
          <label for="confirm_password" class="floating-label">Confirm Password</label>
          <button type="button" class="pw-toggle" data-target="confirm_password">
            <!-- Eye open -->
            <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" />
              <circle cx="12" cy="12" r="3" />
            </svg>
            <!-- Eye off -->
            <svg class="eye-off-icon" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none">
              <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.65 21.65 0 0 1 5.1-6.36M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.65 21.65 0 0 1-4.21 5.64" />
              <line x1="1" y1="1" x2="23" y2="23" />
            </svg>
          </button>
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
  <?php if (isset($_SESSION['register_success'])): ?>
    <script>
      Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: '<?php echo $_SESSION['register_success']; ?>',
        timer: 2000,
        showConfirmButton: false
      }).then(() => {
        window.location.href = "login.php";
      });
    </script>
    <?php unset($_SESSION['register_success']); ?>
  <?php endif; ?>

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