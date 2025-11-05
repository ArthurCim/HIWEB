<aside class="sidebar col-2">
    <h3>Menu</h3>
    <ul class="nav-list">
        <?php
        // Ambil nama file aktif dan foldernya
        $currentPage = basename($_SERVER['PHP_SELF']);
        $currentDir  = basename(dirname($_SERVER['PHP_SELF']));

        // Daftar halaman dalam folder master
        $masterPages = ['management_user.php', 'course.php', 'lesson.php', 'stage.php'];
        $isMasterActive = in_array($currentPage, $masterPages);

        // Helper untuk tentukan path relatif
        function linkTo($path) {
            $dir = basename(dirname($_SERVER['PHP_SELF']));
            if ($dir == 'master' || $dir == 'builder') {
                return "../" . $path;
            }
            return $path;
        }
        ?>

        <!-- Dashboard -->
        <li>
            <a href="<?php echo linkTo('dashboard.php'); ?>"
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
                    <a href="<?php echo linkTo('master/management_user.php'); ?>"
                       class="<?php echo $currentPage == 'management_user.php' ? 'active' : ''; ?>">
                        Management User
                    </a>
                </li>
                <li>
                    <a href="<?php echo linkTo('master/course.php'); ?>"
                       class="<?php echo $currentPage == 'course.php' ? 'active' : ''; ?>">
                        Data Course
                    </a>
                </li>
                <li>
                    <a href="<?php echo linkTo('master/lesson.php'); ?>"
                       class="<?php echo $currentPage == 'lesson.php' ? 'active' : ''; ?>">
                        Data Lesson
                    </a>
                </li>
            </ul>
        </li>

        <!-- Lesson Builder -->
        <li>
            <a href="<?php echo linkTo('builder/lesson_builder.php'); ?>"
               class="<?php echo $currentPage == 'lesson_builder.php' ? 'active' : ''; ?>">
                Lesson Builder
            </a>
        </li>

        <li><a href="#">Pengaturan</a></li>
    </ul>
</aside>
