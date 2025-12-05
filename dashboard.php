<?php
include "db.php";

$userQuery = mysqli_query($conn, "SELECT * FROM users ORDER BY id_user ASC");

$result = $conn->query("SELECT COUNT(*) AS total_user FROM users");
$data = $result->fetch_assoc();
$total_user = $data['total_user'];

$course = $conn->query("SELECT COUNT(*) AS total_course FROM courses");
$total_course = $course->fetch_assoc()['total_course'];

$premium = $conn->query("SELECT COUNT(*) AS total_premium FROM users WHERE is_premium = '1'");
$total_premium = $premium->fetch_assoc()['total_premium'];

$page_title = "Dashboard";
$page_css   = "dashboard.css";
include "includes/header.php";
?>


<div class="container-fluid">
  <div class="row">
    <?php include "includes/sidebar.php"; ?>

    <main class="main col">
      <h2>Selamat datang di kelas!</h2>
      <p>Halo rek!</p>

      <div class="stats">
        <div class="card">
          <div class="title">Total User</div>
          <div class="value"><?php echo $total_user; ?></div>
          <!-- <div class="meta">+12% dari bulan lalu</div> -->
        </div>
        <div class="card">
          <div class="title">Total Course</div>
          <div class="value"><?php echo $total_course; ?></div>
          <!-- <div class="meta">+8% dari bulan lalu</div> -->
        </div>
        <div class="card">
          <div class="title">Total User Premium</div>
          <div class="value"><?php echo $total_premium; ?></div>
          <!-- <div class="meta">Revenue : </div> -->
        </div>
      </div>

      <div class="content">
        <div class="table-panel">
          <table>
            <thead>
              <tr>
                <th>Nama</th>
                <th>Premium</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = mysqli_fetch_assoc($userQuery)): ?>
                <tr>
                  <td><?= htmlspecialchars($row['nama']); ?></td>
                  <td>
                    <?php if ($row['is_premium'] == 1): ?>
                      <span class="badge warn">Premium</span>
                    <?php else: ?>
                      <span class="badge success">Free</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <button class="mimo-btn mimo-btn-secondary edit-btn"
                      data-id="<?= $row['id_user']; ?>"
                      data-nama="<?= htmlspecialchars($row['nama']); ?>"
                      data-email="<?= htmlspecialchars($row['email'] ?? '-'); ?>"
                      data-bs-toggle="modal" data-bs-target="#editUserModal">
                      Edit
                    </button>

                    <button class="mimo-btn mimo-btn-danger delete-btn"
                      data-id="<?= $row['id_user']; ?>">
                      Hapus
                    </button>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>

          </table>
        </div>

        <div class="widget">
          <div class="gambar_kucing">
            <img src="assets/Screenshot (528).png" alt="">
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<?php include "includes/footer.php"; ?>

<!-- Script khusus dashboard -->
<script>
  document.getElementById("logoutBtn").addEventListener("click", function(e) {
    e.preventDefault();
    Swal.fire({
      title: 'Yakin ingin logout?',
      text: "Anda akan keluar dari sesi ini.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Ya, logout!',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = "login/logout.php";
      }
    });
  });
</script>