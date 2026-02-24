<?php
// app/api/fields_of_science.php
header('Content-Type: application/json');
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../src/FilterHelper.php';

$datasetId = $_GET['dataset_id'] ?? null;
$level = (int)($_GET['level'] ?? 1); // 1 or 2

if (!$datasetId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing dataset_id']);
    exit;
}

$filters = $_GET;
$filters['dataset_id'] = $datasetId;
$where = FilterHelper::buildWhereClause($filters, 'p');

$col = ($level == 2) ? 'pf.level2' : 'pf.level1';

$sql = "SELECT $col as field, COUNT(DISTINCT pf.project_db_id) as count
        FROM project_fields pf
        JOIN projects p ON pf.project_db_id = p.id
        WHERE " . $where['sql'] . " AND $col IS NOT NULL AND $col != ''
        GROUP BY $col
        ORDER BY count DESC
        LIMIT 20";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($where['params']);
    echo json_encode($stmt->fetchAll());
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
