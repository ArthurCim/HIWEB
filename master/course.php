<?php
include "../db.php";
session_start();

if (!isset($_SESSION['login'])) {
    header('Location:../login/login.php');
    exit();
}

$page_title = "Data Course";
$result = mysqli_query($conn, "SELECT * FROM courses ORDER BY id_courses ASC");
$page_css = "../includes/css/course.css";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title; ?></title>
    <link rel="stylesheet" href="<?= $page_css; ?>">

    <!-- Tambahkan CDN jika diperlukan -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</head>

<body>

<?php include "../includes/header.php"; ?>

<div class="container">
    <div class="row">

        <!-- Sidebar -->
        <?php include "../includes/sidebar.php"; ?>

        <!-- Main Content -->
        <main class="main col">
        <main class="main">
            <div class="page-header d-flex justify-content-between align-items-center">
                <h2>Data Course</h2>
                <button class="mimo-btn mimo-btn-primary" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                    + Tambah Course
                </button>
            </div>

            <div class="table-panel">
                <table id="courseTable" class="display">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Courses</th>
                            <th>Deskripsi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($result)) { ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($row['nama_courses']); ?></td>
                                    <td><?= htmlspecialchars($row['deskripsi']); ?></td>
                                    <td>
                                        <button class="mimo-btn mimo-btn-secondary edit-btn"
                                            data-id="<?= $row['id_courses']; ?>"
                                            data-nama="<?= htmlspecialchars($row['nama_courses']); ?>"
                                            data-deskripsi="<?= htmlspecialchars($row['deskripsi']); ?>"
                                            data-bs-toggle="modal" data-bs-target="#editCourseModal">
                                            Edit
                                        </button>

                                        <button class="mimo-btn mimo-btn-danger delete-btn"
                                            data-id="<?= $row['id_courses']; ?>">
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Tambah Course -->
    <div class="modal fade" id="addCourseModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="addCourseForm" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label>Nama Course</label>
                        <input type="text" class="form-control" name="nama_courses" required>
                    </div>
                    <div class="mb-3">
                        <label>Deskripsi</label>
                        <textarea class="form-control" name="deskripsi" required></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Course -->
    <div class="modal fade" id="editCourseModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="editCourseForm" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="id_courses" id="editCourseId">

                    <div class="mb-3">
                        <label>Nama Course</label>
                        <input type="text" class="form-control" name="nama_courses" id="editNama" required>
                    </div>

                    <div class="mb-3">
                        <label>Deskripsi</label>
                        <textarea class="form-control" name="deskripsi" id="editDeskripsi" required></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include "../includes/footer.php"; ?>

<script>
$(document).ready(function() {

    $('#courseTable').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        info: true,
        pageLength: 5,
        language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            paginate: {
                first: "Awal",
                last: "Akhir",
                next: "›",
                previous: "‹"
            }
        },
        dom: '<"top"f>rt<"bottom"p><"clear">'
    });

    // Isi modal edit
    $('#courseTable').on('click', '.edit-btn', function() {
        $('#editCourseId').val($(this).data('id'));
        $('#editNama').val($(this).data('nama'));
        $('#editDeskripsi').val($(this).data('deskripsi'));
    });

    // Tambah Course
    $('#addCourseForm').submit(function(e) {
        e.preventDefault();
        $.post('proses/course_add.php', $(this).serialize(), function(response) {

            if (response.trim() === "success") {
                Swal.fire('Berhasil', 'Course berhasil ditambahkan!', 'success')
                .then(() => location.reload());
            } else {
                Swal.fire('Gagal', response, 'error');
            }
        });
    });

    // Edit Course
    $('#editCourseForm').submit(function(e) {
        e.preventDefault();
        $.post('proses/course_edit.php', $(this).serialize(), function(res) {
            Swal.fire('Berhasil', 'Course diperbarui!', 'success')
                .then(() => location.reload());
        });
    });

    // Hapus Course
    $('#courseTable').on('click', '.delete-btn', function() {
        let id = $(this).data('id');

        Swal.fire({
            title: 'Hapus course?',
            text: "Data akan dihapus permanen.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, hapus'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('proses/course_delete.php', { id_courses: id }, function(response) {
                    let res = JSON.parse(response);

                    if (res.status === 'success') {
                        Swal.fire('Berhasil!', 'Data dihapus', 'success')
                            .then(() => location.reload());
                    } else {
                        Swal.fire('Gagal!', res.message, 'error');
                    }
                });
            }
        });
    });

});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
