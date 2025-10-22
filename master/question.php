<?php
include "../db.php";
session_start();
if (!isset($_SESSION['login'])) {
    header('Location:../login/login.php');
    exit();
}
$page_title = "Data Question";

// Ambil semua courses untuk dropdown filter / modal
$courses_res = mysqli_query($conn, "SELECT id_courses, nama_courses FROM courses ORDER BY nama_courses ASC");

// Ambil semua question untuk tabel (join minimal info)
$result = mysqli_query($conn,
    "SELECT q.id_question, q.content, q.answers_type, s.nama_stage, l.nama_lesson, c.nama_courses
     FROM question q
     LEFT JOIN stage s ON q.id_stage = s.id_stage
     LEFT JOIN lesson l ON s.id_lesson = l.id_lesson
     LEFT JOIN courses c ON l.id_courses = c.id_courses
     ORDER BY q.id_question ASC"
);

// include template
include "../includes/header.php";
include "../includes/navbar.php";
?>
<div class="container-fluid">
    <div class="row">
        <?php include "../includes/sidebar.php"; ?>

        <main class="main col">
            <div class="page-header d-flex justify-content-between align-items-center">
                <h2>Data Question</h2>
                <button class="mimo-btn mimo-btn-primary" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
                    + Tambah Question
                </button>
            </div>

            <div class="table-panel">
                <table id="questionTable" class="display table table-striped" style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Course</th>
                            <th>Lesson</th>
                            <th>Stage</th>
                            <th>Pertanyaan</th>
                            <th>Tipe</th>
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
                                <td>" . htmlspecialchars($row['nama_lesson']) . "</td>
                                <td>" . htmlspecialchars($row['nama_stage']) . "</td>
                                <td>" . htmlspecialchars($row['content']) . "</td>
                                <td>" . htmlspecialchars($row['answers_type']) . "</td>
                                <td>
                                    <button class='mimo-btn mimo-btn-secondary edit-btn' 
                                        data-id='" . $row['id_question'] . "'
                                        data-bs-toggle='modal' data-bs-target='#editQuestionModal'>Edit</button>
                                    <button class='mimo-btn mimo-btn-danger delete-btn' data-id='" . $row['id_question'] . "'>Hapus</button>
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

<!-- Modal Tambah Question -->
<div class="modal fade" id="addQuestionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="addQuestionForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Question</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row gx-3">
                    <div class="col-md-4 mb-3">
                        <label>Course</label>
                        <select name="id_courses" id="addCourse" class="form-control" required>
                            <option value="">Pilih Course</option>
                            <?php while ($c = mysqli_fetch_assoc($courses_res)) : ?>
                                <option value="<?= htmlspecialchars($c['id_courses']) ?>"><?= htmlspecialchars($c['nama_courses']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Lesson</label>
                        <select name="id_lesson" id="addLesson" class="form-control" required>
                            <option value="">Pilih Lesson</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Stage</label>
                        <select name="id_stage" id="addStage" class="form-control" required>
                            <option value="">Pilih Stage</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label>Pertanyaan</label>
                    <textarea name="content" class="form-control" rows="3" required></textarea>
                </div>

                <div class="mb-3">
                    <label>Tipe Jawaban</label>
                    <select name="answers_type" id="addAnswersType" class="form-control" required>
                        <option value="multiple_choice">Multiple Choice</option>
                        <option value="essay">Essay</option>
                    </select>
                </div>

                <div id="addOptionsWrap">
                    <!-- 4 opsi default A-D -->
                    <?php
                    $labels = ['A','B','C','D'];
                    for ($i=0;$i<4;$i++): ?>
                        <div class="mb-2 option-row" data-label="<?= $labels[$i] ?>">
                            <label>Opsi <?= $labels[$i] ?></label>
                            <input type="text" name="options[]" class="form-control" required>
                            <div class="form-text">Centang jika jawaban benar:
                                <input type="radio" name="correct_answer" value="<?= $labels[$i] ?>" <?= $i===0 ? 'checked' : '' ?>>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>

            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Question -->
<div class="modal fade" id="editQuestionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="editQuestionForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Question</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_question" id="editQuestionId">
                <div class="row gx-3">
                    <div class="col-md-4 mb-3">
                        <label>Course</label>
                        <select name="id_courses" id="editCourse" class="form-control" required>
                            <option value="">Pilih Course</option>
                            <?php
                            // reload courses for edit modal
                            $courses_res2 = mysqli_query($conn, "SELECT id_courses, nama_courses FROM courses ORDER BY nama_courses ASC");
                            while ($c = mysqli_fetch_assoc($courses_res2)) {
                                echo "<option value='".htmlspecialchars($c['id_courses'])."'>".htmlspecialchars($c['nama_courses'])."</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Lesson</label>
                        <select name="id_lesson" id="editLesson" class="form-control" required>
                            <option value="">Pilih Lesson</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Stage</label>
                        <select name="id_stage" id="editStage" class="form-control" required>
                            <option value="">Pilih Stage</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label>Pertanyaan</label>
                    <textarea name="content" id="editContent" class="form-control" rows="3" required></textarea>
                </div>

                <div class="mb-3">
                    <label>Tipe Jawaban</label>
                    <select name="answers_type" id="editAnswersType" class="form-control" required>
                        <option value="multiple_choice">Multiple Choice</option>
                        <option value="essay">Essay</option>
                    </select>
                </div>

                <div id="editOptionsWrap">
                    <!-- will be filled via AJAX -->
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
    // DataTable init
    $('#questionTable').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        info: true,
        pageLength: 10,
        language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            paginate: { first: "Awal", last: "Akhir", next: "›", previous: "‹" }
        },
        dom: '<"top"f>rt<"bottom"p><"clear">'
    });

    // cascade for add modal: course -> lesson -> stage
    $('#addCourse').on('change', function(){
        var id = $(this).val();
        $('#addLesson').html('<option>Loading...</option>');
        $('#addStage').html('<option value="">Pilih Stage</option>');
        if (!id) { $('#addLesson').html('<option value="">Pilih Lesson</option>'); return; }
        $.getJSON('ajax/get_lessons.php', {id_courses: id}, function(res){
            var html = '<option value="">Pilih Lesson</option>';
            $.each(res, function(i, row){ html += '<option value="'+row.id_lesson+'">'+row.nama_lesson+'</option>'; });
            $('#addLesson').html(html);
        });
    });

    $('#addLesson').on('change', function(){
        var id = $(this).val();
        $('#addStage').html('<option>Loading...</option>');
        if (!id) { $('#addStage').html('<option value="">Pilih Stage</option>'); return; }
        $.getJSON('ajax/get_stages.php', {id_lesson: id}, function(res){
            var html = '<option value="">Pilih Stage</option>';
            $.each(res, function(i, row){ html += '<option value="'+row.id_stage+'">'+row.nama_stage+'</option>'; });
            $('#addStage').html(html);
        });
    });

    // show/hide options for add modal
    $('#addAnswersType').on('change', function(){
        if ($(this).val() === 'essay') {
            $('#addOptionsWrap').hide();
            $('#addOptionsWrap').find('input,textarea,select').prop('required', false);
        } else {
            $('#addOptionsWrap').show();
            $('#addOptionsWrap').find('input').prop('required', true);
        }
    }).trigger('change');

    // submit add form
    $('#addQuestionForm').on('submit', function(e){
        e.preventDefault();
        var form = $(this);
        $.post('proses/question_add.php', form.serialize(), function(data){
            try {
                var res = JSON.parse(data);
            } catch (e) {
                Swal.fire('Error','Response tidak valid','error');
                return;
            }
            if (res.status === 'success') {
                Swal.fire('Berhasil', res.message, 'success').then(()=> location.reload());
            } else {
                Swal.fire('Gagal', res.message, 'error');
            }
        });
    });

    // Edit: open modal and fill data via AJAX
    $('#questionTable').on('click', '.edit-btn', function(){
        var id = $(this).data('id');
        $('#editOptionsWrap').html('<p>Loading...</p>');
        $.getJSON('ajax/get_question.php', {id_question: id}, function(res){
            if (!res || !res.question) {
                Swal.fire('Error','Data tidak ditemukan','error');
                return;
            }
            var q = res.question;
            $('#editQuestionId').val(q.id_question);
            $('#editContent').val(q.content);
            $('#editAnswersType').val(q.answers_type);

            // set courses -> then lessons -> stages sequentially
            $('#editCourse').val(res.course_id).trigger('change');

            // after lessons loaded, set lesson then trigger loading stages
            var waitLessons = setInterval(function(){
                if ($('#editLesson option').length > 1) {
                    $('#editLesson').val(res.lesson_id).trigger('change');
                    clearInterval(waitLessons);
                }
            }, 100);

            var waitStages = setInterval(function(){
                if ($('#editStage option').length > 1) {
                    $('#editStage').val(res.stage_id);
                    clearInterval(waitStages);
                }
            }, 100);

            $('#editOptionsWrap').empty();
            if (q.answers_type === 'essay') {
                $('#editOptionsWrap').html('<div class="alert alert-info">Tipe essay — tidak ada opsi pilihan.</div>');
            } else {
                // build option inputs from returned options array
                var html = '';
                $.each(res.options, function(i,opt){
                    var label = opt.label; // A,B,C...
                    html += '<div class="mb-2 option-row" data-label="'+label+'">';
                    html += '<label>Opsi '+label+'</label>';
                    html += '<input type="text" name="options[]" class="form-control" value="'+(opt.option_text||'')+'" required>';
                    html += '<div class="form-text">Centang jika jawaban benar: ';
                    html += '<input type="radio" name="correct_answer" value="'+label+'" '+(opt.is_correct == 1 ? 'checked' : '')+'>';
                    html += '</div></div>';
                });
                $('#editOptionsWrap').html(html);
            }
        });
    });

    // cascade for edit modal: when editCourse changed, load lessons
    $('#editCourse').on('change', function(){
        var id = $(this).val();
        $('#editLesson').html('<option>Loading...</option>');
        $('#editStage').html('<option value="">Pilih Stage</option>');
        if (!id) { $('#editLesson').html('<option value="">Pilih Lesson</option>'); return; }
        $.getJSON('ajax/get_lessons.php', {id_courses: id}, function(res){
            var html = '<option value="">Pilih Lesson</option>';
            $.each(res, function(i, row){ html += '<option value="'+row.id_lesson+'">'+row.nama_lesson+'</option>'; });
            $('#editLesson').html(html);
        });
    });

    $('#editLesson').on('change', function(){
        var id = $(this).val();
        $('#editStage').html('<option>Loading...</option>');
        if (!id) { $('#editStage').html('<option value="">Pilih Stage</option>'); return; }
        $.getJSON('ajax/get_stages.php', {id_lesson: id}, function(res){
            var html = '<option value="">Pilih Stage</option>';
            $.each(res, function(i, row){ html += '<option value="'+row.id_stage+'">'+row.nama_stage+'</option>'; });
            $('#editStage').html(html);
        });
    });

    // show/hide options for edit modal
    $('#editAnswersType').on('change', function(){
        if ($(this).val() === 'essay') {
            $('#editOptionsWrap').hide();
        } else {
            $('#editOptionsWrap').show();
        }
    });

    // submit edit form
    $('#editQuestionForm').on('submit', function(e){
        e.preventDefault();
        var form = $(this);
        $.post('proses/question_edit.php', form.serialize(), function(data){
            try { var res = JSON.parse(data); } catch(e){ Swal.fire('Error','Response tidak valid','error'); return; }
            if (res.status === 'success') {
                Swal.fire('Berhasil', res.message, 'success').then(()=> location.reload());
            } else {
                Swal.fire('Gagal', res.message, 'error');
            }
        });
    });

    // delete
    $('#questionTable').on('click', '.delete-btn', function(){
        var id = $(this).data('id');
        Swal.fire({
            title: 'Yakin ingin menghapus?',
            text: "Question akan dihapus permanen.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('proses/question_delete.php', {id_question: id}, function(data){
                    try { var res = JSON.parse(data); } catch(e){ Swal.fire('Error','Response tidak valid','error'); return; }
                    if (res.status === 'success') {
                        Swal.fire('Berhasil!', res.message, 'success').then(()=> location.reload());
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                    }
                });
            }
        });
    });

});
</script>
</body>
</html>
