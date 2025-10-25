<?php
use PHPUnit\Framework\TestCase;

class RegisterTest extends TestCase {
    private $conn;

    protected function setUp(): void {
        $this->conn = new mysqli("localhost", "root", "", "mimo");
        if ($this->conn->connect_error) {
            $this->fail("Koneksi database gagal: " . $this->conn->connect_error);
        }

        $this->conn->query("DELETE FROM users");
        $_SESSION = [];
    }

    public function testRegisterSuccess() {
        $_POST = [
            'username' => 'aziz',
            'email' => 'aziz@example.com',
            'password' => '12345',
            'confirm_password' => '12345'
        ];
        $_SERVER["REQUEST_METHOD"] = "POST";

        ob_start();
        include __DIR__ . '/../register_process.php';
        ob_end_clean();

        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $_POST['email']);
        $stmt->execute();
        $result = $stmt->get_result();

        $this->assertEquals(1, $result->num_rows, "User harus tersimpan di database");
    }

    public function testRegisterPasswordMismatch() {
        $_POST = [
            'username' => 'aziz2',
            'email' => 'aziz2@example.com',
            'password' => '12345',
            'confirm_password' => '54321'
        ];
        $_SERVER["REQUEST_METHOD"] = "POST";

        ob_start();
        include __DIR__ . '/../tests/register_process.php';
        ob_end_clean();

        $this->assertEquals("Password tidak sama", $_SESSION['register_error']);
    }
}
