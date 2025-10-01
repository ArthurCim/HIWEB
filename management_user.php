<?php
include "db.php";
$page_title = "Management User";
$result = mysqli_query($conn, "SELECT * FROM users ORDER BY id_users ASC");

include "includes/header.php";
include "includes/navbar.php";
?>
<div class="container-fluid">
    <div class="row">
        <?php include "includes/sidebar.php"; ?>

        <main class="main col">
            <div class="page-header d-flex justify-content-between align-items-center">
                <h2>Management User</h2>
                <button class="mimo-btn mimo-btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">+ Tambah User</button>
            </div>

            <div class="table-panel">
                <table id="userTable" class="display">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<tr>
                                <td>" . $no++ . "</td>
                                <td>" . htmlspecialchars($row['nama']) . "</td>
                                <td>" . htmlspecialchars($row['email']) . "</td>
                                <td>
                                  <button class='mimo-btn mimo-btn-secondary edit-btn' 
                                    data-id='" . $row['id_users'] . "' 
                                    data-nama='" . htmlspecialchars($row['nama']) . "' 
                                    data-email='" . htmlspecialchars($row['email']) . "' 
                                    data-bs-toggle='modal' data-bs-target='#editUserModal'>Edit</button>
                                  <button class='mimo-btn mimo-btn-danger delete-btn' data-id='" . $row['id_users'] . "'>Hapus</button>
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

    <!-- Modal Tambah User -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="addUserForm" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Nama</label>
                        <input type="text" class="form-control" name="nama" required>
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label>Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label>Konfirmasi Password</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>


    <!-- Modal Edit User -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="editUserForm" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="editUserId">
                    <div class="mb-3">
                        <label>Nama</label>
                        <input type="text" class="form-control" name="nama" id="editNama" required>
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" id="editEmail" required>
                    </div>
                    <div class="mb-3">
                        <label>Password Baru (kosongkan jika tidak diubah)</label>
                        <input type="password" class="form-control" name="password" id="editPassword">
                    </div>
                    <div class="mb-3">
                        <label>Konfirmasi Password Baru</label>
                        <input type="password" class="form-control" name="confirm_password" id="editConfirmPassword">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Update</button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        $(document).ready(function() {
            $('#userTable').DataTable({
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
            $('#userTable').on('click', '.edit-btn', function() {
                $('#editUserId').val($(this).data('id'));
                $('#editNama').val($(this).data('nama'));
                $('#editEmail').val($(this).data('email'));
            });

            // Tambah user
            $('#addUserForm').on('submit', function(e) {
                e.preventDefault();
                $.post('user_add.php', $(this).serialize(), function() {
                    Swal.fire('Berhasil', 'User berhasil ditambahkan!', 'success').then(() => location.reload());
                });
            });

            // Edit user
            $('#editUserForm').on('submit', function(e) {
                e.preventDefault();
                $.post('user_edit.php', $(this).serialize(), function() {
                    Swal.fire('Berhasil', 'User berhasil diperbarui!', 'success').then(() => location.reload());
                });
            });

            // Hapus user
            $('#userTable').on('click', '.delete-btn', function() {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Yakin ingin menghapus?',
                    text: "User akan dihapus permanen.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post('user_delete.php', {
                            id: id
                        }, function() {
                            Swal.fire('Dihapus!', 'User telah dihapus.', 'success').then(() => location.reload());
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