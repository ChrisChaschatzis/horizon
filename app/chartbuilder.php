<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horizon Europe BI - Δημιουργία Γραφημάτων</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="css/style.css">

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
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
                <a href="analytics.php" class="nav-link">
                    <i data-lucide="pie-chart"></i>
                    <span>Αναλύσεις</span>
                </a>
                <a href="chartbuilder.php" class="nav-link active">
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
                    <select id="datasetSelector" class="dataset-selector"></select>
                </div>
                <div id="lastUpdated" style="color: var(--text-secondary); font-size: 0.85rem; font-weight: 500;"></div>
            </div>

            <div class="card full-width" style="margin-bottom: 2rem;">
                <h4 style="margin-top:0; margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem;">
                    <i data-lucide="settings"></i> Ρυθμίσεις Γραφήματος
                </h4>
                <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; align-items: flex-end;">
                    <div class="form-group">
                        <label class="form-label">Τύπος Γραφήματος</label>
                        <select id="cbChartType" class="form-control">
                            <option value="bar">Ράβδος (Bar)</option>
                            <option value="line">Γραμμή (Line)</option>
                            <option value="pie">Πίτα (Pie)</option>
                            <option value="doughnut">Ντόνατ (Doughnut)</option>
                            <option value="radar">Ραντάρ (Radar)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Διάσταση X</label>
                        <select id="cbX" class="form-control">
                            <option value="coordinator_country">Χώρα Συντονιστή</option>
                            <option value="consortium_country">Χώρα Κοινοπραξίας</option>
                            <option value="fields_of_science">Επιστημονικό Πεδίο</option>
                            <option value="keyword">Λέξη-Κλειδί</option>
                            <option value="invest_priority">Επενδυτική Προτεραιότητα</option>
                            <option value="pillar">Πρόγραμμα / Pillar</option>
                            <option value="type_of_action">Τύπος Δράσης</option>
                            <option value="year">Έτος</option>
                            <option value="has_greek_any_role">Ελληνικός Ρόλος (Ναι/Όχι)</option>
                            <option value="is_greek_coordinator">Έλληνας Συντονιστής (Ναι/Όχι)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Μετρική Y</label>
                        <select id="cbY" class="form-control">
                            <option value="count">Πλήθος Έργων</option>
                            <option value="sum_eu_contribution">Σύνολο Συνεισφοράς ΕΕ</option>
                            <option value="average_invest_priority">Μ.Ο. % Επενδυτικής Προτεραιότητας</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Top N</label>
                        <input type="number" id="cbTop" class="form-control" value="10" min="1" max="100" style="width: 80px;">
                    </div>

                    <div class="form-group">
                         <button class="btn btn-primary" onclick="buildChart()">
                            <i data-lucide="play-circle"></i> Δημιουργία
                         </button>
                    </div>
                </div>

                 <div class="filters-bar mt-2">
                    <span style="color: var(--muted); margin-right: 0.5rem; font-size: 0.9rem;">Γρήγορα Φίλτρα:</span>
                    <button class="filter-btn" id="filterGreekAny" onclick="toggleFilter('only_greek_any_role')">🇬🇷 Ελληνική Συμμετοχή</button>
                    <button class="filter-btn" id="filterGreekCoord" onclick="toggleFilter('only_greek_coordinator')">🇬🇷 Έλληνας Συντονιστής</button>
                    <button class="filter-btn" onclick="resetFilters()">
                        <i data-lucide="rotate-ccw" style="width:14px;"></i> Επαναφορά
                    </button>
                </div>
            </div>

            <!-- Chart Result -->
             <div class="card chart-card full-width" id="chartResultCard" style="display:none;">
                <div class="chart-header">
                    <h4 class="chart-title" id="chartTitle">Προσαρμοσμένο Γράφημα</h4>
                    <div class="chart-actions">
                         <button class="btn-icon" onclick="exportChart('customChart', 'Custom_Chart')">
                            <i data-lucide="camera"></i>
                         </button>
                    </div>
                </div>
                <div class="chart-container" style="height: 500px;">
                    <canvas id="customChart"></canvas>
                </div>
            </div>

             <!-- Data Table -->
            <div class="card table-card full-width" id="tableResultCard" style="display:none; margin-top: 2rem; padding: 1rem;">
                <table id="customTable" class="display" style="width:100%">
                    <thead>
                        <tr>
                            <th>Ετικέτα</th>
                            <th>Τιμή</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

        </main>
    </div>
    <script src="js/main.js"></script>
    <script src="js/chartbuilder.js"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
