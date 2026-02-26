<?php
// app/api/import.php
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../src/Importer.php';
require_once __DIR__ . '/../src/SchemaFixer.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$datasetName = $_POST['dataset_name'] ?? '';
$notes = $_POST['notes'] ?? '';

// Check files
$excelFile = null;

if (!empty($_FILES['excel_file_manual']['tmp_name'])) {
    $excelFile = $_FILES['excel_file_manual'];
} elseif (!empty($_FILES['excel_file']['tmp_name'])) {
    $excelFile = $_FILES['excel_file'];
}

if (!$excelFile || $excelFile['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No valid Excel file provided']);
    exit;
}

// JSONL is optional
$jsonlPath = null;
if (!empty($_FILES['jsonl_file']['tmp_name']) && $_FILES['jsonl_file']['error'] === UPLOAD_ERR_OK) {
    $jsonlPath = $_FILES['jsonl_file']['tmp_name'];
}

// Move Excel to temp
$tempExcel = sys_get_temp_dir() . '/' . uniqid('import_') . '.xlsx';
if (!move_uploaded_file($excelFile['tmp_name'], $tempExcel)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to move uploaded file']);
    exit;
}

// Run Importer
$importer = new Importer($pdo);
$result = $importer->import($tempExcel, $jsonlPath, $datasetName, $notes);

// Run SchemaFixer to ensure compatibility
SchemaFixer::fix($pdo, true);

// Clean up
@unlink($tempExcel);

if ($result['success']) {
    echo json_encode($result);
} else {
    http_response_code(500);
    echo json_encode($result);
}
