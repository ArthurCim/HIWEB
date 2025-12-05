<?php

/**
 * Sync premium status - Check if user has active subscription and update is_premium flag
 * Called after payment to ensure immediate update
 */

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

include __DIR__ . '/db.php';

$id_user = $_SESSION['id_user'];

try {
    // Check if user has active subscription
    $check_sub = $conn->prepare(
        "SELECT COUNT(*) as count FROM user_subscriptions 
         WHERE id_user = ? AND payment_status = 'PAID' AND end_date > NOW()"
    );
    $check_sub->bind_param("s", $id_user);
    $check_sub->execute();
    $check_result = $check_sub->get_result()->fetch_assoc();
    $has_active_sub = $check_result['count'] > 0;
    $check_sub->close();

    // Update is_premium flag based on active subscription
    $target_premium = $has_active_sub ? 1 : 0;
    $update = $conn->prepare("UPDATE users SET is_premium = ? WHERE id_user = ?");
    $update->bind_param("is", $target_premium, $id_user);
    $update->execute();
    $update->close();

    // Get updated user data
    $user_check = $conn->prepare("SELECT id_user, nama, email, is_premium FROM users WHERE id_user = ?");
    $user_check->bind_param("s", $id_user);
    $user_check->execute();
    $user_result = $user_check->get_result()->fetch_assoc();
    $user_check->close();

    echo json_encode([
        'status' => 'success',
        'is_premium' => (int)$user_result['is_premium'],
        'has_active_subscription' => $has_active_sub
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
