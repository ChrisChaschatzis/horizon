// app/js/analytics.js

let chartKeywords = null;
let chartFields = null;
let chartPriorities = null;

$(document).ready(function() {
    initDatasetSelector(updateAnalytics);
});

function updateAnalytics() {
    if (!currentDatasetId) return;

    const query = getFilterQuery();

    // 1. Keywords
    $.get(`${API_BASE}/keywords.php?${query}&top=15`, function(data) {
        const labels = data.map(d => d.keyword);
        const values = data.map(d => d.count);
        renderBarChart('chartKeywords', labels, values, 'Κορυφαίες Λέξεις-Κλειδιά', chartKeywords, (c) => chartKeywords = c, true);
    });

    // 2. Fields of Science
    $.get(`${API_BASE}/fields_of_science.php?${query}&level=1`, function(data) {
        const labels = data.map(d => d.field);
        const values = data.map(d => d.count);
        renderBarChart('chartFields', labels, values, 'Επιστημονικά Πεδία', chartFields, (c) => chartFields = c, true);
    });

    // 3. Investment Priorities
    $.get(`${API_BASE}/invest_priorities.php?${query}`, function(data) {
        const labels = data.map(d => d.label);
        // round to 1 decimal
        const values = data.map(d => parseFloat(d.avg_percent).toFixed(1));
        renderBarChart('chartPriorities', labels, values, 'Μ.Ο. % Προτεραιότητας', chartPriorities, (c) => chartPriorities = c, false);
    });
}

function renderBarChart(canvasId, labels, data, label, instance, setInstance, horizontal = false) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    if (instance) instance.destroy();

    const config = {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: label,
                data: data,
                backgroundColor: colors.accent2,
                borderRadius: 4,
                borderSkipped: false
            }]
        },
        options: {
            indexAxis: horizontal ? 'y' : 'x',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: { color: colors.glass2, borderColor: colors.stroke }
                },
                y: {
                    grid: { display: false } // clean look
                }
            }
        }
    };

    const chart = new Chart(ctx, config);
    setInstance(chart);
}
