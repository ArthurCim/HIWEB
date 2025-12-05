<?php
session_start();
include __DIR__ . '/db.php';
include __DIR__ . '/config/midtrans_config.php';
include __DIR__ . '/includes/midtrans_helper.php';

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Sync premium status with active subscriptions
$id_user = $_SESSION['id_user'] ?? null;
if ($id_user) {
    // Check for active subscriptions
    $active_sub = $conn->prepare(
        "SELECT COUNT(*) as count FROM user_subscriptions 
         WHERE id_user = ? AND payment_status = 'PAID' AND end_date > NOW()"
    );
    $active_sub->bind_param("s", $id_user);
    $active_sub->execute();
    $active_sub_result = $active_sub->get_result()->fetch_assoc();
    $has_active_sub = $active_sub_result['count'] > 0;
    $active_sub->close();

    // Update is_premium flag based on active subscriptions
    $target_premium = $has_active_sub ? 1 : 0;
    $update_flag = $conn->prepare("UPDATE users SET is_premium = ? WHERE id_user = ?");
    $update_flag->bind_param("is", $target_premium, $id_user);
    $update_flag->execute();
    $update_flag->close();
}

// Now include get_user_data which will fetch the updated is_premium value
include __DIR__ . '/includes/get_user_data.php';

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
        <a href="#" class="logo-container">
            <div class="logo">
                <img src="assets/locoput.svg" alt="Logo CodePlay" width="100" height="100">
            </div>
            <div class="logo-text">CodePlay</div>
        </a>

        <nav class="main-menu" id="mainMenu">
            <a href="landing.php#home" class="menu-item active">Home</a>
            <a href="landing.php#about" class="menu-item">About</a>
            <a href="landing.php#contact" class="menu-item">Contact</a>

            <?php if (isset($_SESSION['login']) && $_SESSION['role'] === 'user'): ?>

                <div class="profile-wrapper">
                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Crect fill='%23DDD' width='100' height='100'/%3E%3Ccircle cx='50' cy='35' r='15' fill='%23999'/%3E%3Cellipse cx='50' cy='70' rx='25' ry='20' fill='%23999'/%3E%3C/svg%3E" alt="Foto Profil" style="background: #f0f0f0; border-radius: 50%; object-fit: cover;"
                        class="profile-pic" id="profileBtn">

                    <div class="dropdown-menu" id="profileDropdown">
                        <a href="dashboard_user.php">👤 Profile</a>
                        <a href="#" id="logoutBtn" class="logout">🚪 Logout</a>
                    </div>
                </div>

            <?php else: ?>
                <div class="nav-actions">
                    <a href="login/login.php" id="loginBtn" class="login">Login</a>
                </div>
            <?php endif; ?>
        </nav>
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
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Crect fill='%23DDD' width='100' height='100'/%3E%3Ccircle cx='50' cy='35' r='15' fill='%23999'/%3E%3Cellipse cx='50' cy='70' rx='25' ry='20' fill='%23999'/%3E%3C/svg%3E" alt="Foto Profil" style="background: #f0f0f0; border-radius: 50%; object-fit: cover;">
                    </div>

                    <div class="profile-info">
                        <h3><?= htmlspecialchars($user['nama'] ?? 'User'); ?></h3>
                        <p><?= htmlspecialchars($user['email'] ?? 'email@example.com'); ?></p>
                    </div>
                </div>

                <div class="divider"></div>

                <h3 class="section-title">Your Activities</h3>

                <div class="kv full">
                    <span>Learning coding for</span>
                    <span class="badge success"><?= $stats['days_learning'] ?? 0; ?> days</span>
                </div>

                <div class="kv full">
                    <span>Stages Completed</span>
                    <span class="badge success"><?= $stats['completed_stage'] ?? 0; ?>/<?= $stats['total_stage'] ?? 0; ?></span>
                </div>

                <div class="kv full">
                    <span>Progress</span>
                    <span class="badge warn"><?= $progress_percentage; ?>%</span>
                </div>

                <!-- PREMIUM CLEAN BOX -->
                <div class="premium-box clean-premium">
                    <div class="premium-left">
                        <span class="premium-badge"><?= $is_premium ? 'Premium' : 'Free'; ?></span>

                        <div class="premium-text">
                            <div>Status: <strong><?= $is_premium ? 'Aktif' : 'Tidak Aktif'; ?></strong></div>
                            <div class="expire">
                                <?php if ($is_premium): ?>
                                    Expire: <?= date('d M Y', strtotime($premium_expire_date)); ?>
                                <?php else: ?>
                                    Upgrade untuk membuka fitur premium
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <button class="premium-btn" id="premiumBtn" style="flex: 1;">
                            <?= $is_premium ? 'Manage' : 'Upgrade'; ?>
                        </button>
                        <button class="premium-btn" id="refreshPremiumBtn" style="flex: 1; background: rgba(255,255,255,0.1);" title="Refresh status">
                            ↻ Refresh
                        </button>
                    </div>
                </div>

            </div>

            <!-- STAT CARDS CLEAN MODE -->
            <div class="stats">
                <?php
                $cards = [
                    ["Total Course", $stats['total_course'] ?? 0, 0],
                    ["Last Course", !empty($last_course) ? $last_course : "Belum ada", 0],
                    ["Completion", $progress_percentage . "%",0],
                ];

                foreach ($cards as $c): ?>
                    <div class="card clean">
                        <div class="info">
                            <div class="title"><?= $c[0]; ?></div>
                            <div class="value"><?= $c[1]; ?></div>
                        </div>

                        <div class="circular-progress" data-percentage="<?= is_numeric($c[2]) ? $c[2] : 0; ?>">
                            <span class="progress-value"><?= is_numeric($c[2]) ? $c[2] : 0; ?>%</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </main>
    </div>

    <?php include "includes/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="<?= MIDTRANS_CLIENT_KEY; ?>"></script>

    <script>
        // Subscription plans dari server
        window.subscriptionPlans = <?= json_encode($subscription_plans ?? []); ?>;

        // Dropdown script
        document.addEventListener('DOMContentLoaded', () => {
            const toggles = document.querySelectorAll('.dropdown-toggle');
            toggles.forEach(toggle => {
                toggle.addEventListener('click', () => {
                    toggle.nextElementSibling.classList.toggle('show');
                    toggle.classList.toggle('active');
                });
            });

            // Auto-settle any pending payments on page load
            autoSettlePendingPayments();
        });

        // Circular progress animation
        document.addEventListener("DOMContentLoaded", () => {
            document.querySelectorAll(".circular-progress").forEach(el => {
                const val = el.getAttribute("data-percentage");
                el.style.setProperty("--percentage", val);
                el.querySelector(".progress-value").innerText = val + "%";
            });
        });

        // Auto-settle pending payments
        function autoSettlePendingPayments() {
            fetch('includes/auto_settle_payments.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.settled_count > 0) {
                        console.log('Auto-settled ' + data.settled_count + ' payment(s)');
                        // Reload to show updated premium status
                        setTimeout(() => location.reload(), 1000);
                    }
                })
                .catch(err => console.log('Auto-settle check completed'));
        }

        // Refresh button handler
        document.getElementById('refreshPremiumBtn').addEventListener('click', function() {
            this.disabled = true;
            this.textContent = '⟳ Checking...';

            fetch('includes/auto_settle_payments.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            title: 'Status Diperbarui!',
                            text: data.message,
                            icon: 'success'
                        }).then(() => location.reload());
                    } else {
                        Swal.fire('Info', data.message || 'Tidak ada perubahan', 'info');
                        this.disabled = false;
                        this.textContent = '↻ Refresh';
                    }
                })
                .catch(err => {
                    Swal.fire('Error', 'Gagal mengecek status', 'error');
                    this.disabled = false;
                    this.textContent = '↻ Refresh';
                });
        });
    </script>
    <script>
        // Premium Button - Midtrans Integration
        document.getElementById("premiumBtn").addEventListener("click", function(e) {
            e.preventDefault();

            const isPremium = this.innerText.includes('Manage');

            if (isPremium) {
                // Jika sudah premium, tampilkan menu manage
                showManagePremium();
            } else {
                // Jika belum premium, tampilkan pilihan paket
                showUpgradeOptions();
            }
        });

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

        function showUpgradeOptions() {
            // Compute monthly base price from server-provided plans (prefer 1-month), else fallback
            const plans = window.subscriptionPlans || [];
            let monthly = null;
            if (plans.length > 0) {
                // try to find 1-month plan
                for (const p of plans) {
                    if (Number(p.durasi_bulan) === 1) {
                        monthly = Number(p.harga);
                        break;
                    }
                }
                if (monthly === null) {
                    // derive from smallest duration plan
                    plans.sort((a, b) => Number(a.durasi_bulan) - Number(b.durasi_bulan));
                    const p = plans[0];
                    monthly = Number(p.harga) / Math.max(1, Number(p.durasi_bulan));
                }
            }
            if (monthly === null) monthly = 99000; // fallback

            // Compute discounted package prices
            const price1 = Math.round(monthly * 1);
            const price3 = Math.round(monthly * 3 * (1 - 0.05));
            const price12 = Math.round(monthly * 12 * (1 - 0.15));

            const html = `
                    <div style="text-align: left; padding: 20px;">
                        <div style="margin: 15px 0;">
                            <input type="radio" id="package_1month" name="package" value="1" checked>
                            <label for="package_1month">1 Bulan - Rp ${price1.toLocaleString()}</label>
                        </div>
                        <div style="margin: 15px 0;">
                            <input type="radio" id="package_3month" name="package" value="3">
                            <label for="package_3month">3 Bulan - Rp ${price3.toLocaleString()} (Diskon 5%)</label>
                        </div>
                        <div style="margin: 15px 0;">
                            <input type="radio" id="package_12month" name="package" value="12">
                            <label for="package_12month">12 Bulan - Rp ${price12.toLocaleString()} (Diskon 15%)</label>
                        </div>
                    </div>`;

            Swal.fire({
                title: 'Pilih Paket Premium',
                html: `<div style="text-align:left; padding:20px;">${html}</div>`,
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Lanjut Pembayaran',
                cancelButtonText: 'Batal',
                didOpen: () => {
                    document.querySelectorAll('input[name="package"]').forEach(radio => {
                        radio.addEventListener('change', (e) => {
                            console.log('Selected:', e.target.value);
                        });
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const selectedPackage = document.querySelector('input[name="package"]:checked').value;
                    proceedToPayment(selectedPackage);
                }
            });
        }

        function proceedToPayment(months) {
            // Show loading
            Swal.fire({
                title: 'Memproses...',
                html: 'Sedang membuat transaksi pembayaran',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send AJAX request
            fetch('midtrans/create_payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        duration_months: months
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Close loading
                        Swal.close();

                        // Show Midtrans Snap
                        snap.pay(data.token, {
                            onSuccess: function(result) {
                                handlePaymentSuccess(result);
                            },
                            onPending: function(result) {
                                handlePaymentPending(result);
                            },
                            onError: function(result) {
                                handlePaymentError(result);
                            },
                            onClose: function() {
                                Swal.fire('Info', 'Pembayaran dibatalkan', 'info');
                            }
                        });
                    } else {
                        Swal.fire('Error', data.message || 'Gagal membuat transaksi', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Terjadi kesalahan: ' + error.message, 'error');
                });
        }

        function handlePaymentSuccess(result) {
            // Sync premium status immediately
            fetch('includes/sync_premium_status.php')
                .then(response => response.json())
                .then(data => {
                    Swal.fire({
                        title: 'Pembayaran Berhasil!',
                        text: 'Premium Anda akan aktif sekarang juga',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                })
                .catch(err => {
                    console.error('Sync error:', err);
                    // Still reload even if sync fails
                    Swal.fire({
                        title: 'Pembayaran Berhasil!',
                        text: 'Premium Anda akan aktif sekarang juga',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                });
        }

        function handlePaymentPending(result) {
            Swal.fire({
                title: 'Pembayaran Tertunda',
                text: 'Silakan selesaikan pembayaran Anda. Kami akan mengkonfirmasi status pembayaran.',
                icon: 'info',
                confirmButtonText: 'OK'
            }).then(() => {
                location.reload();
            });
        }

        function handlePaymentError(result) {
            Swal.fire({
                title: 'Pembayaran Gagal',
                text: 'Terjadi kesalahan saat memproses pembayaran. Silakan coba lagi.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }

        function showManagePremium() {
            Swal.fire({
                title: 'Kelola Premium',
                html: `
                    <div style="text-align: center; padding: 20px;">
                        <p>Status: <strong>Aktif</strong></p>
                        <p style="margin: 20px 0;">Paket premium Anda masih aktif</p>
                    </div>
                `,
                confirmButtonText: 'Kembali',
                icon: 'info'
            });
        }
    </script>

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
        const profileBtn = document.getElementById("profileBtn");
        const dropdown = document.getElementById("profileDropdown");

        profileBtn.addEventListener("click", () => {
            dropdown.style.display = dropdown.style.display === "flex" ? "none" : "flex";
        });

        // klik di luar dropdown → tutup
        document.addEventListener("click", function(e) {
            if (!profileBtn.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.style.display = "none";
            }
        });
    </script>


</body>

</html>