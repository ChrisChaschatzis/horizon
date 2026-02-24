<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horizon Europe BI - Αναλύσεις</title>
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
                <a href="analytics.php" class="nav-link active">
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
            </nav>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <button class="mobile-menu-btn"><i data-lucide="menu"></i></button>
                <div class="filters-bar">
                    <select id="datasetSelector" class="dataset-selector">
                        <option value="">Φόρτωση...</option>
                    </select>

                    <button class="filter-btn" id="filterGreekAny" onclick="toggleFilter('only_greek_any_role')">
                        🇬🇷 Ελληνική Συμμετοχή
                    </button>
                    <button class="filter-btn" id="filterGreekCoord" onclick="toggleFilter('only_greek_coordinator')">
                        🇬🇷 Έλληνας Συντονιστής
                    </button>
                    <button class="filter-btn" onclick="resetFilters()">
                        <i data-lucide="rotate-ccw" style="width:16px;height:16px;"></i> Καθαρισμός
                    </button>
                </div>
                <div id="lastUpdated" style="color: var(--text-secondary); font-size: 0.85rem; font-weight: 500;"></div>
            </div>

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

        </main>
    </div>
    <script src="js/main.js"></script>
    <script src="js/analytics.js"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
