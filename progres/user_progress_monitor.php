<?php
// user_progress_monitor.php
include "../db.php";
session_start();
if (!isset($_SESSION['login'])) {
    header('Location:../login/login.php');
    exit();
}
$page_title = "User Progress Monitor";
$page_css = "user_progress.css";

// Get filter parameters
$filter_course = $_GET['course'] ?? '';
$filter_lesson = $_GET['lesson'] ?? '';
$filter_status = $_GET['status'] ?? '';
$search_user = $_GET['search'] ?? '';

// Build query
$whereConditions = ["1=1"];
$params = [];
$types = "";

if ($filter_course) {
    $whereConditions[] = "c.id_courses = ?";
    $params[] = $filter_course;
    $types .= "s";
}

if ($filter_lesson) {
    $whereConditions[] = "l.id_lesson = ?";
    $params[] = $filter_lesson;
    $types .= "s";
}

if ($search_user) {
    $whereConditions[] = "(u.nama LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%{$search_user}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

$whereClause = implode(" AND ", $whereConditions);

$sql = "SELECT 
            u.id_user,
            u.nama AS user_name,
            u.email,
            u.role,
            c.id_courses,
            c.nama_courses,
            l.id_lesson,
            l.nama_lesson,
            COUNT(DISTINCT s.id_stage) AS total_stages,
            COUNT(DISTINCT usp.id_stage) AS completed_stages,
            ROUND((COUNT(DISTINCT usp.id_stage) / COUNT(DISTINCT s.id_stage)) * 100, 2) AS progress_percentage,
            MAX(usp.completion_date) AS last_activity,
            SUM(usp.score) AS total_score
        FROM users u
        CROSS JOIN courses c
        LEFT JOIN lesson l ON l.id_courses = c.id_courses
        LEFT JOIN stage s ON s.id_lesson = l.id_lesson
        LEFT JOIN user_stage_progress usp ON usp.id_user = u.id_user 
            AND usp.id_stage = s.id_stage
        WHERE {$whereClause}
            AND u.role = 'user'
            AND EXISTS (
                SELECT 1 
                FROM user_stage_progress up2
                INNER JOIN stage s2 ON up2.id_stage = s2.id_stage
                INNER JOIN lesson l2 ON s2.id_lesson = l2.id_lesson
                WHERE up2.id_user = u.id_user 
                  AND l2.id_courses = c.id_courses
            )
        GROUP BY u.id_user, c.id_courses, l.id_lesson
        HAVING total_stages > 0";



if ($filter_status === 'completed') {
    $sql .= " AND progress_percentage = 100";
} elseif ($filter_status === 'in_progress') {
    $sql .= " AND progress_percentage > 0 AND progress_percentage < 100";
} elseif ($filter_status === 'not_started') {
    $sql .= " AND progress_percentage = 0";
}

$sql .= " ORDER BY u.nama ASC, c.nama_courses ASC, l.nama_lesson ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$progressData = [];
while ($row = $result->fetch_assoc()) {
    $progressData[] = $row;
}
$stmt->close();

// Get courses for filter
$courses = $conn->query("SELECT id_courses, nama_courses FROM courses ORDER BY nama_courses ASC");

// Get lessons for filter
$lessons_query = "SELECT id_lesson, nama_lesson FROM lesson";
if ($filter_course) {
    $lessons_query .= " WHERE id_courses = '{$filter_course}'";
}
$lessons_query .= " ORDER BY nama_lesson ASC";
$lessons = $conn->query($lessons_query);

// Calculate summary statistics
$total_users = count(array_unique(array_column($progressData, 'id_user')));
$avg_progress = $progressData ? array_sum(array_column($progressData, 'progress_percentage')) / count($progressData) : 0;
// print_r($avg_progress);
// exit();
$completed_count = count(array_filter($progressData, fn($d) => $d['progress_percentage'] == 100));
$in_progress_count = count(array_filter($progressData, fn($d) => $d['progress_percentage'] > 0 && $d['progress_percentage'] < 100));

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title; ?></title>

    <link rel="stylesheet" href="<?= $page_css; ?>">
</head>

<body>

    <!-- Header -->
    <?php include "../includes/header.php"; ?> 

    <div class="container">
    <div class="row">
        <?php include "../includes/sidebar.php"; ?>

        <main class="main col-10">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-2">📊 User Progress Monitor</h2>
                    <p class="text-muted mb-0">Pantau progress pembelajaran setiap user secara real-time</p>
                </div>
                <button class="export-btn" onclick="exportToCSV()">
                    📥 Export Data
                </button>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stats-card users">
                        <div class="icon">👥</div>
                        <div class="value"><?= $total_users ?></div>
                        <div class="label">Total Users</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card avg-progress">
                        <div class="icon">📈</div>
                        <div class="value">
                            <?= number_format($avg_progress, 1) ?><span class="unit">%</span>
                        </div>
                        <div class="label">Avg Progress</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card completed">
                        <div class="icon">✅</div>
                        <div class="value"><?= $completed_count ?></div>
                        <div class="label">Completed</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card active">
                        <div class="icon">🔥</div>
                        <div class="value"><?= $in_progress_count ?></div>
                        <div class="label">In Progress</div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filter-section">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Course</label>
                        <select name="course" class="form-select" id="filterCourse">
                            <option value="">All Courses</option>
                            <?php while ($course = $courses->fetch_assoc()): ?>
                                <option value="<?= $course['id_courses'] ?>" <?= $filter_course == $course['id_courses'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($course['nama_courses']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Lesson</label>
                        <select name="lesson" class="form-select" id="filterLesson">
                            <option value="">All Lessons</option>
                            <?php while ($lesson = $lessons->fetch_assoc()): ?>
                                <option value="<?= $lesson['id_lesson'] ?>" <?= $filter_lesson == $lesson['id_lesson'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($lesson['nama_lesson']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="completed" <?= $filter_status == 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="in_progress" <?= $filter_status == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="not_started" <?= $filter_status == 'not_started' ? 'selected' : '' ?>>Not Started</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Search User</label>
                        <input type="text" name="search" class="form-control" placeholder="Name or email..." value="<?= htmlspecialchars($search_user) ?>">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>

            <!-- Progress Table -->
            <div class="progress-table">
                <div class="progress-table-wrapper">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Course</th>
                                <th>Lesson</th>
                                <th>Progress</th>
                                <th>Stages</th>
                                <th>Score</th>
                                <th>Last Activity</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($progressData)): ?>
                                <tr>
                                    <td colspan="9">
                                        <div class="empty-state">
                                            <i class="fas fa-inbox"></i>
                                            <p class="mb-0">No progress data found</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($progressData as $data):
                                    $progress = $data['progress_percentage'];
                                    $progressClass = $progress == 100 ? 'complete' : ($progress >= 50 ? 'high' : ($progress > 0 ? 'medium' : 'low'));
                                    $statusClass = $progress == 100 ? 'completed' : ($progress > 0 ? 'in-progress' : 'not-started');
                                    $statusText = $progress == 100 ? 'Completed' : ($progress > 0 ? 'In Progress' : 'Not Started');
                                    $initials = strtoupper(substr($data['user_name'], 0, 2));
                                ?>
                                    <tr>
                                        <td>
                                            <div class="user-info">
                                                <div class="user-avatar"><?= $initials ?></div>
                                                <div class="user-details">
                                                    <h6><?= htmlspecialchars($data['user_name']) ?></h6>
                                                    <small><?= htmlspecialchars($data['email']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($data['nama_courses']) ?></td>
                                        <td><span class="lesson-badge"><?= htmlspecialchars($data['nama_lesson']) ?></span></td>
                                        <td style="min-width: 180px;">
                                            <div class="progress-bar-wrapper">
                                                <div class="progress-bar-fill <?= $progressClass ?>" style="width: <?= $progress ?>%"></div>
                                            </div>
                                            <div class="progress-text"><?= number_format($progress, 1) ?>%</div>
                                        </td>
                                        <td><strong><?= $data['completed_stages'] ?></strong> / <?= $data['total_stages'] ?></td>
                                        <td><strong><?= $data['total_score'] ?? 0 ?></strong></td>
                                        <td>
                                            <?php if ($data['last_activity']): ?>
                                                <small><?= date('d M Y', strtotime($data['last_activity'])) ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge-status <?= $statusClass ?>"><?= $statusText ?></span></td>
                                        <td>
                                            <button class="btn-detail" onclick="viewDetail('<?= $data['id_user'] ?>', '<?= $data['id_lesson'] ?>')">
                                                View Detail
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Progress Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>

<script>
    // Dynamic lesson filter based on course
    $('#filterCourse').on('change', function() {
        const courseId = $(this).val();
        const $lessonSelect = $('#filterLesson');

        $lessonSelect.html('<option value="">Loading...</option>');

        if (!courseId) {
            $.get('../ajax/get_lessons.php', function(data) {
                let html = '<option value="">All Lessons</option>';
                data.forEach(lesson => {
                    html += `<option value="${lesson.id_lesson}">${lesson.nama_lesson}</option>`;
                });
                $lessonSelect.html(html);
            });
            return;
        }

        $.get('../ajax/get_lessons.php', {
            id_courses: courseId
        }, function(data) {
            let html = '<option value="">All Lessons</option>';
            data.forEach(lesson => {
                html += `<option value="${lesson.id_lesson}">${lesson.nama_lesson}</option>`;
            });
            $lessonSelect.html(html);
        });
    });

    // View user detail
    function viewDetail(userId, lessonId) {
        $('#detailModal').modal('show');
        $('#detailContent').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');

        $.get('../ajax/get_user_progress_detail.php', {
            id_user: userId,
            id_lesson: lessonId
        }, function(response) {
            if (response.status === 'success') {
                renderDetailView(response.data);
            } else {
                $('#detailContent').html(`<div class="alert alert-danger">${response.message}</div>`);
            }
        }).fail(function() {
            $('#detailContent').html('<div class="alert alert-danger">Failed to load data</div>');
        });
    }

    function renderDetailView(data) {
        let html = `
        <div class="row mb-4">
            <div class="col-md-6">
                <h6 class="text-muted mb-2">User Information</h6>
                <h4>${data.user_name}</h4>
                <p class="text-muted mb-0">${data.email}</p>
            </div>
            <div class="col-md-6 text-end">
                <h6 class="text-muted mb-2">Learning Path</h6>
                <h5>${data.course_name}</h5>
                <p class="text-muted mb-0">${data.lesson_name}</p>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Stage</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Score</th>
                        <th>Completed At</th>
                    </tr>
                </thead>
                <tbody>`;

        data.stages.forEach(stage => {
            const statusBadge = stage.is_completed ?
                '<span class="badge bg-success">Completed</span>' :
                '<span class="badge bg-secondary">Not Started</span>';

            html += `
            <tr>
                <td><strong>${stage.nama_stage}</strong></td>
                <td><span class="badge bg-info">${stage.type}</span></td>
                <td>${statusBadge}</td>
                <td>${stage.score || '-'}</td>
                <td>${stage.completion_date ? new Date(stage.completion_date).toLocaleString() : '-'}</td>
            </tr>`;
        });

        html += `
                </tbody>
            </table>
        </div>`;

        $('#detailContent').html(html);
    }

    // Export to CSV
    function exportToCSV() {
        const rows = [];
        rows.push(['User Name', 'Email', 'Course', 'Lesson', 'Progress %', 'Completed Stages', 'Total Stages', 'Total Score', 'Last Activity', 'Status']);

        $('tbody tr').each(function() {
            if ($(this).find('td').length > 1) {
                const row = [];
                row.push($(this).find('.user-details h6').text());
                row.push($(this).find('.user-details small').text());
                row.push($(this).find('td:eq(1)').text());
                row.push($(this).find('.lesson-badge').text());
                row.push($(this).find('.progress-text').text().replace('%', ''));
                row.push($(this).find('td:eq(4)').text().split('/')[0].trim());
                row.push($(this).find('td:eq(4)').text().split('/')[1].trim());
                row.push($(this).find('td:eq(5) strong').text());
                row.push($(this).find('td:eq(6) small').text());
                row.push($(this).find('.badge-status').text());
                rows.push(row);
            }
        });

        const csv = rows.map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
        const blob = new Blob([csv], {
            type: 'text/csv'
        });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `user_progress_${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
    }
</script>