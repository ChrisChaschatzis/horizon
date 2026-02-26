// app/js/dashboard.js

let chartCoordInstance = null;
let chartGreekInstance = null;
let chartConsortiumInstance = null;

// Summary Charts
let chartSumPillar = null;
let chartSumProgPart = null;
let chartSumProgEu = null;
let chartSumMission = null;

$(document).ready(function() {
    initDatasetSelector(function() {
        updateDashboard();
    });
});

function updateDashboard() {
    if (!currentDatasetId) return;

    if (currentDatasetType === 'summary') {
        updateSummaryDashboard();
        $('#projects-view').hide();
        $('#summary-view').show();
    } else {
        updateProjectsDashboard();
        $('#projects-view').show();
        $('#summary-view').hide();
    }
}

// --- STANDARD DASHBOARD ---
function updateProjectsDashboard() {
    const query = getFilterQuery();

    $.get(`${API_BASE}/kpis.php?${query}`, function(data) {
        $('#kpiTotal').text(data.total_projects);
        $('#kpiGreekAny').text(data.projects_with_greece_any_role);
        $('#kpiGreekAnyPct').text(data.percent_with_greece_any_role + '%');
        $('#kpiGreekCoord').text(data.projects_with_greek_coordinator);
        $('#kpiGreekCoordPct').text(data.percent_with_greek_coordinator + '%');

        if (data.projects_with_errors > 0) {
            $('#kpiStatus').text('Προσοχή').css('color', colors.danger);
        } else if (data.projects_no_keywords > 0 || data.projects_no_priorities > 0) {
            $('#kpiStatus').text('Ελλιπή').css('color', colors.warn);
        } else {
            $('#kpiStatus').text('Καλή').css('color', colors.success);
        }
    });

    $.get(`${API_BASE}/ranking.php?${query}&type=coordinator&top=10`, function(res) {
        renderBarChart('chartCoord', res.labels, res.data, 'Έργα (Πλήθος)');
    });

    $.get(`${API_BASE}/greek_breakdown.php?${query}`, function(res) {
        renderDoughnutChart('chartGreek', res.labels, res.data);
    });

    $.get(`${API_BASE}/ranking.php?${query}&type=consortium&top=10`, function(res) {
        renderBarChart('chartConsortium', res.labels, res.data, 'Έργα (Πλήθος)');
    });
}

// --- SUMMARY DASHBOARD ---
function updateSummaryDashboard() {
    const query = getFilterQuery();

    // KPIs
    $.get(`${API_BASE}/summary.php?action=kpis&${query}`, function(data) {
        $('#summKpiPart').text(data.total_participation);
        $('#summKpiEu').text(data.total_eu_contribution);
        $('#summKpiGroups').text(data.group_count);
    });

    // Charts
    // 1. Pillar
    $.get(`${API_BASE}/summary.php?action=chart&type=pillar&${query}`, function(res) {
        renderGroupedBar('chartSumPillar', res.labels, res.datasets, 'Participations');
    });
    // 2. Prog Part
    $.get(`${API_BASE}/summary.php?action=chart&type=prog_part&${query}`, function(res) {
        renderGroupedBar('chartSumProgPart', res.labels, res.datasets, 'Participations');
    });
    // 3. Prog EU
    $.get(`${API_BASE}/summary.php?action=chart&type=prog_eu&${query}`, function(res) {
        renderGroupedBar('chartSumProgEu', res.labels, res.datasets, 'EU Contribution (€)');
    });
    // 4. Mission EU
    $.get(`${API_BASE}/summary.php?action=chart&type=mission_eu&${query}`, function(res) {
        renderGroupedBar('chartSumMission', res.labels, res.datasets, 'EU Contribution (€)');
    });
}

function renderBarChart(canvasId, labels, data, label) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    if (canvasId === 'chartCoord' && chartCoordInstance) chartCoordInstance.destroy();
    if (canvasId === 'chartConsortium' && chartConsortiumInstance) chartConsortiumInstance.destroy();

    const config = {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: label,
                data: data,
                backgroundColor: colors.accent,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: colors.grid, borderColor: colors.muted } },
                x: { grid: { display: false } }
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
            plugins: { legend: { position: 'right', labels: { color: colors.text } } }
        }
    };
    chartGreekInstance = new Chart(ctx, config);
}

function renderGroupedBar(canvasId, labels, datasets, yLabel) {
    const ctx = document.getElementById(canvasId).getContext('2d');

    // Manage instances
    if (canvasId === 'chartSumPillar' && chartSumPillar) chartSumPillar.destroy();
    if (canvasId === 'chartSumProgPart' && chartSumProgPart) chartSumProgPart.destroy();
    if (canvasId === 'chartSumProgEu' && chartSumProgEu) chartSumProgEu.destroy();
    if (canvasId === 'chartSumMission' && chartSumMission) chartSumMission.destroy();

    // Assign colors from palette
    datasets.forEach((ds, i) => {
        ds.backgroundColor = colors.palette[i % colors.palette.length];
        ds.borderRadius = 4;
    });

    const config = {
        type: 'bar',
        data: {
            labels: labels,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: { color: colors.text }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: colors.grid, borderColor: colors.muted },
                    title: { display: true, text: yLabel, color: colors.muted }
                },
                x: { grid: { display: false } }
            }
        }
    };

    const chart = new Chart(ctx, config);

    if (canvasId === 'chartSumPillar') chartSumPillar = chart;
    if (canvasId === 'chartSumProgPart') chartSumProgPart = chart;
    if (canvasId === 'chartSumProgEu') chartSumProgEu = chart;
    if (canvasId === 'chartSumMission') chartSumMission = chart;
}
