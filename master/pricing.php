<?php
include "../db.php";
session_start();
if (!isset($_SESSION['login'])) {
    header('Location:../login/login.php');
    exit();
}

$page_title = "Subscription Plans";
$result = mysqli_query($conn, "SELECT * FROM subscription_plans ORDER BY durasi_bulan ASC");

include "../includes/headpog.php";
?>
<div class="container-fluid">
    <div class="row">
        <?php include "../includes/sidebar.php"; ?>

        <main class="main col">
            <div class="page-header d-flex justify-content-between align-items-center">
                <h2>Subscription Plans</h2>
                <button class="mimo-btn mimo-btn-primary" data-bs-toggle="modal" data-bs-target="#addPlanModal">
                    + Tambah Plan
                </button>
            </div>

            <div class="table-panel">
                <table id="planTable" class="display">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Plan</th>
                            <th>Deskripsi</th>
                            <th>Durasi (Bulan)</th>
                            <th>Harga</th>
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
                                <td>" . htmlspecialchars($row['deskripsi']) . "</td>
                                <td>" . $row['durasi_bulan'] . "</td>
                                <td>Rp " . number_format($row['harga'], 0, ',', '.') . "</td>
                                <td>
                                    <button class='mimo-btn mimo-btn-secondary edit-btn' 
                                        data-id='" . htmlspecialchars($row['id_plan']) . "' 
                                        data-nama='" . htmlspecialchars($row['nama']) . "' 
                                        data-deskripsi='" . htmlspecialchars($row['deskripsi']) . "'
                                        data-durasi='" . $row['durasi_bulan'] . "'
                                        data-harga='" . $row['harga'] . "'
                                        data-bs-toggle='modal' data-bs-target='#editPlanModal'>Edit</button>
                                    <button class='mimo-btn mimo-btn-danger delete-btn' data-id='" . htmlspecialchars($row['id_plan']) . "'>Hapus</button>
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

<!-- Modal Tambah Plan -->
<div class="modal fade" id="addPlanModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="addPlanForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Subscription Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label>Nama Plan</label>
                    <input type="text" class="form-control" name="nama" placeholder="e.g., 1 Month, 3 Months" required>
                </div>
                <div class="mb-3">
                    <label>Deskripsi</label>
                    <textarea class="form-control" name="deskripsi" placeholder="Deskripsi singkat plan" required></textarea>
                </div>
                <div class="mb-3">
                    <label>Durasi (Bulan)</label>
                    <input type="number" class="form-control" name="durasi_bulan" min="1" placeholder="1, 3, 12, dst" required>
                </div>
                <div class="mb-3">
                    <label>Harga (Rp)</label>
                    <input type="number" class="form-control" name="harga" step="0.01" min="0" placeholder="99000" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Plan -->
<div class="modal fade" id="editPlanModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="editPlanForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Subscription Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_plan" id="editPlanId">
                <div class="mb-3">
                    <label>Nama Plan</label>
                    <input type="text" class="form-control" name="nama" id="editNama" required>
                </div>
                <div class="mb-3">
                    <label>Deskripsi</label>
                    <textarea class="form-control" name="deskripsi" id="editDeskripsi" required></textarea>
                </div>
                <div class="mb-3">
                    <label>Durasi (Bulan)</label>
                    <input type="number" class="form-control" name="durasi_bulan" id="editDurasi" min="1" required>
                </div>
                <div class="mb-3">
                    <label>Harga (Rp)</label>
                    <input type="number" class="form-control" name="harga" id="editHarga" step="0.01" min="0" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

<?php include "../includes/footer.php"; ?>

<script>
    $(function() {
        const baseUrl = '../master/proses/';

        // Add Plan
        $('#addPlanForm').on('submit', function(e) {
            e.preventDefault();
            $.post(baseUrl + 'pricing_add.php', $(this).serialize(), function(res) {
                if (res.status === 'success') {
                    Swal.fire('Sukses', 'Plan berhasil ditambahkan', 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', res.message || 'Gagal menambah plan', 'error');
                }
            }, 'json').fail(function() {
                Swal.fire('Error', 'Gagal menghubungi server', 'error');
            });
        });

        // Edit button - populate modal
        $(document).on('click', '.edit-btn', function() {
            $('#editPlanId').val($(this).data('id'));
            $('#editNama').val($(this).data('nama'));
            $('#editDeskripsi').val($(this).data('deskripsi'));
            $('#editDurasi').val($(this).data('durasi'));
            $('#editHarga').val($(this).data('harga'));
        });

        // Edit Plan
        $('#editPlanForm').on('submit', function(e) {
            e.preventDefault();
            $.post(baseUrl + 'pricing_edit.php', $(this).serialize(), function(res) {
                if (res.status === 'success') {
                    Swal.fire('Sukses', 'Plan berhasil diupdate', 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', res.message || 'Gagal update plan', 'error');
                }
            }, 'json').fail(function() {
                Swal.fire('Error', 'Gagal menghubungi server', 'error');
            });
        });

        // Delete Plan
        $(document).on('click', '.delete-btn', function() {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Yakin ingin menghapus?',
                text: 'Data akan dihapus secara permanen',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post(baseUrl + 'pricing_delete.php', {
                        id_plan: id
                    }, function(res) {
                        if (res.status === 'success') {
                            Swal.fire('Sukses', 'Plan berhasil dihapus', 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Error', res.message || 'Gagal hapus plan', 'error');
                        }
                    }, 'json').fail(function() {
                        Swal.fire('Error', 'Gagal menghubungi server', 'error');
                    });
                }
            });
        });

        // Initialize DataTable
        $('#planTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.5/i18n/id.json'
            }
        });
    });
</script>