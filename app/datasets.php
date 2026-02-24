<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datasets - Horizon Europe BI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="css/style.css">

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
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
                <a href="chartbuilder.php" class="nav-link">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>
                    <span>Δημιουργία Γραφημάτων</span>
                </a>
                <a href="datasets.php" class="nav-link active">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    <span>Δεδομένα</span>
                </a>
            </nav>
        </aside>

        <main class="main-content">
             <div class="top-bar">
                <h3>Διαχείριση Δεδομένων</h3>
                <div id="lastUpdated" style="color: var(--muted); font-size: 0.9rem;"></div>
            </div>

            <!-- Import Form -->
            <div class="card full-width" style="margin-bottom: 2rem;">
                <h4 style="margin-top: 0;">Εισαγωγή Νέου Dataset</h4>
                <form id="importForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="form-label">Όνομα Dataset</label>
                        <input type="text" name="dataset_name" class="form-control" required placeholder="π.χ. CORDIS 2026-02">
                    </div>

                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Αρχείο Excel (.xlsx) - Υποχρεωτικό</label>
                            <input type="file" name="excel_file" class="form-control" accept=".xlsx" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Αρχείο JSONL (.jsonl) - Προαιρετικό αλλά συνιστάται</label>
                            <input type="file" name="jsonl_file" class="form-control" accept=".jsonl">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Σημειώσεις</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" id="btnImport">Εισαγωγή Dataset</button>

                    <div id="progressContainer" style="margin-top: 1rem; display: none;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span id="importStatus" style="color: var(--muted);">Εκκίνηση μεταφόρτωσης...</span>
                            <span id="uploadPercent" style="color: var(--accent);">0%</span>
                        </div>
                        <progress id="uploadProgress" value="0" max="100" style="width: 100%; height: 8px; border-radius: 4px;"></progress>
                    </div>
                </form>
            </div>

            <!-- Dataset List -->
            <div class="card table-card full-width">
                <table id="datasetsTable" class="display" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Όνομα</th>
                            <th>Ημερομηνία</th>
                            <th>Έργα</th>
                            <th>Σημειώσεις</th>
                            <th>Ενέργειες</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

        </main>
    </div>
    <script src="js/main.js"></script>
    <script src="js/datasets.js"></script>
</body>
</html>
