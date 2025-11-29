<?php
$page_title = "Dashboard_user";
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

    <div class="container">

        <!-- Sidebar -->
        <?php include "includes/sidebar.php"; ?>

        <!-- MAIN -->
        <main class="main">

            <h2>Selamat datang di kelas!</h2>
            <p>Halo rek!</p>

            <div class="stats">

                <div class="card">
                    <div class="info">
                        <div class="title">Total Course</div>
                        <div class="value">2</div>
                    </div>
                    <div class="circular-progress" data-percentage="100">
                        <span class="progress-value">100%</span>
                    </div>
                </div>

                <div class="card">
                    <div class="info">
                        <div class="title">Last Course</div>
                        <div class="value">Pemrograman Web</div>
                    </div>
                    <div class="circular-progress" data-percentage="8">
                        <span class="progress-value">8%</span>
                    </div>
                </div>

                <div class="card">
                    <div class="info">
                        <div class="title">Last Stage</div>
                        <div class="value">Mantap Bro Bisa</div>
                    </div>
                    <div class="circular-progress" data-percentage="15">
                        <span class="progress-value">15%</span>
                    </div>
                </div>

            </div>

            <!-- Content Grid -->
            <div class="content">

                <!-- Widget -->
                <div class="widget">
                    <h3>Your Activities</h3>

                    <div class="kv">
                        <span>Learning coding for</span>
                        <span class="badge success">8 days</span>
                    </div>

                    <div class="kv">
                        <span>Active Streak</span>
                        <span class="badge success">2 days</span>
                    </div>

                    <div class="kv">
                        <span>Energy</span>
                        <span class="badge warn">8</span>
                    </div>
                </div>

            </div>
            <!-- end content -->

        </main>
        <!-- end main -->
    </div>
    <!-- end container -->

    <?php include "includes/footer.php"; ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggles = document.querySelectorAll('.dropdown-toggle');

            toggles.forEach(toggle => {
                toggle.addEventListener('click', () => {
                    const submenu = toggle.nextElementSibling;

                    submenu.classList.toggle('show');
                    toggle.classList.toggle('active');
                });
            });
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const items = document.querySelectorAll(".circular-progress");

            items.forEach(el => {
                const val = el.getAttribute("data-percentage");
                el.style.setProperty("--percentage", val);
                el.querySelector(".progress-value").innerText = val + "%";
            });
        });
    </script>

</body>

</html>