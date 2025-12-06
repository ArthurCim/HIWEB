<?php
// Clean OTP helper (fixed copy) - used for testing until original is repaired

function create_password_resets_table($conn)
{
    $sql = "CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_user VARCHAR(100) NULL,
        email VARCHAR(255) NOT NULL,
        otp_hash VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        used TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($sql);
}

function generate_otp($length = 6)
{
    $min = (int) pow(10, $length - 1);
    $max = (int) pow(10, $length) - 1;
    return (string) random_int($min, $max);
}

function load_env_vars()
{
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
        if (class_exists('Dotenv\\Dotenv')) {
            try {
                $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
                // Use load() so variables are available to getenv()/putenv
                $dotenv->load();
            } catch (\Exception $e) {
                // ignore
            }
        }
    }

    // If dotenv didn't populate getenv (e.g. configuration), try a simple parser
    $envPath = __DIR__ . '/..' . DIRECTORY_SEPARATOR . '.env';
    if (file_exists($envPath) && !getenv('MAIL_FROM')) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') === false) continue;
            [$k, $v] = array_map('trim', explode('=', $line, 2));
            $v = trim($v, " \"'");
            putenv("$k=$v");
            $_ENV[$k] = $v;
            $_SERVER[$k] = $v;
        }
    }
}

function sendOtpWithSandiApp($email, $otp)
{
    load_env_vars();

    $subject = getenv('MAIL_SUBJECT') ?: 'Kode OTP - Reset Password';
    $body = getenv('MAIL_TEMPLATE') ?: "Kode OTP Anda: %OTP%\nKode berlaku selama 10 menit.";
    $body = str_replace('%OTP%', $otp, $body);

    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            $smtpHost = getenv('MAIL_HOST') ?: 'smtp.gmail.com';
            $smtpPort = getenv('MAIL_PORT') ?: 587;
            $smtpUser = getenv('MAIL_USERNAME') ?: '';
            $smtpPass = getenv('MAIL_PASSWORD') ?: '';
            $smtpSecure = getenv('MAIL_ENCRYPTION') ?: 'tls';
            $from = getenv('MAIL_FROM') ?: ($smtpUser ?: 'no-reply@example.com');
            $fromName = getenv('MAIL_FROM_NAME') ?: 'No Reply';

            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;
            $mail->SMTPSecure = $smtpSecure;
            $mail->Port = (int)$smtpPort;

            $mail->setFrom($from, $fromName);
            $mail->addAddress($email);

            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body = $body;

            $mail->send();
            return true;
        } catch (\Exception $e) {
            error_log('PHPMailer send failed: ' . $e->getMessage());
        }
    }

    $headers = 'From: ' . (getenv('MAIL_FROM') ?: 'no-reply@example.com') . "\r\n" .
        'Reply-To: ' . (getenv('MAIL_FROM') ?: 'no-reply@example.com') . "\r\n" .
        'X-Mailer: PHP/' . phpversion();

    $sent = @mail($email, $subject, $body, $headers);
    if (!$sent) {
        error_log('Fallback mail() failed for OTP to ' . $email);
    }
    return $sent;
}
