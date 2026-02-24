// app/js/main.js

const API_BASE = 'api';
let currentDatasetId = null;
let activeFilters = {
    only_greek_any_role: false,
    only_greek_coordinator: false,
    exclude_error: false
};

// Colors matching CSS
const colors = {
    bg1: '#0f172a',
    bg2: '#1e293b',
    text: '#f8fafc',
    muted: '#94a3b8',
    accent: '#38bdf8',
    accent2: '#818cf8',
    warn: '#fbbf24',
    danger: '#f87171',
    success: '#4ade80',
    grid: 'rgba(255, 255, 255, 0.1)',
    palette: [
        '#38bdf8', '#818cf8', '#fbbf24', '#f87171', '#a78bfa',
        '#34d399', '#f472b6', '#fcd34d', '#2dd4bf', '#60a5fa'
    ]
};

// Chart.js defaults
Chart.defaults.color = colors.muted;
Chart.defaults.borderColor = colors.grid;
Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15, 23, 42, 0.9)';
Chart.defaults.plugins.tooltip.borderColor = 'rgba(255, 255, 255, 0.1)';
Chart.defaults.plugins.tooltip.borderWidth = 1;
Chart.defaults.plugins.tooltip.padding = 10;
Chart.defaults.plugins.tooltip.titleColor = colors.text;
Chart.defaults.plugins.tooltip.bodyColor = colors.muted;

// DataTables Greek Language
const datatableGreek = {
    "search": "Αναζήτηση:",
    "lengthMenu": "Εμφάνιση _MENU_ εγγραφών",
    "info": "Εμφάνιση _START_ έως _END_ από _TOTAL_ εγγραφές",
    "infoEmpty": "Εμφάνιση 0 έως 0 από 0 εγγραφές",
    "infoFiltered": "(φιλτραρισμένο από _MAX_ συνολικά εγγραφές)",
    "emptyTable": "Δεν βρέθηκαν δεδομένα",
    "paginate": {
        "first": "Πρώτη",
        "last": "Τελευταία",
        "next": "Επόμενη",
        "previous": "Προηγούμενη"
    },
    "processing": "Επεξεργασία..."
};

$(document).ready(function() {
    // Mobile Menu Toggle
    $('.mobile-menu-btn').on('click', function() {
        $('.sidebar').toggleClass('open');
    });

    // Close sidebar when clicking outside on mobile
    $(document).on('click', function(e) {
        if ($(window).width() <= 1024) {
            if (!$(e.target).closest('.sidebar').length && !$(e.target).closest('.mobile-menu-btn').length) {
                $('.sidebar').removeClass('open');
            }
        }
    });
});

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
        $('#lastUpdated').text('Ενημερώθηκε: ' + new Date(date).toLocaleString('el-GR'));
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

// Chart Export with White Background and Title
function exportChart(canvasId, filename) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    // Create a dummy canvas to draw white bg and title
    const tempCanvas = document.createElement('canvas');
    const ctx = tempCanvas.getContext('2d');

    // Set dimensions
    tempCanvas.width = canvas.width;
    tempCanvas.height = canvas.height + 50; // Extra space for title

    // Fill white background
    ctx.fillStyle = '#FFFFFF';
    ctx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);

    // Draw Title (Need to fetch from DOM or Chart object? Easier from DOM if strictly requested "Title visibly drawn")
    // Find title element in .chart-header .chart-title relative to canvas
    // Or just pass title as arg?
    // Let's look for sibling h4
    const titleEl = $(canvas).closest('.chart-card').find('.chart-title');
    const titleText = titleEl.text() || filename.replace(/_/g, ' ');

    ctx.fillStyle = '#000000';
    ctx.font = 'bold 20px Inter, sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText(titleText, tempCanvas.width / 2, 35);

    // Draw original chart
    ctx.drawImage(canvas, 0, 50);

    // Create link
    const link = document.createElement('a');
    link.download = (filename || 'chart') + '.png';
    link.href = tempCanvas.toDataURL('image/png');
    link.click();
}
