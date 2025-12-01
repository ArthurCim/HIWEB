<?php
// stage_answer_review.php
include "../db.php";
session_start();
if (!isset($_SESSION['login'])) {
    header('Location:../login/login.php');
    exit();
}
$page_title = "Stage Answer Review";
$page_css = "stage_answer.css";

// Get filter parameters
$filter_course = $_GET['course'] ?? '';
$filter_lesson = $_GET['lesson'] ?? '';
$filter_stage = $_GET['stage'] ?? '';
$filter_user = $_GET['user'] ?? '';
$filter_correct = $_GET['correct'] ?? '';

// Build WHERE conditions
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

if ($filter_stage) {
    $whereConditions[] = "s.id_stage = ?";
    $params[] = $filter_stage;
    $types .= "s";
}

if ($filter_user) {
    $whereConditions[] = "(u.nama LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%{$filter_user}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

$whereClause = implode(" AND ", $whereConditions);

// Main query - FIXED VERSION
// Relasi: stage_answer -> question -> stage -> lesson -> courses
$sql = "SELECT DISTINCT
            sa.id_answer,
            sa.id_user,
            sa.id_question,
            sa.jawaban_user AS answer,
            sa.is_correct,
            sa.submitted_at,
            u.nama as user_name,
            u.email as user_email,
            s.nama_stage,
            s.id_stage,
            s.type as stage_type,
            l.id_lesson,
            l.nama_lesson,
            c.id_courses,
            c.nama_courses,
            q.content as question_text
        FROM stage_answer sa
        JOIN users u ON sa.id_user = u.id_user
        JOIN question q ON sa.id_question = q.id_question
        JOIN stage s ON s.id_question = q.id_question
        JOIN lesson l ON s.id_lesson = l.id_lesson
        JOIN courses c ON l.id_courses = c.id_courses
        WHERE {$whereClause}";

if ($filter_correct === '1') {
    $sql .= " AND sa.is_correct = 1";
} elseif ($filter_correct === '0') {
    $sql .= " AND sa.is_correct = 0";
}

$sql .= " ORDER BY sa.submitted_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$answers = [];
while ($row = $result->fetch_assoc()) {
    $answers[] = $row;
}
$stmt->close();

// Get courses for filter
$courses = $conn->query("SELECT id_courses, nama_courses FROM courses ORDER BY nama_courses ASC");

// Get lessons for filter
$lessons_query = "SELECT id_lesson, nama_lesson FROM lesson";
if ($filter_course) {
    $lessons_query .= " WHERE id_courses = ?";
    $stmt_lessons = $conn->prepare($lessons_query);
    $stmt_lessons->bind_param("s", $filter_course);
    $stmt_lessons->execute();
    $lessons = $stmt_lessons->get_result();
} else {
    $lessons_query .= " ORDER BY nama_lesson ASC";
    $lessons = $conn->query($lessons_query);
}

// Get stages for filter
$stages_query = "SELECT s.id_stage, s.nama_stage FROM stage s JOIN lesson l ON s.id_lesson = l.id_lesson WHERE s.type = 'quiz'";
if ($filter_lesson) {
    $stages_query .= " AND s.id_lesson = ?";
    $stmt_stages = $conn->prepare($stages_query);
    $stmt_stages->bind_param("s", $filter_lesson);
    $stmt_stages->execute();
    $stages = $stmt_stages->get_result();
} else {
    $stages_query .= " ORDER BY s.nama_stage ASC";
    $stages = $conn->query($stages_query);
}

// Calculate statistics
$total_answers = count($answers);
$correct_answers = count(array_filter($answers, fn($a) => $a['is_correct'] == 1));
$incorrect_answers = count(array_filter($answers, fn($a) => $a['is_correct'] == 0));
$accuracy = $total_answers > 0 ? ($correct_answers / $total_answers) * 100 : 0;
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

            <!-- Sidebar -->
            <?php include "../includes/sidebar.php"; ?>

            <!-- Main Content -->
            <main class="main col">

                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-2">✍️ Stage Answer Review</h2>
                        <p class="text-muted mb-0">Review semua jawaban quiz dari users</p>
                    </div>

                    <button class="export-btn" onclick="exportAnswers()">
                        📥 Export Answers
                    </button>
                </div>

                <!-- Statistics Section -->
                <div class="row mb-4">

                    <div class="col-md-3 mb-3">
                        <div class="stats-card total">
                            <div class="stats-icon">📝</div>
                            <div class="stats-value"><?= $total_answers ?></div>
                            <div class="stats-label">Total Answers</div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="stats-card correct">
                            <div class="stats-icon">✅</div>
                            <div class="stats-value"><?= $correct_answers ?></div>
                            <div class="stats-label">Correct</div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="stats-card incorrect">
                            <div class="stats-icon">❌</div>
                            <div class="stats-value"><?= $incorrect_answers ?></div>
                            <div class="stats-label">Incorrect</div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="stats-card accuracy">
                            <div class="stats-icon">🎯</div>
                            <div class="stats-value"><?= number_format($accuracy, 1) ?>%</div>
                            <div class="stats-label">Accuracy</div>
                        </div>
                    </div>

                </div>

                <!-- Filters -->
                <div class="filter-card">
                    <form method="GET">
                        <div class="filter-row-top">
                            <div class="filter-item">
                                <label class="form-label fw-bold">Course</label>
                                <select name="course" class="form-select">
                                    <option>All Courses</option>
                                    <?php mysqli_data_seek($courses, 0);
                                    while ($course = $courses->fetch_assoc()): ?>
                                        <option value="<?= $course['id_courses'] ?>"
                                            <?= $filter_course == $course['id_courses'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($course['nama_courses']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="filter-item">
                                <label class="form-label fw-bold">Lesson</label>
                                <select name="lesson" class="form-select">
                                    <option>All Lessons</option>
                                    <?php if (isset($stages)): mysqli_data_seek($stages, 0);
                                        while ($stage = $stages->fetch_assoc()): ?>
                                            <option value="<?= $stage['id_stage'] ?>"
                                                <?= $filter_stage == $stage['id_stage'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($stage['nama_stage']) ?>
                                            </option>
                                    <?php endwhile;
                                    endif; ?>
                                </select>
                            </div>

                            <div class="filter-item">
                                <label class="form-label fw-bold">Stage</label>
                                <select name="stage" class="form-select">
                                    <option>All Stages</option>
                                    <?php if (isset($stages)): mysqli_data_seek($stages, 0);
                                        while ($stage = $stages->fetch_assoc()): ?>
                                            <option value="<?= $stage['id_stage'] ?>"
                                                <?= $filter_stage == $stage['id_stage'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($stage['nama_stage']) ?>
                                            </option>
                                    <?php endwhile;
                                    endif; ?>
                                </select>
                            </div>

                            <div class="filter-item">
                                <label class="form-label fw-bold">Correctness</label>
                                <select name="correct" class="form-select">
                                    <option>All</option>
                                    <option value="1" <?= $filter_correct === '1' ? 'selected' : '' ?>>Correct Only</option>
                                    <option value="0" <?= $filter_correct === '0' ? 'selected' : '' ?>>Incorrect Only</option>
                                </select>
                            </div>
                        </div>
                        <div class="filter-row-bottom">
                            <input type="text" name="user" class="form-control search-input"
                                placeholder="Search user by name or email...">

                            <button type="submit" class="btn btn-primary filter-btn">
                                🔍 Filter
                            </button>
                        </div>

                    </form>
                </div>




                <!-- Answers List -->
                <div class="answers-container">

                    <?php if (empty($answers)): ?>

                        <div class="empty-state">
                            <i class="fas fa-clipboard-list"></i>
                            <h4>No Answers Found</h4>
                            <p>Belum ada jawaban quiz atau tidak ada yg sesuai filter</p>
                        </div>

                    <?php else: ?>

                        <?php foreach ($answers as $answer):
                            $isCorrect = $answer['is_correct'] == 1;
                            $statusClass = $isCorrect ? 'correct' : 'incorrect';
                            $statusIcon = $isCorrect ? '✅' : '❌';
                            $statusText = $isCorrect ? 'Correct' : 'Incorrect';
                            $initials = strtoupper(substr($answer['user_name'], 0, 2));
                        ?>

                            <div class="answer-card <?= $statusClass ?>">

                                <div class="answer-header">
                                    <div class="user-info-card">
                                        <div class="user-avatar-large"><?= $initials ?></div>
                                        <div class="user-details-card">
                                            <h5><?= htmlspecialchars($answer['user_name']) ?></h5>
                                            <small><?= htmlspecialchars($answer['user_email']) ?></small>
                                        </div>
                                    </div>

                                    <div class="answer-status">
                                        <span class="status-badge <?= $statusClass ?>">
                                            <?= $statusIcon ?> <?= $statusText ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="question-section">
                                    <div class="question-label">❓ Question</div>
                                    <div class="question-text">
                                        <?= nl2br(htmlspecialchars($answer['question_text'])) ?>
                                    </div>
                                </div>

                                <div class="answer-section <?= $statusClass ?>">
                                    <div class="answer-label">💡 User Answer</div>
                                    <div class="answer-text">
                                        <?= nl2br(htmlspecialchars($answer['answer'])) ?>
                                    </div>
                                </div>

                                <div class="meta-info">

                                    <div class="meta-item">
                                        <i class="fas fa-book"></i>
                                        <span class="course-badge">
                                            <?= htmlspecialchars($answer['nama_courses']) ?>
                                        </span>
                                    </div>

                                    <div class="meta-item">
                                        <i class="fas fa-bookmark"></i>
                                        <span><?= htmlspecialchars($answer['nama_lesson']) ?></span>
                                    </div>

                                    <div class="meta-item">
                                        <i class="fas fa-layer-group"></i>
                                        <span class="stage-badge">
                                            <?= htmlspecialchars($answer['nama_stage']) ?>
                                        </span>
                                    </div>

                                    <div class="meta-item">
                                        <i class="fas fa-clock"></i>
                                        <span><?= date('d M Y, H:i', strtotime($answer['submitted_at'])) ?></span>
                                    </div>

                                    <div class="meta-item">
                                        <button class="btn-view-detail"
                                            onclick="viewAnswerDetail('<?= $answer['id_answer'] ?>')">
                                            View Detail
                                        </button>
                                    </div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </main>

        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" id="answerDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">

            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Answer Detail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body" id="answerDetailContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary"></div>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <!-- Footer -->
    <?php include "../includes/footer.php"; ?>

</body>

</html>


<script>
    // Dynamic filters
    $('#filterCourse').on('change', function() {
        const courseId = $(this).val();

        // Update lessons
        $.get('../ajax/get_lessons.php', {
            id_courses: courseId
        }, function(data) {
            let html = '<option value="">All Lessons</option>';
            data.forEach(lesson => {
                html += `<option value="${lesson.id_lesson}">${lesson.nama_lesson}</option>`;
            });
            $('#filterLesson').html(html);
        });

        // Clear stages
        $('#filterStage').html('<option value="">All Stages</option>');
    });

    $('#filterLesson').on('change', function() {
        const lessonId = $(this).val();

        if (!lessonId) {
            $('#filterStage').html('<option value="">All Stages</option>');
            return;
        }

        $.get('../ajax/get_stages.php', {
            id_lesson: lessonId,
            type: 'quiz'
        }, function(data) {
            let html = '<option value="">All Stages</option>';
            data.forEach(stage => {
                html += `<option value="${stage.id_stage}">${stage.nama_stage}</option>`;
            });
            $('#filterStage').html(html);
        });
    });

    // View answer detail with correct answer
    function viewAnswerDetail(answerId) {
        $('#answerDetailModal').modal('show');
        $('#answerDetailContent').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');

        $.get('../ajax/get_answer_detail.php', {
            id_answer: answerId
        }, function(response) {
            if (response.status === 'success') {
                renderAnswerDetail(response.data);
            } else {
                $('#answerDetailContent').html(`<div class="alert alert-danger">${response.message}</div>`);
            }
        }).fail(function() {
            $('#answerDetailContent').html('<div class="alert alert-danger">Failed to load data</div>');
        });
    }

    function renderAnswerDetail(data) {
        const isCorrect = data.is_correct == 1;
        const statusBadge = isCorrect ?
            '<span class="badge bg-success fs-5">✅ Correct Answer</span>' :
            '<span class="badge bg-danger fs-5">❌ Incorrect Answer</span>';

        let html = `
        <div class="text-center mb-4">
            ${statusBadge}
        </div>
        
        <div class="mb-4">
            <h6 class="text-muted mb-2">User Information</h6>
            <h5>${data.user_name}</h5>
            <p class="text-muted mb-0">${data.user_email}</p>
        </div>
        
        <div class="mb-4">
            <h6 class="text-muted mb-2">Question</h6>
            <div class="p-3 bg-light rounded">
                ${data.question_text}
            </div>
        </div>
        
        <div class="mb-4">
            <h6 class="text-muted mb-2">User's Answer</h6>
            <div class="p-3 ${isCorrect ? 'bg-success' : 'bg-danger'} bg-opacity-10 rounded">
                <strong>${data.user_answer}</strong>
            </div>
        </div>`;

        if (data.correct_answer) {
            html += `
        <div class="mb-4">
            <h6 class="text-muted mb-2">✅ Correct Answer</h6>
            <div class="p-3 bg-success bg-opacity-10 rounded">
                <strong>${data.correct_answer}</strong>
            </div>
        </div>`;
        }

        if (data.all_options && data.all_options.length > 0) {
            html += `
        <div class="mb-4">
            <h6 class="text-muted mb-2">All Options</h6>
            <div class="list-group">`;

            data.all_options.forEach(opt => {
                const isUserAnswer = opt.option_text === data.user_answer;
                const isCorrectOption = opt.is_correct == 1;

                let badgeClass = '';
                let badge = '';

                if (isCorrectOption) {
                    badgeClass = 'list-group-item-success';
                    badge = '<span class="badge bg-success">✓ Correct</span>';
                }

                if (isUserAnswer && !isCorrectOption) {
                    badgeClass = 'list-group-item-danger';
                    badge = '<span class="badge bg-danger">User chose this</span>';
                }

                html += `
                <div class="list-group-item ${badgeClass}">
                    ${opt.option_text} ${badge}
                </div>`;
            });

            html += `
            </div>
        </div>`;
        }

        html += `
        <div class="text-muted small">
            <i class="fas fa-clock"></i> Submitted at: ${new Date(data.submitted_at).toLocaleString()}
        </div>`;

        $('#answerDetailContent').html(html);
    }

    // Export function
    function exportAnswers() {
        const rows = [];
        rows.push(['User', 'Email', 'Course', 'Lesson', 'Stage', 'Question', 'Answer', 'Correct', 'Submitted At']);

        $('.answer-card').each(function() {
            const row = [];
            row.push($(this).find('.user-details-card h5').text());
            row.push($(this).find('.user-details-card small').text());
            row.push($(this).find('.course-badge').text());
            row.push($(this).find('.meta-item:eq(1) span').text());
            row.push($(this).find('.stage-badge').text());
            row.push($(this).find('.question-text').text().trim());
            row.push($(this).find('.answer-text').text().trim());
            row.push($(this).find('.status-badge').text().includes('Correct') ? 'Yes' : 'No');
            row.push($(this).find('.meta-item:has(.fa-clock) span').text());
            rows.push(row);
        });

        const csv = rows.map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
        const blob = new Blob([csv], {
            type: 'text/csv'
        });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `stage_answers_${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
    }
</script>