<?php
include "db.php"; // koneksi DB

$result = mysqli_query($conn, "SELECT * FROM courses ORDER BY id_courses ASC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Data Course</title>
    <link rel="stylesheet" href="assets/management_user.css">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --bg: #f5f7fb;
            --panel: #ffffff;
            --accent-1: #4e73df;
            --accent-2: #6f42c1;
            --muted: #6b7280;
            --success: #10b981;
            --danger: #ef4444;
            --radius: 10px;
            --gap: 18px;
            --max-width: 1200px;
            --glass: rgba(255, 255, 255, 0.6);
        }

        * {
            box-sizing: border-box
        }

        html,
        body {
            height: 100%
        }

        body {
            margin: 0;
            font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
            background: var(--bg);
            color: #111827;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            line-height: 1.4;
            padding-top: 72px;
            /* space for fixed navbar */
        }

        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 72px;
            background: linear-gradient(90deg, var(--accent-1), var(--accent-2));
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 1050;
            box-shadow: 0 4px 18px rgba(16, 24, 40, 0.08);
        }

        .navbar .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            font-size: 18px;
        }

        .navbar .brand img {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: var(--glass);
            padding: 4px;
        }

        .navbar .nav-actions {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .navbar a.logout {
            color: #fff;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.12);
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: 600;
        }

        .navbar a.logout:hover {
            opacity: 0.95
        }

        .container-fluid {
            margin-top: 20px;
        }

        .sidebar {
            background: var(--panel);
            border-radius: var(--radius);
            padding: 16px;
            box-shadow: 0 6px 18px rgba(16, 24, 40, 0.04);
            height: fit-content;
        }

        .sidebar h3 {
            margin: 0 0 8px 0;
            font-size: 14px;
            color: var(--muted);
        }

        .nav-list {
            list-style: none;
            padding: 0;
            margin: 12px 0 0 0;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .nav-list a {
            display: flex;
            gap: 10px;
            align-items: center;
            padding: 8px 10px;
            color: #374151;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
        }

        .nav-list a:hover {
            background: #f3f4f6
        }

        .nav-list a.active {
            background: linear-gradient(90deg, var(--accent-1), var(--accent-2));
            color: #fff;
            box-shadow: 0 6px 18px rgba(78, 115, 223, 0.12);
        }

        .main {
            min-height: 60vh;
        }

        .page-header {
            margin-bottom: 16px;
        }

        .table-panel {
            overflow: auto;
            background: var(--panel);
            border-radius: 12px;
            padding: 12px;
            box-shadow: 0 6px 18px rgba(16, 24, 40, 0.04);
        }

        .mimo-btn {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: 0.2s;
            border: none;
        }

        .mimo-btn-primary {
            background: linear-gradient(90deg, var(--accent-1), var(--accent-2));
            color: #fff;
        }

        .mimo-btn-primary:hover {
            opacity: 0.9;
        }

        .mimo-btn-danger {
            background: var(--danger);
            color: #fff;
        }

        .mimo-btn-danger:hover {
            opacity: 0.9;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="brand">
            <img src="assets/logo putih.svg" alt="Logo">
            <h1>MIMO</h1>
        </div>
        <div class="nav-actions">
            <a href="#" id="logoutBtn" class="logout">Logout</a>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <aside class="sidebar col-2">
                <h3>Menu</h3>
                <ul class="nav-list">
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="course.php" class="active">Data Course</a></li>
                    <li><a href="management_user.php">Management User</a></li>
                    <li><a href="#">Pengaturan</a></li>
                </ul>
            </aside>

            <main class="main col">
                <div class="page-header d-flex justify-content-between align-items-center">
                    <h2>Course</h2>
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

    <!-- jQuery + Bootstrap + DataTables -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

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
                        $.post('course_delete.php', {
                            id_courses: id
                        }, function() {
                            Swal.fire('Dihapus!', 'Course telah dihapus.', 'success').then(() => location.reload());
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