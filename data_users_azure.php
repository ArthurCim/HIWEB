<?php
// -----------------------------
// Hubungkan ke database (dari db.php)
// -----------------------------
include 'db.php';

// -----------------------------
// Ambil semua data user dari tabel 'users'
// -----------------------------
$sql = "SELECT * FROM users";
$result = $conn->query($sql);

// -----------------------------
// Tampilkan hasilnya dalam tabel HTML
// -----------------------------
echo "<h2>Daftar User dari Database Azure</h2>";

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellspacing='0' cellpadding='8'>";
    echo "<tr>";
    // Ambil nama kolom secara otomatis
    $fields = $result->fetch_fields();
    foreach ($fields as $field) {
        echo "<th>{$field->name}</th>";
    }
    echo "</tr>";

    // Tampilkan semua baris data
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Tidak ada data user.</p>";
}

// Tutup koneksi
$conn->close();
?>
