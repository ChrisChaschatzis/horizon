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

    // Metrics
    'count': 'Πλήθος Έργων',
    'sum_eu_contribution': 'Σύνολο Συνεισφοράς ΕΕ (€)',
    'average_invest_priority': 'Μ.Ο. % Προτεραιότητας'
};

$(document).ready(function() {
    initDatasetSelector();
});

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

    // Color logic
    let bgColors = colors.accent;
    if (type === 'pie' || type === 'doughnut' || type === 'radar') {
        bgColors = colors.palette;
    } else {
        bgColors = colors.accent;
    }

    const config = {
        type: type,
        data: {
            labels: data.labels,
            datasets: [{
                label: yLabel,
                data: data.series,
                backgroundColor: bgColors,
                borderColor: (type === 'line' || type === 'radar') ? colors.accent : colors.bg1,
                borderWidth: 2,
                fill: (type === 'radar' || type === 'line' && false),
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: (type === 'pie' || type === 'doughnut' || type === 'radar'),
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

    $('#customTable').html('<thead><tr><th>Ετικέτα</th><th>Τιμή</th></tr></thead><tbody></tbody>');

    const rows = data.labels.map((lbl, i) => [lbl, data.series[i]]);

    dataTable = $('#customTable').DataTable({
        data: rows,
        columns: [
            { title: "Ετικέτα" },
            { title: "Τιμή" }
        ],
        pageLength: 10,
        dom: 'fltip',
        language: datatableGreek
    });
}
