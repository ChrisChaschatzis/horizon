// app/js/datasets.js

$(document).ready(function() {
    loadDatasets();

    // Form Submit
    $('#importForm').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const btn = $('#btnImport');

        // Progress UI
        const progressContainer = $('#progressContainer');
        const status = $('#importStatus');
        const percent = $('#uploadPercent');
        const progressBar = $('#uploadProgress');

        btn.prop('disabled', true).hide();
        progressContainer.show();
        status.text('Μεταφόρτωση...');
        percent.text('0%');
        progressBar.val(0);

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
                        progressBar.val(percentComplete);
                        percent.text(percentComplete + '%');
                        if (percentComplete === 100) {
                             status.text('Επεξεργασία αρχείου (αυτό μπορεί να πάρει λίγο χρόνο)...');
                        }
                    }
                }, false);
                return xhr;
            },
            success: function(res) {
                btn.prop('disabled', false).show();
                progressContainer.hide();

                if (res.success) {
                    alert('Επιτυχής εισαγωγή! Dataset ID: ' + res.dataset_id);
                    $('#importForm')[0].reset();
                    loadDatasets();
                } else {
                    alert('Σφάλμα: ' + res.error);
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).show();
                progressContainer.hide();
                alert('Σφάλμα: ' + (xhr.responseJSON?.error || xhr.statusText));
            }
        });
    });
});

function loadDatasets() {
    $.get(`${API_BASE}/datasets.php`, function(data) {
        // Check if DataTable already initialized
        let table;
        if ($.fn.DataTable.isDataTable('#datasetsTable')) {
            table = $('#datasetsTable').DataTable();
            table.clear();
        } else {
            table = $('#datasetsTable').DataTable({
                pageLength: 10,
                dom: 'fltip',
                language: {
                    search: "",
                    searchPlaceholder: "Αναζήτηση...",
                    paginate: {
                        previous: "Προηγ.",
                        next: "Επόμ."
                    },
                    info: "Εμφάνιση _START_ έως _END_ από _TOTAL_ εγγραφές",
                    infoEmpty: "Εμφάνιση 0 έως 0 από 0 εγγραφές",
                    infoFiltered: "(φιλτραρισμένο από _MAX_ συνολικά εγγραφές)",
                    lengthMenu: "Εμφάνιση _MENU_ εγγραφών"
                },
                order: [[0, 'desc']] // Sort by ID desc
            });
            table.clear();
        }

        data.forEach(d => {
            const date = new Date(d.created_at).toLocaleString();
            const deleteBtn = `<button class="btn-icon" onclick="deleteDataset(${d.id})" style="color:var(--danger); border-color:var(--danger);">Διαγραφή</button>`;

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
