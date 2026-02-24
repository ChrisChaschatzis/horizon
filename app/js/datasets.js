// app/js/datasets.js

$(document).ready(function() {
    loadDatasets();

    // Form Submit
    $('#importForm').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const btn = $('#btnImport');
        const status = $('#importStatus');

        btn.prop('disabled', true).text('Importing...');
        status.text('Uploading and processing... This may take a while.');

        $.ajax({
            url: `${API_BASE}/import.php`,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(res) {
                btn.prop('disabled', false).text('Import Dataset');
                status.text('Import successful! Dataset ID: ' + res.dataset_id);
                $('#importForm')[0].reset();
                loadDatasets();
            },
            error: function(xhr) {
                btn.prop('disabled', false).text('Import Dataset');
                status.text('Error: ' + (xhr.responseJSON?.error || xhr.statusText));
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
                    searchPlaceholder: "Search..."
                },
                order: [[0, 'desc']] // Sort by ID desc
            });
            table.clear();
        }

        data.forEach(d => {
            const date = new Date(d.created_at).toLocaleString();
            const deleteBtn = `<button class="btn-icon" onclick="deleteDataset(${d.id})" style="color:var(--danger); border-color:var(--danger);">Delete</button>`;

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
    if (!confirm("Are you sure you want to delete this dataset? This cannot be undone.")) return;

    $.ajax({
        url: `${API_BASE}/datasets.php?id=${id}`,
        type: 'DELETE',
        success: function() {
            loadDatasets();
        },
        error: function(xhr) {
            alert("Error deleting dataset: " + (xhr.responseJSON?.error || xhr.statusText));
        }
    });
}
