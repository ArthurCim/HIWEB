<?php
include "db.php";

$result = $conn->query("SELECT COUNT(*) AS total_user FROM users");
$data = $result->fetch_assoc();
$total_user = $data['total_user'];

$course = $conn->query("SELECT COUNT(*) AS total_course FROM courses");
$total_course = $course->fetch_assoc()['total_course'];




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
          <div class="meta">+12% dari bulan lalu</div>
        </div>
        <div class="card">
          <div class="title">Total Course</div>
          <div class="value"><?php echo $total_course; ?></div>
          <div class="meta">+8% dari bulan lalu</div>
        </div>
        <div class="card">
          <div class="title">Total User Premium</div>
          <div class="value">200</div>
          <div class="meta">Revenue : </div>
        </div>
      </div>

      <div class="content">
        <div class="table-panel">
          <table>
            <thead>
              <tr>
                <th>Nama</th>
                <th>Status</th>
                <th>Premium</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Aslan</td>
                <td><span class="badge success">Aktif</span></td>
                <td>18-09-2025</td>
              </tr>
              <tr>
                <td>Asl</td>
                <td><span class="badge warn">Pending</span></td>
                <td>17-09-2025</td>
              </tr>
              <tr>
                <td>Lan</td>
                <td><span class="badge danger">Banned</span></td>
                <td>16-09-2025</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="widget">
          <h3>Notifikasi</h3>
          <div class="kv">
            <span>Update sistem</span>
            <span class="badge success">Baru</span>
          </div>
          <div class="kv">
            <span>Maintenance</span>
            <span class="badge warn">Segera</span>
          </div>
          <div class="kv">
            <span>Error login</span>
            <span class="badge danger">Kritis</span>
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