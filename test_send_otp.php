<?php
require __DIR__ . '/vendor/autoload.php';
// Use the clean helper for testing
require __DIR__ . '/includes/otp_helper_clean.php';

// Determine recipient from env
$to = getenv('MAIL_FROM') ?: getenv('MAIL_USERNAME') ?: 'you@example.com';
$otp = (string) random_int(100000, 999999);

echo "Attempting to send OTP to: $to\n";
$result = sendOtpWithSandiApp($to, $otp);
echo 'Result: ' . (is_bool($result) ? ($result ? 'true' : 'false') : var_export($result, true)) . "\n";
// Print a short hint about environment
echo "MAIL_HOST=" . (getenv('MAIL_HOST') ?: 'not set') . "\n";
echo "MAIL_USERNAME=" . (getenv('MAIL_USERNAME') ?: 'not set') . "\n";
$pw = getenv('MAIL_PASSWORD') ? '*** set ***' : '*** empty ***';
echo "MAIL_PASSWORD=" . $pw . "\n";
