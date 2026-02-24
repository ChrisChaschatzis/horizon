<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chart Builder - Horizon Europe BI - Δημιουργία Γραφημάτων</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="css/style.css">

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="logo">CORDIS BI</div>
            <nav>
                <a href="index.php" class="nav-link">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    <span>Πίνακας Ελέγχου</span>
                </a>
                <a href="analytics.php" class="nav-link">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                    <span>Αναλύσεις</span>
                </a>
                <a href="chartbuilder.php" class="nav-link active">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>
                    <span>Δημιουργία Γραφημάτων</span>
                </a>
                <a href="datasets.php" class="nav-link">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    <span>Δεδομένα</span>
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div class="filters-bar">
                    <select id="datasetSelector" class="dataset-selector"></select>
                </div>
                <div id="lastUpdated" style="color: var(--muted); font-size: 0.9rem;"></div>
            </div>

            <div class="card full-width" style="margin-bottom: 2rem;">
                <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
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
                         <button class="btn btn-primary" onclick="buildChart()">Δημιουργία Γραφήματος</button>
                    </div>
                </div>

                 <div class="filters-bar mt-2">
                    <span style="color: var(--muted); margin-right: 0.5rem;">Φίλτρα:</span>
                    <button class="filter-btn" id="filterGreekAny" onclick="toggleFilter('only_greek_any_role')">🇬🇷 Ελληνική Συμμετοχή</button>
                    <button class="filter-btn" id="filterGreekCoord" onclick="toggleFilter('only_greek_coordinator')">🇬🇷 Έλληνας Συντονιστής</button>
                    <button class="filter-btn" onclick="resetFilters()">🔄 Επαναφορά</button>
                </div>
            </div>

            <!-- Chart Result -->
             <div class="card chart-card full-width" id="chartResultCard" style="display:none;">
                <div class="chart-header">
                    <h4 class="chart-title" id="chartTitle">Προσαρμοσμένο Γράφημα</h4>
                    <div class="chart-actions">
                         <button class="btn-icon" onclick="exportChart('customChart', 'Custom_Chart')">📷 PNG</button>
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
</body>
</html>
