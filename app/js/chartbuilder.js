// app/js/chartbuilder.js

let customChart = null;
let dataTable = null;

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
        alert("Please select a dataset first.");
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
            alert("Error building chart: " + (xhr.responseJSON?.error || xhr.statusText));
        }
    });
}

function renderChart(data, type, xDim, yMetric) {
    $('#chartResultCard').show();
    const ctx = document.getElementById('customChart').getContext('2d');

    if (customChart) customChart.destroy();

    // Auto title with rudimentary capitalization
    const formatName = (s) => s.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    const title = `${formatName(yMetric)} by ${formatName(xDim)}`;
    $('#chartTitle').text(title);

    // Color logic
    let bgColors = colors.accent;
    if (type === 'pie' || type === 'doughnut' || type === 'radar') {
        bgColors = colors.palette;
    } else {
        // Bar chart usually one color unless we want rainbow
        bgColors = colors.accent;
    }

    const config = {
        type: type,
        data: {
            labels: data.labels,
            datasets: [{
                label: formatName(yMetric),
                data: data.series,
                backgroundColor: bgColors,
                borderColor: (type === 'line' || type === 'radar') ? colors.accent : colors.bg1,
                borderWidth: 2,
                fill: (type === 'radar' || type === 'line' && false), // line fill false usually
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
                y: { beginAtZero: true, grid: { color: colors.glass2, borderColor: colors.stroke } },
                x: { grid: { display: false } }
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

    // Rebuild header because DataTable destroys it
    // Actually best to just clear data and add rows if columns same
    // But columns are always Label, Value.

    // Wait, if I destroy, I need to recreate HTML structure or let DataTable handle it?
    // Usually destroy keeps the table element but removes functionality.

    // Just reset HTML to be safe
    $('#customTable').html('<thead><tr><th>Label</th><th>Value</th></tr></thead><tbody></tbody>');

    const rows = data.labels.map((lbl, i) => [lbl, data.series[i]]);

    dataTable = $('#customTable').DataTable({
        data: rows,
        columns: [
            { title: "Label" },
            { title: "Value" }
        ],
        pageLength: 10,
        dom: 'fltip',
        language: {
            search: "",
            searchPlaceholder: "Search...",
            paginate: {
                previous: "Prev",
                next: "Next"
            }
        }
    });
}
