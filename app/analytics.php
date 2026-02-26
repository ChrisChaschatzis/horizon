<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horizon Europe BI - Αναλύσεις</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <!-- Main Style -->
    <link rel="stylesheet" href="css/style.css">

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        .tab-btn {
            background: none;
            border: none;
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-secondary);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }
        .tab-btn:hover {
            color: var(--primary-color);
        }
        .tab-btn.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .hidden { display: none !important; }

        /* DataTable styling tweaks for glassmorphism */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_processing,
        .dataTables_wrapper .dataTables_paginate {
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }
        table.dataTable {
            width: 100% !important;
            border-collapse: collapse !important;
        }
        table.dataTable thead th {
            border-bottom: 1px solid var(--border-color) !important;
            color: var(--text-primary);
        }
        table.dataTable tbody td {
            border-bottom: 1px solid var(--border-color) !important;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="logo">
                <i data-lucide="bar-chart-2"></i> CORDIS BI
            </div>
            <nav>
                <a href="index.php" class="nav-link">
                    <i data-lucide="layout-dashboard"></i>
                    <span>Σύνοψη</span>
                </a>
                <a href="analytics.php" class="nav-link active">
                    <i data-lucide="pie-chart"></i>
                    <span>Αναλύσεις</span>
                </a>
                <a href="converter.php" class="nav-link">
                    <i data-lucide="arrow-left-right"></i>
                    <span>Μετατροπέας</span>
                </a>
                <a href="chartbuilder.php" class="nav-link">
                    <i data-lucide="line-chart"></i>
                    <span>Δημιουργία Γραφημάτων</span>
                </a>
                <a href="datasets.php" class="nav-link">
                    <i data-lucide="database"></i>
                    <span>Διαχείριση Δεδομένων</span>
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <button class="mobile-menu-btn"><i data-lucide="menu"></i></button>
                <div class="filters-bar">
                    <select id="datasetSelector" class="dataset-selector">
                        <option value="">Φόρτωση...</option>
                    </select>

                    <!-- Standard Project Filters -->
                    <div id="projectFilters" class="filter-group" style="display:flex; gap:0.5rem;">
                        <button class="filter-btn" id="filterGreekAny" onclick="toggleFilter('only_greek_any_role')">
                            🇬🇷 Ελληνική Συμμετοχή
                        </button>
                        <button class="filter-btn" id="filterGreekCoord" onclick="toggleFilter('only_greek_coordinator')">
                            🇬🇷 Έλληνας Συντονιστής
                        </button>
                    </div>

                    <!-- Summary Filters -->
                    <div id="summaryFilters" class="filter-group hidden">
                        <!-- Entity Multi-Select could go here if implemented, for now handled globally via dataset context -->
                    </div>

                    <button class="filter-btn" onclick="window.location.reload()">
                        <i data-lucide="rotate-ccw" style="width:16px;height:16px;"></i>
                    </button>
                </div>
            </div>

            <!-- PROJECTS VIEW -->
            <div id="viewProjects" class="view-section">
                <div class="charts-grid">
                    <!-- Top Keywords -->
                    <div class="card chart-card">
                        <div class="chart-header">
                            <h4 class="chart-title">Δημοφιλέστερες Λέξεις-Κλειδιά</h4>
                            <div class="chart-actions">
                                 <button class="btn-icon" onclick="exportChart('chartKeywords', 'Top_Keywords')">
                                    <i data-lucide="camera"></i>
                                 </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="chartKeywords"></canvas>
                        </div>
                    </div>

                    <!-- Fields of Science -->
                    <div class="card chart-card">
                        <div class="chart-header">
                            <h4 class="chart-title">Επιστημονικά Πεδία</h4>
                             <div class="chart-actions">
                                 <button class="btn-icon" onclick="exportChart('chartFields', 'Fields_Science')">
                                    <i data-lucide="camera"></i>
                                 </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="chartFields"></canvas>
                        </div>
                    </div>

                    <!-- Investment Priorities -->
                    <div class="card chart-card full-width">
                        <div class="chart-header">
                            <h4 class="chart-title">Επενδυτικές Προτεραιότητες Ε.Ε.</h4>
                             <div class="chart-actions">
                                 <button class="btn-icon" onclick="exportChart('chartPriorities', 'Invest_Priorities')">
                                    <i data-lucide="camera"></i>
                                 </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="chartPriorities"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SUMMARY VIEW -->
            <div id="viewSummary" class="view-section hidden">
                <div class="tabs">
                    <button class="tab-btn active" onclick="switchTab('pillar')">Pillar Analysis</button>
                    <button class="tab-btn" onclick="switchTab('programme')">Programme Analysis</button>
                    <button class="tab-btn" onclick="switchTab('mission')">Mission Analysis</button>
                </div>

                <!-- Pillar Tab -->
                <div id="tab-pillar" class="tab-content active">
                    <div class="card">
                        <div class="chart-header">
                            <h4 class="chart-title">Pillar Participation Data</h4>
                        </div>
                        <div style="padding: 1rem;">
                            <table id="tablePillar" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Group/Entity</th>
                                        <th>Pillar</th>
                                        <th>Participation</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card chart-card full-width" style="margin-top:1rem;">
                        <div class="chart-container">
                            <canvas id="chartPillarSummary"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Programme Tab -->
                <div id="tab-programme" class="tab-content">
                    <div class="card">
                         <div class="chart-header">
                            <h4 class="chart-title">Programme Data (Participation & EU Contribution)</h4>
                        </div>
                        <div style="padding: 1rem;">
                            <table id="tableProgramme" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Group/Entity</th>
                                        <th>Programme</th>
                                        <th>Participation</th>
                                        <th>EU Contribution (€)</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="charts-grid" style="margin-top:1rem;">
                        <div class="card chart-card">
                             <h4 class="chart-title" style="padding:1rem;">Participation</h4>
                             <div class="chart-container"><canvas id="chartProgPartSummary"></canvas></div>
                        </div>
                        <div class="card chart-card">
                             <h4 class="chart-title" style="padding:1rem;">EU Contribution</h4>
                             <div class="chart-container"><canvas id="chartProgEuSummary"></canvas></div>
                        </div>
                    </div>
                </div>

                <!-- Mission Tab -->
                <div id="tab-mission" class="tab-content">
                    <div class="card">
                        <div class="chart-header">
                            <h4 class="chart-title">Mission EU Contribution Data</h4>
                        </div>
                        <div style="padding: 1rem;">
                            <table id="tableMission" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Group/Entity</th>
                                        <th>Mission</th>
                                        <th>EU Contribution (€)</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card chart-card full-width" style="margin-top:1rem;">
                        <div class="chart-container">
                            <canvas id="chartMissionSummary"></canvas>
                        </div>
                    </div>
                </div>

            </div>

        </main>
    </div>

    <!-- Shared Main JS -->
    <script src="js/main.js"></script>

    <!-- Original Analytics Logic for Projects (loaded first, but we will override initDatasetSelector behavior slightly) -->
    <!-- Actually, let's load it but control execution via checks or event listeners -->
    <script src="js/analytics.js"></script>

    <!-- Specific Analytics JS -->
    <script>
        // Global State for Analytics
        // We need to redefine/shim updateAnalytics from js/analytics.js so it doesn't run on summary datasets
        // Or we hijack the flow.

        const originalUpdateAnalytics = window.updateAnalytics; // Capture if exists

        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();

            const selector = document.getElementById('datasetSelector');

            // We need to intercept the change event handled by js/analytics.js or main.js
            // Since we can't easily remove anonymous listeners, we add our own that runs logic

            selector.addEventListener('change', (e) => {
                const opt = selector.options[selector.selectedIndex];
                if(opt) {
                    const type = opt.getAttribute('data-type') || 'projects';
                    handleDatasetChange(selector.value, type);
                }
            });

            // Initial load check
            // We need to wait for main.js to fetch datasets and populate the selector
            // main.js usually triggers 'change' or calls a callback.
            // Let's use a MutationObserver on the selector options?
            const observer = new MutationObserver((mutations) => {
                if (selector.options.length > 1 && selector.value) {
                     const opt = selector.options[selector.selectedIndex];
                     const type = opt.getAttribute('data-type') || 'projects';
                     handleDatasetChange(selector.value, type);
                     observer.disconnect(); // Once loaded
                }
            });
            observer.observe(selector, { childList: true });
        });

        function handleDatasetChange(datasetId, type) {
            console.log("Analytics Page: Dataset type detected:", type);

            if (type === 'summary') {
                document.getElementById('viewProjects').classList.add('hidden');
                document.getElementById('viewSummary').classList.remove('hidden');
                document.getElementById('projectFilters').classList.add('hidden');
                document.getElementById('summaryFilters').classList.remove('hidden');
                loadSummaryData(datasetId);
            } else {
                document.getElementById('viewProjects').classList.remove('hidden');
                document.getElementById('viewSummary').classList.add('hidden');
                document.getElementById('projectFilters').classList.remove('hidden');
                document.getElementById('summaryFilters').classList.add('hidden');

                // Trigger standard analytics update if we are in project mode
                // js/analytics.js listens to initDatasetSelector which callbacks updateAnalytics
                // If we are here, main.js might have triggered it already, or we need to manually call it.
                if (typeof updateAnalytics === 'function') {
                    // Update global var expected by analytics.js if needed (it usually reads selector value)
                    updateAnalytics();
                }
            }
        }

        window.switchTab = function(tabName) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));

            document.getElementById('tab-' + tabName).classList.add('active');
            // Find button by text or order
            const btns = document.querySelectorAll('.tab-btn');
            if(tabName === 'pillar') btns[0].classList.add('active');
            if(tabName === 'programme') btns[1].classList.add('active');
            if(tabName === 'mission') btns[2].classList.add('active');
        }

        let summaryChartsMap = {};

        async function loadSummaryData(datasetId) {
            // Helper to init DataTable if not exists
            const initTable = (id, columns) => {
                if ($.fn.DataTable.isDataTable('#' + id)) {
                    $('#' + id).DataTable().destroy();
                }
                return $('#' + id).DataTable({
                    dom: 'fltip',
                    columns: columns,
                    pageLength: 10,
                    language: {
                        search: "Αναζήτηση:",
                        lengthMenu: "Εμφάνιση _MENU_ εγγραφών",
                        info: "Εμφάνιση _START_ έως _END_ από _TOTAL_ εγγραφές",
                        paginate: { first: "First", last: "Last", next: "Next", previous: "Previous" }
                    }
                });
            };

            try {
                // 1. Pillar Data
                const resPillar = await fetch(`api/summary.php?action=chart&dataset_id=${datasetId}&type=pillar`);
                const dataPillar = await resPillar.json();
                let rowsPillar = [];
                if(dataPillar.datasets) {
                    dataPillar.datasets.forEach(ds => {
                        ds.data.forEach((val, idx) => {
                            if(val > 0) rowsPillar.push([ds.label, dataPillar.labels[idx], val]);
                        });
                    });
                }
                const tPillar = initTable('tablePillar', [{title:'Group'}, {title:'Pillar'}, {title:'Participation'}]);
                tPillar.clear().rows.add(rowsPillar).draw();
                renderSummaryChart('chartPillarSummary', dataPillar, 'bar');

                // 2. Programme Data
                const resProgPart = await fetch(`api/summary.php?action=chart&dataset_id=${datasetId}&type=prog_part`);
                const dataProgPart = await resProgPart.json();
                const resProgEu = await fetch(`api/summary.php?action=chart&dataset_id=${datasetId}&type=prog_eu`);
                const dataProgEu = await resProgEu.json();

                let rowsProg = [];
                if(dataProgPart.datasets) {
                    dataProgPart.datasets.forEach(dsPart => {
                        const group = dsPart.label;
                        const dsEu = dataProgEu.datasets ? dataProgEu.datasets.find(d => d.label === group) : null;

                        dsPart.data.forEach((val, idx) => {
                            const prog = dataProgPart.labels[idx];
                            let euVal = 0;
                            if (dsEu) {
                                // Find index of this prog in EU labels (labels might be sorted same, but safer to find)
                                const euIdx = dataProgEu.labels.indexOf(prog);
                                if(euIdx !== -1) euVal = dsEu.data[euIdx];
                            }
                            if(val > 0 || euVal > 0) {
                                rowsProg.push([group, prog, val, Math.round(euVal).toLocaleString()]);
                            }
                        });
                    });
                }

                const tProg = initTable('tableProgramme', [{title:'Group'}, {title:'Programme'}, {title:'Participation'}, {title:'EU (€)'}]);
                tProg.clear().rows.add(rowsProg).draw();

                renderSummaryChart('chartProgPartSummary', dataProgPart, 'bar');
                renderSummaryChart('chartProgEuSummary', dataProgEu, 'bar');


                // 3. Mission Data
                const resMiss = await fetch(`api/summary.php?action=chart&dataset_id=${datasetId}&type=mission_eu`);
                const dataMiss = await resMiss.json();
                let rowsMiss = [];
                if(dataMiss.datasets) {
                    dataMiss.datasets.forEach(ds => {
                        ds.data.forEach((val, idx) => {
                            if(val > 0) rowsMiss.push([ds.label, dataMiss.labels[idx], Math.round(val).toLocaleString()]);
                        });
                    });
                }
                const tMiss = initTable('tableMission', [{title:'Group'}, {title:'Mission'}, {title:'EU (€)'}]);
                tMiss.clear().rows.add(rowsMiss).draw();
                renderSummaryChart('chartMissionSummary', dataMiss, 'bar');
            } catch(e) {
                console.error("Error loading summary data", e);
            }
        }

        function renderSummaryChart(canvasId, data, type) {
            const ctx = document.getElementById(canvasId).getContext('2d');
            if (summaryChartsMap[canvasId]) summaryChartsMap[canvasId].destroy();

            // Simple Palette
            const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];

            const datasets = data.datasets ? data.datasets.map((ds, i) => ({
                label: ds.label,
                data: ds.data,
                backgroundColor: colors[i % colors.length],
                borderColor: colors[i % colors.length],
                borderWidth: 1
            })) : [];

            summaryChartsMap[canvasId] = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels || [],
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { stacked: false },
                        y: { beginAtZero: true }
                    },
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }
    </script>
</body>
</html>
