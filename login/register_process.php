<?php
header("Content-Type: application/json");
include __DIR__ . '/../db.php';

// Pastikan request adalah POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "status" => "error",
        "message" => "Metode tidak diizinkan. Gunakan POST."
    ]);
    exit();
}

// Ambil input JSON atau form-data
$input = json_decode(file_get_contents("php://input"), true);
$username = trim($input['username'] ?? $_POST['username'] ?? '');
$email = trim($input['email'] ?? $_POST['email'] ?? '');
$password = $input['password'] ?? $_POST['password'] ?? '';
$confirm = $input['confirm_password'] ?? $_POST['confirm_password'] ?? '';

// Validasi input
if ($username === '' || $email === '' || $password === '' || $confirm === '') {
    echo json_encode([
        "status" => "error",
        "message" => "Semua field harus diisi."
    ]);
    exit();
}

if ($password !== $confirm) {
    echo json_encode([
        "status" => "error",
        "message" => "Password tidak sama."
    ]);
    exit();
}

// Cek apakah email sudah terdaftar
$stmt = $conn->prepare("SELECT id_user FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->close();
    echo json_encode([
        "status" => "error",
        "message" => "Email sudah digunakan."
    ]);
    exit();
}
$stmt->close();

// Ambil ID terakhir
$result = $conn->query("SELECT id_user FROM users ORDER BY id_user DESC LIMIT 1");
$lastId = $result->fetch_assoc();

if ($lastId) {
    $num = (int) substr($lastId['id_user'], 6);
    $num++;
    $newId = "users_" . str_pad($num, 3, "0", STR_PAD_LEFT);
} else {
    $newId = "users_001";
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Simpan user baru
$stmt = $conn->prepare("INSERT INTO users (id_user, nama, email, password) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $newId, $username, $email, $hashedPassword);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Registrasi berhasil!",
        "data" => [
            "id_user" => $newId,
            "username" => $username,
            "email" => $email
        ]
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Gagal menyimpan data: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
