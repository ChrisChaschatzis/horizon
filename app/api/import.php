<?php
// app/api/import.php

header('Content-Type: application/json');

// Error handling for API
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    require_once __DIR__ . '/../db_connect.php';
    require_once __DIR__ . '/../src/Importer.php';

    // Check method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Μη επιτρεπτή μέθοδος (Method not allowed)', 405);
    }

    // Check files
    $excelFile = null;
    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $excelFile = $_FILES['excel_file'];
    } elseif (isset($_FILES['excel_file_manual']) && $_FILES['excel_file_manual']['error'] !== UPLOAD_ERR_NO_FILE) {
        $excelFile = $_FILES['excel_file_manual'];
    }

    if (!$excelFile) {
        throw new Exception('Λείπει το αρχείο Excel (Παρακαλώ επιλέξτε ή σύρετε ένα αρχείο)', 400);
    }
    $jsonlFile = $_FILES['jsonl_file'] ?? null;
    $datasetName = $_POST['dataset_name'] ?? 'Dataset ' . date('Y-m-d H:i');
    $notes = $_POST['notes'] ?? '';

    // Check upload errors
    if ($excelFile['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Σφάλμα μεταφόρτωσης αρχείου Excel: ' . $excelFile['error'], 400);
    }

    // Move uploaded files
    $uploadDir = __DIR__ . '/../data/uploads/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            throw new Exception('Αποτυχία δημιουργίας φακέλου μεταφόρτωσης', 500);
        }
    }

    $excelPath = $uploadDir . uniqid() . '_' . basename($excelFile['name']);
    if (!move_uploaded_file($excelFile['tmp_name'], $excelPath)) {
        throw new Exception('Αποτυχία μετακίνησης αρχείου Excel', 500);
    }

    // JSONL is optional
    $jsonlPath = null;
    if ($jsonlFile && $jsonlFile['error'] === UPLOAD_ERR_OK && $jsonlFile['size'] > 0) {
        $jsonlPath = $uploadDir . uniqid() . '_' . basename($jsonlFile['name']);
        if (!move_uploaded_file($jsonlFile['tmp_name'], $jsonlPath)) {
            throw new Exception('Αποτυχία μετακίνησης αρχείου JSONL', 500);
        }
    }

    // Run Import
    require_once __DIR__ . '/../src/SchemaFixer.php';
    // Ensure DB schema is up to date (silently)
    SchemaFixer::fix($pdo, true);

    $importer = new Importer($pdo);
    $result = $importer->import($excelPath, $jsonlPath, $datasetName, $notes);

    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(500);
        echo json_encode($result);
    }

} catch (Exception $e) {
    $code = $e->getCode() ?: 500;
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
