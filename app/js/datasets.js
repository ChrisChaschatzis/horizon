// app/js/datasets.js

$(document).ready(function() {
    loadDatasets();
    setupDragAndDrop();

    // Form Submit
    $('#importForm').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const btn = $('#btnImport');

        // Progress UI
        const wrapper = $('#progressWrapper');
        const status = $('#importStatus');
        const percent = $('#uploadPercent');
        const progressBar = $('#uploadProgress');

        btn.prop('disabled', true).addClass('disabled');
        wrapper.show();
        status.text('Μεταφόρτωσης...');
        percent.text('0%');
        progressBar.css('width', '0%');

        $.ajax({
            url: `${API_BASE}/import.php`,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        const percentComplete = Math.round((evt.loaded / evt.total) * 100);
                        progressBar.css('width', percentComplete + '%');
                        percent.text(percentComplete + '%');
                        if (percentComplete === 100) {
                             status.text('Επεξεργασία δεδομένων...');
                        }
                    }
                }, false);
                return xhr;
            },
            success: function(res) {
                btn.prop('disabled', false).removeClass('disabled');

                if (res.success) {
                    status.text('Ολοκληρώθηκε!');
                    progressBar.css('background', 'var(--success)');
                    setTimeout(() => wrapper.fadeOut(), 3000);

                    alert('Επιτυχής εισαγωγή! Dataset ID: ' + res.dataset_id);
                    $('#importForm')[0].reset();
                    $('#fileName').text('');
                    loadDatasets();
                } else {
                    status.text('Σφάλμα');
                    progressBar.css('background', 'var(--danger)');
                    alert('Σφάλμα: ' + res.error);
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).removeClass('disabled');
                status.text('Σφάλμα σύνδεσης');
                progressBar.css('background', 'var(--danger)');
                alert('Σφάλμα: ' + (xhr.responseJSON?.error || xhr.statusText));
            }
        });
    });
});

function setupDragAndDrop() {
    const dropZone = $('#dropZone');
    const fileInput = $('#fileInput');
    const fileName = $('#fileName');

    // Drag events
    dropZone.on('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('dragover');
    });

    dropZone.on('dragleave drop', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
    });

    // File selection
    fileInput.on('change', function() {
        if (this.files.length > 0) {
            fileName.text(this.files[0].name);
            dropZone.css('border-color', 'var(--accent)');
        } else {
            fileName.text('');
            dropZone.css('border-color', '');
        }
    });
}

function loadDatasets() {
    $.get(`${API_BASE}/datasets.php`, function(data) {
        let table;
        if ($.fn.DataTable.isDataTable('#datasetsTable')) {
            table = $('#datasetsTable').DataTable();
            table.clear();
        } else {
            table = $('#datasetsTable').DataTable({
                pageLength: 10,
                dom: 'fltip',
                language: datatableGreek,
                order: [[0, 'desc']] // Sort by ID desc
            });
            table.clear();
        }

        data.forEach(d => {
            const date = new Date(d.created_at).toLocaleString('el-GR');
            const deleteBtn = `<button class="btn-icon" onclick="deleteDataset(${d.id})" title="Διαγραφή"><i class="icon-trash">🗑️</i></button>`;

            table.row.add([
                d.id,
                d.name,
                date,
                d.project_count,
                d.notes || '',
                deleteBtn
            ]);
        });

        table.draw();
    });
}

function deleteDataset(id) {
    if (!confirm("Είστε σίγουροι ότι θέλετε να διαγράψετε αυτό το dataset; Η ενέργεια δεν μπορεί να αναιρεθεί.")) return;

    $.ajax({
        url: `${API_BASE}/datasets.php?id=${id}`,
        type: 'DELETE',
        success: function() {
            loadDatasets();
        },
        error: function(xhr) {
            alert("Σφάλμα διαγραφής dataset: " + (xhr.responseJSON?.error || xhr.statusText));
        }
    });
}
