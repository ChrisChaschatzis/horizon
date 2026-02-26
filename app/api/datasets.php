<?php
require_once __DIR__ . '/../db_connect.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // List datasets
    $stmt = $pdo->query("SELECT id, name, created_at, excel_filename, jsonl_filename, notes, dataset_type, entities_count,
        (SELECT COUNT(*) FROM projects WHERE dataset_id = datasets.id) as project_count
        FROM datasets ORDER BY created_at DESC");
    echo json_encode($stmt->fetchAll());

} elseif ($method === 'DELETE') {
    // Delete dataset
    $input = json_decode(file_get_contents('php://input'), true);
    // Support query param or body
    $id = $_GET['id'] ?? ($input['id'] ?? null);

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Λείπει το ID']);
        exit;
    }

    // Check if exists
    $stmt = $pdo->prepare("SELECT * FROM datasets WHERE id = ?");
    $stmt->execute([$id]);
    $dataset = $stmt->fetch();

    if (!$dataset) {
        http_response_code(404);
        echo json_encode(['error' => 'Το Dataset δεν βρέθηκε']);
        exit;
    }

    // Delete database record, cascading should handle projects.
    $del = $pdo->prepare("DELETE FROM datasets WHERE id = ?");
    $del->execute([$id]);

    echo json_encode(['success' => true]);

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Μη επιτρεπτή μέθοδος']);
}
