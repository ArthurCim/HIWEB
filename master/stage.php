<?php
include "../db.php";
session_start();
if (!isset($_SESSION['login'])) {
    header('Location:../login/login.php');
    exit();
}
$page_title = "Stage Manager";

// ambil courses
$courses_q = $conn->query("SELECT id_courses, nama_courses FROM courses ORDER BY nama_courses ASC");

// ambil semua stage (initial) untuk render grid
$sql = "SELECT s.id_stage, s.nama_stage, s.deskripsi, s.type, l.id_lesson, l.nama_lesson, c.id_courses, c.nama_courses
        FROM stage s
        LEFT JOIN lesson l ON s.id_lesson = l.id_lesson
        LEFT JOIN courses c ON l.id_courses = c.id_courses
        ORDER BY c.nama_courses ASC, l.nama_lesson ASC, s.id_stage ASC";
$stages_q = $conn->query($sql);

include "../includes/header.php";
include "../includes/navbar.php";
?>
<style>
    :root {
        --accent-1: #4e73df;
        --accent-2: #6f42c1;
    }

    .container-actions {
        display: flex;
        gap: 12px;
        align-items: center;
        justify-content: flex-end;
    }

    .stage-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
    }

    @media (max-width:1100px) {
        .stage-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width:800px) {
        .stage-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width:520px) {
        .stage-grid {
            grid-template-columns: 1fr;
        }
    }

    .card-stage {
        position: relative;
        background: #fff;
        padding: 16px;
        border-radius: 10px;
        box-shadow: 0 8px 22px rgba(28, 30, 37, 0.06);
        transition: transform .12s;
    }

    .card-stage:hover {
        transform: translateY(-6px);
    }

    .card-stage .accent {
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 6px;
        border-radius: 10px 0 0 10px;
        background: linear-gradient(180deg, var(--accent-1), var(--accent-2));
    }

    .card-stage .title {
        font-weight: 700;
        font-size: 1.05rem;
        margin-bottom: 6px;
    }

    .card-stage .meta {
        font-size: .9rem;
        color: #556;
        margin-bottom: 8px;
    }

    .card-stage .desc {
        font-size: .9rem;
        color: #666;
        min-height: 46px;
    }

    .card-stage .actions {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
        margin-top: 10px;
    }

    .btn-small {
        padding: 6px 10px;
        border-radius: 6px;
        font-size: .85rem;
        cursor: pointer;
        border: none;
    }

    .btn-edit {
        background: linear-gradient(90deg, var(--accent-1), var(--accent-2));
        color: #fff;
    }

    .btn-delete {
        background: #fff;
        border: 1px solid #f3c6c9;
        color: #842029;
    }

    .badge-type {
        padding: 4px 8px;
        border-radius: 999px;
        font-size: .78rem;
        font-weight: 600;
        color: #fff;
    }

    .badge-materi {
        background: linear-gradient(90deg, var(--accent-1), var(--accent-2));
    }

    .badge-quiz {
        background: #ff7b7b;
    }

    .filter-row {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 16px;
        align-items: center;
    }

    .filter-row .form-control {
        min-width: 200px;
        max-width: 420px;
    }

    .empty-ill {
        text-align: center;
        padding: 28px;
        color: #888;
        border: 2px dashed #eee;
        border-radius: 8px;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <?php include "../includes/sidebar.php"; ?>
        <main class="main col">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h2>Stage Manager</h2>
                    <p class="text-muted">Generate otomatis stage untuk sebuah lesson. Edit tiap stage (nama, deskripsi, type) setelah dibuat.</p>
                </div>
                <div class="container-actions">
                    <button class="mimo-btn mimo-btn-primary" data-bs-toggle="modal" data-bs-target="#generateModal">+ Generate Stage</button>
                </div>
            </div>

            <div class="filter-row mb-3">
                <select id="filterCourse" class="form-control">
                    <option value="">Semua Course</option>
                    <?php if ($courses_q && $courses_q->num_rows) {
                        $courses_q->data_seek(0);
                        while ($c = $courses_q->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($c['id_courses']) . '">' . htmlspecialchars($c['nama_courses']) . '</option>';
                        }
                        $courses_q->data_seek(0);
                    } ?>
                </select>

                <select id="filterLesson" class="form-control">
                    <option value="">Semua Lesson</option>
                </select>

                <input id="searchStage" class="form-control" placeholder="Cari nama atau deskripsi stage..." style="min-width:260px;">
            </div>

            <div id="cardsWrap">
                <?php if ($stages_q && $stages_q->num_rows): ?>
                    <div class="stage-grid" id="stageGrid">
                        <?php while ($s = $stages_q->fetch_assoc()):
                            $escCourse = htmlspecialchars($s['nama_courses']);
                            $escLesson = htmlspecialchars($s['nama_lesson']);
                            $escName = htmlspecialchars($s['nama_stage']);
                            $escDesc = htmlspecialchars($s['deskripsi']);
                            $dataCourse = htmlspecialchars($s['id_courses']);
                            $dataLesson = htmlspecialchars($s['id_lesson']);
                            $idStage = intval($s['id_stage']);
                            $type = $s['type'];
                        ?>
                            <div class="card-stage" data-course="<?= $dataCourse ?>" data-lesson="<?= $dataLesson ?>" data-title="<?= strtolower($escName) ?>" data-desc="<?= strtolower($escDesc) ?>">
                                <div class="accent"></div>
                                <div class="title"><?= $escName ?></div>
                                <div class="meta"><strong><?= $escLesson ?></strong> • <?= $escCourse ?> &nbsp;
                                    <span class="badge-type <?= $type === 'materi' ? 'badge-materi' : 'badge-quiz' ?>"><?= strtoupper($type) ?></span>
                                </div>
                                <div class="desc"><?= nl2br($escDesc ?: '-') ?></div>
                                <div class="actions">
                                    <button class="btn-small btn-edit edit-btn" data-id="<?= $idStage ?>" data-bs-toggle="modal" data-bs-target="#editStageModal">Edit</button>
                                    <button class="btn-small btn-delete delete-btn" data-id="<?= $idStage ?>">Hapus</button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-ill">Belum ada stage. Gunakan tombol <strong>Generate Stage</strong> untuk membuatnya otomatis.</div>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>

<!-- Modal Generate -->
<div class="modal fade" id="generateModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="generateForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Generate Stage Otomatis</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label>Pilih Course</label>
                    <select id="genCourse" name="id_courses" class="form-control" required>
                        <option value="">Pilih Course</option>
                        <?php if ($courses_q && $courses_q->num_rows) {
                            mysqli_data_seek($courses_q, 0);
                            while ($c = $courses_q->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($c['id_courses']) . '">' . htmlspecialchars($c['nama_courses']) . '</option>';
                            }
                            mysqli_data_seek($courses_q, 0);
                        } ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Pilih Lesson</label>
                    <select id="genLesson" name="id_lesson" class="form-control" required>
                        <option value="">Pilih Lesson</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Jumlah Stage</label>
                    <input type="number" name="count" min="1" max="200" class="form-control" required value="5">
                    <div class="form-text">Contoh: 10 → akan membuat Stage 1 sampai Stage 10</div>
                </div>
                <div class="mb-3">
                    <label>Aturan Awal Tipe (opsional)</label>
                    <select name="default_type" class="form-control">
                        <option value="materi" selected>materi</option>
                        <option value="quiz">quiz</option>
                    </select>
                    <div class="form-text">Tipe default untuk semua stage yang di-generate (bisa diubah manual nanti).</div>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Generate</button></div>
        </form>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="editStageModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="editForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Stage</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_stage" id="edit_id_stage">
                <div class="mb-3">
                    <label>Nama Stage</label>
                    <input id="editNama" name="nama_stage" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Deskripsi</label>
                    <textarea id="editDesc" name="deskripsi" class="form-control" rows="4"></textarea>
                </div>
                <div class="mb-3">
                    <label>Tipe</label>
                    <select id="editType" name="type" class="form-control">
                        <option value="materi">materi</option>
                        <option value="quiz">quiz</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-success">Update</button></div>
        </form>
    </div>
</div>

<?php include "../includes/footer.php"; ?>

<script>
    $(function() {
        const getLessonsUrl = "../ajax/get_lessons.php";
        const getStagesUrl = "../ajax/get_stages.php";
        const genUrl = "../ajax/stage_generate.php";
        const getStageUrl = "../ajax/stage_get.php";
        const editUrl = "../ajax/stage_edit.php";
        const deleteUrl = "../ajax/stage_delete.php";

        // populate lesson for generate modal & filters
        $('#genCourse').on('change', function() {
            const id = $(this).val();
            $('#genLesson').html('<option>Loading...</option>');
            if (!id) {
                $('#genLesson').html('<option value="">Pilih Lesson</option>');
                return;
            }
            $.getJSON(getLessonsUrl, {
                id_courses: id
            }, function(res) {
                let html = '<option value="">Pilih Lesson</option>';
                $.each(res, function(i, r) {
                    html += `<option value="${r.id_lesson}">${r.nama_lesson}</option>`;
                });
                $('#genLesson').html(html);
            }).fail(() => $('#genLesson').html('<option value="">Pilih Lesson</option>'));
        });

        // filterCourse -> populate lessons and filter grid
        $('#filterCourse').on('change', function() {
            const id = $(this).val();
            $('#filterLesson').html('<option>Loading...</option>');
            if (!id) {
                $('#filterLesson').html('<option value="">Semua Lesson</option>');
                $('#stageGrid .card-stage').show();
                return;
            }
            $.getJSON(getLessonsUrl, {
                id_courses: id
            }, function(res) {
                let html = '<option value="">Semua Lesson</option>';
                $.each(res, function(i, r) {
                    html += `<option value="${r.id_lesson}">${r.nama_lesson}</option>`;
                });
                $('#filterLesson').html(html);
                filterCards();
            }).fail(() => $('#filterLesson').html('<option value="">Semua Lesson</option>'));
        });

        $('#filterLesson').on('change', filterCards);
        $('#searchStage').on('input', filterCards);

        function filterCards() {
            const course = $('#filterCourse').val();
            const lesson = $('#filterLesson').val();
            const q = $('#searchStage').val().toLowerCase();
            $('#stageGrid .card-stage').each(function() {
                const c = $(this).data('course') + '';
                const l = $(this).data('lesson') + '';
                const t = ($(this).data('title') || '') + '';
                const d = ($(this).data('desc') || '') + '';
                let show = true;
                if (course && course !== c) show = false;
                if (lesson && lesson !== l) show = false;
                if (q && !(t.indexOf(q) !== -1 || d.indexOf(q) !== -1)) show = false;
                $(this).toggle(show);
            });
        }

        // generate form
        $('#generateForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: "../ajax/stage_generate.php",
                method: "POST",
                data: $(this).serialize(),
                dataType: "json",
                timeout: 10000,
                success: function(resp) {
                    if (resp && resp.status === 'success') {
                        Swal.fire('Berhasil', resp.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Gagal', resp.message || 'Unknown error', 'error');
                    }
                },
                error: function(xhr, status, err) {
                    // tampilkan response mentah ke console agar mudah dibaca
                    console.error('AJAX ERROR', status, err);
                    console.log('RAW RESPONSE:', xhr.responseText);
                    Swal.fire('Error', 'Response invalid — cek console (raw response).', 'error');
                }
            });
        });


        // edit button: load stage
        $(document).on('click', '.edit-btn', function() {
            const id = $(this).data('id');
            $.getJSON(getStageUrl, {
                id_stage: id
            }, function(resp) {
                if (!resp || resp.status !== 'success') {
                    Swal.fire('Error', 'Data tidak ditemukan', 'error');
                    return;
                }
                const d = resp.data;
                $('#edit_id_stage').val(d.id_stage);
                $('#editNama').val(d.nama_stage);
                $('#editDesc').val(d.deskripsi);
                $('#editType').val(d.type);
            }).fail(() => Swal.fire('Error', 'Server error', 'error'));
        });

        // submit edit
        $('#editForm').on('submit', function(e) {
            e.preventDefault();
            const data = $(this).serialize();
            $.post(editUrl, data, function(resp) {
                try {
                    resp = JSON.parse(resp);
                } catch (e) {
                    Swal.fire('Error', 'Response invalid', 'error');
                    return;
                }
                if (resp.status === 'success') {
                    Swal.fire('Berhasil', resp.message, 'success').then(() => location.reload());
                } else Swal.fire('Gagal', resp.message, 'error');
            }).fail(() => Swal.fire('Error', 'Server error', 'error'));
        });

        // delete
        $(document).on('click', '.delete-btn', function() {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Yakin hapus?',
                text: 'Stage akan dihapus permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus'
            }).then((r) => {
                if (r.isConfirmed) {
                    $.post(deleteUrl, {
                        id_stage: id
                    }, function(resp) {
                        try {
                            resp = JSON.parse(resp);
                        } catch (e) {
                            Swal.fire('Error', 'Response invalid', 'error');
                            return;
                        }
                        if (resp.status === 'success') {
                            Swal.fire('Berhasil', resp.message, 'success').then(() => location.reload());
                        } else Swal.fire('Gagal', resp.message, 'error');
                    }).fail(() => Swal.fire('Error', 'Server error', 'error'));
                }
            });
        });

    });
</script>