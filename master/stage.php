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
        ORDER BY c.nama_courses ASC, l.nama_lesson ASC, s.id_stage ASC";
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
        <form id="generateStep1" class="modal-content">
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
                    <select id="genLesson" name="id_lesson" class="form-control" required>
                        <option value="">Pilih Lesson</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Jumlah Stage</label>
                    <input type="number" id="genCount" name="count" min="1" max="200" class="form-control" required value="5">
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
    $(function() {
        // --- existing variables used earlier --- //
        const getLessonsUrl = "../ajax/get_lessons.php";
        // ... (other urls remain)

        // populate genLesson initially (re-use existing getLessons call)
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

        // when course changed for modal step1
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

        // Step1 submit => build builder modal
        $('#generateStep1').on('submit', function(e) {
            e.preventDefault();
            const lesson = $('#genLesson').val();
            const count = parseInt($('#genCount').val()) || 0;
            if (!lesson || count < 1) return Swal.fire('Oops', 'Pilih lesson dan jumlah stage minimal 1', 'error');

            // prepare builder
            $('#builder_lesson').val(lesson);
            $('#builderInfo').text(`Lesson ID: ${lesson} — ${count} stage(s)`);
            buildStages(count);
            $('#generateModal').modal('hide');
            $('#builderModal').modal('show');
        });

        // builder state
        let builderStages = []; // array of stage objects

        function uid(prefix = 'id') {
            return prefix + '_' + Math.random().toString(36).slice(2, 9);
        }

        function buildStages(count) {
            builderStages = [];
            for (let i = 1; i <= count; i++) {
                builderStages.push({
                    index: i,
                    id_stage: uid('st'),
                    nama_stage: `Stage ${i}`,
                    type: 'materi',
                    deskripsi: '',
                    details: [] // for materi: array of detail blocks; for quiz: one detail with question and options
                });
            }
            renderBuilder();
        }

        // add single stage (at end)
        $('#addStageBtn').on('click', function() {
            const idx = builderStages.length + 1;
            builderStages.push({
                index: idx,
                id_stage: uid('st'),
                nama_stage: `Stage ${idx}`,
                type: 'materi',
                deskripsi: '',
                details: []
            });
            renderBuilder();
        });

        $('#resetBuilderBtn').on('click', function() {
            Swal.fire({
                title: 'Reset semua?',
                text: 'Semua perubahan pada builder akan hilang.',
                icon: 'warning',
                showCancelButton: true
            }).then(r => {
                if (r.isConfirmed) {
                    builderStages = [];
                    renderBuilder();
                }
            });
        });

        // render builder UI
        function renderBuilder() {
            let html = '';
            builderStages.forEach((s, idx) => {
                html += `
      <div class="card mb-3 builder-stage" data-idx="${idx}">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div><h5>Stage ${idx+1}</h5></div>
            <div>
              <button type="button" class="btn btn-sm btn-outline-danger remove-stage">Hapus</button>
            </div>
          </div>

          <div class="mb-2">
            <label>Nama Stage</label>
            <input class="form-control stage-name" name="stage[${idx}][nama_stage]" value="${escapeHtml(s.nama_stage)}">
          </div>

          <div class="mb-2">
            <label>Deskripsi singkat</label>
            <textarea class="form-control stage-desc" name="stage[${idx}][deskripsi]" rows="2">${escapeHtml(s.deskripsi)}</textarea>
          </div>

          <div class="mb-2">
            <label>Tipe</label>
            <select class="form-control stage-type" name="stage[${idx}][type]">
              <option value="materi" ${s.type==='materi'?'selected':''}>Materi</option>
              <option value="quiz" ${s.type==='quiz'?'selected':''}>Quiz</option>
            </select>
          </div>

          <div class="stage-workarea mt-2" id="workarea-${idx}">
            ${s.type === 'materi' ? renderMateriEditor(idx, s) : renderQuizEditor(idx, s)}
          </div>
        </div>
      </div>
      `;
            });
            $('#builderWrap').html(html);
        }

        // helper escape
        function escapeHtml(text) {
            return $('<div/>').text(text || '').html();
        }

        // render materi editor (simple: multiple materi blocks)
        function renderMateriEditor(idx, s) {
            // show one materi block by default
            return `
    <div class="materi-blocks" data-idx="${idx}">
      <button type="button" class="btn btn-sm btn-outline-primary mb-2 add-materi" data-idx="${idx}">Tambah sub-materi</button>
      <div class="materi-list" id="materi-list-${idx}">
        ${s.details.length ? s.details.map((d,i)=>materiBlockHtml(idx,i,d)).join('') : materiBlockHtml(idx,0,{id_detail:uid('dt'), judul:'', isi:'', media:''})}
      </div>
    </div>
    `;
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
        <input class="form-control" name="stage[${stageIdx}][details][${detailIdx}][judul]" value="${escapeHtml(d.judul||'')}">
      </div>
      <div class="mb-2">
        <label>Isi (HTML / teks)</label>
        <textarea class="form-control" name="stage[${stageIdx}][details][${detailIdx}][isi]" rows="3">${escapeHtml(d.isi||'')}</textarea>
      </div>
      <div class="mb-2">
        <label>Gambar (opsional)</label>
        <input type="file" name="stage[${stageIdx}][details][${detailIdx}][media]" class="form-control">
      </div>
    </div>
    `;
        }

        // render quiz editor
        function renderQuizEditor(idx, s) {
            // s.details: array; for simplicity we make one question per stage by default
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
          <textarea class="form-control" name="stage[${idx}][details][0][isi]" rows="3">${escapeHtml(d.isi||'')}</textarea>
        </div>
        <div class="mb-2">
          <label>Tipe Quiz</label>
          <select class="form-control" name="stage[${idx}][details][0][quiz_type]">
            <option value="pilihan_ganda" ${d.quiz_type==='pilihan_ganda'?'selected':''}>Pilihan Ganda</option>
            <option value="isian" ${d.quiz_type==='isian'?'selected':''}>Isian</option>
          </select>
        </div>
        <div class="mb-2 options-wrap">
          <label>Opsi (untuk Pilihan Ganda)</label>
          <div class="options-list">
            ${ (d.options && d.options.length) ? d.options.map((op,i)=>quizOptionHtml(idx,0,i,op)).join('') : quizOptionHtml(idx,0,0,{id:uid('op'), text:'', is_correct:0}) }
          </div>
          <button type="button" class="btn btn-sm btn-outline-primary add-option">Tambah Opsi</button>
        </div>
      </div>
    `;
        }

        function quizOptionHtml(stageIdx, detailIdx, optIdx, op) {
            return `
    <div class="d-flex mb-1 align-items-center option-item">
      <input type="checkbox" class="me-2 is-correct" name="stage[${stageIdx}][details][${detailIdx}][options][${optIdx}][is_correct]" ${op.is_correct ? 'checked' : ''}/>
      <input class="form-control me-2" name="stage[${stageIdx}][details][${detailIdx}][options][${optIdx}][text]" value="${escapeHtml(op.text||'')}" placeholder="Teks opsi">
      <button type="button" class="btn btn-sm btn-outline-danger remove-option">Hapus</button>
    </div>
    `;
        }

        // delegate events inside builderWrap

        // remove entire stage
        $(document).on('click', '.remove-stage', function() {
            const idx = $(this).closest('.builder-stage').data('idx');
            builderStages.splice(idx, 1);
            // reindex indices
            renderBuilder();
        });

        // when user changes stage name/type/desc we keep values only on submit (no heavy sync)
        // but we must re-render area on type change:
        $(document).on('change', '.stage-type', function() {
            const block = $(this).closest('.builder-stage');
            const idx = block.data('idx');
            const val = $(this).val();
            // update in-memory
            // Try to fetch existing values from DOM to preserve filled textareas
            const name = block.find('.stage-name').val() || `Stage ${idx+1}`;
            const desc = block.find('.stage-desc').val() || '';
            builderStages[idx].nama_stage = name;
            builderStages[idx].deskripsi = desc;
            builderStages[idx].type = val;
            // reset details
            builderStages[idx].details = [];
            renderBuilder();
        });

        // add materi sub-block
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

        // remove materi block
        $(document).on('click', '.remove-materi', function() {
            $(this).closest('.materi-block').remove();
        });

        // add option for quiz
        $(document).on('click', '.add-option', function() {
            const wrap = $(this).closest('.options-wrap');
            const list = wrap.find('.options-list');
            const count = list.find('.option-item').length;
            // find stage idx from nearest .quiz-area
            const stageIdx = $(this).closest('.quiz-area').data('idx');
            list.append(quizOptionHtml(stageIdx, 0, count, {
                id: uid('op'),
                text: '',
                is_correct: 0
            }));
        });

        // remove option
        $(document).on('click', '.remove-option', function() {
            $(this).closest('.option-item').remove();
        });

        // Submit builder form -> assemble FormData and send to backend
        $('#builderForm').on('submit', function(e) {
            e.preventDefault();
            const lesson = $('#builder_lesson').val();
            if (!lesson) return Swal.fire('Error', 'Lesson tidak ditemukan', 'error');

            const fd = new FormData();
            fd.append('id_lesson', lesson);

            // walk DOM to collect stage data
            $('#builderWrap .builder-stage').each(function(i, el) {
                const $el = $(el);
                const name = $el.find('.stage-name').val() || `Stage ${i+1}`;
                const desc = $el.find('.stage-desc').val() || '';
                const type = $el.find('.stage-type').val() || 'materi';
                const id_stage = uid('st');

                fd.append(`stages[${i}][id_stage]`, id_stage);
                fd.append(`stages[${i}][nama_stage]`, name);
                fd.append(`stages[${i}][deskripsi]`, desc);
                fd.append(`stages[${i}][type]`, type);

                const work = $el.find('.stage-workarea');

                if (type === 'materi') {
                    work.find('.materi-block').each(function(j, mb) {
                        const $mb = $(mb);
                        const judulField = $mb.find('input[name$="[judul]"]');
                        const isiField = $mb.find('textarea[name$="[isi]"]');
                        const mediaField = $mb.find('input[type="file"]');

                        const detailId = uid('dt');
                        fd.append(`stages[${i}][details][${j}][id_detail]`, detailId);
                        fd.append(`stages[${i}][details][${j}][judul]`, judulField.val() || '');
                        fd.append(`stages[${i}][details][${j}][isi]`, isiField.val() || '');

                        if (mediaField && mediaField.length && mediaField[0].files && mediaField[0].files[0]) {
                            fd.append(`stages[${i}][details][${j}][media]`, mediaField[0].files[0]);
                        }
                    });
                } else {
                    // quiz
                    const qText = work.find('textarea[name$="[isi]"]').val() || '';
                    const quizType = work.find('select[name$="[quiz_type]"]').val() || 'pilihan_ganda';
                    const detailId = uid('dt');
                    fd.append(`stages[${i}][details][0][id_detail]`, detailId);
                    fd.append(`stages[${i}][details][0][isi]`, qText);
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

            // Show loading
            Swal.fire({
                title: 'Menyimpan...',
                text: 'Mohon tunggu',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            // send
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
                        Swal.fire('Berhasil', resp.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Gagal', resp.message || 'Unknown', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    Swal.close();
                    console.error('AJAX Error:', status, error);
                    console.error('Response:', xhr.responseText);
                    Swal.fire('Error', 'Terjadi kesalahan server', 'error');
                }
            });
        });

    });

    $(function() {
        const getLessonsUrl = "../ajax/get_lessons.php";
        const genUrl = "../ajax/stage_generate.php";
        const getStageUrl = "../ajax/stage_get.php";
        const deleteUrl = "../ajax/stage_delete.php";

        // populate lesson list in modal when course selected
        $('#genCourse').on('change', function() {
            const id = $(this).val();
            $('#genLesson').html('<option>Loading...</option>');
            if (!id) {
                // load all lessons
                $.getJSON(getLessonsUrl, {}, function(res) {
                    renderGenLessons(res);
                }).fail(() => $('#genLesson').html('<option value="">Pilih Lesson</option>'));
                return;
            }
            $.getJSON(getLessonsUrl, {
                id_courses: id
            }, function(res) {
                renderGenLessons(res);
            }).fail(() => $('#genLesson').html('<option value="">Pilih Lesson</option>'));
        });

        function renderGenLessons(res) {
            let html = '<option value="">Pilih Lesson</option>';
            $.each(res, function(i, r) {
                html += `<option value="${r.id_lesson}">${r.nama_lesson}</option>`;
            });
            $('#genLesson').html(html);
        }

        // initial load for genLesson: load all lessons
        $.getJSON(getLessonsUrl, {}, function(res) {
            renderGenLessons(res);
        });

        // filterCourse -> populate lessons for filter and call filterCards
        $('#filterCourse').on('change', function() {
            const id = $(this).val();
            $('#filterLesson').html('<option>Loading...</option>');
            if (!id) {
                $('#filterLesson').html('<option value="">Semua Lesson</option>');
                filterCards();
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

            $('.lesson-block').each(function() {
                const block = $(this);
                const blockCourse = block.data('course') + '';
                const blockLesson = block.data('lesson') + '';

                if (course && course !== blockCourse) {
                    block.hide();
                    return;
                }
                if (lesson && lesson !== blockLesson) {
                    block.hide();
                    return;
                }

                // check cards inside
                let anyVisible = false;
                block.find('.card-stage').each(function() {
                    const card = $(this);
                    const t = (card.data('title') || '') + '';
                    const d = (card.data('desc') || '') + '';
                    let show = true;
                    if (q && !(t.indexOf(q) !== -1 || d.indexOf(q) !== -1)) show = false;
                    card.toggle(show);
                    if (show) anyVisible = true;
                });

                if (anyVisible) block.show();
                else block.hide();
            });
        }

        // generate form
        $('#generateForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: genUrl,
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
                    console.error('AJAX ERROR', status, err);
                    console.log('RAW RESPONSE:', xhr.responseText);
                    Swal.fire('Error', 'Response invalid — cek console (raw response).', 'error');
                }
            });
        });
    });

    // Global variables for stage builder
    let builderStages = []; // array of stage objects

    function renderBuilder() {
        let html = '';
        builderStages.forEach((s, idx) => {
            html += `
      <div class="card mb-3 builder-stage" data-idx="${idx}">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <h5 class="mb-0">Stage ${idx + 1}</h5>
            <button type="button" class="btn btn-sm btn-outline-danger remove-stage">Hapus Stage</button>
          </div>

          <div class="mb-2">
            <label>Nama Stage</label>
            <input type="text" class="form-control stage-name" value="${s.nama_stage || `Stage ${idx + 1}`}">
          </div>

          <div class="mb-2">
            <label>Deskripsi</label>
            <textarea class="form-control stage-desc">${s.deskripsi || ''}</textarea>
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
      </div>
      `;
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
          <textarea class="form-control" name="stage[${idx}][details][0][isi]" rows="3">${d.isi||''}</textarea>
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
            ${d.options.map((op,i) => quizOptionHtml(idx,0,i,op)).join('')}
          </div>
          <button type="button" class="btn btn-sm btn-outline-primary add-option">Tambah Opsi</button>
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
        <input class="form-control" name="stage[${stageIdx}][details][${detailIdx}][judul]" value="${d.judul||''}">
      </div>
      <div class="mb-2">
        <label>Isi (HTML / teks)</label>
        <textarea class="form-control" name="stage[${stageIdx}][details][${detailIdx}][isi]" rows="3">${d.isi||''}</textarea>
      </div>
      <div class="mb-2">
        <label>Gambar (opsional)</label>
        <input type="file" name="stage[${stageIdx}][details][${detailIdx}][media]" class="form-control">
      </div>
    </div>`;
    }

    function quizOptionHtml(stageIdx, detailIdx, optIdx, op) {
        return `
    <div class="d-flex mb-1 align-items-center option-item">
      <input type="checkbox" class="me-2 is-correct" name="stage[${stageIdx}][details][${detailIdx}][options][${optIdx}][is_correct]" ${op.is_correct ? 'checked' : ''}/>
      <input class="form-control me-2" name="stage[${stageIdx}][details][${detailIdx}][options][${optIdx}][text]" value="${op.text||''}" placeholder="Teks opsi">
      <button type="button" class="btn btn-sm btn-outline-danger remove-option">Hapus</button>
    </div>`;
    }

    function uid(prefix = 'id') {
        return prefix + '_' + Math.random().toString(36).slice(2, 9);
    }

    async function openGenerateForLesson(id_lesson) {
        try {
            // Show loading
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

            // Note: `get_stage_by_lesson.php` already includes details (materi or quiz) in `stage.details`.
            // We no longer need to fetch per-stage details again here.

            // Close loading
            Swal.close();

            // Validate each stage has required fields
            stages = stages.map(stage => ({
                ...stage,
                nama_stage: stage.nama_stage || '',
                deskripsi: stage.deskripsi || '',
                type: stage.type || 'materi',
                details: stage.details || []
            }));

            // ✅ populate builder dengan data existing stages
            showStageFormBuilder(stages, id_lesson);

        } catch (err) {
            console.error("Gagal memuat data stage:", err);
            Swal.fire("Error", "Terjadi kesalahan saat memuat stage!", "error");
        }
    }

    function showStageFormBuilder(stages, id_lesson) {
        // Set lesson ID
        $('#builder_lesson').val(id_lesson);

        // Populate builderStages array with existing data
        builderStages = stages.map((stage, index) => {
            const details = stage.details || [];

            // If it's a quiz, ensure options array exists
            if (stage.type === 'quiz' && details.length > 0) {
                details[0].options = details[0].options || [];
            }

            return {
                index: index + 1,
                id_stage: stage.id_stage,
                nama_stage: stage.nama_stage,
                type: stage.type,
                deskripsi: stage.deskripsi || '',
                details: details
            };
        });

        // Update builder info
        $('#builderInfo').text(`Lesson ID: ${id_lesson} — ${stages.length} stage(s) existing`);

        // Log the data for debugging
        console.log('📦 Loaded stages:', builderStages);

        // Render builder
        renderBuilder();

        // Show modal
        $('#builderModal').modal('show');
    }

    function addStageField() {
        const container = document.querySelector('.form-builder-body');
        container.insertAdjacentHTML('beforeend', `
    <div class="stage-item">
      <input type="hidden" name="id_stage[]" value="">
      <label>Nama Stage</label>
      <input type="text" name="nama_stage[]" class="form-control">

      <label>Deskripsi</label>
      <textarea name="deskripsi[]" class="form-control"></textarea>

      <label>Type</label>
      <select name="type[]" class="form-control">
        <option value="materi">Materi</option>
        <option value="quiz">Quiz</option>
      </select>
      <hr>
    </div>
  `);
    }



    // 🧩 Form Builder Generator
    function addStageForm(container, data = null, number = 1) {
        const div = document.createElement('div');
        div.className = 'stage-form border p-3 rounded mb-3 bg-gray-50';

        div.innerHTML = `
    <h5 class="font-bold mb-2">Stage ${number}</h5>
    <label>Nama Stage</label>
    <input type="text" name="stages[${number}][nama_stage]" class="form-control mb-2" 
      value="${data ? data.nama_stage : ''}">
    
    <label>Deskripsi</label>
    <textarea name="stages[${number}][deskripsi]" class="form-control mb-2">${data ? data.deskripsi : ''}</textarea>

    <label>Urutan</label>
    <input type="number" name="stages[${number}][urutan]" class="form-control mb-2" 
      value="${data ? data.urutan : number}">

    <h6 class="mt-3">Materi</h6>
    <div class="materi-area"></div>

    <button type="button" class="btn btn-sm btn-outline-primary mb-2" 
      onclick="addMateriForm(this)">+ Tambah Materi</button>
  `;

        container.appendChild(div);

        // kalau sudah ada materi
        if (data && data.materi && data.materi.length > 0) {
            const materiArea = div.querySelector('.materi-area');
            data.materi.forEach((m, i) => {
                addMateriForm(div.querySelector('button'), m, i + 1);
            });
        }
    }

    function addMateriForm(button, materiData = null, index = 1) {
        const area = button.previousElementSibling;
        const div = document.createElement('div');
        div.className = 'materi-form p-2 border rounded mb-2 bg-white';

        div.innerHTML = `
    <label>Judul Materi</label>
    <input type="text" name="materi[][judul]" class="form-control mb-1" 
      value="${materiData ? materiData.judul : ''}">
    <label>Isi Materi</label>
    <textarea name="materi[][isi]" class="form-control mb-1">${materiData ? materiData.isi : ''}</textarea>
    <label>Media</label>
    <input type="file" name="materi[][media]" class="form-control mb-1">
  `;

        area.appendChild(div);
    }

    function addStageField() {
        const container = document.querySelector('.form-builder-body');
        const index = container.querySelectorAll('.stage-item').length + 1;
        container.insertAdjacentHTML('beforeend', `
    <div class="stage-item">
      <input type="hidden" name="id_stage[]" value="">
      <label>Nama Stage</label>
      <input type="text" name="nama_stage[]" class="form-control" placeholder="Stage ${index}">

      <label>Deskripsi</label>
      <textarea name="deskripsi[]" class="form-control"></textarea>

      <label>Type</label>
      <select name="type[]" class="form-control">
        <option value="materi">Materi</option>
        <option value="quiz">Quiz</option>
      </select>

      <hr>
    </div>
  `);
    }
</script>