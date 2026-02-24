<?php

require_once __DIR__ . '/src/Importer.php';

// Find mock files
$files = glob(__DIR__ . '/data/cordis_enriched_*.xlsx');
if (empty($files)) {
    die("No Excel file found in data/");
}
$excelFile = $files[0];
$timestamp = str_replace(['cordis_enriched_', '.xlsx'], '', basename($excelFile));
$jsonlFile = __DIR__ . "/data/cordis_results_{$timestamp}.jsonl";

if (!file_exists($jsonlFile)) {
    die("JSONL file not found: $jsonlFile");
}

echo "Testing import with:\nExcel: $excelFile\nJSONL: $jsonlFile\n";

$importer = new Importer($pdo);
$result = $importer->import($excelFile, $jsonlFile, "Test Dataset " . date('H:i:s'), "Imported via test script");

if ($result['success']) {
    echo "Import successful! Dataset ID: " . $result['dataset_id'] . "\n";

    // Verify count
    $stmt = $pdo->query("SELECT COUNT(*) FROM projects WHERE dataset_id = " . $result['dataset_id']);
    echo "Projects imported: " . $stmt->fetchColumn() . "\n";

    // Verify enrichment (check if invest_priorities_json is not empty for some)
    $stmt = $pdo->query("SELECT COUNT(*) FROM projects WHERE dataset_id = " . $result['dataset_id'] . " AND invest_priorities_json != '{}'");
    echo "Projects with priorities: " . $stmt->fetchColumn() . "\n";

} else {
    echo "Import failed: " . $result['error'] . "\n";
}
