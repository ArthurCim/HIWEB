<aside class="sidebar col-2">
    <h3>Menu</h3>
    <ul class="nav-list">
        <?php
        // Ambil nama file aktif
        $currentPage = basename($_SERVER['PHP_SELF']);

        // Tentukan base URL dinamis dari root HIWEB
        $baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/HIWEB/';

        // Daftar halaman dalam folder master
        $masterPages = ['management_user.php', 'course.php', 'lesson.php', 'pricing.php'];
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
            <a href="#masterMenu" data-bs-toggle="collapse" role="button"
               aria-expanded="<?php echo $isMasterActive ? 'true' : 'false'; ?>"
               aria-controls="masterMenu">
               Master ▾
            </a>
            <ul class="collapse <?php echo $isMasterActive ? 'show' : ''; ?>" id="masterMenu">
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
                <li>
                    <a href="<?php echo $baseUrl; ?>master/pricing.php"
                       class="<?php echo $currentPage == 'pricing.php' ? 'active' : ''; ?>">
                       Data Harga
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
</aside>
