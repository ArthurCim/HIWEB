<?php
// Test dashboard premium status logic
include "db.php";

// Use first user from database
$user_query = $conn->prepare("SELECT id_user, nama, email, is_premium FROM users LIMIT 1");
$user_query->execute();
$user_result = $user_query->get_result();
$user = $user_result->fetch_assoc();
$user_query->close();

$id_user = $user['id_user'];

echo "=== DASHBOARD PREMIUM STATUS TEST ===\n";
echo "User: " . $user['nama'] . " (" . $id_user . ")\n";
echo "Current is_premium in DB: " . $user['is_premium'] . "\n\n";

// Simulate dashboard logic
echo "1. Check for active subscriptions...\n";
$active_sub = $conn->prepare(
    "SELECT COUNT(*) as count FROM user_subscriptions 
     WHERE id_user = ? AND payment_status = 'PAID' AND end_date > NOW()"
);
$active_sub->bind_param("s", $id_user);
$active_sub->execute();
$active_sub_result = $active_sub->get_result()->fetch_assoc();
$has_active_sub = $active_sub_result['count'] > 0;

echo "   Active subscriptions: " . ($has_active_sub ? "YES" : "NO") . "\n\n";

// Update is_premium based on active subscriptions
echo "2. Updating is_premium flag...\n";
$target_premium = $has_active_sub ? 1 : 0;
echo "   Setting is_premium = $target_premium\n";

$update_flag = $conn->prepare("UPDATE users SET is_premium = ? WHERE id_user = ?");
$update_flag->bind_param("is", $target_premium, $id_user);
$update_result = $update_flag->execute();
$update_flag->close();

if ($update_result) {
    echo "   ✓ Update successful\n\n";
} else {
    echo "   ✗ Update failed\n\n";
}

// Re-fetch user data
echo "3. Re-fetching user data...\n";
$user_refetch = $conn->prepare("SELECT id_user, nama, email, is_premium FROM users WHERE id_user = ?");
$user_refetch->bind_param("s", $id_user);
$user_refetch->execute();
$user_refetch_result = $user_refetch->get_result();
if ($user_refetch_result->num_rows > 0) {
    $user = $user_refetch_result->fetch_assoc();
    echo "   is_premium after refetch: " . $user['is_premium'] . "\n\n";
}
$user_refetch->close();

// Determine is_premium variable
echo "4. Determining is_premium variable...\n";
if ((int)$user['is_premium'] === 1) {
    $is_premium = true;
    echo "   is_premium = TRUE\n";

    // Get subscription end date
    $sub_for_date = $conn->prepare(
        "SELECT us.end_date FROM user_subscriptions us 
         WHERE us.id_user = ? AND us.payment_status = 'PAID' AND us.end_date > NOW()
         ORDER BY us.end_date DESC LIMIT 1"
    );
    $sub_for_date->bind_param("s", $id_user);
    $sub_for_date->execute();
    $sub_for_date_result = $sub_for_date->get_result();
    if ($sub_for_date_result->num_rows > 0) {
        $sub_data = $sub_for_date_result->fetch_assoc();
        $premium_expire_date = $sub_data['end_date'];
        echo "   Premium expires: " . $premium_expire_date . "\n";
    } else {
        echo "   No active subscription found\n";
    }
    $sub_for_date->close();
} else {
    $is_premium = false;
    echo "   is_premium = FALSE\n";
}

echo "\n5. Dashboard display result:\n";
echo "   Badge: " . ($is_premium ? 'Premium' : 'Free') . "\n";
echo "   Status: " . ($is_premium ? 'Aktif' : 'Tidak Aktif') . "\n";
echo "   Button: " . ($is_premium ? 'Manage' : 'Upgrade') . "\n";

$active_sub->close();
