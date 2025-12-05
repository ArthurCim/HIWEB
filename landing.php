<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Codeplay</title>
    <link href="landing.css" rel="stylesheet">

</head>



<body>
    <!-- Header -->
    <header class="header" id="header">
        <?php
        if (!isset($_SESSION)) session_start();
        ?>

        <a href="#home" class="logo-container">
            <div class="logo">
                <img src="assets/locoput.svg" alt="Logo CodePlay" width="100" height="100">
            </div>
            <div class="logo-text">CodePlay</div>
        </a>

        <nav class="main-menu" id="mainMenu">
            <a href="#home" class="menu-item active">Home</a>
            <a href="#about" class="menu-item">About</a>
            <a href="#contact" class="menu-item">Contact</a>

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
    <!-- Home Section -->
    <section id="home" class="section">
        <div class="coverflow-wrapper">
            <div class="info">
                <h2 id="current-title">PHP</h2>
                <p id="current-description">A popular general-purpose scripting language that is especially suited to web development</p>
            </div>

            <div class="coverflow-container" tabindex="0">
                <div class="coverflow" id="coverflow">
                    <div class="coverflow-item" data-index="0">
                        <div class="cover image-loading">
                            <img src="images/php.png" alt="PHP" loading="lazy">
                        </div>
                        <div class="reflection"></div>
                    </div>
                    <div class="coverflow-item" data-index="1">
                        <div class="cover image-loading">
                            <img src="images/Kotlin.png" alt="Kotlin" loading="lazy">
                        </div>
                        <div class="reflection"></div>
                    </div>
                    <div class="coverflow-item" data-index="2">
                        <div class="cover image-loading">
                            <img src="images/coding.png" alt="Coding" loading="lazy">
                        </div>
                        <div class="reflection"></div>
                    </div>
                    <div class="coverflow-item" data-index="3">
                        <div class="cover image-loading">
                            <img src="images/css-3.png" alt="CSS" loading="lazy">
                        </div>
                        <div class="reflection"></div>
                    </div>
                    <div class="coverflow-item" data-index="4">
                        <div class="cover image-loading">
                            <img src="images/java.png" alt="Java" loading="lazy">
                        </div>
                        <div class="reflection"></div>
                    </div>
                </div>

                <button class="nav-button prev" onclick="navigate(-1)">‹</button>
                <button class="nav-button next" onclick="navigate(1)">›</button>

                <div class="dots-container" id="dots"></div>

                <!-- Play/Pause Button -->
                <button class="play-pause-button" id="playPauseBtn" onclick="toggleAutoplay()">
                    <span class="play-icon">▶</span>
                    <span class="pause-icon" style="display: none;">❚❚</span>
                </button>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="section">
        <div class="about-content">
            <div class="about-header">
                <h2>CodePlay</h2>
                <p>A platform that transforms the way you interact with code through immersive experiences</p>
            </div>

            <div class="about-main">
                <div class="about-visual">
                    <div class="showcase-display">
                        <div class="showcase-main">
                            <div class="corner-decoration top-left"></div>
                            <div class="corner-decoration bottom-right"></div>

                            <div class="showcase-logo">
                                <img src="assets/locoput.svg" alt="Logo CodePlay" width="100" height="100">
                            </div>

                            <h3 class="showcase-title">CodePlay</h3>
                            <p class="showcase-subtitle">Transform your coding experience with interactive learning</p>

                            <div class="showcase-badges">
                                <a href="https://developer.mozilla.org/en-US/docs/Web/HTML" target="_blank" rel="noopener noreferrer" class="badge">HTML5</a>
                                <a href="https://developer.mozilla.org/en-US/docs/Web/CSS" target="_blank" rel="noopener noreferrer" class="badge">CSS3</a>
                                <a href="https://developer.mozilla.org/en-US/docs/Web/JavaScript" target="_blank" rel="noopener noreferrer" class="badge">JavaScript</a>
                                <a href="https://developer.mozilla.org/en-US/docs/Learn/CSS/CSS_layout/Responsive_Design" target="_blank" rel="noopener noreferrer" class="badge">Responsive</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="about-info">
                    <h3>About CodePlay</h3>
                    <p>CodePlay is a coding learning platform designed so that anyone can get started without confusion. With interactive exercises, simple explanations, and real projects, you can learn step-by-step at your own pace. From the basics to advanced skills—learning to code becomes easier, more focused, and more enjoyable.</p>
                    <p>Whenever you encounter learning that you find difficult and confusing, Codeplay is here to provide answers to those difficulties by presenting an application interface that is enjoyable and easy to understand.</p>

                    <ul class="feature-list">
                        <li>Interactive coding challenges and quizzes</li>
                        <li>Real-time code collaboration tools</li>
                        <li>Intuitive code editor with syntax highlighting</li>
                        <li>Built-in code snippets and templates</li>
                        <li>Comprehensive documentation and tutorials</li>
                    </ul>

                    <a href="download" class="cta-button">
                        Start Your Project
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M5 12h14m-7-7l7 7-7 7" stroke="currentColor" stroke-width="2" fill="none" />
                        </svg>
                    </a>
                </div>
            </div>

            <div class="stats-section">
                <div class="stat-item">
                    <div class="stat-number">5</div>
                    <div class="stat-label">Instructors</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">100+</div>
                    <div class="stat-label">courses</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">16M+</div>
                    <div class="stat-label">Users</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">∞</div>
                    <div class="stat-label">Things to Learn</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="section">
        <div class="contact-content">
            <div class="contact-header">
                <h2>Get In Touch</h2>
                <p>Got a burning passion? Let's ignite it together.</p>
            </div>

            <div class="contact-container">
                <div class="contact-info-section">
                    <h3>Let's Connect</h3>
                    <p>We're just a message away!</p>

                    <div class="contact-details">
                        <div class="contact-item">
                            <div class="contact-icon">
                                <svg viewBox="0 0 24 24">
                                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
                                </svg>
                            </div>
                            <div class="contact-text">
                                <h4>Email</h4>
                                <p>Codeplay@example.com</p>
                            </div>
                        </div>

                        <div class="contact-item">
                            <div class="contact-icon">
                                <svg viewBox="0 0 24 24">
                                    <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z" />
                                </svg>
                            </div>
                            <div class="contact-text">
                                <h4>Phone</h4>
                                <p>+1 (555) 123-4567</p>
                            </div>
                        </div>

                        <div class="contact-item">
                            <div class="contact-icon">
                                <svg viewBox="0 0 24 24">
                                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
                                </svg>
                            </div>
                            <div class="contact-text">
                                <h4>Address</h4>
                                <p>Land Of Narnia</p>
                            </div>
                        </div>

                        <div class="contact-item">
                            <div class="contact-icon">
                                <svg viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                                </svg>
                            </div>
                            <div class="contact-text">
                                <h4>Business Hours</h4>
                                <p>Monday - Friday: 9:00 AM - 6:00 PM PST</p>
                            </div>
                        </div>
                    </div>

                    <div class="social-links">
                        <h4>Follow Us</h4>
                        <div class="social-buttons">
                            <a href="#sosmed" class="social-btn" title="Facebook">
                                <svg viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                </svg>
                            </a>
                            <a href="#sosmed" class="social-btn" title="Twitter">
                                <svg viewBox="0 0 24 24">
                                    <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z" />
                                </svg>
                            </a>
                            <!-- <a href="#" class="social-btn" title="LinkedIn">
                                <svg viewBox="0 0 24 24">
                                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                                </svg>
                            </a> -->
                            <a href="#sosmed" class="social-btn" title="Instagram">
                                <svg viewBox="0 0 24 24">
                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zM5.838 12a6.162 6.162 0 1112.324 0 6.162 6.162 0 01-12.324 0zM12 16a4 4 0 110-8 4 4 0 010 8zm4.965-10.405a1.44 1.44 0 112.881.001 1.44 1.44 0 01-2.881-.001z" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="contact-form-section">
                    <form class="contact-form" onsubmit="handleSubmit(event)">
                        <div class="form-group">
                            <label for="name">Your Name</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" required>
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" required></textarea>
                        </div>
                        <button type="submit" class="submit-btn">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-copyright">
                © 2025 CodePlay. All rights reserved. | Designed by Team <a href="https://youtu.be/0J31XOv-PPo?si=qXMNCndbUKX-gsz8" target="_blank" rel="noopener noreferrer">OurTeam</a>
            </div>
            <div class="footer-links">
                <a href="#privacy" onclick="event.preventDefault(); alert('Privacy Policy page would go here');">Privacy Policy</a>
                <a href="#terms" onclick="event.preventDefault(); alert('Terms of Service page would go here');">Terms of Service</a>
            </div>
        </div>
    </footer>

    <!-- Scroll to top button -->
    <div class="scroll-to-top" id="scrollToTop">
        <span>↑</span>
    </div>

    <script src="landing.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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