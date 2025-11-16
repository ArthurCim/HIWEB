<?php
// user_progress_monitor.php
include "../db.php";
session_start();
if (!isset($_SESSION['login'])) {
    header('Location:../login/login.php');
    exit();
}
$page_title = "User Progress Monitor";

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

include "../includes/header.php";
include "../includes/navbar.php";
?>

<style>
    :root {
        --primary: #4e73df;
        --success: #1cc88a;
        --warning: #f6c23e;
        --danger: #e74a3b;
        --info: #36b9cc;
    }

    .stats-card {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        transition: transform 0.2s;
    }

    .stats-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
    }

    .stats-card .icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-bottom: 12px;
    }

    .stats-card.users .icon {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .stats-card.avg-progress .icon {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }


    .stats-card.completed .icon {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .stats-card.active .icon {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    }

    .stats-card .value {
        font-size: 32px;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 4px;
    }

    .stats-card .unit {
        font-size: 18px;
        vertical-align: super;
        margin-left: 2px;
        color: #2d3748;
    }


    .stats-card .label {
        color: #718096;
        font-size: 14px;
        font-weight: 500;
    }


    .progress-table {
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        position: relative;
    }

    /* --- Scrollable body --- */
    .progress-table-wrapper {
        max-height: 500px;
        /* tinggi maksimal area scroll */
        overflow-y: auto;
        overflow-x: hidden;
        display: block;
    }

    /* --- Fix header tetap di atas --- */
    .progress-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
    }

    /* --- Table styling --- */
    .progress-table table {
        width: 100%;
        border-collapse: collapse;
    }

    .progress-table th {
        padding: 16px;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 13px;
        letter-spacing: 0.5px;
    }

    .progress-table td {
        padding: 14px 16px;
        vertical-align: middle;
        border-bottom: 1px solid #e2e8f0;
    }

    .progress-table tbody tr:hover {
        background: #f7fafc;
    }

    /* --- Optional: biar tampilan scrollbar lebih halus --- */
    .progress-table-wrapper::-webkit-scrollbar {
        width: 8px;
    }

    .progress-table-wrapper::-webkit-scrollbar-thumb {
        background: #c3c7d0;
        border-radius: 4px;
    }

    .progress-table-wrapper::-webkit-scrollbar-thumb:hover {
        background: #a0a5b3;
    }


    .user-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 600;
        font-size: 16px;
    }

    .user-details h6 {
        margin: 0;
        font-size: 14px;
        font-weight: 600;
        color: #2d3748;
    }

    .user-details small {
        color: #718096;
        font-size: 12px;
    }

    .progress-bar-wrapper {
        width: 100%;
        height: 8px;
        background: #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
        position: relative;
    }

    .progress-bar-fill {
        height: 100%;
        border-radius: 10px;
        transition: width 0.3s ease;
    }

    .progress-bar-fill.low {
        background: linear-gradient(90deg, #f093fb 0%, #f5576c 100%);
    }

    .progress-bar-fill.medium {
        background: linear-gradient(90deg, #ffecd2 0%, #fcb69f 100%);
    }

    .progress-bar-fill.high {
        background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
    }

    .progress-bar-fill.complete {
        background: linear-gradient(90deg, #43e97b 0%, #38f9d7 100%);
    }

    .progress-text {
        font-size: 13px;
        font-weight: 600;
        color: #2d3748;
        margin-top: 4px;
    }

    .badge-status {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }

    .badge-status.completed {
        background: #d4edda;
        color: #155724;
    }

    .badge-status.in-progress {
        background: #fff3cd;
        color: #856404;
    }

    .badge-status.not-started {
        background: #f8d7da;
        color: #721c24;
    }

    .filter-section {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 24px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    }

    .filter-section .form-control,
    .filter-section .form-select {
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 14px;
    }

    .filter-section .form-control:focus,
    .filter-section .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .btn-detail {
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        border: none;
        cursor: pointer;
        transition: transform 0.2s;
    }

    .btn-detail:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #718096;
    }

    .empty-state i {
        font-size: 64px;
        margin-bottom: 16px;
        opacity: 0.5;
    }

    .lesson-badge {
        background: #e2e8f0;
        color: #2d3748;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }

    .export-btn {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        color: #fff;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.2s;
    }

    .export-btn:hover {
        transform: translateY(-2px);
    }
</style>

<div class="container-fluid">
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