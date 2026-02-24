<?php
// app/api/invest_priorities.php
header('Content-Type: application/json');
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../src/FilterHelper.php';

$datasetId = $_GET['dataset_id'] ?? null;
if (!$datasetId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing dataset_id']);
    exit;
}

$filters = $_GET;
$filters['dataset_id'] = $datasetId;
$where = FilterHelper::buildWhereClause($filters, 'p');

$sql = "SELECT ip.label, AVG(ip.percent) as avg_percent, COUNT(DISTINCT ip.project_db_id) as project_count
        FROM invest_priorities ip
        JOIN projects p ON ip.project_db_id = p.id
        WHERE " . $where['sql'] . "
        GROUP BY ip.label
        ORDER BY avg_percent DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($where['params']);
    echo json_encode($stmt->fetchAll());
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
