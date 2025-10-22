<aside class="sidebar col-2">
    <h3>Menu</h3>
    <ul class="nav-list">
        <?php
        // Ambil nama file yang sedang aktif (tanpa folder)
        $currentPage = basename($_SERVER['PHP_SELF']);

        // Daftar halaman dalam folder master
        $masterPages = ['management_user.php', 'course.php', 'lesson.php', 'stage.php'];

        // Cek apakah halaman sekarang berasal dari folder master
        $isMasterActive = in_array($currentPage, $masterPages);
        ?>

        <!-- Dashboard -->
        <li>
            <a href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) == 'master') ? '../dashboard.php' : 'dashboard.php'; ?>"
               class="<?php echo $currentPage == 'dashboard.php' ? 'active' : ''; ?>">
                Dashboard
            </a>
        </li>

        <!-- Dropdown Master -->
        <li>
            <a href="#masterMenu" data-bs-toggle="collapse" role="button"
               aria-expanded="<?php echo $isMasterActive ? 'true' : 'false'; ?>"
               aria-controls="masterMenu">
                Master ▾
            </a>
            <ul class="collapse <?php echo $isMasterActive ? 'show' : ''; ?>" id="masterMenu">
                <li>
                    <a href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) == 'master') ? 'management_user.php' : 'master/management_user.php'; ?>"
                       class="<?php echo $currentPage == 'management_user.php' ? 'active' : ''; ?>">
                        Management User
                    </a>
                </li>
                <li>
                    <a href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) == 'master') ? 'course.php' : 'master/course.php'; ?>"
                       class="<?php echo $currentPage == 'course.php' ? 'active' : ''; ?>">
                        Data Course
                    </a>
                </li>
                <li>
                    <a href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) == 'master') ? 'lesson.php' : 'master/lesson.php'; ?>"
                       class="<?php echo $currentPage == 'lesson.php' ? 'active' : ''; ?>">
                        Data Lesson
                    </a>
                </li>
                <li>
                    <a href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) == 'master') ? 'stage.php' : 'master/stage.php'; ?>"
                       class="<?php echo $currentPage == 'stage.php' ? 'active' : ''; ?>">
                        Data Stage
                    </a>
                </li>
            </ul>
        </li>

        <li><a href="#">Pengaturan</a></li>
    </ul>
</aside>
