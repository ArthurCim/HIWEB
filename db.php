<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mimo";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// require __DIR__ . '/vendor/autoload.php';
// use Dotenv\Dotenv;

// // Load .env
// if (file_exists(__DIR__ . '/.env')) {
//     $dotenv = Dotenv::createImmutable(__DIR__);
//     $dotenv->load();
// }

// $servername = $_ENV['DB_HOST'] ?? getenv('DB_HOST');
// $username   = $_ENV['DB_USER'] ?? getenv('DB_USER');
// $password   = $_ENV['DB_PASS'] ?? getenv('DB_PASS');
// $dbname     = $_ENV['DB_NAME'] ?? getenv('DB_NAME');
// $port       = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?? 3306;
// $sslcert    = $_ENV['DB_SSL_CERT'] ?? getenv('DB_SSL_CERT') ?? '';

// $conn = mysqli_init();

// if ($sslcert && file_exists($sslcert)) {
//     // ✅ aktifkan SSL sebelum koneksi
//     mysqli_ssl_set($conn, NULL, NULL, $sslcert, NULL, NULL);
//     $flags = MYSQLI_CLIENT_SSL;
// } else {
//     die(json_encode([
//         "status" => "error",
//         "message" => "File sertifikat SSL tidak ditemukan di path: $sslcert"
//     ]));
// }

// if (!mysqli_real_connect($conn, $servername, $username, $password, $dbname, $port, NULL, $flags)) {
//     die(json_encode([
//         "status" => "error",
//         "message" => "Koneksi SSL gagal: " . mysqli_connect_error()
//     ]));
// }

// echo "✅ Koneksi ke Azure MySQL berhasil (SSL aktif)";
?>


