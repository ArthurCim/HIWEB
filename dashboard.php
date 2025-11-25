<?php
$page_title = "Dashboard";
$page_css   = "includes/css/dashboard.css";
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo $page_title ?? "CodePlay"; ?></title>

  <!-- Load CSS Dashboard -->
  <link rel="stylesheet" href="<?php echo $page_css; ?>">
</head>
<body>

  <?php include "includes/header.php"; ?>
  <?php include "includes/navbar.php"; ?>

  <!-- ===================== -->
  <!--   LAYOUT UTAMA PURE CSS -->
  <!-- ===================== -->
  <div class="container">

    <!-- Sidebar -->
    <?php include "includes/sidebar.php"; ?>

    <!-- MAIN -->
    <main class="main">
      
      <h2>Selamat datang di kelas!</h2>
      <p>Halo rek!</p>

      <!-- Stats -->
      <div class="stats">
        <div class="card">
          <div class="title">Total User</div>
          <div class="value">1,240</div>
          <div class="meta">+12% dari bulan lalu</div>
        </div>

        <div class="card">
          <div class="title">Transaksi</div>
          <div class="value">3,560</div>
          <div class="meta">+8% dari bulan lalu</div>
        </div>

        <div class="card">
          <div class="title">Revenue</div>
          <div class="value">Rp 12.500.000</div>
          <div class="meta">+15% dari bulan lalu</div>
        </div>
      </div>

      <!-- Content Grid -->
      <div class="content">

        <!-- Table Panel -->
        <div class="table-panel">
          <table>
            <thead>
              <tr>
                <th>Nama</th>
                <th>Status</th>
                <th>Tanggal</th>
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

        <!-- Widget -->
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
      <!-- end content -->

    </main>
    <!-- end main -->
  </div>
  <!-- end container -->

  <?php include "includes/footer.php"; ?>

</body>
</html>
