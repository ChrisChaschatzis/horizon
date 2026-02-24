// app/js/dashboard.js

let chartCoordInstance = null;
let chartGreekInstance = null;
let chartConsortiumInstance = null;

$(document).ready(function() {
    initDatasetSelector(function() {
        // Initial load
        updateDashboard();
    });
});

function updateDashboard() {
    if (!currentDatasetId) return;

    const query = getFilterQuery();

    // 1. KPIs & Data Quality
    $.get(`${API_BASE}/kpis.php?${query}`, function(data) {
        // Core KPIs
        $('#kpiTotal').text(data.total_projects);
        $('#kpiGreekAny').text(data.projects_with_greece_any_role);
        $('#kpiGreekAnyPct').text(data.percent_with_greece_any_role + '%');
        $('#kpiGreekCoord').text(data.projects_with_greek_coordinator);
        $('#kpiGreekCoordPct').text(data.percent_with_greek_coordinator + '%');

        // Data Quality
        $('#dqKeywords').text(data.projects_no_keywords);
        $('#dqPriorities').text(data.projects_no_priorities);
        $('#dqErrors').text(data.projects_with_errors);

        // Overall Status
        if (data.projects_with_errors > 0) {
            $('#kpiStatus').text('Προσοχή').css('color', colors.danger);
        } else if (data.projects_no_keywords > 0 || data.projects_no_priorities > 0) {
            $('#kpiStatus').text('Ελλιπή').css('color', colors.warn);
        } else {
            $('#kpiStatus').text('Καλή').css('color', colors.success);
        }
    });

    // 2. Coordinator Ranking
    $.get(`${API_BASE}/ranking.php?${query}&type=coordinator&top=10`, function(res) {
        renderBarChart('chartCoord', res.labels, res.data, 'Έργα (Πλήθος)');
    });

    // 3. Greek Breakdown
    $.get(`${API_BASE}/greek_breakdown.php?${query}`, function(res) {
        renderDoughnutChart('chartGreek', res.labels, res.data);
    });

    // 4. Consortium Ranking
    $.get(`${API_BASE}/ranking.php?${query}&type=consortium&top=10`, function(res) {
        renderBarChart('chartConsortium', res.labels, res.data, 'Έργα (Πλήθος)');
    });
}

function renderBarChart(canvasId, labels, data, label) {
    const ctx = document.getElementById(canvasId).getContext('2d');

    let instance = null;
    if (canvasId === 'chartCoord') instance = chartCoordInstance;
    if (canvasId === 'chartConsortium') instance = chartConsortiumInstance;

    if (instance) instance.destroy();

    const config = {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: label,
                data: data,
                backgroundColor: colors.accent,
                borderRadius: 4,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: colors.grid, borderColor: colors.muted }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    };

    const chart = new Chart(ctx, config);

    if (canvasId === 'chartCoord') chartCoordInstance = chart;
    if (canvasId === 'chartConsortium') chartConsortiumInstance = chart;
}

function renderDoughnutChart(canvasId, labels, data) {
    const ctx = document.getElementById(canvasId).getContext('2d');

    if (chartGreekInstance) chartGreekInstance.destroy();

    const config = {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [colors.accent, colors.accent2, colors.warn, colors.danger],
                borderColor: colors.bg1,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
                legend: {
                    position: 'right',
                    labels: { color: colors.text }
                }
            }
        }
    };

    chartGreekInstance = new Chart(ctx, config);
}
