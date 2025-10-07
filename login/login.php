<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login</title>
  <link rel="stylesheet" href="assets/login.css" />
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
  <div class="left-side">
    <img class="bg-left-side" src="../assets/Group 103 (1).png" alt="Illustration" />
    <img class="icon-left-side" src="../assets/logo putih.svg">
    <div class="left-caption">
      <p>© 2025 Hijauteam - All Rights Reserved</p>
    </div>
  </div>

  <div class="right-side">
    <div class="form-box">
      <div class="icon">
        <img src="../assets/ikonakunlogin.svg" alt="Logo" />
      </div>
      <h1>Login Your Account</h1>
      <form action="login_process.php" method="post">
        <div class="input-group">
          <input type="email" id="email" name="email" required />
          <label for="email">Email</label>
        </div>

        <div class="input-group pw-wrap">
          <input type="password" id="password" name="password" required />
          <label for="password">Password</label>
          <button type="button" class="pw-toggle" data-target="password">
            <!-- Eye open -->
            <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
            <!-- Eye off -->
            <svg class="eye-off-icon" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none">
              <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.65 21.65 0 0 1 5.1-6.36M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.65 21.65 0 0 1-4.21 5.64"/>
              <line x1="1" y1="1" x2="23" y2="23"/>
            </svg>
          </button>
        </div>

        <div class="forgot">
          <a href="#">Forgot Password?</a>
        </div>

        <button type="submit">Login</button>

        <div class="register-link">
          Don't have an account? <a href="Register.php">Register</a>
        </div>
      </form>
    </div>
  </div>

  <?php if (isset($_SESSION['login_error'])): ?>
    <script>
      Swal.fire({
        icon: 'error',
        title: 'Login Gagal',
        text: '<?php echo $_SESSION['login_error']; ?>',
        confirmButtonColor: '#d33'
      });
    </script>
    <?php unset($_SESSION['login_error']); ?>
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
