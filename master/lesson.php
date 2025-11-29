<?php
include "../db.php";
session_start();
if (!isset($_SESSION['login'])) {
    header('Location:../login/login.php');
    exit();
}

$page_title = "Data Lesson";

// Query data lesson + join course
$result = mysqli_query($conn, "
    SELECT lesson.*, courses.nama_courses 
    FROM lesson 
    LEFT JOIN courses ON lesson.id_courses = courses.id_courses 
    ORDER BY id_lesson ASC
");

// Ambil semua course untuk dropdown (modal tambah)
$courses = mysqli_query($conn, "SELECT * FROM courses ORDER BY nama_courses ASC");

$page_css = "../includes/css/lesson.css";

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title><?= $page_title; ?></title>
    <link rel="stylesheet" href="<?= $page_css; ?>">
</head>
<body>

<?php include "../includes/header.php"; ?>

<div class="container">
    <div class="row">

        <!-- Sidebar -->
        <?php include "../includes/sidebar.php"; ?>

        <!-- Main Content -->
        <main class="main col">
            <div class="page-header d-flex justify-content-between align-items-center">
                <h2>Data Lesson</h2>
                <button class="mimo-btn mimo-btn-primary" data-bs-toggle="modal" data-bs-target="#addLessonModal">
                    + Tambah Lesson
                </button>
            </div>

            <div class="table-panel">
                <table id="lessonTable" class="display">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Lesson</th>
                            <th>Deskripsi</th>
                            <th>Nama Course</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($result)) {
                        ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= htmlspecialchars($row['nama_lesson']); ?></td>
                            <td><?= htmlspecialchars($row['deskripsi']); ?></td>
                            <td><?= htmlspecialchars($row['nama_courses'] ?: '-'); ?></td>
                            <td>
                                <button class="mimo-btn mimo-btn-secondary edit-btn"
                                    data-id="<?= $row['id_lesson']; ?>"
                                    data-nama="<?= htmlspecialchars($row['nama_lesson']); ?>"
                                    data-deskripsi="<?= htmlspecialchars($row['deskripsi']); ?>"
                                    data-course="<?= htmlspecialchars($row['id_courses']); ?>"
                                    data-bs-toggle="modal" data-bs-target="#editLessonModal">
                                    Edit
                                </button>

                                <button class="mimo-btn mimo-btn-danger delete-btn"
                                    data-id="<?= $row['id_lesson']; ?>">
                                    Hapus
                                </button>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<!-- Modal Tambah Lesson -->
<div class="modal fade" id="addLessonModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="addLessonForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Lesson</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label>Nama Lesson</label>
                    <input type="text" class="form-control" name="nama_lesson" required>
                </div>

                <div class="mb-3">
                    <label>Deskripsi</label>
                    <textarea class="form-control" name="deskripsi" required></textarea>
                </div>

                <div class="mb-3">
                    <label>Pilih Course</label>
                    <select name="id_courses" class="form-select" required>
                        <option value="">-- Pilih Course --</option>

                        <?php while ($c = mysqli_fetch_assoc($courses)) { ?>
                            <option value="<?= $c['id_courses']; ?>">
                                <?= htmlspecialchars($c['nama_courses']); ?>
                            </option>
                        <?php } ?>

                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-primary" type="submit">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Lesson -->
<div class="modal fade" id="editLessonModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="editLessonForm" class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Edit Lesson</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <input type="hidden" name="id_lesson" id="editLessonId">

                <div class="mb-3">
                    <label>Nama Lesson</label>
                    <input type="text" class="form-control" name="nama_lesson" id="editNama" required>
                </div>

                <div class="mb-3">
                    <label>Deskripsi</label>
                    <textarea class="form-control" name="deskripsi" id="editDeskripsi" required></textarea>
                </div>

                <div class="mb-3">
                    <label>Pilih Course</label>
                    <select name="id_courses" id="editCourse" class="form-select" required>
                        <option value="">-- Pilih Course --</option>
                        <?php
                        $courses2 = mysqli_query($conn, "SELECT * FROM courses ORDER BY nama_courses ASC");
                        while ($c2 = mysqli_fetch_assoc($courses2)) {
                            echo "<option value='{$c2['id_courses']}'>" . htmlspecialchars($c2['nama_courses']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-success" type="submit">Update</button>
            </div>

        </form>
    </div>
</div>

<?php include "../includes/footer.php"; ?>

<script>
$(document).ready(function () {

    /* Datatable */
    $('#lessonTable').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        info: true,
        pageLength: 5,
        language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            paginate: { first: "Awal", last: "Akhir", next: "›", previous: "‹" }
        },
        dom: '<"top"f>rt<"bottom"p><"clear">'
    });

    /* Isi modal edit */
    $('#lessonTable').on('click', '.edit-btn', function () {
        $('#editLessonId').val($(this).data('id'));
        $('#editNama').val($(this).data('nama'));
        $('#editDeskripsi').val($(this).data('deskripsi'));
        $('#editCourse').val($(this).data('course'));
    });

    /* Tambah lesson */
    $('#addLessonForm').on('submit', function (e) {
        e.preventDefault();
        $.post('proses/lesson_add.php', $(this).serialize(), function () {
            Swal.fire("Berhasil", "Lesson berhasil ditambahkan!", "success")
                .then(() => location.reload());
        });
    });

    /* Edit lesson */
    $('#editLessonForm').on('submit', function (e) {
        e.preventDefault();
        $.post('proses/lesson_edit.php', $(this).serialize(), function () {
            Swal.fire("Berhasil", "Lesson berhasil diperbarui!", "success")
                .then(() => location.reload());
        });
    });

    /* Hapus lesson */
    $('#lessonTable').on('click', '.delete-btn', function () {
        let id = $(this).data('id');

        Swal.fire({
            title: 'Yakin ingin menghapus?',
            text: "Lesson akan dihapus permanen.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then(result => {
            if (result.isConfirmed) {
                $.post('proses/lesson_delete.php', { id_lesson: id }, function () {
                    Swal.fire("Dihapus!", "Lesson telah dihapus.", "success")
                        .then(() => location.reload());
                });
            }
        });
    });

});
</script>

</body>
</html>
