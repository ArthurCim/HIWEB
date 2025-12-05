<?php

/**
 * Health Check untuk Midtrans Integration
 * Jalankan file ini: http://localhost/HIWEB/midtrans/health_check.php
 */

// Mulai session
session_start();

// Include database
include __DIR__ . '/../db.php';

// Array untuk menyimpan status
$checks = [];

// 1. Check PHP Version
$checks['PHP Version'] = [
    'status' => version_compare(PHP_VERSION, '7.0.0', '>='),
    'message' => 'PHP ' . PHP_VERSION . ' (Minimum: 7.0.0)',
    'required' => true
];

// 2. Check Session
$checks['Session Active'] = [
    'status' => isset($_SESSION),
    'message' => 'Session is ' . (isset($_SESSION) ? 'active' : 'not active'),
    'required' => true
];

// 3. Check Database Connection
$checks['Database Connection'] = [
    'status' => $conn && !$conn->connect_error,
    'message' => $conn->connect_error ? 'Connection failed: ' . $conn->connect_error : 'Connected successfully',
    'required' => true
];

// 4. Check Config File
$config_file = __DIR__ . '/../config/midtrans_config.php';
$checks['Midtrans Config File'] = [
    'status' => file_exists($config_file),
    'message' => file_exists($config_file) ? 'File exists' : 'File not found: ' . $config_file,
    'required' => true
];

// 5. Check Helper File
$helper_file = __DIR__ . '/../includes/midtrans_helper.php';
$checks['Midtrans Helper File'] = [
    'status' => file_exists($helper_file),
    'message' => file_exists($helper_file) ? 'File exists' : 'File not found: ' . $helper_file,
    'required' => true
];

// 6. Check Get User Data File
$get_user_file = __DIR__ . '/../includes/get_user_data.php';
$checks['Get User Data File'] = [
    'status' => file_exists($get_user_file),
    'message' => file_exists($get_user_file) ? 'File exists' : 'File not found: ' . $get_user_file,
    'required' => true
];

// 7. Check if Midtrans class available
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    include_once __DIR__ . '/../vendor/autoload.php';
    $checks['Midtrans Library'] = [
        'status' => class_exists('Midtrans\Snap'),
        'message' => class_exists('Midtrans\Snap') ? 'Midtrans library loaded' : 'Midtrans library not found',
        'required' => true
    ];
} else {
    $checks['Midtrans Library'] = [
        'status' => false,
        'message' => 'Composer autoload not found. Run: composer require midtrans/midtrans-php',
        'required' => true
    ];
}

// 8. Check Required Tables
$required_tables = ['user_subscriptions', 'transactions', 'user_stage_progress'];
foreach ($required_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    $checks["Database Table: $table"] = [
        'status' => $result && $result->num_rows > 0,
        'message' => ($result && $result->num_rows > 0) ? 'Table exists' : 'Table not found',
        'required' => true
    ];
}

// 9. Check Directories
$directories = [
    'config' => __DIR__ . '/../config',
    'includes' => __DIR__ . '/../includes',
    'midtrans' => __DIR__,
    'logs' => __DIR__ . '/../logs'
];

foreach ($directories as $name => $path) {
    $checks["Directory: $name"] = [
        'status' => is_dir($path),
        'message' => is_dir($path) ? 'Directory exists' : 'Directory not found: ' . $path,
        'required' => ($name != 'logs')
    ];
}

// 10. Check File Permissions (for logs)
$logs_dir = __DIR__ . '/../logs';
if (is_dir($logs_dir)) {
    $checks['Logs Directory Writable'] = [
        'status' => is_writable($logs_dir),
        'message' => is_writable($logs_dir) ? 'Directory is writable' : 'Directory is not writable',
        'required' => false
    ];
} else {
    $checks['Logs Directory Writable'] = [
        'status' => false,
        'message' => 'Logs directory does not exist',
        'required' => false
    ];
}

// 11. Check Dashboard File
$dashboard_file = __DIR__ . '/../dashboard_user.php';
$checks['Dashboard File'] = [
    'status' => file_exists($dashboard_file),
    'message' => file_exists($dashboard_file) ? 'File exists' : 'File not found: ' . $dashboard_file,
    'required' => true
];

// 12. Check API Endpoints
$endpoints = [
    'create_payment.php',
    'notification.php',
    'success.php',
    'failed.php'
];

foreach ($endpoints as $endpoint) {
    $path = __DIR__ . '/' . $endpoint;
    $checks["Endpoint: $endpoint"] = [
        'status' => file_exists($path),
        'message' => file_exists($path) ? 'File exists' : 'File not found: ' . $path,
        'required' => true
    ];
}

// Calculate overall status
$all_required_pass = true;
$total_checks = count($checks);
$passed_checks = 0;

foreach ($checks as $check) {
    if ($check['status']) {
        $passed_checks++;
    }
    if ($check['required'] && !$check['status']) {
        $all_required_pass = false;
    }
}

// HTML Output
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Midtrans Health Check</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .status-summary {
            padding: 20px 30px;
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .status-item {
            flex: 1;
            min-width: 150px;
            text-align: center;
        }

        .status-item .number {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }

        .status-item .label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .checks {
            padding: 30px;
        }

        .check-item {
            display: flex;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            background: #f8f9fa;
            border-left: 4px solid #ddd;
        }

        .check-item.pass {
            background: #e8f5e9;
            border-left-color: #4caf50;
        }

        .check-item.fail {
            background: #ffebee;
            border-left-color: #f44336;
        }

        .check-item.warning {
            background: #fff3e0;
            border-left-color: #ff9800;
        }

        .check-icon {
            font-size: 20px;
            margin-right: 15px;
            min-width: 30px;
            text-align: center;
        }

        .check-content {
            flex: 1;
        }

        .check-name {
            font-weight: 600;
            margin-bottom: 3px;
            color: #333;
        }

        .check-message {
            font-size: 12px;
            color: #666;
        }

        .check-required {
            display: inline-block;
            font-size: 10px;
            background: #667eea;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            margin-left: 10px;
        }

        .footer {
            padding: 20px 30px;
            background: #f8f9fa;
            border-top: 1px solid #e0e0e0;
            text-align: center;
            font-size: 12px;
            color: #666;
        }

        .overall-status {
            padding: 20px 30px;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
        }

        .overall-status.ready {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .overall-status.not-ready {
            background: #ffebee;
            color: #c62828;
        }

        @media (max-width: 600px) {
            .status-summary {
                flex-direction: column;
                gap: 10px;
            }

            .status-item {
                min-width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Midtrans Integration Health Check</h1>
            <p><?= date('Y-m-d H:i:s'); ?></p>
        </div>

        <div class="status-summary">
            <div class="status-item">
                <div class="number"><?= $passed_checks; ?>/<?= $total_checks; ?></div>
                <div class="label">Checks Passed</div>
            </div>
            <div class="status-item">
                <div class="number" style="color: <?= $all_required_pass ? '#4caf50' : '#f44336'; ?>;">
                    <?= $all_required_pass ? '✓' : '✗'; ?>
                </div>
                <div class="label">Required Checks</div>
            </div>
            <div class="status-item">
                <div class="number"><?= round(($passed_checks / $total_checks) * 100); ?>%</div>
                <div class="label">Overall Progress</div>
            </div>
        </div>

        <div class="overall-status <?= $all_required_pass ? 'ready' : 'not-ready'; ?>">
            <?= $all_required_pass ? '✓ SISTEM SIAP DIGUNAKAN' : '✗ ADA MASALAH YANG PERLU DIPERBAIKI'; ?>
        </div>

        <div class="checks">
            <?php foreach ($checks as $name => $check): ?>
                <div class="check-item <?= $check['status'] ? 'pass' : ($check['required'] ? 'fail' : 'warning'); ?>">
                    <div class="check-icon">
                        <?php
                        if ($check['status']) {
                            echo '✓';
                        } else {
                            echo '✗';
                        }
                        ?>
                    </div>
                    <div class="check-content">
                        <div class="check-name">
                            <?= $name; ?>
                            <?php if ($check['required']): ?>
                                <span class="check-required">REQUIRED</span>
                            <?php endif; ?>
                        </div>
                        <div class="check-message"><?= $check['message']; ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="footer">
            <p>Jika ada error, silakan baca dokumentasi di MIDTRANS_SETUP.md atau IMPLEMENTATION_SUMMARY.md</p>
            <p style="margin-top: 10px; opacity: 0.7;">Last Check: <?= date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>
</body>

</html>