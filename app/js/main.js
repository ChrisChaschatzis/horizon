// app/js/main.js

const API_BASE = 'api';
let currentDatasetId = null;
let activeFilters = {
    only_greek_any_role: false,
    only_greek_coordinator: false,
    exclude_error: false
};

// Colors
const colors = {
    bg1: '#0B1220',
    text: '#EAF0FF',
    accent: '#5EEAD4',
    accent2: '#60A5FA',
    warn: '#FBBF24',
    muted: 'rgba(234, 240, 255, 0.7)',
    glass2: 'rgba(255, 255, 255, 0.12)',
    palette: [
        '#5EEAD4', '#60A5FA', '#FBBF24', '#FB7185', '#A78BFA',
        '#34D399', '#818CF8', '#F472B6', '#FCD34D', '#2DD4BF'
    ]
};

// Chart.js defaults
Chart.defaults.color = colors.muted;
Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.1)';
Chart.defaults.font.family = "'Inter', system-ui, sans-serif";

function initDatasetSelector(callback) {
    $.get(`${API_BASE}/datasets.php`, function(data) {
        const sel = $('#datasetSelector');
        sel.empty();
        if (data.length === 0) {
            sel.append('<option value="">Δεν βρέθηκαν datasets</option>');
            return;
        }

        data.forEach(d => {
            sel.append(`<option value="${d.id}" data-date="${d.created_at}">${d.name}</option>`);
        });

        // Select first by default
        currentDatasetId = data[0].id;
        sel.val(currentDatasetId);
        updateLastUpdated(data[0].created_at);

        // Callback to load dashboard
        if (callback) callback();

        // Change event
        sel.on('change', function() {
            currentDatasetId = $(this).val();
            const date = $(this).find(':selected').data('date');
            updateLastUpdated(date);
            triggerRefresh();
        });
    });
}

function updateLastUpdated(date) {
    if (date) {
        $('#lastUpdated').text('Ενημερώθηκε: ' + new Date(date).toLocaleString());
    } else {
        $('#lastUpdated').text('');
    }
}

function toggleFilter(key) {
    activeFilters[key] = !activeFilters[key];

    // Update UI
    const btnId = {
        'only_greek_any_role': 'filterGreekAny',
        'only_greek_coordinator': 'filterGreekCoord',
        'exclude_error': 'filterNoErrors'
    }[key];

    if (activeFilters[key]) {
        $(`#${btnId}`).addClass('active');
    } else {
        $(`#${btnId}`).removeClass('active');
    }

    triggerRefresh();
}

function resetFilters() {
    activeFilters = {
        only_greek_any_role: false,
        only_greek_coordinator: false,
        exclude_error: false
    };
    $('.filter-btn').removeClass('active');
    triggerRefresh();
}

function triggerRefresh() {
    // Dispatch event or call global update function
    if (typeof updateDashboard === 'function') {
        updateDashboard();
    }
}

function getFilterQuery() {
    let q = `dataset_id=${currentDatasetId}`;
    for (const [key, val] of Object.entries(activeFilters)) {
        if (val) q += `&${key}=true`;
    }
    return q;
}

function exportChart(canvasId, filename) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    // Create a temporary link
    const link = document.createElement('a');
    link.download = (filename || 'chart') + '.png';

    // Simple export (transparent)
    link.href = canvas.toDataURL('image/png');
    link.click();
}
