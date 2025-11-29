<?php
include "../db.php";
session_start();
if (!isset($_SESSION['login'])) {
    header('Location:../login/login.php');
    exit();
}
$page_title = "Management User";
$result = mysqli_query($conn, "SELECT * FROM users ORDER BY id_user ASC");
$page_css   = "../includes/css/maus.css";
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?= $page_title; ?></title>

  <link rel="stylesheet" href="<?= $page_css; ?>">
</head>
<body>
    <?php include "../includes/header.php"; ?>

<div class="container">

  <?php include "../includes/sidebar.php"; ?>

  <main class="main">

    <h2>Management User</h2>

    <!-- BUTTON TAMBAH -->
    <button class="mimo-btn open-modal" data-target="#modalAdd">
      + Tambah User
    </button>

    <!-- TABLE -->
    <div class="table-panel">
      <table class="mimo-table" id="userTable">
        <thead>
          <tr>
            <th>Nama</th>
            <th>Email</th>
            <th>Role</th>
            <th>Aksi</th>
          </tr>
        </thead>

        <tbody>
          <tr>
            <td>Aslan</td>
            <td>aslan@example.com</td>
            <td>Admin</td>
            <td>
              <button class="mimo-btn small open-modal"
                      data-target="#modalEdit"
                      data-nama="Aslan"
                      data-email="aslan@example.com"
                      data-role="Admin">
                Edit
              </button>

              <button class="mimo-btn danger small open-modal"
                      data-target="#modalDelete"
                      data-user="Aslan">
                Hapus
              </button>
            </td>
          </tr>

          <tr>
            <td>Cis</td>
            <td>cis@example.com</td>
            <td>User</td>
            <td>
              <button class="mimo-btn small open-modal"
                      data-target="#modalEdit"
                      data-nama="Cis"
                      data-email="cis@example.com"
                      data-role="User">
                Edit
              </button>

              <button class="mimo-btn danger small open-modal"
                      data-target="#modalDelete"
                      data-user="Cis">
                Hapus
              </button>
            </td>
          </tr>

        </tbody>
      </table>
    </div>

  </main>
</div>

<?php include "../includes/footer.php"; ?>

<div class="modal" id="modalAdd">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Tambah User</h3>
      <span class="modal-close">&times;</span>
    </div>

    <form>
      <div class="input-group">
        <label>Nama</label>
        <input type="text" required>
      </div>

      <div class="input-group">
        <label>Email</label>
        <input type="email" required>
      </div>

      <div class="input-group">
        <label>Role</label>
        <select required>
          <option>Admin</option>
          <option>User</option>
        </select>
      </div>

      <div class="modal-footer">
        <button class="mimo-btn" type="submit">Simpan</button>
        <button class="mimo-btn secondary modal-close-btn" type="button">Batal</button>
      </div>
    </form>
  </div>
</div>

<div class="modal" id="modalEdit">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Edit User</h3>
      <span class="modal-close">&times;</span>
    </div>

    <form id="editForm">

      <div class="input-group">
        <label>Nama</label>
        <input type="text" name="nama" required>
      </div>

      <div class="input-group">
        <label>Email</label>
        <input type="email" name="email" required>
      </div>

      <div class="input-group">
        <label>Role</label>
        <select name="role" required>
          <option>Admin</option>
          <option>User</option>
        </select>
      </div>

      <div class="modal-footer">
        <button class="mimo-btn" type="submit">Update</button>
        <button class="mimo-btn secondary modal-close-btn" type="button">Batal</button>
      </div>
    </form>
  </div>
</div>

<div class="modal" id="modalDelete">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Hapus User</h3>
      <span class="modal-close">&times;</span>
    </div>

    <p id="deleteText">Yakin ingin menghapus?</p>

    <div class="modal-footer">
      <button class="mimo-btn danger">Hapus</button>
      <button class="mimo-btn secondary modal-close-btn" type="button">Batal</button>
    </div>
  </div>
</div>

<script>
// OPEN MODAL
document.querySelectorAll('.open-modal').forEach(btn => {
  btn.addEventListener('click', e => {
    const target = btn.dataset.target;
    const modal  = document.querySelector(target);
    modal.classList.add('show');

    // Isi data untuk modal edit
    if (target === "#modalEdit") {
      document.querySelector('#modalEdit input[name=nama]').value  = btn.dataset.nama;
      document.querySelector('#modalEdit input[name=email]').value = btn.dataset.email;
      document.querySelector('#modalEdit select[name=role]').value = btn.dataset.role;
    }

    // Isi data untuk modal delete
    if (target === "#modalDelete") {
      document.querySelector('#deleteText').innerText =
        `Yakin ingin menghapus user "${btn.dataset.user}"?`;
    }
  });
});

// CLOSE MODAL
document.querySelectorAll('.modal-close, .modal-close-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.modal.show').forEach(m => m.classList.remove('show'));
  });
});

// CLOSE WHEN CLICK OUTSIDE CONTENT
document.querySelectorAll('.modal').forEach(modal => {
  modal.addEventListener('click', e => {
    if (e.target.classList.contains('modal')) {
      modal.classList.remove('show');
    }
  });
});
</script>

</body>
</html>