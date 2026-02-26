// app/js/converter.js

let uploadedFiles = []; // { id, name, server_file, type, group, selected }

$(document).ready(function() {
    setupMultiUpload();
});

function setupMultiUpload() {
    const dropZone = $('#dropZoneMulti');
    const input = $('#fileInputMulti');

    input.on('change', function() {
        if (this.files.length) handleFiles(this.files);
    });

    dropZone.on('dragover', function(e) { e.preventDefault(); $(this).addClass('dragover'); });
    dropZone.on('dragleave drop', function(e) { e.preventDefault(); $(this).removeClass('dragover'); });
    dropZone.on('drop', function(e) {
        if (e.originalEvent.dataTransfer.files.length) handleFiles(e.originalEvent.dataTransfer.files);
    });
}

function handleFiles(fileList) {
    const formData = new FormData();
    for (let i = 0; i < fileList.length; i++) {
        formData.append('files[]', fileList[i]);
    }

    // Show loading?
    $('#dropZoneMulti .upload-text').text('Μεταφόρτωση...');

    $.ajax({
        url: 'api/converter.php?action=upload',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(res) {
            const data = JSON.parse(res);
            data.files.forEach(f => {
                uploadedFiles.push({
                    id: Math.random().toString(36).substr(2, 9),
                    name: f.file_name,
                    server_file: f.server_file,
                    type: f.detected_type,
                    group: '', // User must set
                    status: f.status,
                    selected: true
                });
            });
            renderFileList();
            $('#stagingArea').show();
            $('#actionArea').show();
            $('#dropZoneMulti .upload-text').text('Σύρετε πολλά αρχεία εδώ ή κάντε κλικ');
        },
        error: function(xhr) {
            alert('Upload failed');
        }
    });
}

function renderFileList() {
    const container = $('#fileList');
    container.empty();

    uploadedFiles.forEach(f => {
        const typeBadge = f.type === 'UNKNOWN'
            ? `<span class="badge badge-err">UNKNOWN</span>`
            : `<span class="badge badge-ok">${f.type}</span>`;

        const groupVal = f.group || '';

        const html = `
        <div class="file-card">
            <input type="checkbox" onchange="toggleSelect('${f.id}')" ${f.selected ? 'checked' : ''}>
            <div class="file-info">
                <div class="file-name" title="${f.name}">${f.name}</div>
                <div class="file-type">${typeBadge}</div>
            </div>
            <div style="flex: 2; max-width: 300px;">
                <input type="text" class="form-control"
                    placeholder="Group Label (π.χ. Greece)"
                    value="${groupVal}"
                    onchange="updateGroup('${f.id}', this.value)"
                    style="padding: 0.25rem 0.5rem; font-size: 0.9rem;">
            </div>
             <button class="btn-icon" onclick="removeFile('${f.id}')"><i data-lucide="x"></i></button>
        </div>
        `;
        container.append(html);
    });
    lucide.createIcons();
}

function toggleSelect(id) {
    const f = uploadedFiles.find(x => x.id === id);
    if (f) f.selected = !f.selected;
}

function updateGroup(id, val) {
    const f = uploadedFiles.find(x => x.id === id);
    if (f) f.group = val;
}

function removeFile(id) {
    uploadedFiles = uploadedFiles.filter(x => x.id !== id);
    renderFileList();
    if (uploadedFiles.length === 0) {
        $('#stagingArea').hide();
        $('#actionArea').hide();
    }
}

function clearAllFiles() {
    uploadedFiles = [];
    renderFileList();
    $('#stagingArea').hide();
    $('#actionArea').hide();
}

function applyBulkGroup() {
    const val = $('#bulkGroupInput').val();
    if (!val) return;
    uploadedFiles.forEach(f => {
        if (f.selected) f.group = val;
    });
    renderFileList();
}

function executeMerge() {
    // Group files by group_label
    const groups = {};
    let hasError = false;

    uploadedFiles.forEach(f => {
        if (!f.group) {
            alert(`Το αρχείο "${f.name}" δεν έχει Group Label!`);
            hasError = true;
            return;
        }
        if (!groups[f.group]) groups[f.group] = [];
        groups[f.group].push({
            server_file: f.server_file,
            type: f.type
        });
    });

    if (hasError) return;
    if (Object.keys(groups).length === 0) {
        alert("Δεν υπάρχουν αρχεία προς συγχώνευση.");
        return;
    }

    const payload = {
        groups: Object.keys(groups).map(g => ({
            label: g,
            files: groups[g]
        }))
    };

    $('#btnMerge').prop('disabled', true).text('Επεξεργασία...');

    $.ajax({
        url: 'api/converter.php?action=merge',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(payload),
        success: function(res) {
            const data = JSON.parse(res); // or auto parsed
            if (data.success) {
                $('#mergeStatus').text('Επιτυχία!');
                $('#downloadLinkArea').show();
                $('#packFilename').text(data.filename);
                $('#downloadBtn').attr('href', data.download_url);
            } else {
                alert('Error: ' + data.error);
            }
            $('#btnMerge').prop('disabled', false).html('<i data-lucide="merge"></i> Συγχώνευση & Λήψη Excel');
        },
        error: function(xhr) {
             alert('Merge failed: ' + xhr.responseText);
             $('#btnMerge').prop('disabled', false).html('<i data-lucide="merge"></i> Συγχώνευση & Λήψη Excel');
        }
    });
}
