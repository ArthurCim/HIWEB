<?php
include "../../db.php";

$id = $_POST['id_courses'] ?? '';

if ($id === '') {
    echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM courses WHERE id_courses = ?");
    $stmt->bind_param("s", $id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        // Tangani error foreign key (1451)
        if ($stmt->errno == 1451) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Course tidak bisa dihapus karena masih memiliki data di tabel Lesson yang terkait.'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menghapus data.'
            ]);
        }
    }

    $stmt->close();
} catch (Throwable $e) {
    // Pesan aman untuk user
    echo json_encode([
        'status' => 'error',
        'message' => 'Course tidak bisa dihapus karena masih memiliki data di tabel Lesson yang terkait.'
    ]);
}

$conn->close();
