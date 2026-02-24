<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horizon Europe BI - Δεδομένα</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="css/style.css">

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <!-- Lucide Icons -->
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
                <a href="chartbuilder.php" class="nav-link">
                    <i data-lucide="line-chart"></i>
                    <span>Δημιουργία Γραφημάτων</span>
                </a>
                <a href="datasets.php" class="nav-link active">
                    <i data-lucide="database"></i>
                    <span>Διαχείριση Δεδομένων</span>
                </a>
            </nav>
        </aside>

        <main class="main-content">
             <div class="top-bar">
                <button class="mobile-menu-btn"><i data-lucide="menu"></i></button>
                <div style="flex:1;">
                    <h3 style="margin:0; font-size: 1.25rem;">Διαχείριση Δεδομένων</h3>
                </div>
            </div>

            <!-- Import Form -->
            <div class="card full-width" style="margin-bottom: 2rem;">
                <h4 style="margin-top: 0; margin-bottom: 1.5rem;">Εισαγωγή Νέου Dataset</h4>
                <form id="importForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="form-label">Όνομα Dataset</label>
                        <input type="text" name="dataset_name" class="form-control" required placeholder="π.χ. CORDIS 2026-02">
                    </div>

                    <!-- Drag & Drop Zone -->
                    <div class="form-group">
                        <label class="form-label">Αρχεία Εισαγωγής</label>

                        <!-- Manual Input -->
                        <div style="margin-bottom: 0.75rem;">
                            <input type="file" name="excel_file_manual" id="fileInputManual" class="form-control" accept=".xlsx">
                        </div>

                        <!-- Drop Zone -->
                        <div class="upload-zone" id="dropZone">
                            <input type="file" name="excel_file" id="fileInputDrop" class="file-input" accept=".xlsx">
                            <div class="upload-content">
                                <i data-lucide="upload-cloud" class="upload-icon"></i>
                                <div class="upload-text">Σύρετε το αρχείο Excel εδώ ή κάντε κλικ</div>
                                <div class="upload-sub">Υποστηρίζεται: .xlsx (Υποχρεωτικό)</div>
                                <div id="fileNameDrop" style="margin-top: 0.5rem; color: var(--accent); font-weight: 500;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- JSONL Optional -->
                    <div class="form-group">
                        <label class="form-label">Αρχείο JSONL (Προαιρετικό - για εμπλουτισμό)</label>
                        <input type="file" name="jsonl_file" class="form-control" accept=".jsonl">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Σημειώσεις</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" id="btnImport">
                        <i data-lucide="save"></i> Εισαγωγή Dataset
                    </button>

                    <!-- Progress Bar -->
                    <div id="progressWrapper" style="display: none;">
                        <div class="progress-status">
                            <span id="importStatus">Μεταφόρτωση...</span>
                            <span id="uploadPercent">0%</span>
                        </div>
                        <div class="progress-container">
                            <div id="uploadProgress" class="progress-bar"></div>
                        </div>
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
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
