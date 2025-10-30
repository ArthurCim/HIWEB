<?php
// stage_manager.php
include "../db.php";
session_start();
if (!isset($_SESSION['login'])) {
    header('Location:../login/login.php');
    exit();
}
$page_title = "Stage Manager";

// ambil courses
$courses_q = $conn->query("SELECT id_courses, nama_courses FROM courses ORDER BY nama_courses ASC");

// ambil all lessons + courses + stage, nanti kita group per lesson
$sql = "SELECT 
            l.id_lesson, l.nama_lesson, 
            c.id_courses, c.nama_courses,
            s.id_stage, s.nama_stage, s.deskripsi, s.type
        FROM lesson l
        LEFT JOIN courses c ON l.id_courses = c.id_courses
        LEFT JOIN stage s ON s.id_lesson = l.id_lesson
        ORDER BY c.nama_courses ASC, l.nama_lesson ASC, 
                CAST(REGEXP_REPLACE(s.nama_stage, '[^0-9]', '') AS UNSIGNED) ASC";
$res = $conn->query($sql);

// build groups
$groups = [];
if ($res && $res->num_rows) {
    while ($r = $res->fetch_assoc()) {
        $lid = $r['id_lesson'];
        if (!isset($groups[$lid])) {
            $groups[$lid] = [
                'id_lesson' => $lid,
                'nama_lesson' => $r['nama_lesson'],
                'id_courses' => $r['id_courses'],
                'nama_courses' => $r['nama_courses'],
                'stages' => []
            ];
        }
        if (!empty($r['id_stage'])) {
            $groups[$lid]['stages'][] = [
                'id_stage' => $r['id_stage'],
                'nama_stage' => $r['nama_stage'],
                'deskripsi' => $r['deskripsi'],
                'type' => $r['type']
            ];
        }
    }
}

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
        transition: transform .12s
    }

    .card-stage:hover {
        transform: translateY(-6px)
    }

    .card-stage .accent {
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 6px;
        border-radius: 10px 0 0 10px;
        background: linear-gradient(180deg, var(--accent-1), var(--accent-2))
    }

    .card-stage .title {
        font-weight: 700;
        font-size: 1.05rem;
        margin-bottom: 6px
    }

    .card-stage .meta {
        font-size: .9rem;
        color: #556;
        margin-bottom: 8px
    }

    .card-stage .desc {
        font-size: .9rem;
        color: #666;
        min-height: 46px
    }

    .card-stage .actions {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
        margin-top: 10px
    }

    .btn-small {
        padding: 6px 10px;
        border-radius: 6px;
        font-size: .85rem;
        cursor: pointer;
        border: none
    }

    .btn-edit {
        background: linear-gradient(90deg, var(--accent-1), var(--accent-2));
        color: #fff
    }

    .btn-delete {
        background: #fff;
        border: 1px solid #f3c6c9;
        color: #842029
    }

    .badge-type {
        padding: 4px 8px;
        border-radius: 999px;
        font-size: .78rem;
        font-weight: 600;
        color: #fff
    }

    .badge-materi {
        background: linear-gradient(90deg, var(--accent-1), var(--accent-2))
    }

    .badge-quiz {
        background: #ff7b7b
    }

    .filter-row {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 16px;
        align-items: center
    }

    .filter-row .form-control {
        min-width: 200px;
        max-width: 420px
    }

    .empty-ill {
        text-align: center;
        padding: 28px;
        color: #888;
        border: 2px dashed #eee;
        border-radius: 8px
    }

    .lesson-block {
        margin-bottom: 18px;
        padding: 12px;
        border-radius: 10px;
        background: #fbfbff
    }
</style>

<div class="container-fluid">
    <div class="row">
        <?php include "../includes/sidebar.php"; ?>
        <main class="main col">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h2>Stage Manager</h2>
                    <p class="text-muted">Stage dibuat & dikelompokkan per <strong>Lesson</strong>. Gunakan Generate untuk membuat stage untuk lesson tertentu.</p>
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
                <?php if (!empty($groups)): ?>
                    <?php foreach ($groups as $lesson): ?>
                        <div class="lesson-block" data-course="<?= htmlspecialchars($lesson['id_courses']) ?>" data-lesson="<?= htmlspecialchars($lesson['id_lesson']) ?>">
                            <div class="mb-2 d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= htmlspecialchars($lesson['nama_lesson']) ?></strong>
                                    <div class="text-muted small"><?= htmlspecialchars($lesson['nama_courses']) ?></div>
                                </div>
                                <div>
                                    <button class="mimo-btn mimo-btn-sm me-2" onclick="reorderStages('<?= htmlspecialchars($lesson['id_lesson']) ?>')">Reorder Stages</button>
                                    <button class="mimo-btn mimo-btn-sm" onclick="openGenerateForLesson('<?= htmlspecialchars($lesson['id_lesson']) ?>')">Generate untuk lesson</button>
                                </div>
                            </div>

                            <?php if (!empty($lesson['stages'])): ?>
                                <div class="stage-grid">
                                    <?php foreach ($lesson['stages'] as $s):
                                        $escName = htmlspecialchars($s['nama_stage']);
                                        $escDesc = htmlspecialchars($s['deskripsi']);
                                        $type = $s['type'];
                                        $idStage = $s['id_stage'];
                                    ?>
                                        <div class="card-stage" data-course="<?= htmlspecialchars($lesson['id_courses']) ?>" data-lesson="<?= htmlspecialchars($lesson['id_lesson']) ?>" data-title="<?= strtolower($escName) ?>" data-desc="<?= strtolower($escDesc) ?>">
                                            <div class="accent"></div>
                                            <div class="title"><?= $escName ?></div>
                                            <div class="meta"><strong><?= htmlspecialchars($lesson['nama_lesson']) ?></strong> • <?= htmlspecialchars($lesson['nama_courses']) ?> &nbsp;
                                                <span class="badge-type <?= $type === 'materi' ? 'badge-materi' : 'badge-quiz' ?>"><?= strtoupper($type) ?></span>
                                            </div>
                                            <div class="desc"><?= nl2br($escDesc ?: '-') ?></div>
                                            <div class="actions">
                                                <!-- Delete button removed as requested -->
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-ill">Belum ada stage untuk lesson ini. Gunakan tombol <strong>Generate</strong>.</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-ill">Belum ada lesson / stage. Buat lesson terlebih dahulu atau gunakan Generate.</div>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>

<!-- Modal Generate Step 1 -->
<div class="modal fade" id="generateModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="generateStep1" class="modal-content" novalidate>
            <div class="modal-header">
                <h5 class="modal-title">Generate Stage — Step 1</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label>Pilih Course (opsional)</label>
                    <select id="genCourse" name="id_courses" class="form-control">
                        <option value="">(Optional) Pilih Course untuk filter lesson</option>
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
                    <select id="genLesson" name="id_lesson" class="form-control">
                        <option value="">Pilih Lesson</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label>Jumlah Stage</label>
                    <input type="number" id="genCount" name="count" min="1" max="200" class="form-control" value="5">
                    <div class="form-text">Contoh: 5 → akan membuat form builder untuk Stage 1 sampai Stage 5</div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary">Lanjutkan ke Builder</button>
            </div>
        </form>
    </div>
</div>


<!-- Modal Builder Step 2 -->
<div class="modal fade" id="builderModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <form id="builderForm" class="modal-content" enctype="multipart/form-data">
            <div class="modal-header">
                <h5 class="modal-title">Stage Builder — Isi detail tiap Stage</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="max-height:70vh; overflow:auto;">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong id="builderInfo"></strong>
                        </div>
                        <div>
                            <button type="button" id="addStageBtn" class="btn btn-sm btn-outline-secondary">Tambah Stage</button>
                            <button type="button" id="resetBuilderBtn" class="btn btn-sm btn-outline-danger">Reset</button>
                        </div>
                    </div>
                </div>
                <div id="builderWrap"></div>
            </div>
            <div class="modal-footer">
                <input type="hidden" name="id_lesson" id="builder_lesson">
                <button class="btn btn-success">Generate & Simpan Semua</button>
            </div>
        </form>
    </div>
</div>


<?php include "../includes/footer.php"; ?>

<script>
    // Global variables
    let builderStages = [];
    let initialStages = [];
    let deletedStages = []; // 🔥 TAMBAHAN: Track stages yang dihapus

    function uid(prefix = 'id') {
        return prefix + '_' + Math.random().toString(36).slice(2, 9);
    }

    function escapeHtml(text) {
        return $('<div/>').text(text || '').html();
    }

    // 🔥 FIX #3: Strip HTML tags dari text
    function stripHtml(html) {
        const temp = document.createElement('div');
        temp.innerHTML = html;
        return temp.textContent || temp.innerText || '';
    }

    function reorderStages(lessonId) {
        if (!lessonId) return;

        Swal.fire({
            title: 'Reorder Stages',
            text: 'This will reorder all stages in this lesson numerically. Continue?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, reorder',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../ajax/reorder_stages.php',
                    type: 'POST',
                    data: {
                        id_lesson: lessonId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire('Success', 'Stages have been reordered successfully', 'success')
                                .then(() => window.location.reload());
                        } else {
                            Swal.fire('Error', response.message || 'Failed to reorder stages', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Failed to reorder stages', 'error');
                    }
                });
            }
        });
    }

    async function openGenerateForLesson(id_lesson) {
        try {
            Swal.fire({
                title: 'Memuat data...',
                text: 'Mengambil data stage dan materi',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            console.log("🔍 Memuat stage untuk lesson:", id_lesson);
            const res = await fetch(`../ajax/get_stage_by_lesson.php?id_lesson=${id_lesson}`);
            const text = await res.text();

            let stages;
            try {
                stages = JSON.parse(text);
                console.log("📦 Loaded stages data:", stages);
            } catch (e) {
                console.error("❌ JSON Parse Error:", e, "Raw text:", text);
                Swal.fire("Error", "Data dari server tidak valid", "error");
                return;
            }

            if (!Array.isArray(stages)) {
                console.error("❌ Data stages bukan array:", stages);
                Swal.fire("Error", "Data stages tidak valid", "error");
                return;
            }

            Swal.close();

            // Validate and mark as existing
            stages = stages.map(stage => ({
                ...stage,
                nama_stage: stage.nama_stage || '',
                deskripsi: stage.deskripsi || '',
                type: stage.type || 'materi',
                details: stage.details || [],
                isExisting: true
            }));

            // 🔥 RESET deletedStages saat buka builder baru
            deletedStages = [];

            showStageFormBuilder(stages, id_lesson);

        } catch (err) {
            console.error("Gagal memuat data stage:", err);
            Swal.fire("Error", "Terjadi kesalahan saat memuat stage!", "error");
        }
    }

    function showStageFormBuilder(stages, id_lesson) {
        $('#builder_lesson').val(id_lesson);

        // Save initial state
        initialStages = JSON.parse(JSON.stringify(stages));
        builderStages = JSON.parse(JSON.stringify(stages));

        console.log('Initial stages loaded:', initialStages.length);

        $('#builderInfo').text(`Lesson ID: ${id_lesson} — ${stages.length} stage(s) existing`);

        renderBuilder();
        $('#builderModal').modal('show');
    }

    function renderBuilder() {
        console.log('Rendering stages:', builderStages.length);

        let html = '';
        builderStages.forEach((s, idx) => {
            // 🎯 AUTO-GENERATE nama stage berdasarkan index
            const autoStageName = `Stage ${idx + 1}`;

            html += `
        <div class="card mb-3 builder-stage" data-idx="${idx}">
            <div class="card-body">
                <input type="hidden" class="stage-id" value="${s.id_stage || ''}">
                <input type="hidden" class="stage-existing" value="${s.isExisting ? '1' : '0'}">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="mb-0">${autoStageName} ${s.isExisting ? '<span class="badge bg-info">Existing</span>' : '<span class="badge bg-success">New</span>'}</h5>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-stage">Hapus Stage</button>
                </div>

                <div class="mb-2">
                    <label>Nama Stage (Otomatis)</label>
                    <input type="text" class="form-control stage-name" value="${escapeHtml(autoStageName)}" disabled readonly style="background-color: #f0f0f0; cursor: not-allowed;">
                    <small class="text-muted">Nama stage dibuat otomatis berdasarkan urutan</small>
                </div>

                <div class="mb-2">
                    <label>Deskripsi</label>
                    <textarea class="form-control stage-desc" rows="2">${escapeHtml(s.deskripsi)}</textarea>
                </div>

                <div class="mb-2">
                    <label>Tipe Stage</label>
                    <select class="form-control stage-type">
                        <option value="materi" ${s.type === 'materi' ? 'selected' : ''}>Materi</option>
                        <option value="quiz" ${s.type === 'quiz' ? 'selected' : ''}>Quiz</option>
                    </select>
                </div>

                <div class="stage-workarea mt-2" id="workarea-${idx}">
                    ${s.type === 'materi' ? renderMateriEditor(idx, s) : renderQuizEditor(idx, s)}
                </div>
            </div>
        </div>`;
        });
        $('#builderWrap').html(html);
    }

    function renderMateriEditor(idx, s) {
        return `
        <div class="materi-blocks" data-idx="${idx}">
            <button type="button" class="btn btn-sm btn-outline-primary mb-2 add-materi" data-idx="${idx}">Tambah sub-materi</button>
            <div class="materi-list" id="materi-list-${idx}">
                ${s.details.length ? s.details.map((d,i)=>materiBlockHtml(idx,i,d)).join('') : materiBlockHtml(idx,0,{id_detail:uid('dt'), judul:'', isi:'', media:''})}
            </div>
        </div>`;
    }

    function materiBlockHtml(stageIdx, detailIdx, d) {
        return `
        <div class="card mb-2 p-2 materi-block" data-stage="${stageIdx}" data-detail="${detailIdx}">
            <div class="d-flex justify-content-between">
                <strong>Sub-materi ${detailIdx+1}</strong>
                <button type="button" class="btn btn-sm btn-outline-danger remove-materi">Hapus</button>
            </div>
            <div class="mb-2">
                <label>Judul (opsional)</label>
                <input type="text" class="form-control" name="stage[${stageIdx}][details][${detailIdx}][judul]" value="${escapeHtml(d.judul||'')}">
            </div>
            <div class="mb-2">
                <label>Isi (HTML / teks)</label>
                <textarea class="form-control materi-isi" name="stage[${stageIdx}][details][${detailIdx}][isi]" rows="3">${escapeHtml(d.isi||'')}</textarea>
            </div>
            <div class="mb-2">
                <label>Gambar (opsional)</label>
                <input type="file" name="stage[${stageIdx}][details][${detailIdx}][media]" class="form-control">
            </div>
        </div>`;
    }

    function renderQuizEditor(idx, s) {
        const d = s.details.length ? s.details[0] : {
            id_detail: uid('dt'),
            isi: '',
            quiz_type: 'pilihan_ganda',
            options: [{
                id: uid('op'),
                text: '',
                is_correct: 0
            }]
        };
        return `
        <div class="quiz-area" data-idx="${idx}">
            <div class="mb-2">
                <label>Pertanyaan</label>
                <textarea class="form-control quiz-isi" name="stage[${idx}][details][0][isi]" rows="3">${escapeHtml(d.isi||'')}</textarea>
            </div>
            <div class="mb-2">
                <label>Tipe Quiz</label>
                <select class="form-control" name="stage[${idx}][details][0][quiz_type]">
                    <option value="pilihan_ganda" ${d.quiz_type === 'pilihan_ganda' ? 'selected' : ''}>Pilihan Ganda</option>
                    <option value="isian" ${d.quiz_type === 'isian' ? 'selected' : ''}>Isian</option>
                </select>
            </div>
            <div class="mb-2 options-wrap">
                <label>Opsi (untuk Pilihan Ganda)</label>
                <div class="options-list">
                    ${(d.options||[]).map((op,i) => quizOptionHtml(idx,0,i,op)).join('')}
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary add-option">Tambah Opsi</button>
            </div>
        </div>`;
    }

    function quizOptionHtml(stageIdx, detailIdx, optIdx, op) {
        return `
        <div class="d-flex mb-1 align-items-center option-item">
            <input type="checkbox" class="me-2 is-correct" name="stage[${stageIdx}][details][${detailIdx}][options][${optIdx}][is_correct]" ${op.is_correct ? 'checked' : ''}/>
            <input class="form-control me-2" name="stage[${stageIdx}][details][${detailIdx}][options][${optIdx}][text]" value="${escapeHtml(op.text||'')}" placeholder="Teks opsi">
            <button type="button" class="btn btn-sm btn-outline-danger remove-option">Hapus</button>
        </div>`;
    }

    $(function() {
        const getLessonsUrl = "../ajax/get_lessons.php";

        // Load lessons
        function renderGenLessons(res) {
            let html = '<option value="">Pilih Lesson</option>';
            $.each(res, function(i, r) {
                html += `<option value="${r.id_lesson}">${r.nama_lesson}</option>`;
            });
            $('#genLesson').html(html);
        }

        $.getJSON(getLessonsUrl, {}, function(res) {
            renderGenLessons(res);
        });

        $('#genCourse').on('change', function() {
            const id = $(this).val();
            $('#genLesson').html('<option>Loading...</option>');
            if (!id) {
                $.getJSON(getLessonsUrl, {}, res => renderGenLessons(res));
                return;
            }
            $.getJSON(getLessonsUrl, {
                id_courses: id
            }, res => renderGenLessons(res));
        });

        // Filter functionality
        $('#filterCourse').on('change', function() {
            const idCourse = $(this).val();

            $('#filterLesson').html('<option value="">Semua Lesson</option>');

            if (idCourse) {
                $.getJSON('../ajax/get_lessons.php', {
                    id_courses: idCourse
                }, function(data) {
                    data.forEach(function(lesson) {
                        $('#filterLesson').append(
                            `<option value="${lesson.id_lesson}">${lesson.nama_lesson}</option>`
                        );
                    });
                });
            } else {
                $.getJSON('../ajax/get_lessons.php', function(data) {
                    data.forEach(function(lesson) {
                        $('#filterLesson').append(
                            `<option value="${lesson.id_lesson}">${lesson.nama_lesson}</option>`
                        );
                    });
                });
            }

            $('#filterLesson').val('');
            filterCards();
        });


        $('#filterLesson').on('change', filterCards);
        $('#searchStage').on('input', filterCards);

        function filterCards() {
            const course = $('#filterCourse').val();
            const lesson = $('#filterLesson').val();
            const q = ($('#searchStage').val() || '').toLowerCase().trim();

            $('.lesson-block').each(function() {
                const block = $(this);
                const blockCourse = String(block.data('course') || '');
                const blockLesson = String(block.data('lesson') || '');
                const cards = block.find('.card-stage');
                const emptyIll = block.find('.empty-ill');

                if (course && blockCourse !== course) {
                    block.hide();
                    return;
                }

                if (lesson && blockLesson !== lesson) {
                    block.hide();
                    return;
                }

                if (q) {
                    let anyVisible = false;
                    cards.each(function() {
                        const card = $(this);
                        const t = (card.data('title') || '').toLowerCase();
                        const d = (card.data('desc') || '').toLowerCase();
                        const match = t.includes(q) || d.includes(q);
                        card.toggle(match);
                        if (match) anyVisible = true;
                    });
                    emptyIll.hide();
                    block.toggle(anyVisible);
                    return;
                }

                cards.show();

                if (cards.length === 0) {
                    emptyIll.show();
                    block.show();
                } else {
                    emptyIll.hide();
                    block.show();
                }
            });
        }



        // ✅ FIX #1: Step 1 - Validasi apakah lesson sudah punya stage
        $('#generateStep1').on('submit', function(e) {
            e.preventDefault();

            const lesson = $('#genLesson').val();
            const lessonText = $('#genLesson option:selected').text().trim();
            const courseText = $('#genCourse option:selected').text().trim() || '(Semua Course)';
            const count = parseInt($('#genCount').val(), 10);

            if (!lesson) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Pilih Lesson dulu!',
                    text: 'Kamu harus memilih satu lesson sebelum lanjut ke builder.',
                    confirmButtonColor: '#3085d6',
                });
                return;
            }

            if (!count || count < 1) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Jumlah Stage tidak valid!',
                    text: 'Isi jumlah stage minimal 1.',
                    confirmButtonColor: '#3085d6',
                });
                return;
            }

            // ✅ CEK APAKAH SUDAH PUNYA STAGE - BLOKIR JIKA SUDAH ADA
            $.getJSON('../ajax/check_stage_exists.php', {
                id_lesson: lesson
            }, function(res) {
                if (res && res.length > 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lesson Sudah Memiliki Stage!',
                        html: `Lesson <b>${lessonText}</b> sudah memiliki <b>${res.length}</b> stage.<br><br>
                        <strong>Tidak dapat membuat stage baru dari sini.</strong><br><br>
                        Gunakan tombol <b>"Generate untuk lesson"</b> di samping nama lesson untuk mengedit stage yang sudah ada.`,
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'Mengerti'
                    });
                    return; // STOP DI SINI - TIDAK LANJUT KE BUILDER
                }

                // ✅ Jika lolos validasi (tidak ada stage) → lanjut ke builder
                $('#builder_lesson').val(lesson);
                $('#builderInfo').text(`Lesson ID: ${lesson} — ${count} stage(s) baru`);
                deletedStages = []; // Reset deleted stages
                buildStages(count);
                $('#generateModal').modal('hide');
                $('#builderModal').modal('show');
            }).fail(function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Mengecek Stage!',
                    text: 'Terjadi kesalahan saat memeriksa data stage.',
                    confirmButtonColor: '#d33',
                });
            });
        });



        // Update fungsi buildStages untuk generate awal
        function buildStages(count) {
            builderStages = [];
            initialStages = [];
            for (let i = 1; i <= count; i++) {
                builderStages.push({
                    id_stage: uid('st'),
                    nama_stage: `Stage ${i}`, // Auto-generated
                    type: 'materi',
                    deskripsi: '',
                    details: [],
                    isExisting: false
                });
            }
            renderBuilder();
        }


        // Update fungsi Add Stage Button
        $('#addStageBtn').on('click', function() {
            const newIndex = builderStages.length + 1;
            builderStages.push({
                id_stage: uid('st'),
                nama_stage: `Stage ${newIndex}`, // Ini tidak dipakai lagi, tapi tetap ada untuk compatibility
                deskripsi: '',
                type: 'materi',
                details: [],
                isExisting: false
            });
            console.log('Added new stage, total:', builderStages.length);
            renderBuilder(); // Re-render akan auto-update semua nama stage
        });

        // Reset builder
        $('#resetBuilderBtn').on('click', function() {
            Swal.fire({
                title: 'Reset semua?',
                text: 'Semua perubahan pada builder akan hilang.',
                icon: 'warning',
                showCancelButton: true
            }).then(r => {
                if (r.isConfirmed) {
                    builderStages = [];
                    deletedStages = [];
                    renderBuilder();
                }
            });
        });

        $(document).on('click', '.remove-stage', function() {
            const block = $(this).closest('.builder-stage');
            const idx = block.data('idx');
            const stageId = block.find('.stage-id').val();
            const isExisting = block.find('.stage-existing').val() === '1';

            Swal.fire({
                title: 'Hapus Stage?',
                html: `Stage <b>Stage ${idx + 1}</b> akan dihapus.<br><small class="text-muted">Stage lainnya akan otomatis diurutkan ulang.</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#d33'
            }).then(result => {
                if (result.isConfirmed) {
                    // 🔥 Jika stage existing, masukkan ke deletedStages
                    if (isExisting && stageId && stageId.length > 0) {
                        deletedStages.push(stageId);
                        console.log('✅ Stage marked for deletion:', stageId);
                        console.log('📝 Deleted stages list:', deletedStages);
                    }

                    // Hapus dari builderStages array
                    builderStages.splice(idx, 1);
                    console.log('🗑️ Removed stage at index:', idx);

                    // 🎯 AUTO-REORDER: Render ulang untuk update semua nama stage
                    renderBuilder();

                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Stage Dihapus',
                        text: 'Stage berhasil dihapus. Urutan stage lainnya telah diperbarui.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            });
        });

        // Change stage type
        $(document).on('change', '.stage-type', function() {
            const block = $(this).closest('.builder-stage');
            const idx = block.data('idx');
            const val = $(this).val();

            const name = block.find('.stage-name').val() || `Stage ${idx+1}`;
            const desc = block.find('.stage-desc').val() || '';
            builderStages[idx].nama_stage = name;
            builderStages[idx].deskripsi = desc;
            builderStages[idx].type = val;
            builderStages[idx].details = [];
            renderBuilder();
        });

        // Add materi sub-block
        $(document).on('click', '.add-materi', function() {
            const idx = $(this).data('idx');
            const list = $('#materi-list-' + idx);
            const count = list.find('.materi-block').length;
            list.append(materiBlockHtml(idx, count, {
                id_detail: uid('dt'),
                judul: '',
                isi: '',
                media: ''
            }));
        });

        // Remove materi block
        $(document).on('click', '.remove-materi', function() {
            $(this).closest('.materi-block').remove();
        });

        // Add quiz option
        $(document).on('click', '.add-option', function() {
            const wrap = $(this).closest('.options-wrap');
            const list = wrap.find('.options-list');
            const count = list.find('.option-item').length;
            const stageIdx = $(this).closest('.quiz-area').data('idx');
            list.append(quizOptionHtml(stageIdx, 0, count, {
                id: uid('op'),
                text: '',
                is_correct: 0
            }));
        });

        // Remove quiz option
        $(document).on('click', '.remove-option', function() {
            $(this).closest('.option-item').remove();
        });

        // Submit builder form
        $('#builderForm').on('submit', function(e) {
            e.preventDefault();
            const lesson = $('#builder_lesson').val();
            if (!lesson) return Swal.fire('Error', 'Lesson tidak ditemukan', 'error');

            const fd = new FormData();
            fd.append('id_lesson', lesson);

            console.log('📤 Submitting stages:', builderStages.length);
            console.log('🗑️ Deleted stages:', deletedStages.length);

            // 🔥 Kirim daftar stage yang dihapus
            if (deletedStages.length > 0) {
                fd.append('deleted_stages', JSON.stringify(deletedStages));
                console.log('📋 Stages to delete:', deletedStages);
            }

            // Process all stages
            builderStages.forEach((stage, i) => {
                const block = $('#builderWrap .builder-stage').eq(i);
                if (!block || !block.length) return;

                // ✅ TIDAK AMBIL NAMA STAGE DARI INPUT - akan auto-generated di backend
                const desc = block.find('.stage-desc').val() || stage.deskripsi || '';
                const type = block.find('.stage-type').val() || stage.type || 'materi';
                const existingId = block.find('.stage-id').val() || '';
                const isExisting = block.find('.stage-existing').val() === '1';

                const id_stage = existingId && existingId.length > 0 ? existingId : uid('st');

                console.log(`Stage ${i + 1}: ID: ${id_stage}, Type: ${type}, Existing: ${isExisting}`);

                fd.append(`stages[${i}][id_stage]`, id_stage);
                // nama_stage akan di-generate otomatis di backend berdasarkan urutan
                fd.append(`stages[${i}][deskripsi]`, desc);
                fd.append(`stages[${i}][type]`, type);
                fd.append(`stages[${i}][isExisting]`, isExisting ? '1' : '0');

                const work = block.find('.stage-workarea');
                if (type === 'materi') {
                    work.find('.materi-block').each(function(j, mb) {
                        const $mb = $(mb);
                        const judulField = $mb.find('input[name$="[judul]"]');
                        const isiField = $mb.find('textarea.materi-isi');
                        const mediaField = $mb.find('input[type="file"]');

                        const judulValue = judulField.val() || '';
                        const isiValue = isiField.val() || '';
                        const isiClean = stripHtml(isiValue);

                        fd.append(`stages[${i}][details][${j}][judul]`, judulValue);
                        fd.append(`stages[${i}][details][${j}][isi]`, isiClean);

                        if (mediaField && mediaField.length && mediaField[0].files && mediaField[0].files[0]) {
                            fd.append(`stages[${i}][details][${j}][media]`, mediaField[0].files[0]);
                        }
                    });
                } else {
                    const qTextField = work.find('textarea.quiz-isi');
                    const qText = qTextField.val() || '';
                    const qTextClean = stripHtml(qText);
                    const quizType = work.find('select[name$="[quiz_type]"]').val() || 'pilihan_ganda';

                    fd.append(`stages[${i}][details][0][isi]`, qTextClean);
                    fd.append(`stages[${i}][details][0][quiz_type]`, quizType);

                    work.find('.options-list .option-item').each(function(optIdx, oi) {
                        const $oi = $(oi);
                        const text = $oi.find('input[type="text"], input.form-control').first().val() || '';
                        const isCorrect = $oi.find('input[type="checkbox"]').prop('checked') ? '1' : '0';
                        fd.append(`stages[${i}][details][0][options][${optIdx}][text]`, text);
                        fd.append(`stages[${i}][details][0][options][${optIdx}][is_correct]`, isCorrect);
                    });
                }
            });

            // Add JSON payload as backup
            try {
                const payloadStages = builderStages.map((stage, i) => {
                    const block = $('#builderWrap .builder-stage').eq(i);
                    const desc = block.find('.stage-desc').val() || stage.deskripsi || '';
                    const type = block.find('.stage-type').val() || stage.type || 'materi';
                    const existingId = block.find('.stage-id').val() || '';
                    const isExisting = block.find('.stage-existing').val() === '1';

                    const id_stage = existingId && existingId.length > 0 ? existingId : uid('st');

                    const details = [];
                    const work = block.find('.stage-workarea');
                    if (type === 'materi') {
                        work.find('.materi-block').each(function(j, mb) {
                            const $mb = $(mb);
                            const judulValue = $mb.find('input[name$="[judul]"]').val() || '';
                            const isiValue = $mb.find('textarea.materi-isi').val() || '';
                            const isiClean = stripHtml(isiValue);

                            details.push({
                                judul: judulValue,
                                isi: isiClean,
                                media: null
                            });
                        });
                    } else {
                        const qText = work.find('textarea.quiz-isi').val() || '';
                        const qTextClean = stripHtml(qText);
                        const quizType = work.find('select[name$="[quiz_type]"]').val() || 'pilihan_ganda';
                        const opts = [];
                        work.find('.options-list .option-item').each(function(optIdx, oi) {
                            const $oi = $(oi);
                            opts.push({
                                text: $oi.find('input[type="text"], input.form-control').first().val() || '',
                                is_correct: $oi.find('input[type="checkbox"]').prop('checked') ? '1' : '0'
                            });
                        });
                        details.push({
                            isi: qTextClean,
                            quiz_type: quizType,
                            options: opts
                        });
                    }

                    return {
                        id_stage: id_stage,
                        // nama_stage akan auto-generated di backend
                        deskripsi: desc,
                        type: type,
                        isExisting: isExisting,
                        details: details
                    };
                });

                fd.append('stages_json', JSON.stringify(payloadStages));
                console.log('📦 Stages JSON payload:', payloadStages.length, 'items');
            } catch (err) {
                console.error('❌ Failed to build stages_json:', err);
            }

            // Show loading
            Swal.fire({
                title: 'Menyimpan...',
                text: 'Mohon tunggu',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            // Send AJAX
            $.ajax({
                url: '../ajax/stage_generate_full.php',
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(resp) {
                    Swal.close();
                    if (resp.status === 'success') {
                        if (resp.actions) console.log('✅ Stage actions:', resp.actions);

                        let message = resp.message;
                        if (resp.deleted_details && resp.deleted_details.length > 0) {
                            message += '<br><br><small>' + resp.deleted_details.join('<br>') + '</small>';
                        }

                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            html: message
                        }).then(() => location.reload());
                    } else {
                        console.error('❌ Save failed:', resp);
                        Swal.fire('Gagal', resp.message || 'Unknown error', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    Swal.close();
                    console.error('❌ AJAX Error:', status, error);
                    console.error('Response:', xhr.responseText);
                    Swal.fire('Error', 'Terjadi kesalahan server', 'error');
                }
            });
        });
    });
</script>