<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horizon Europe BI - Σύνοψη</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="css/style.css">

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .filter-chip {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-secondary);
            padding: 4px 10px;
            border-radius: 16px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .filter-chip:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text);
        }
        .filter-chip.active {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent);
        }
    </style>
</head>
<body>
    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <i data-lucide="bar-chart-2"></i> CORDIS BI
            </div>
            <nav>
                <a href="index.php" class="nav-link active">
                    <i data-lucide="layout-dashboard"></i>
                    <span>Σύνοψη</span>
                </a>
                <a href="analytics.php" class="nav-link">
                    <i data-lucide="pie-chart"></i>
                    <span>Αναλύσεις</span>
                </a>
                <a href="chartbuilder.php" class="nav-link">
                    <i data-lucide="line-chart"></i>
                    <span>Δημιουργία Γραφημάτων</span>
                </a>
                <a href="datasets.php" class="nav-link">
                    <i data-lucide="database"></i>
                    <span>Διαχείριση Δεδομένων</span>
                </a>
                <a href="converter.php" class="nav-link">
                    <i data-lucide="file-check-2"></i>
                    <span>Μετατροπέας / Συγχώνευση</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <button class="mobile-menu-btn"><i data-lucide="menu"></i></button>
                <div class="filters-bar">
                    <select id="datasetSelector" class="dataset-selector">
                        <option value="">Φόρτωση δεδομένων...</option>
                    </select>

                    <!-- Projects Filters -->
                    <button class="filter-btn filter-btn-projects" id="filterGreekAny" onclick="toggleFilter('only_greek_any_role')">
                        🇬🇷 Ελληνική Συμμετοχή
                    </button>
                    <button class="filter-btn filter-btn-projects" id="filterGreekCoord" onclick="toggleFilter('only_greek_coordinator')">
                        🇬🇷 Έλληνας Συντονιστής
                    </button>
                    <button class="filter-btn filter-btn-projects" id="filterNoErrors" onclick="toggleFilter('exclude_error')">
                        <i data-lucide="check-circle" style="width:16px;height:16px;"></i> Έγκυρα Έργα
                    </button>

                    <!-- Summary Group Selector (Hidden by default) -->
                    <div id="groupSelectorContainer" style="display:none; align-items:center; gap:0.5rem; border-left:1px solid var(--grid); padding-left:1rem; margin-left:1rem;">
                        <!-- Chips injected via JS -->
                    </div>

                    <button class="filter-btn" onclick="resetFilters()">
                        <i data-lucide="rotate-ccw" style="width:16px;height:16px;"></i> Καθαρισμός
                    </button>
                </div>
                <div id="lastUpdated" style="color: var(--text-secondary); font-size: 0.85rem; font-weight: 500;"></div>
            </div>

            <!-- PROJECTS VIEW -->
            <div id="projects-view">
                <!-- KPI Cards -->
                <div class="kpi-grid">
                    <div class="card kpi-card">
                        <div class="kpi-title">Συνολικά Έργα</div>
                        <div class="kpi-value" id="kpiTotal">0</div>
                        <div class="kpi-trend trend-neutral"><i data-lucide="layers" style="width:16px;"></i> Επιλεγμένο Dataset</div>
                    </div>
                    <div class="card kpi-card">
                        <div class="kpi-title">Έργα με Ελληνική Συμμετοχή</div>
                        <div class="kpi-value" id="kpiGreekAny">0</div>
                        <div class="kpi-sub" id="kpiGreekAnyPct">0%</div>
                    </div>
                    <div class="card kpi-card">
                        <div class="kpi-title">Έργα με Έλληνα Συντονιστή</div>
                        <div class="kpi-value" id="kpiGreekCoord">0</div>
                        <div class="kpi-sub" id="kpiGreekCoordPct">0%</div>
                    </div>
                     <div class="card kpi-card">
                        <div class="kpi-title">Ποιότητα Δεδομένων</div>
                        <div class="kpi-value" id="kpiStatus" style="font-size: 1.8rem; margin-top: 0.5rem;">OK</div>
                        <div class="kpi-sub">Κατάσταση</div>
                    </div>
                </div>

                <!-- Dashboard Charts -->
                <div class="charts-grid">
                    <!-- Coordinator Ranking -->
                    <div class="card chart-card">
                        <div class="chart-header">
                            <h4 class="chart-title">Κατάταξη Χωρών Συντονιστών</h4>
                            <div class="chart-actions">
                                <button class="btn-icon" onclick="exportChart('chartCoord', 'Coordinator_Ranking')">
                                    <i data-lucide="camera"></i>
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="chartCoord"></canvas>
                        </div>
                    </div>

                    <!-- Greek Breakdown -->
                    <div class="card chart-card">
                        <div class="chart-header">
                            <h4 class="chart-title">Ανάλυση Ελληνικής Συμμετοχής</h4>
                            <div class="chart-actions">
                                 <button class="btn-icon" onclick="exportChart('chartGreek', 'Greek_Breakdown')">
                                    <i data-lucide="camera"></i>
                                 </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="chartGreek"></canvas>
                        </div>
                    </div>

                    <!-- Consortium Ranking -->
                    <div class="card chart-card full-width">
                         <div class="chart-header">
                            <h4 class="chart-title">Κατάταξη Χωρών στις Κοινοπραξίες</h4>
                            <div class="chart-actions">
                                 <button class="btn-icon" onclick="exportChart('chartConsortium', 'Consortium_Ranking')">
                                    <i data-lucide="camera"></i>
                                 </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="chartConsortium"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SUMMARY VIEW (New) -->
            <div id="summary-view" style="display:none;">
                 <div class="kpi-grid">
                    <div class="card kpi-card">
                        <div class="kpi-title">Συνολικές Συμμετοχές</div>
                        <div class="kpi-value" id="summKpiPart">0</div>
                    </div>
                    <div class="card kpi-card">
                        <div class="kpi-title">Συνολική Κοινοτική Συνεισφορά</div>
                        <div class="kpi-value" id="summKpiEu">0 €</div>
                    </div>
                    <div class="card kpi-card">
                        <div class="kpi-title">Groups / Entities</div>
                        <div class="kpi-value" id="summKpiGroups">0</div>
                    </div>
                </div>

                <div class="charts-grid">
                    <!-- Pillar Overview -->
                    <div class="card chart-card full-width">
                        <div class="chart-header">
                            <h4 class="chart-title">Overview by Pillar</h4>
                             <div class="chart-actions">
                                <button class="btn-icon" onclick="exportChart('chartSumPillar', 'Pillar_Overview')">
                                    <i data-lucide="camera"></i>
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="chartSumPillar"></canvas>
                        </div>
                    </div>

                    <!-- Programme Participation -->
                    <div class="card chart-card full-width">
                         <div class="chart-header">
                            <h4 class="chart-title">Participation across Programmes</h4>
                             <div class="chart-actions">
                                <button class="btn-icon" onclick="exportChart('chartSumProgPart', 'Prog_Part')">
                                    <i data-lucide="camera"></i>
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="chartSumProgPart"></canvas>
                        </div>
                    </div>

                    <!-- Programme EU -->
                    <div class="card chart-card">
                         <div class="chart-header">
                            <h4 class="chart-title">EU Contribution across Programmes</h4>
                             <div class="chart-actions">
                                <button class="btn-icon" onclick="exportChart('chartSumProgEu', 'Prog_EU')">
                                    <i data-lucide="camera"></i>
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="chartSumProgEu"></canvas>
                        </div>
                    </div>

                     <!-- Mission EU -->
                    <div class="card chart-card">
                         <div class="chart-header">
                            <h4 class="chart-title">EU Contribution across Missions</h4>
                             <div class="chart-actions">
                                <button class="btn-icon" onclick="exportChart('chartSumMission', 'Mission_EU')">
                                    <i data-lucide="camera"></i>
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="chartSumMission"></canvas>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script src="js/main.js"></script>
    <script src="js/dashboard.js"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
