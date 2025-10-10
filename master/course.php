<?php
include "../db.php";
$page_title = "Data Course";
$result = mysqli_query($conn, "SELECT * FROM courses ORDER BY id_courses ASC");

// include template
include "../includes/header.php";
include "../includes/navbar.php";
?>
<div class="container-fluid">
    <div class="row">
        <?php include "../includes/sidebar.php"; ?>

        <main class="main col">
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
                            <th>Deskripsi Courses</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<tr>
                                <td>" . $no++ . "</td>
                                <td>" . htmlspecialchars($row['nama_courses']) . "</td>
                                <td>" . htmlspecialchars($row['deskripsi']) . "</td>
                                <td>
                                    <button class='mimo-btn mimo-btn-secondary edit-btn' 
                                        data-id='" . $row['id_courses'] . "' 
                                        data-nama='" . htmlspecialchars($row['nama_courses']) . "' 
                                        data-deskripsi='" . htmlspecialchars($row['deskripsi']) . "'
                                        data-bs-toggle='modal' data-bs-target='#editCourseModal'>Edit</button>
                                    <button class='mimo-btn mimo-btn-danger delete-btn' data-id='" . $row['id_courses'] . "'>Hapus</button>
                                </td>
                            </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<!-- Modal Tambah Courses -->
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

        // Edit: isi data ke modal
        $('#courseTable').on('click', '.edit-btn', function() {
            $('#editCourseId').val($(this).data('id'));
            $('#editNama').val($(this).data('nama'));
            $('#editDeskripsi').val($(this).data('deskripsi'));
        });

        // Tambah course
        $('#addCourseForm').on('submit', function(e) {
            e.preventDefault();
            $.post('proses/course_add.php', $(this).serialize(), function() {
                Swal.fire('Berhasil', 'Course berhasil ditambahkan!', 'success').then(() => location.reload());
            });
        });

        // Edit course
        $('#editCourseForm').on('submit', function(e) {
            e.preventDefault();
            $.post('course_edit.php', $(this).serialize(), function() {
                Swal.fire('Berhasil', 'Course berhasil diperbarui!', 'success').then(() => location.reload());
            });
        });

        // Hapus course
        $('#courseTable').on('click', '.delete-btn', function() {
            var id = $(this).data('id');
            Swal.fire({
                title: 'Yakin ingin menghapus?',
                text: "Course akan dihapus permanen.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post({
                        url: 'proses/course_delete.php',
                        data: {
                            id_courses: id
                        },
                        dataType: 'json',
                        success: function(res) {
                            if (res.status === 'success') {
                                Swal.fire('Berhasil!', 'Course telah dihapus.', 'success')
                                    .then(() => location.reload());
                            } else {
                                Swal.fire('Gagal!', res.message, 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error!', 'Terjadi kesalahan pada server.', 'error');
                        }
                    });


                }
            });
        });

        // Logout
        $('#logoutBtn').on('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Yakin ingin logout?',
                text: "Anda akan keluar dari sesi ini.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, logout!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "login/logout.php";
                }
            });
        });
    });
</script>
</body>

</html>