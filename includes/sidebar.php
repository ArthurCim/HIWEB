<aside class="sidebar col-2">
    <h3>Menu</h3>
    <ul class="nav-list">
        <li>
            <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                Dashboard
            </a>
        </li>

        <!-- Dropdown Master -->
        <li>
            <a href="#masterMenu" data-bs-toggle="collapse" role="button"
                aria-expanded="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['management_user.php', 'course.php', 'lesson.php']) ? 'true' : 'false'; ?>"
                aria-controls="masterMenu">
                Master ▾
            </a>
            <ul class="collapse <?php echo in_array(basename($_SERVER['PHP_SELF']), ['management_user.php', 'course.php', 'lesson.php']) ? 'show' : ''; ?>" id="masterMenu">
                <li>
                    <a href="management_user.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'management_user.php' ? 'active' : ''; ?>">
                        Management User
                    </a>
                </li>
                <li>
                    <a href="course.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'course.php' ? 'active' : ''; ?>">
                        Data Course
                    </a>
                </li>
                <li>
                    <a href="lesson.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'lesson.php' ? 'active' : ''; ?>">
                        Data Lesson
                    </a>
                </li>
            </ul>
        </li>

        <li><a href="#">Pengaturan</a></li>
    </ul>
</aside>