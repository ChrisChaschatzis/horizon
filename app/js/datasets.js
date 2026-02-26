// app/js/datasets.js

$(document).ready(function() {
    loadDatasets();
    setupDragAndDrop();

    // Form Submit
    $('#importForm').on('submit', function(e) {
        e.preventDefault();

        // Validate file selection
        const manualFile = $('#fileInputManual')[0].files[0];
        const dropFile = $('#fileInputDrop')[0].files[0];

        if (!manualFile && !dropFile) {
            alert('Παρακαλώ επιλέξτε ένα αρχείο Excel.');
            return;
        }

        const formData = new FormData(this);
        const btn = $('#btnImport');

        // Progress UI
        const wrapper = $('#progressWrapper');
        const status = $('#importStatus');
        const percent = $('#uploadPercent');
        const progressBar = $('#uploadProgress');

        btn.prop('disabled', true).addClass('disabled');
        wrapper.show();
        status.text('Μεταφόρτωση...');
        percent.text('0%');
        progressBar.css('width', '0%');
        progressBar.css('background', 'linear-gradient(90deg, var(--accent), var(--accent2))');

        $.ajax({
            url: `import.php`, // Relative path or API_BASE? In previous file it was API_BASE/import.php but file structure suggests root/import.php or similar. Let's assume root import.php calls API. Wait, test_import.php uses Importer class.
            // Looking at datasets.php form action, it wasn't specified, but JS handled it.
            // Previous JS used `${API_BASE}/import.php`. Let's check if that exists.
            // It doesn't seem to exist in file list.
            // But we have `src/Importer.php`.
            // I should create `api/import.php`.
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
                    $('#fileNameDrop').text('');
                    $('#dropZone').css('border-color', '');
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
    const fileInputDrop = $('#fileInputDrop');
    const fileNameDrop = $('#fileNameDrop');

    // Drag events
    dropZone.on('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('dragover');
    });

    dropZone.on('dragleave drop', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
    });

    // File selection from Drop Zone Input
    fileInputDrop.on('change', function() {
        if (this.files.length > 0) {
            fileNameDrop.text(this.files[0].name);
            dropZone.css('border-color', 'var(--accent)');
        } else {
            fileNameDrop.text('');
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
            const deleteBtn = `<button class="btn-icon" onclick="deleteDataset(${d.id})" title="Διαγραφή"><i data-lucide="trash-2" style="width:16px;"></i></button>`;

            const typeBadge = d.dataset_type === 'summary'
                ? `<span style="background:var(--accent2); color:#fff; padding:2px 6px; border-radius:4px; font-size:0.75rem;">PACK (${d.entities_count})</span>`
                : `<span style="background:var(--muted); color:#fff; padding:2px 6px; border-radius:4px; font-size:0.75rem;">CORDIS</span>`;

            table.row.add([
                d.id,
                `<div>${d.name} ${typeBadge}</div>`,
                date,
                d.dataset_type === 'summary' ? '-' : d.project_count,
                d.notes || '',
                deleteBtn
            ]);
        });

        table.draw();
        lucide.createIcons();
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
