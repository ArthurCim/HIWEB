<?php
$page_title = "Dashboard_user";
$page_css   = "dashboard_user.css";
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $page_title ?? "CodePlay"; ?></title>

    <link rel="stylesheet" href="<?= $page_css; ?>">
</head>
<header class="header" id="header">
    <a href="#home" class="logo-container">
        <div class="logo">
            <img src="assets/locoput.svg" alt="Logo CodePlay" width="100" height="100">
        </div>
        <div class="logo-text">CodePlay</div>
    </a>

    <div class="nav-actions">
        <a href="#" id="logoutBtn" class="logout">Logout</a>
    </div>

    <div class="menu-toggle" id="menuToggle">
        <span></span>
        <span></span>
        <span></span>
    </div>
</header>

<body>

    <div class="container">
        <main class="main">
            <div class="widget user-widget clean">

                <div class="profile-area">
                    <div class="profile-pic clean-hover">
                        <img src="<?= $user['foto'] ?? 'includes/assets/hiyaa.jpg'; ?>" alt="Foto Profil">
                    </div>

                    <div class="profile-info">
                        <h3><?= $user['nama'] ?? 'User'; ?></h3>
                        <p><?= $user['email'] ?? 'email@example.com'; ?></p>
                    </div>
                </div>

                <div class="divider"></div>

                <h3 class="section-title">Your Activities</h3>

                <div class="kv full">
                    <span>Learning coding for</span>
                    <span class="badge success">8 days</span>
                </div>

                <div class="kv full">
                    <span>Active Streak</span>
                    <span class="badge success">2 days</span>
                </div>

                <div class="kv full">
                    <span>Energy</span>
                    <span class="badge warn">8</span>
                </div>

                <!-- PREMIUM CLEAN BOX -->
                <div class="premium-box clean-premium">
                    <div class="premium-left">
                        <span class="premium-badge">Premium</span>

                        <div class="premium-text">
                            <div>Status: <strong>Aktif</strong></div>
                            <div class="expire">Expire: 12 Feb 2026</div>
                        </div>
                    </div>

                    <button class="premium-btn">Manage</button>
                </div>

            </div>

            <!-- STAT CARDS CLEAN MODE -->
            <div class="stats">
                <?php
                $cards = [
                    ["Total Course", "2", 100],
                    ["Last Course", "Pemrograman Web", 8],
                    ["Last Stage", "Mantap Bro Bisa", 15],
                ];

                foreach ($cards as $c): ?>
                    <div class="card clean">
                        <div class="info">
                            <div class="title"><?= $c[0]; ?></div>
                            <div class="value"><?= $c[1]; ?></div>
                        </div>

                        <div class="circular-progress" data-percentage="<?= $c[2]; ?>">
                            <span class="progress-value"><?= $c[2]; ?>%</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </main>
    </div>

    <?php include "includes/footer.php"; ?>


    <script>
        // Dropdown script
        document.addEventListener('DOMContentLoaded', () => {
            const toggles = document.querySelectorAll('.dropdown-toggle');
            toggles.forEach(toggle => {
                toggle.addEventListener('click', () => {
                    toggle.nextElementSibling.classList.toggle('show');
                    toggle.classList.toggle('active');
                });
            });
        });

        // Circular progress animation
        document.addEventListener("DOMContentLoaded", () => {
            document.querySelectorAll(".circular-progress").forEach(el => {
                const val = el.getAttribute("data-percentage");
                el.style.setProperty("--percentage", val);
                el.querySelector(".progress-value").innerText = val + "%";
            });
        });
    </script>
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

</body>

</html>