<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horizon Europe BI - Μετατροπέας / Συγχώνευση</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="css/style.css">

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .file-card {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .file-info {
            flex: 1;
            min-width: 0;
        }
        .file-name {
            font-weight: 500;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .file-type {
            font-size: 0.8rem;
            color: var(--muted);
            margin-top: 2px;
        }
        .file-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .badge {
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-ok { background: rgba(74, 222, 128, 0.1); color: #4ade80; }
        .badge-warn { background: rgba(251, 191, 36, 0.1); color: #fbbf24; }
        .badge-err { background: rgba(248, 113, 113, 0.1); color: #f87171; }
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
                <a href="converter.php" class="nav-link active">
                    <i data-lucide="file-check-2"></i>
                    <span>Μετατροπέας / Συγχώνευση</span>
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <button class="mobile-menu-btn"><i data-lucide="menu"></i></button>
                <div style="flex:1;">
                    <h3 style="margin:0; font-size: 1.25rem;">Μετατροπέας & Συγχώνευση Exports</h3>
                </div>
            </div>

            <!-- Upload Zone -->
            <div class="card full-width" style="margin-bottom: 2rem;">
                <h4 style="margin-top:0; margin-bottom:1rem;">1. Μεταφόρτωση Αρχείων (Excel Exports)</h4>
                <p style="color:var(--muted); font-size:0.9rem; margin-bottom:1.5rem;">
                    Ανεβάστε πολλαπλά αρχεία Excel (.xlsx) που προέρχονται από την πλατφόρμα Horizon.
                    Το σύστημα θα αναγνωρίσει αυτόματα τον τύπο τους (Pillar, Programme, Mission κτλ).
                </p>

                <div class="upload-zone" id="dropZoneMulti">
                    <input type="file" id="fileInputMulti" class="file-input" multiple accept=".xlsx">
                    <div class="upload-content">
                        <i data-lucide="upload-cloud" class="upload-icon"></i>
                        <div class="upload-text">Σύρετε πολλά αρχεία εδώ ή κάντε κλικ</div>
                        <div class="upload-sub">Υποστηρίζεται: .xlsx</div>
                    </div>
                </div>
            </div>

            <!-- Staging Area -->
            <div class="card full-width" id="stagingArea" style="display:none; margin-bottom: 2rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                    <h4 style="margin:0;">2. Οργάνωση & Ομαδοποίηση</h4>
                    <button class="btn btn-sm" onclick="clearAllFiles()" style="background:rgba(255,255,255,0.05);">
                        <i data-lucide="trash-2" style="width:14px;"></i> Καθαρισμός
                    </button>
                </div>

                <div id="fileList"></div>

                <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--grid);">
                    <label class="form-label">Μαζική Ανάθεση Group Label (για επιλεγμένα):</label>
                    <div style="display:flex; gap:0.5rem;">
                        <input type="text" id="bulkGroupInput" class="form-control" placeholder="π.χ. Greece - SMEs">
                        <button class="btn btn-secondary" onclick="applyBulkGroup()">Εφαρμογή</button>
                    </div>
                </div>
            </div>

            <!-- Action Area -->
            <div class="card full-width" id="actionArea" style="display:none;">
                <h4 style="margin-top:0; margin-bottom:1rem;">3. Δημιουργία Comparison Pack</h4>
                <div style="display:flex; gap:1rem; align-items:center;">
                    <button class="btn btn-primary" id="btnMerge" onclick="executeMerge()">
                        <i data-lucide="merge"></i> Συγχώνευση & Λήψη Excel
                    </button>
                    <div id="mergeStatus" style="color:var(--muted); font-size:0.9rem;"></div>
                </div>

                <div id="downloadLinkArea" style="margin-top:1rem; display:none;">
                    <a href="#" id="downloadBtn" class="btn btn-success">
                        <i data-lucide="download"></i> Λήψη: <span id="packFilename"></span>
                    </a>
                    <p style="margin-top:0.5rem; font-size:0.85rem; color:var(--text);">
                        Το αρχείο μπορεί τώρα να εισαχθεί στη σελίδα "Διαχείριση Δεδομένων".
                    </p>
                </div>
            </div>

        </main>
    </div>

    <script src="js/main.js"></script>
    <script src="js/converter.js"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
