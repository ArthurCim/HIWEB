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

$free = $conn->query("SELECT COUNT(*) AS total_free FROM users WHERE is_premium = '0'");
$total_free = $free->fetch_assoc()['total_free'];


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
                <th>Aksi</th>
                <th>Premium</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = mysqli_fetch_assoc($userQuery)): ?>
                <tr>
                  <td><?= htmlspecialchars($row['nama']); ?></td>
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
                  <td>
                    <?php if ($row['is_premium'] == 1): ?>
                      <span class="badge warn">Premium</span>
                    <?php else: ?>
                      <span class="badge success">Free</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>

          </table>
        </div>

        <div class="widget">
          <div id="chartdiv" style="width: 100%;
  height: 500px; "></div>
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
<script src="https://cdn.amcharts.com/lib/5/index.js"></script>
<script src="https://cdn.amcharts.com/lib/5/percent.js"></script>
<script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>

<script>
  const totalPremium = <?= json_encode($total_premium); ?>;
  const totalFree = <?= json_encode($total_free); ?>;
</script>


<script>
am5.ready(function() {

// Create root element
// https://www.amcharts.com/docs/v5/getting-started/#Root_element
var root = am5.Root.new("chartdiv");

// Set themes
// https://www.amcharts.com/docs/v5/concepts/themes/
root.setThemes([
  am5themes_Animated.new(root)
]);

// Create chart
// https://www.amcharts.com/docs/v5/charts/percent-charts/pie-chart/
var chart = root.container.children.push(
  am5percent.PieChart.new(root, {
    endAngle: 270
  })
);

// Create series
// https://www.amcharts.com/docs/v5/charts/percent-charts/pie-chart/#Series
var series = chart.series.push(
  am5percent.PieSeries.new(root, {
    valueField: "value",
    categoryField: "category",
    endAngle: 270
  })
);

series.states.create("hidden", {
  endAngle: -90
});

// Set data
// https://www.amcharts.com/docs/v5/charts/percent-charts/pie-chart/#Setting_data
series.data.setAll([{
  category: "Premium",
  value: totalPremium
}, {
  category: "Free",
  value: totalFree
}]);

series.appear(1000, 100);

}); // end am5.ready()
</script>