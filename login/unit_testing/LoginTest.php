<?php
use PHPUnit\Framework\TestCase;

class LoginTest extends TestCase {
    private $conn;

    protected function setUp(): void {
        $this->conn = new mysqli("localhost", "root", "", "mimo");
        if ($this->conn->connect_error) {
            $this->fail("Koneksi database gagal: " . $this->conn->connect_error);
        }

        $this->conn->query("DELETE FROM users");
        $_SESSION = [];
    }

    public function testLoginSuccess() {
        $hash = password_hash("12345", PASSWORD_DEFAULT);
        $this->conn->query("INSERT INTO users (id_user, nama, email, PASSWORD) VALUES ('users_001', 'aziz', 'aziz@example.com', '$hash')");

        $_POST = [
            'email' => 'aziz@example.com',
            'password' => '12345'
        ];
        $_SERVER["REQUEST_METHOD"] = "POST";

        ob_start();
        include __DIR__ . '/../login_process.php';
        ob_end_clean();

        $this->assertTrue($_SESSION['login'], "User harus berhasil login");
        $this->assertEquals('aziz@example.com', $_SESSION['user_email']);
    }

    public function testLoginWrongPassword() {
        $hash = password_hash("12345", PASSWORD_DEFAULT);
        $this->conn->query("INSERT INTO users (id_user, nama, email, PASSWORD) VALUES ('users_002', 'aziz', 'aziz@example.com', '$hash')");

        $_POST = [
            'email' => 'aziz@example.com',
            'password' => 'salah'
        ];
        $_SERVER["REQUEST_METHOD"] = "POST";

        ob_start();
        include __DIR__ . '/../tests/login_process.php';
        ob_end_clean();

        $this->assertEquals("Email atau password salah!", $_SESSION['login_error']);
    }
}
