<?php
// CLI runner to include endpoint from its own directory
$arg = $argv[1] ?? '';
if (!$arg) {
    echo "Usage: php test_run_answer_cli.php <id_answer>\n";
    exit(1);
}
chdir(__DIR__ . '/ajax');
$_GET['id_answer'] = $arg;
// emulate session
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$_SESSION['login'] = true;
ob_start();
include 'get_answer_detail.php';
$out = ob_get_clean();
// print raw JSON
echo $out . "\n";
