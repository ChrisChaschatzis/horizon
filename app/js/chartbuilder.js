// app/js/chartbuilder.js

let customChart = null;
let dataTable = null;

const dict = {
    // Dimensions
    'coordinator_country': 'Χώρα Συντονιστή',
    'consortium_country': 'Χώρα Κοινοπραξίας',
    'fields_of_science': 'Επιστημονικό Πεδίο',
    'keyword': 'Λέξη-Κλειδί',
    'invest_priority': 'Επενδυτική Προτεραιότητα',
    'pillar': 'Πρόγραμμα / Pillar',
    'type_of_action': 'Τύπος Δράσης',
    'year': 'Έτος',
    'has_greek_any_role': 'Ελληνικός Ρόλος',
    'is_greek_coordinator': 'Έλληνας Συντονιστής',

    // Summary Dimensions
    'framework_programme': 'Πρόγραμμα',
    'mission': 'Αποστολή (Mission)',
    'pillar_descr': 'Pillar',
    'group_label': 'Group / Entity',

    // Metrics
    'count': 'Πλήθος Έργων',
    'sum_eu_contribution': 'Σύνολο Συνεισφοράς ΕΕ (€)',
    'average_invest_priority': 'Μ.Ο. % Προτεραιότητας',

    // Summary Metrics
    'participation': 'Συμμετοχές'
};

$(document).ready(function() {
    initDatasetSelector(function() {
        populateOptions();
        updateDashboard();
    });

    // Listen for dataset type changes (handled in initDatasetSelector change event)
    $('#datasetSelector').on('change', function() {
        populateOptions();
    });
});

function populateOptions() {
    const xSel = $('#cbX');
    const ySel = $('#cbY');
    xSel.empty();
    ySel.empty();

    if (currentDatasetType === 'summary') {
        // Summary Options
        xSel.append(new Option('Pillar', 'pillar_descr'));
        xSel.append(new Option('Framework Programme', 'framework_programme'));
        xSel.append(new Option('Mission', 'mission'));
        // xSel.append(new Option('Group', 'group_label')); // Usually series

        ySel.append(new Option('Participations', 'participation'));
        ySel.append(new Option('EU Contribution (€)', 'sum_eu_contribution'));
    } else {
        // Project Options
        xSel.append(new Option('Χώρα Συντονιστή', 'coordinator_country'));
        xSel.append(new Option('Χώρα Κοινοπραξίας', 'consortium_country'));
        xSel.append(new Option('Επιστημονικό Πεδίο', 'fields_of_science'));
        xSel.append(new Option('Λέξη-Κλειδί', 'keyword'));
        xSel.append(new Option('Επενδυτική Προτεραιότητα', 'invest_priority'));
        xSel.append(new Option('Πρόγραμμα / Pillar', 'pillar'));
        xSel.append(new Option('Τύπος Δράσης', 'type_of_action'));
        xSel.append(new Option('Έτος', 'year'));
        xSel.append(new Option('Ελληνικός Ρόλος (Ναι/Όχι)', 'has_greek_any_role'));
        xSel.append(new Option('Έλληνας Συντονιστής (Ναι/Όχι)', 'is_greek_coordinator'));

        ySel.append(new Option('Πλήθος Έργων', 'count'));
        ySel.append(new Option('Σύνολο Συνεισφοράς ΕΕ', 'sum_eu_contribution'));
        ySel.append(new Option('Μ.Ο. % Επενδυτικής Προτεραιότητας', 'average_invest_priority'));
    }
}

function updateDashboard() {
    // Refresh chart if already built
    if (customChart) {
        buildChart();
    }
}

function buildChart() {
    if (!currentDatasetId) {
        alert("Παρακαλώ επιλέξτε ένα dataset πρώτα.");
        return;
    }

    const type = $('#cbChartType').val();
    const xDim = $('#cbX').val();
    const yMetric = $('#cbY').val();
    const topN = $('#cbTop').val();

    const payload = {
        dataset_id: currentDatasetId,
        dataset_type: currentDatasetType,
        x_dimension: xDim,
        y_metric: yMetric,
        chart_type: type,
        top_n: topN,
        filters: activeFilters
    };

    $.ajax({
        url: `${API_BASE}/chartbuilder.php`,
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(payload),
        success: function(res) {
            renderChart(res, type, xDim, yMetric);
            renderTable(res);
        },
        error: function(xhr) {
            console.error(xhr);
            alert("Σφάλμα δημιουργίας γραφήματος: " + (xhr.responseJSON?.error || xhr.statusText));
        }
    });
}

function renderChart(data, type, xDim, yMetric) {
    $('#chartResultCard').show();
    const ctx = document.getElementById('customChart').getContext('2d');

    if (customChart) customChart.destroy();

    const xLabel = dict[xDim] || xDim;
    const yLabel = dict[yMetric] || yMetric;
    const title = `${yLabel} ανά ${xLabel}`;

    $('#chartTitle').text(title);

    // Check if we have multiple datasets (grouped/series)
    // Current API response structure for PROJECTS is { labels: [], series: [] } (series is array of numbers)
    // For SUMMARY we might want { labels: [], datasets: [{label:'Group1', data:[]}, ...] }
    // Let's standardize API response to always be chart.js compatible if possible, or handle both.

    let chartData = {};

    // If response has 'datasets' key, it's multi-series (Grouped)
    if (data.datasets) {
        // Assign colors
        data.datasets.forEach((ds, i) => {
            ds.backgroundColor = colors.palette[i % colors.palette.length];
            ds.borderColor = colors.bg1;
        });

        chartData = {
            labels: data.labels,
            datasets: data.datasets
        };
    } else {
        // Single series
        let bgColors = colors.accent;
        if (type === 'pie' || type === 'doughnut' || type === 'radar') {
            bgColors = colors.palette;
        }

        chartData = {
            labels: data.labels,
            datasets: [{
                label: yLabel,
                data: data.series,
                backgroundColor: bgColors,
                borderColor: (type === 'line' || type === 'radar') ? colors.accent : colors.bg1,
                borderWidth: 2,
                fill: (type === 'radar' || (type === 'line' && false)),
                tension: 0.3
            }]
        };
    }

    const config = {
        type: type,
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true, // Always show legend for potential groups
                    position: 'right',
                    labels: { color: colors.text }
                }
            },
            scales: (type === 'pie' || type === 'doughnut' || type === 'radar') ? {} : {
                y: {
                    beginAtZero: true,
                    grid: { color: colors.glass2, borderColor: colors.muted },
                    title: { display: true, text: yLabel, color: colors.muted }
                },
                x: {
                    grid: { display: false },
                    title: { display: true, text: xLabel, color: colors.muted }
                }
            }
        }
    };

    customChart = new Chart(ctx, config);
}

function renderTable(data) {
    $('#tableResultCard').show();

    if (dataTable) {
        dataTable.destroy();
        $('#customTable').empty();
    }

    // Determine columns
    let columns = [{ title: "Ετικέτα" }];
    let rows = [];

    if (data.datasets) {
        // Multi-series
        // Columns: Label, Group1, Group2...
        data.datasets.forEach(ds => {
            columns.push({ title: ds.label });
        });

        // Rows
        data.labels.forEach((lbl, i) => {
            let row = [lbl];
            data.datasets.forEach(ds => {
                row.push(ds.data[i]);
            });
            rows.push(row);
        });

    } else {
        // Single series
        columns.push({ title: "Τιμή" });
        rows = data.labels.map((lbl, i) => [lbl, data.series[i]]);
    }

    // Build Header
    let thead = '<thead><tr>';
    columns.forEach(c => thead += `<th>${c.title}</th>`);
    thead += '</tr></thead><tbody></tbody>';

    $('#customTable').html(thead);

    dataTable = $('#customTable').DataTable({
        data: rows,
        columns: columns.map((_, i) => ({ targets: i })), // Just index mapping
        pageLength: 10,
        dom: 'fltip',
        language: datatableGreek
    });
}
