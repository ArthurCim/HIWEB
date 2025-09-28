

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <link rel="stylesheet" href="dashboard.css">
</head>
<body>
  <nav class="navbar">
    <div class="brand">
      <img src="assets/logo putih.svg" alt="Logo">
      <h1>Dashboard</h1>
    </div>
    <div class="nav-actions">
      <a href="logout.php" class="logout">Logout</a>
    </div>
  </nav>

  <div class="container">
    <aside class="sidebar">
      <h3>Menu</h3>
      <ul class="nav-list">
        <li><a href="#" class="active">Dashboard</a></li>
        <li><a href="#">Laporan</a></li>
        <li><a href="#">Pengaturan</a></li>
      </ul>
    </aside>

    <main class="main">
      <h2>Selamat datang di Dashboard!</h2>
      <p>Halo rek!</p>

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

      <div class="content">
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
</body>
</html>
