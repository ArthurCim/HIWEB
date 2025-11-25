<aside class="sidebar">
    <h3>Menu</h3>
    <ul class="nav-list">
        <?php
        $currentPage = basename($_SERVER['PHP_SELF']);

        $baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/HIWEB/';

        $masterPages = ['management_user.php', 'course.php', 'lesson.php'];
        $isMasterActive = in_array($currentPage, $masterPages);
        ?>

        <!-- Dashboard -->
        <li>
            <a href="<?php echo $baseUrl; ?>dashboard.php"
               class="<?php echo $currentPage == 'dashboard.php' ? 'active' : ''; ?>">
               Dashboard
            </a>
        </li>

        <!-- Dropdown Master -->
        <li>
            <a href="javascript:void(0)" class="dropdown-toggle">
               Master
            </a>
            <ul class="submenu <?php echo $isMasterActive ? 'show' : ''; ?>">
                <li>
                    <a href="<?php echo $baseUrl; ?>master/management_user.php"
                       class="<?php echo $currentPage == 'management_user.php' ? 'active' : ''; ?>">
                       Management User
                    </a>
                </li>
                <li>
                    <a href="<?php echo $baseUrl; ?>master/course.php"
                       class="<?php echo $currentPage == 'course.php' ? 'active' : ''; ?>">
                       Data Course
                    </a>
                </li>
                <li>
                    <a href="<?php echo $baseUrl; ?>master/lesson.php"
                       class="<?php echo $currentPage == 'lesson.php' ? 'active' : ''; ?>">
                       Data Lesson
                    </a>
                </li>
            </ul>
        </li>

        <!-- Menu lain -->
        <li>
            <a href="<?php echo $baseUrl; ?>builder/lesson_builder.php"
               class="<?php echo $currentPage == 'lesson_builder.php' ? 'active' : ''; ?>">
               Lesson Builder
            </a>
        </li>

        <li>
            <a href="<?php echo $baseUrl; ?>builder/stage_answer_review.php"
               class="<?php echo $currentPage == 'stage_answer_review.php' ? 'active' : ''; ?>">
               Stage Answer Review
            </a>
        </li>

        <li>
            <a href="<?php echo $baseUrl; ?>progres/user_progress_monitor.php"
               class="<?php echo $currentPage == 'user_progress_monitor.php' ? 'active' : ''; ?>">
               User Progress
            </a>
        </li>

        <li>
            <!-- <a href="<?php echo $baseUrl; ?>pengaturan.php" -->
            <a href="#"
               class="<?php echo $currentPage == 'pengaturan.php' ? 'active' : ''; ?>">
               Pengaturan
            </a>
        </li>
    </ul>
    <style>
        .sidebar{
  overflow:auto;
  background: rgba(255,255,255,0.25);
  backdrop-filter: blur(18px);
  border: 1px solid rgba(255,255,255,0.25);
  box-shadow:0 8px 20px rgba(0,0,0,0.12);
  border-radius: 12px;
  padding: 12px;
}

.sidebar h3{
  margin:0 0 8px 0;
  font-size:14px;
  color:var(--muted);
}

.nav-list{
  list-style:none;
  padding:0;
  margin:12px 0 0 0;
  display:flex;
  flex-direction:column;
  gap:8px;
}
.nav-list a{
  display:flex;
  gap:10px;
  align-items:center;
  padding:8px 10px;
  color:#e5e7eb;
  text-decoration:none;
  border-radius:8px;
  font-size:14px;
  transition:0.2s;
}
.nav-list a:hover{ background: rgba(255,255,255,0.15); }

.nav-list a.active{
  background: linear-gradient(90deg, var(--accent-1), var(--accent-2));
  color:#fff;
  box-shadow: 0 6px 18px rgba(0,0,0,0.25);
}

/* Dropdown */
.dropdown-toggle {
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.dropdown-toggle::after {
  content: '▾';
  font-size: 12px;
  transition: 0.2s;
  opacity: 0.7;
}

.dropdown-toggle.active::after {
  transform: rotate(180deg);
  opacity: 1;
}

.submenu {
  max-height: 0;
  overflow: hidden;
  padding-left: 15px;

  display: flex;
  flex-direction: column;
  gap: 6px;

  opacity: 0;
  transform: translateY(-4px);

  transition:
    max-height 0.6s cubic-bezier(0.25, 0.1, 0.25, 1),
    opacity 0.45s ease,
    transform 0.45s ease;
}

/* Saat terbuka (smooth turun pelan) */
.submenu.show {
  max-height: 500px; /* cukup besar untuk semua item */
  opacity: 1;
  transform: translateY(0);
}


/* Style link submenu */
.submenu a {
  font-size: 13px;
  padding: 6px 10px;
  opacity: 0.85;
}

.submenu a:hover {
  background: rgba(255,255,255,0.12);
}

/* Toggle Master menu */
.dropdown-toggle {
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.dropdown-toggle::after {
  content: "▾";
  font-size: 13px;
  transition: transform 0.35s ease;
  opacity: 0.7;
}

.dropdown-toggle.active::after {
  transform: rotate(180deg);
  opacity: 1;
}
    </style>
</aside>
