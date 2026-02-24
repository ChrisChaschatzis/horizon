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
</head>
<body>
    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">CORDIS BI</div>
            <nav>
                <a href="index.php" class="nav-link active">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <span>Σύνοψη</span>
                </a>
                <a href="analytics.php" class="nav-link">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    <span>Αναλύσεις</span>
                </a>
                <a href="chartbuilder.php" class="nav-link">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M12 6v6l4 2"></path>
                    </svg>
                    <span>Δημιουργία Γραφημάτων</span>
                </a>
                <a href="datasets.php" class="nav-link">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    <span>Διαχείριση Δεδομένων</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <button class="mobile-menu-btn">☰</button>
                <div class="filters-bar">
                    <select id="datasetSelector" class="dataset-selector">
                        <option value="">Φόρτωση δεδομένων...</option>
                    </select>

                    <button class="filter-btn" id="filterGreekAny" onclick="toggleFilter('only_greek_any_role')">
                        🇬🇷 Ελληνική Συμμετοχή
                    </button>
                    <button class="filter-btn" id="filterGreekCoord" onclick="toggleFilter('only_greek_coordinator')">
                        🇬🇷 Έλληνας Συντονιστής
                    </button>
                    <button class="filter-btn" id="filterNoErrors" onclick="toggleFilter('exclude_error')">
                        ✅ Έγκυρα Έργα
                    </button>
                    <button class="filter-btn" onclick="resetFilters()">
                        🔄 Καθαρισμός Φίλτρων
                    </button>
                </div>
                <div id="lastUpdated" style="color: var(--muted); font-size: 0.85rem; font-weight: 500;"></div>
            </div>

            <!-- KPI Cards -->
            <div class="kpi-grid">
                <div class="card kpi-card">
                    <h3>Συνολικά Έργα</h3>
                    <div class="kpi-value" id="kpiTotal">0</div>
                    <div class="kpi-sub">Επιλεγμένο Dataset</div>
                </div>
                <div class="card kpi-card">
                    <h3>Έργα με Ελληνική Συμμετοχή</h3>
                    <div class="kpi-value" id="kpiGreekAny">0</div>
                    <div class="kpi-sub" id="kpiGreekAnyPct">0%</div>
                </div>
                <div class="card kpi-card">
                    <h3>Έργα με Έλληνα Συντονιστή</h3>
                    <div class="kpi-value" id="kpiGreekCoord">0</div>
                    <div class="kpi-sub" id="kpiGreekCoordPct">0%</div>
                </div>
                 <!-- Quick Data Quality Status -->
                 <div class="card kpi-card">
                    <h3>Ποιότητα Δεδομένων</h3>
                    <div class="kpi-value" id="kpiStatus" style="font-size: 1.5rem; margin-top: 0.5rem;">OK</div>
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
                            <button class="btn-icon" onclick="exportChart('chartCoord', 'Coordinator_Ranking')">📷 Εξαγωγή σε PNG</button>
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
                             <button class="btn-icon" onclick="exportChart('chartGreek', 'Greek_Breakdown')">📷 Εξαγωγή σε PNG</button>
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
                             <button class="btn-icon" onclick="exportChart('chartConsortium', 'Consortium_Ranking')">📷 Εξαγωγή σε PNG</button>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="chartConsortium"></canvas>
                    </div>
                </div>

                <!-- Data Quality Details -->
                <div class="card full-width" style="margin-top: 0;">
                    <h4 class="chart-title" style="margin-bottom: 1.5rem;">Λεπτομέρειες Ποιότητας Δεδομένων</h4>
                    <div class="kpi-grid" style="margin-bottom: 0; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                         <div class="kpi-card" style="border: none; background: transparent; padding: 0; box-shadow: none;">
                             <h3>Χωρίς Λέξεις-Κλειδιά</h3>
                             <div class="kpi-value" style="color: var(--danger); font-size: 2rem;" id="dqKeywords">0</div>
                         </div>
                         <div class="kpi-card" style="border: none; background: transparent; padding: 0; box-shadow: none;">
                             <h3>Χωρίς Προτεραιότητες</h3>
                             <div class="kpi-value" style="color: var(--warning); font-size: 2rem;" id="dqPriorities">0</div>
                         </div>
                         <div class="kpi-card" style="border: none; background: transparent; padding: 0; box-shadow: none;">
                             <h3>Σφάλματα Άντλησης</h3>
                             <div class="kpi-value" style="color: var(--danger); font-size: 2rem;" id="dqErrors">0</div>
                         </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script src="js/main.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html>
