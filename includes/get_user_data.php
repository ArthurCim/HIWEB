<?php

/**
 * Script untuk mengambil data user yang login
 */

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: login/login.php");
    exit();
}

$id_user = $_SESSION['id_user'];
$nama_user = $_SESSION['nama'];

// Ambil data user lengkap dari database
$user_query = $conn->prepare("SELECT id_user, nama, email, is_premium FROM users WHERE id_user = ?");
$user_query->bind_param("s", $id_user);
$user_query->execute();
$user_result = $user_query->get_result();

$user_is_premium_flag = false;
if ($user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
    $user_is_premium_flag = (int)$user['is_premium'];
} else {
    $user = ['id_user' => $id_user, 'nama' => $nama_user, 'email' => '', 'is_premium' => 0];
}

$user_query->close();

// Ambil data subscription user - Check premium status berdasarkan payment_status = 'PAID' dan end_date > NOW()
$subscription_query = $conn->prepare(
    "SELECT us.id_subscription, us.start_date, us.end_date, us.payment_status 
     FROM user_subscriptions us 
     WHERE us.id_user = ? AND us.payment_status = 'PAID' AND us.end_date > NOW()
     ORDER BY us.end_date DESC LIMIT 1"
);
$subscription_query->bind_param("s", $id_user);
$subscription_query->execute();
$subscription_result = $subscription_query->get_result();

$subscription = null;
$is_premium = false;
$premium_expire_date = null;

if ($subscription_result->num_rows > 0) {
    $subscription = $subscription_result->fetch_assoc();
    $is_premium = true;
    $premium_expire_date = $subscription['end_date'];
} elseif ($user_is_premium_flag) {
    // Jika users.is_premium = 1 tapi subscription tidak valid, coba ambil data subscription terakhir untuk end_date
    $sub_fallback = $conn->prepare(
        "SELECT us.end_date FROM user_subscriptions us 
         WHERE us.id_user = ? AND us.payment_status = 'PAID'
         ORDER BY us.end_date DESC LIMIT 1"
    );
    $sub_fallback->bind_param("s", $id_user);
    $sub_fallback->execute();
    $sub_fallback_result = $sub_fallback->get_result();

    if ($sub_fallback_result->num_rows > 0) {
        $sub_data = $sub_fallback_result->fetch_assoc();
        $is_premium = true;
        $premium_expire_date = $sub_data['end_date'];
    } else {
        // is_premium = 1 tapi tidak ada subscription sama sekali (shouldn't happen)
        $is_premium = true;
        $premium_expire_date = date('Y-m-d', strtotime('+30 days')); // default 30 hari
    }
    $sub_fallback->close();
}

$subscription_query->close();

// Ambil statistik user
$stats_query = $conn->prepare(
    "SELECT 
        (SELECT COUNT(*) FROM user_stage_progress WHERE id_user = ?) as total_stage,
        (SELECT COUNT(DISTINCT s.id_lesson) FROM user_stage_progress usp JOIN stage s ON usp.id_stage = s.id_stage WHERE usp.id_user = ?) as total_lesson,
        (SELECT COUNT(DISTINCT l.id_courses) FROM user_stage_progress usp JOIN stage s ON usp.id_stage = s.id_stage JOIN lesson l ON s.id_lesson = l.id_lesson WHERE usp.id_user = ?) as total_course,
        (SELECT COUNT(*) FROM user_stage_progress WHERE id_user = ? AND status = 'selesai') as completed_stage,
        (SELECT DATEDIFF(NOW(), created_at) FROM users WHERE id_user = ?) as days_learning
     FROM users WHERE id_user = ?"
);
$stats_query->bind_param("ssssss", $id_user, $id_user, $id_user, $id_user, $id_user, $id_user);
$stats_query->execute();
$stats_result = $stats_query->get_result();

$stats = [
    'total_stage' => 0,
    'total_lesson' => 0,
    'total_course' => 0,
    'completed_stage' => 0,
    'days_learning' => 0
];

if ($stats_result->num_rows > 0) {
    $stats_data = $stats_result->fetch_assoc();
    // Handle NULL values
    $stats['total_stage'] = $stats_data['total_stage'] ?? 0;
    $stats['total_lesson'] = $stats_data['total_lesson'] ?? 0;
    $stats['total_course'] = $stats_data['total_course'] ?? 0;
    $stats['completed_stage'] = $stats_data['completed_stage'] ?? 0;
    $stats['days_learning'] = $stats_data['days_learning'] ?? 0;
}

$stats_query->close();

// Hitung progress percentage
$progress_percentage = ($stats['total_stage'] > 0)
    ? round(($stats['completed_stage'] / $stats['total_stage']) * 100)
    : 0;

// Ambil aktivitas terbaru
$activity_query = $conn->prepare(
    "SELECT usp.id_stage, s.nama_stage, usp.status, usp.completion_date 
     FROM user_stage_progress usp 
     JOIN stage s ON usp.id_stage = s.id_stage 
     WHERE usp.id_user = ? 
     ORDER BY usp.completion_date DESC LIMIT 3"
);
$activity_query->bind_param("s", $id_user);
$activity_query->execute();
$activity_result = $activity_query->get_result();

$recent_activities = [];
while ($row = $activity_result->fetch_assoc()) {
    $recent_activities[] = $row;
}

$activity_query->close();

// Ambil data course terakhir
$last_course_query = $conn->prepare(
    "SELECT l.id_courses, c.nama_courses 
     FROM user_stage_progress usp 
     JOIN stage s ON usp.id_stage = s.id_stage 
     JOIN lesson l ON s.id_lesson = l.id_lesson 
     JOIN courses c ON l.id_courses = c.id_courses 
     WHERE usp.id_user = ? 
     ORDER BY usp.completion_date DESC LIMIT 1"
);
$last_course_query->bind_param("s", $id_user);
$last_course_query->execute();
$last_course_result = $last_course_query->get_result();

$last_course = '';
if ($last_course_result->num_rows > 0) {
    $last_course_data = $last_course_result->fetch_assoc();
    $last_course = $last_course_data['nama_courses'];
}

$last_course_query->close();

// Ambil list subscription plans (master) untuk ditampilkan di dashboard
$plans_query = $conn->prepare(
    "SELECT id_plan, nama, deskripsi, durasi_bulan, harga FROM subscription_plans ORDER BY durasi_bulan ASC"
);
$plans_query->execute();
$plans_result = $plans_query->get_result();

$subscription_plans = [];
while ($p = $plans_result->fetch_assoc()) {
    $subscription_plans[] = $p;
}

$plans_query->close();
