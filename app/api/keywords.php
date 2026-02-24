<?php
// app/api/keywords.php
header('Content-Type: application/json');
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../src/FilterHelper.php';

$datasetId = $_GET['dataset_id'] ?? null;
$top = (int)($_GET['top'] ?? 20);

if (!$datasetId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing dataset_id']);
    exit;
}

$filters = $_GET;
$filters['dataset_id'] = $datasetId;
$where = FilterHelper::buildWhereClause($filters, 'p');

$sql = "SELECT pk.keyword, COUNT(DISTINCT pk.project_db_id) as count
        FROM project_keywords pk
        JOIN projects p ON pk.project_db_id = p.id
        WHERE " . $where['sql'] . "
        GROUP BY pk.keyword
        ORDER BY count DESC
        LIMIT $top";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($where['params']);
    echo json_encode($stmt->fetchAll());
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
