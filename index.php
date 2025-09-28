<?php
session_start();
if (!isset($_SESSION['login'])) {
    header('Location:login.php');
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login</title>
  <link rel="stylesheet" href="#" />
</head>
<body>
  <?php include 'dashboard.php' ?>
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
