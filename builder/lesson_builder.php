<?php
include "../db.php";
session_start();

if (!isset($_SESSION['login'])) {
    header("Location: ../login/login.php");
    exit();
}

$page_title = "Lesson Builder";
$page_css   = "lesson_builder.css"; 

$courses_q = $conn->query("SELECT id_courses, nama_courses FROM courses ORDER BY nama_courses ASC");

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

$groups = [];
if ($res && $res->num_rows) {
    while ($r = $res->fetch_assoc()) {
        $lid = $r['id_lesson'];

        if (!isset($groups[$lid])) {
            $groups[$lid] = [
                "id_lesson"   => $lid,
                "nama_lesson" => $r["nama_lesson"],
                "id_courses"  => $r["id_courses"],
                "nama_courses"=> $r["nama_courses"],
                "stages"      => []
            ];
        }

        if (!empty($r["id_stage"])) {
            $groups[$lid]["stages"][] = [
                "id_stage"   => $r["id_stage"],
                "nama_stage" => $r["nama_stage"],
                "deskripsi"  => $r["deskripsi"],
                "type"       => $r["type"]
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h2>Stage Manager</h2>
                    <p class="text-muted">
                        Stage dibuat & dikelompokkan per <strong>Lesson</strong>.
                        Gunakan Generate untuk membuat stage baru.
                    </p>
                </div>
                <div>
                    <button class="mimo-btn mimo-btn-primary" data-bs-toggle="modal" data-bs-target="#generateModal">
                        + Generate Stage
                    </button>
                </div>
            </div>

            <!-- Filter -->
            <div class="filter-row mb-3">
                <select id="filterCourse" class="form-control">
                    <option value="">Semua Course</option>
                    <?php 
                    if ($courses_q && $courses_q->num_rows) {
                        $courses_q->data_seek(0);
                        while ($c = $courses_q->fetch_assoc()) {
                            echo '<option value="'.$c["id_courses"].'">'.htmlspecialchars($c["nama_courses"]).'</option>';
                        }
                    }
                    ?>
                </select>

                <select id="filterLesson" class="form-control">
                    <option value="">Semua Lesson</option>
                </select>

                <input id="searchStage" class="form-control" placeholder="Cari nama/deskripsi stage..." style="min-width:260px;">
            </div>

            <!-- Cards -->
            <div id="cardsWrap">
            <?php if (!empty($groups)): ?>
                <?php foreach ($groups as $lesson): ?>
                    <div class="lesson-block mb-4"
                         data-course="<?= htmlspecialchars($lesson['id_courses']) ?>"
                         data-lesson="<?= htmlspecialchars($lesson['id_lesson']) ?>">

                        <div class="mb-2 d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= htmlspecialchars($lesson['nama_lesson']) ?></strong>
                                <div class="text-muted small"><?= htmlspecialchars($lesson['nama_courses']) ?></div>
                            </div>
                            <div>
                                <button class="mimo-btn mimo-btn-sm me-2" onclick="reorderStages('<?= $lesson['id_lesson'] ?>')">Reorder</button>
                                <button class="mimo-btn mimo-btn-sm" onclick="openGenerateForLesson('<?= $lesson['id_lesson'] ?>')">Generate</button>
                            </div>
                        </div>

                        <?php if (!empty($lesson['stages'])): ?>
                            <div class="stage-scroll">
                                <div class="stage-grid flex-nowrap">

                                <?php foreach ($lesson['stages'] as $s): ?>
                                    <?php
                                        $escName = htmlspecialchars($s['nama_stage']);
                                        $escDesc = htmlspecialchars($s['deskripsi']);
                                    ?>
                                    <div class="card-stage me-3"
                                         data-course="<?= $lesson['id_courses'] ?>"
                                         data-lesson="<?= $lesson['id_lesson'] ?>"
                                         data-title="<?= strtolower($escName) ?>"
                                         data-desc="<?= strtolower($escDesc) ?>">

                                        <div class="accent"></div>
                                        <div class="title"><?= $escName ?></div>
                                        <div class="meta">
                                            <strong><?= htmlspecialchars($lesson['nama_lesson']) ?></strong> • 
                                            <?= htmlspecialchars($lesson['nama_courses']) ?>
                                            <span class="badge-type <?= $s['type']==='materi' ? 'badge-materi' : 'badge-quiz' ?>">
                                                <?= strtoupper($s['type']) ?>
                                            </span>
                                        </div>
                                        <div class="desc"><?= nl2br($escDesc ?: "-") ?></div>
                                    </div>
                                <?php endforeach; ?>

                                </div>
                            </div>
                        <?php else: ?>
                            <div class="empty-ill">Belum ada stage. Gunakan tombol <strong>Generate</strong>.</div>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>

            <?php else: ?>
                <div class="empty-ill">Belum ada lesson / stage.</div>
            <?php endif; ?>
            </div>

        </main>
    </div>
</div>

<?php include "../includes/footer.php"; ?>

</body>
</html>


<script>
    // Global variables
    let builderStages = [];
    let initialStages = [];
    let deletedStages = [];

    function uid(prefix = 'id') {
        return prefix + '_' + Math.random().toString(36).slice(2, 9);
    }

    function escapeHtml(text) {
        return $('<div/>').text(text || '').html();
    }

    function stripHtml(html) {
        const temp = document.createElement('div');
        temp.innerHTML = html;
        return temp.textContent || temp.innerText || '';
    }

    // 🔧 FUNGSI BARU: Render quiz editor dengan indikator jawaban benar
    function renderQuizEditor(idx, s) {
        const d = s.details && s.details.length ? s.details[0] : {
            id_detail: uid('dt'),
            isi: '',
            quiz_type: 'pilihan_ganda',
            options: [{
                id: uid('op'),
                text: '',
                is_correct: 0
            }, {
                id: uid('op'),
                text: '',
                is_correct: 0
            }]
        };

        // Pastikan minimal ada 2 opsi
        while (d.options.length < 2) {
            d.options.push({
                id: uid('op'),
                text: '',
                is_correct: 0
            });
        }

        return `
        <div class="quiz-area" data-idx="${idx}">
            <h6 class="mb-2"><strong>Quiz Stage ${idx + 1}</strong></h6>
            
            <div class="mb-3">
                <label>Pertanyaan <span class="text-danger">*</span></label>
                <textarea class="form-control quiz-isi" name="stage[${idx}][isi]" rows="3" 
                          placeholder="Masukkan pertanyaan quiz di sini...">${escapeHtml(d.isi || '')}</textarea>
                <small class="text-muted">Pertanyaan wajib diisi</small>
            </div>
            
            <div class="mb-3">
                <label>Tipe Quiz</label>
                <select class="form-control" name="stage[${idx}][quiz_type]">
                    <option value="pilihan_ganda" ${d.quiz_type === 'pilihan_ganda' ? 'selected' : ''}>Pilihan Ganda</option>
                    <option value="isian" ${d.quiz_type === 'isian' ? 'selected' : ''}>Isian</option>
                </select>
            </div>
            
            <div class="mb-3 options-wrap">
                <label>Opsi Jawaban <span class="text-danger">*</span></label>
                <div class="alert alert-info py-2">
                    <small><i class="fas fa-info-circle"></i> Centang kotak untuk menandai jawaban yang benar. Minimal harus ada 1 jawaban benar.</small>
                </div>
                <div class="options-list">
                    ${(d.options || []).map((op, i) => quizOptionHtml(idx, 0, i, op)).join('')}
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary add-option mt-2">
                    <i class="fas fa-plus"></i> Tambah Opsi
                </button>
            </div>
        </div>`;
    }

    // 🔧 FUNGSI BARU: Quiz option dengan styling yang lebih baik
    function quizOptionHtml(stageIdx, detailIdx, optIdx, op) {
        const optionNumber = optIdx + 1;
        const isCorrect = op.is_correct ? true : false;

        // Tentukan warna outline berdasarkan kebenaran jawaban
        const outlineClass = isCorrect ? 'border-success' : 'border-danger';
        const labelText = isCorrect ?
            '<small class="text-success"><strong>Jawaban Benar</strong></small>' :
            '<small class="text-danger"><strong>Jawaban Salah</strong></small>';

        return `
    <div class="option-item card mb-2 ${outlineClass}">
        <div class="card-body py-2">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <input type="checkbox" class="is-correct" 
                           name="stage[${stageIdx}][options][${optIdx}][is_correct]" 
                           ${isCorrect ? 'checked' : ''}
                           id="correct_${stageIdx}_${optIdx}"/>
                    <label class="form-check-label ms-1" for="correct_${stageIdx}_${optIdx}">
                        ${labelText}
                    </label>
                </div>
                
                <div class="flex-grow-1 me-2">
                    <div class="input-group">
                        <span class="input-group-text bg-light">${optionNumber}.</span>
                        <input type="text" class="form-control option-text" 
                               name="stage[${stageIdx}][options][${optIdx}][text]" 
                               value="${escapeHtml(op.text || '')}" 
                               placeholder="Teks opsi jawaban...">
                    </div>
                </div>
                
                <button type="button" class="btn btn-sm btn-outline-danger remove-option" 
                        ${(stageIdx === 0 && optIdx < 2) ? 'disabled' : ''}>
                    <i class="fas fa-times">Hapus</i>
                </button>
            </div>
        </div>
    </div>`;
    }


    // 🔧 FUNGSI BARU: Render materi editor
    function renderMateriEditor(idx, s) {
        const d = s.details && s.details.length ? s.details[0] : {
            id_detail: uid('dt'),
            isi: '',
            media: ''
        };
        return `
        <div class="materi-block" data-idx="${idx}">
            <h6 class="mb-2"><strong>Materi ${idx + 1}</strong></h6>
            <div class="mb-2">
                <label>Isi Materi</label>
                <textarea class="form-control materi-isi" name="stage[${idx}][isi]" rows="4" placeholder="Tulis isi materi di sini...">${escapeHtml(d.isi || '')}</textarea>
            </div>
        </div>`;
    }

    // 🔧 FUNGSI BARU: Sync builder inputs dengan handling yang lebih baik
    function syncBuilderInputs() {
        console.log('🔄 Syncing builder inputs...');

        $('.builder-stage').each(function(index) {
            const stage = builderStages[index];
            if (!stage) {
                console.warn(`⚠️ Stage ${index} tidak ditemukan di builderStages`);
                return;
            }

            // Update basic stage info
            stage.deskripsi = $(this).find('.stage-desc').val() || '';
            stage.type = $(this).find('.stage-type').val() || 'materi';
            stage.nama_stage = `Stage ${index + 1}`;

            const work = $(this).find('.stage-workarea');

            if (stage.type === 'materi') {
                const materiBlock = work.find('.materi-block');
                if (materiBlock.length) {
                    const isi = materiBlock.find('textarea.materi-isi').val() || '';

                    if (!stage.details || stage.details.length === 0) {
                        stage.details = [{
                            id_detail: uid('dt'),
                            isi: '',
                            media: ''
                        }];
                    }

                    stage.details[0].isi = isi.trim();
                }
            } else if (stage.type === 'quiz') {
                const quizArea = work.find('.quiz-area');
                if (quizArea.length) {
                    const isi = quizArea.find('textarea.quiz-isi').val() || '';
                    const quizType = quizArea.find('select[name$="[quiz_type]"]').val() || 'pilihan_ganda';
                    const options = [];

                    // Collect options
                    quizArea.find('.option-item').each(function() {
                        const text = $(this).find('.option-text').val() || '';
                        const is_correct = $(this).find('.is-correct').is(':checked');

                        // Only include non-empty options
                        if (text.trim() !== '') {
                            options.push({
                                id: uid('op'),
                                text: text.trim(),
                                is_correct: is_correct ? 1 : 0
                            });
                        }
                    });

                    // Ensure minimum 2 options
                    while (options.length < 2) {
                        options.push({
                            id: uid('op'),
                            text: '',
                            is_correct: 0
                        });
                    }

                    if (!stage.details || stage.details.length === 0) {
                        stage.details = [{
                            id_detail: uid('dt'),
                            isi: '',
                            quiz_type: 'pilihan_ganda',
                            options: []
                        }];
                    }

                    stage.details[0].isi = isi.trim();
                    stage.details[0].quiz_type = quizType;
                    stage.details[0].options = options;

                    console.log(`Stage ${index + 1} Quiz Data:`, {
                        pertanyaan: isi.trim(),
                        options_count: options.length,
                        has_correct: options.some(opt => opt.is_correct)
                    });
                }
            }
        });

        console.log('✅ Sync completed. Current stages:', JSON.parse(JSON.stringify(builderStages)));
    }

    // 🔧 FUNGSI BARU: Validasi yang lebih informatif
    function validateBeforeSubmit() {
        let isValid = true;
        const errors = [];

        builderStages.forEach((stage, index) => {
            const stageNumber = index + 1;

            if (stage.type === 'quiz') {
                const detail = stage.details && stage.details[0];

                // Validasi pertanyaan
                if (!detail || !detail.isi || detail.isi.trim() === '') {
                    isValid = false;
                    errors.push(`<strong>Stage ${stageNumber}:</strong> Pertanyaan quiz tidak boleh kosong`);
                }

                // Validasi opsi
                if (!detail || !detail.options) {
                    isValid = false;
                    errors.push(`<strong>Stage ${stageNumber}:</strong> Belum ada opsi jawaban`);
                } else {
                    const validOptions = detail.options.filter(opt => opt.text && opt.text.trim() !== '');

                    // Validasi jumlah opsi
                    if (validOptions.length < 2) {
                        isValid = false;
                        errors.push(`<strong>Stage ${stageNumber}:</strong> Minimal harus ada 2 opsi jawaban (saat ini: ${validOptions.length})`);
                    }

                    // Validasi jawaban benar
                    const hasCorrect = validOptions.some(opt => opt.is_correct);
                    if (!hasCorrect) {
                        isValid = false;
                        errors.push(`<strong>Stage ${stageNumber}:</strong> Pilih minimal 1 jawaban yang benar dengan mencentang kotak "Jawaban Benar"`);
                    }

                    // Validasi opsi kosong
                    const emptyOptions = detail.options.filter(opt => !opt.text || opt.text.trim() === '');
                    if (emptyOptions.length > 0 && validOptions.length >= 2) {
                        // Hanya warning, tidak error
                        console.warn(`Stage ${stageNumber}: Terdapat ${emptyOptions.length} opsi kosong yang akan diabaikan`);
                    }
                }
            }
        });

        return {
            isValid,
            errors
        };
    }

    // 🔧 FUNGSI BARU: Add option dengan validasi
    function addQuizOption(button) {
        const wrap = $(button).closest('.options-wrap');
        const list = wrap.find('.options-list');
        const stageIdx = $(button).closest('.quiz-area').data('idx');
        const currentOptions = list.find('.option-item').length;

        const newOptionHtml = quizOptionHtml(stageIdx, 0, currentOptions, {
            id: uid('op'),
            text: '',
            is_correct: 0
        });

        list.append(newOptionHtml);

        // Update sync setelah menambah opsi
        setTimeout(() => {
            syncBuilderInputs();
        }, 100);
    }

    // 🔧 FUNGSI BARU: Remove option dengan validasi
    function removeQuizOption(button) {
        const optionItem = $(button).closest('.option-item');
        const list = optionItem.closest('.options-list');
        const currentOptions = list.find('.option-item').length;

        if (currentOptions > 2) {
            optionItem.remove();
            syncBuilderInputs();

            // Re-number the options
            list.find('.option-item').each(function(index) {
                $(this).find('.input-group-text').text(`${index + 1}.`);
            });
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Tidak Dapat Menghapus',
                text: 'Quiz harus memiliki minimal 2 opsi jawaban',
                timer: 2000
            });
        }
    }

    // 🔧 FUNGSI BARU: Initialize quiz options untuk stage baru
    function initializeNewQuiz(stageIdx) {
        const quizArea = $(`[data-idx="${stageIdx}"] .quiz-area`);
        if (quizArea.length) {
            const optionsList = quizArea.find('.options-list');

            // Clear existing options
            optionsList.empty();

            // Add 2 default options
            for (let i = 0; i < 2; i++) {
                optionsList.append(quizOptionHtml(stageIdx, 0, i, {
                    id: uid('op'),
                    text: '',
                    is_correct: 0
                }));
            }
        }
    }

    // Auto-sync function dengan debounce
    function initAutoSync() {
        let syncTimeout;

        $(document).on('input change', '.builder-stage input, .builder-stage textarea, .builder-stage select', function() {
            clearTimeout(syncTimeout);
            syncTimeout = setTimeout(() => {
                syncBuilderInputs();
                console.log('🔄 Auto-sync triggered');
            }, 500);
        });

        $(document).on('change', '.builder-stage input[type="checkbox"]', function() {
            syncBuilderInputs();
        });
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

            stages = stages.map(stage => ({
                ...stage,
                nama_stage: stage.nama_stage || '',
                deskripsi: stage.deskripsi || '',
                type: stage.type || 'materi',
                details: stage.details || [],
                isExisting: true
            }));

            deletedStages = [];
            showStageFormBuilder(stages, id_lesson);

        } catch (err) {
            console.error("Gagal memuat data stage:", err);
            Swal.fire("Error", "Terjadi kesalahan saat memuat stage!", "error");
        }
    }

    function showStageFormBuilder(stages, id_lesson) {
        $('#builder_lesson').val(id_lesson);
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
                        <label>Nama Stage</label>
                        <input type="text" class="form-control stage-name" value="${escapeHtml(autoStageName)}" disabled readonly style="background-color:#f0f0f0;cursor:not-allowed;">
                        <small class="text-muted">Nama stage dibuat otomatis berdasarkan urutan</small>
                    </div>

                    <div class="mb-2">
                        <label>Deskripsi</label>
                        <textarea class="form-control stage-desc" rows="2" placeholder="Deskripsi singkat stage...">${escapeHtml(s.deskripsi)}</textarea>
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

    $(function() {
        // Inisialisasi auto-sync
        initAutoSync();

        const getLessonsUrl = "../ajax/get_lessons.php";

        // Load lessons
        function renderGenLessons(res) {
            let html = '<option value="">Pilih Lesson</option>';
            $.each(res, function(i, r) {
                html += `<option value="${r.id_lesson}">${r.nama_lesson}</option>`;
            });
            $('#genLesson').html(html);
        }

        // Event handler untuk Course selection di modal Generate
        $('#genCourse').on('change', function() {
            const id = $(this).val();
            const $genLesson = $('#genLesson');
            const $lessonHelpText = $('#lessonHelpText');

            $genLesson.html('<option value="">Loading...</option>');
            $genLesson.prop('disabled', true);
            $lessonHelpText.hide();

            if (!id) {
                $genLesson.html('<option value="">Pilih course terlebih dahulu...</option>');
                $genLesson.prop('disabled', true);
                return;
            }

            $.getJSON(getLessonsUrl, {
                id_courses: id
            }, function(res) {
                if (res && res.length > 0) {
                    renderGenLessons(res);
                    $genLesson.prop('disabled', false);
                    $lessonHelpText.show();
                } else {
                    $genLesson.html('<option value="">Tidak ada lesson untuk course ini</option>');
                    $genLesson.prop('disabled', true);
                    Swal.fire({
                        icon: 'info',
                        title: 'Tidak Ada Lesson',
                        text: 'Course ini belum memiliki lesson. Silakan buat lesson terlebih dahulu.',
                        timer: 3000
                    });
                }
            }).fail(function() {
                $genLesson.html('<option value="">Gagal memuat lesson</option>');
                $genLesson.prop('disabled', true);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Gagal memuat daftar lesson',
                    timer: 2000
                });
            });
        });

        // Reset saat modal ditutup
        $('#generateModal').on('hidden.bs.modal', function() {
            $('#genCourse').val('');
            $('#genLesson').html('<option value="">Pilih course terlebih dahulu...</option>').prop('disabled', true);
            $('#genCount').val(5);
            $('#lessonHelpText').hide();
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

        // Submit Generate Step 1
        $('#generateStep1').on('submit', function(e) {
            e.preventDefault();

            const course = $('#genCourse').val();
            const lesson = $('#genLesson').val();
            const lessonText = $('#genLesson option:selected').text().trim();
            const courseText = $('#genCourse option:selected').text().trim();
            const count = parseInt($('#genCount').val(), 10);

            if (!course) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Pilih Course dulu!',
                    text: 'Kamu harus memilih course terlebih dahulu.',
                    confirmButtonColor: '#3085d6',
                });
                return;
            }

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

            $.getJSON('../ajax/check_stage_exists.php', {
                id_lesson: lesson
            }, function(res) {
                if (res && res.length > 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lesson Sudah Memiliki Stage!',
                        html: `Lesson <b>${lessonText}</b> dari course <b>${courseText}</b> sudah memiliki <b>${res.length}</b> stage.<br><br>
                        <strong>Tidak dapat membuat stage baru dari sini.</strong><br><br>
                        Gunakan tombol <b>"Generate untuk lesson"</b> di samping nama lesson untuk mengedit stage yang sudah ada.`,
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'Mengerti'
                    });
                    return;
                }

                $('#builder_lesson').val(lesson);
                $('#builderInfo').text(`Course: ${courseText} — Lesson: ${lessonText} — ${count} stage(s) baru`);
                deletedStages = [];
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

        function buildStages(count) {
            builderStages = [];
            initialStages = [];
            for (let i = 1; i <= count; i++) {
                builderStages.push({
                    id_stage: uid('st'),
                    nama_stage: `Stage ${i}`,
                    type: 'materi',
                    deskripsi: '',
                    details: [],
                    isExisting: false
                });
            }
            renderBuilder();
        }

        // Tombol tambah stage
        $('#addStageBtn').on('click', function() {
            syncBuilderInputs();

            const newIndex = builderStages.length + 1;
            builderStages.push({
                id_stage: uid('st'),
                nama_stage: `Stage ${newIndex}`,
                deskripsi: '',
                type: 'materi',
                details: [],
                isExisting: false
            });

            console.log('✅ Added new stage, total:', builderStages.length);
            renderBuilder();

            setTimeout(() => {
                $('.builder-stage').last()[0]?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }, 100);
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

        // Remove stage
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
                    if (isExisting && stageId && stageId.length > 0) {
                        deletedStages.push(stageId);
                        console.log('✅ Stage marked for deletion:', stageId);
                    }

                    builderStages.splice(idx, 1);
                    renderBuilder();

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

        // Add option event handler
        $(document).on('click', '.add-option', function() {
            addQuizOption(this);
        });

        // Remove option event handler
        $(document).on('click', '.remove-option', function() {
            removeQuizOption(this);
        });

        // Change stage type handler
        $(document).on('change', '.stage-type', function() {
            const block = $(this).closest('.builder-stage');
            const idx = block.data('idx');
            const val = $(this).val();

            syncBuilderInputs();
            builderStages[idx].type = val;

            if (val === 'materi') {
                builderStages[idx].details = [{
                    id_detail: uid('dt'),
                    isi: '',
                    media: ''
                }];
            } else {
                builderStages[idx].details = [{
                    id_detail: uid('dt'),
                    isi: '',
                    quiz_type: 'pilihan_ganda',
                    options: []
                }];

                // Initialize quiz options
                setTimeout(() => {
                    initializeNewQuiz(idx);
                    syncBuilderInputs();
                }, 100);
            }

            renderBuilder();
        });

        // Real-time validation untuk quiz question
        $(document).on('input', '.quiz-isi', function() {
            const quizArea = $(this).closest('.quiz-area');
            const stageIdx = quizArea.data('idx');

            // Update border color based on content
            if ($(this).val().trim() === '') {
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }

            syncBuilderInputs();
        });

        // Real-time validation untuk options
        $(document).on('input', '.option-text', function() {
            const optionItem = $(this).closest('.option-item');

            // Update border color based on content
            if ($(this).val().trim() === '') {
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }

            syncBuilderInputs();
        });

        // Real-time check untuk jawaban benar
        $(document).on('change', '.is-correct', function() {
            syncBuilderInputs();

            // Update visual feedback
            const optionItem = $(this).closest('.option-item');
            if ($(this).is(':checked')) {
                optionItem.addClass('border-success');
                optionItem.find('.input-group-text').addClass('bg-success text-white');
            } else {
                optionItem.removeClass('border-success');
                optionItem.find('.input-group-text').removeClass('bg-success text-white').addClass('bg-light');
            }
        });

        // Submit handler dengan validasi yang lebih baik
        $('#builderForm').on('submit', function(e) {
            e.preventDefault();

            // Sync terakhir sebelum submit
            syncBuilderInputs();

            console.log('🔍 Final validation check:', builderStages);

            // Validasi sebelum submit
            const validation = validateBeforeSubmit();
            if (!validation.isValid) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validasi Gagal',
                    html: 'Perbaiki kesalahan berikut sebelum menyimpan:<br><br>' +
                        validation.errors.join('<br>') +
                        '<br><br><small class="text-muted">Pastikan semua field yang wajib sudah terisi dengan benar.</small>',
                    confirmButtonText: 'Mengerti',
                    confirmButtonColor: '#d33'
                });
                return;
            }

            // Lanjutkan dengan proses submit...
            proceedWithSubmit();
        });
    });

    // 🔧 FUNGSI BARU: Proceed with submit
    function proceedWithSubmit() {
        const lesson = $('#builder_lesson').val();
        if (!lesson) return Swal.fire('Error', 'Lesson tidak ditemukan', 'error');

        const fd = new FormData();
        fd.append('id_lesson', lesson);

        console.log('📤 Submitting stages:', builderStages.length);
        console.log('🗑️ Deleted stages:', deletedStages.length);

        if (deletedStages.length > 0) {
            fd.append('deleted_stages', JSON.stringify(deletedStages));
        }

        builderStages.forEach((stage, i) => {
            const block = $('#builderWrap .builder-stage').eq(i);
            if (!block || !block.length) return;

            const desc = block.find('.stage-desc').val() || stage.deskripsi || '';
            const type = block.find('.stage-type').val() || stage.type || 'materi';
            const existingId = block.find('.stage-id').val() || '';
            const isExisting = block.find('.stage-existing').val() === '1';
            const id_stage = existingId && existingId.length > 0 ? existingId : uid('st');

            fd.append(`stages[${i}][id_stage]`, id_stage);
            fd.append(`stages[${i}][deskripsi]`, desc);
            fd.append(`stages[${i}][type]`, type);
            fd.append(`stages[${i}][isExisting]`, isExisting ? '1' : '0');

            const work = block.find('.stage-workarea');
            if (type === 'materi') {
                work.find('.materi-block').each(function(j, mb) {
                    const $mb = $(mb);
                    const isiField = $mb.find('textarea.materi-isi');
                    const isiValue = isiField.val() || '';
                    const isiClean = stripHtml(isiValue);

                    fd.append(`stages[${i}][details][${j}][isi]`, isiClean);
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
                    const text = $oi.find('.option-text').val() || '';
                    const isCorrect = $oi.find('.is-correct').is(':checked') ? '1' : '0';

                    // Hanya append opsi yang tidak kosong
                    if (text.trim() !== '') {
                        fd.append(`stages[${i}][details][0][options][${optIdx}][text]`, text);
                        fd.append(`stages[${i}][details][0][options][${optIdx}][is_correct]`, isCorrect);
                    }
                });
            }
        });

        // Juga kirim sebagai JSON untuk backup
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
                        const isiValue = $mb.find('textarea.materi-isi').val() || '';
                        const isiClean = stripHtml(isiValue);
                        details.push({
                            isi: isiClean
                        });
                    });
                } else {
                    const qText = work.find('textarea.quiz-isi').val() || '';
                    const qTextClean = stripHtml(qText);
                    const quizType = work.find('select[name$="[quiz_type]"]').val() || 'pilihan_ganda';
                    const opts = [];

                    work.find('.options-list .option-item').each(function(optIdx, oi) {
                        const $oi = $(oi);
                        const text = $oi.find('.option-text').val() || '';
                        const textClean = stripHtml(text);

                        if (textClean.trim() !== '') {
                            opts.push({
                                text: textClean,
                                is_correct: $oi.find('.is-correct').is(':checked') ? '1' : '0'
                            });
                        }
                    });

                    details.push({
                        isi: qTextClean,
                        quiz_type: quizType,
                        options: opts
                    });
                }

                return {
                    id_stage: id_stage,
                    deskripsi: desc,
                    type: type,
                    isExisting: isExisting,
                    details: details
                };
            });

            fd.append('stages_json', JSON.stringify(payloadStages));
            console.log('📦 Stages JSON payload:', payloadStages);
        } catch (err) {
            console.error('❌ Failed to build stages_json:', err);
        }

        Swal.fire({
            title: 'Menyimpan...',
            text: 'Mohon tunggu',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

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
    }
</script>