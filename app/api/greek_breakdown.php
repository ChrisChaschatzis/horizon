<?php
// app/api/greek_breakdown.php
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

$sql = "SELECT
    SUM(p.is_greek_coordinator) as greek_coordinator,
    SUM(p.has_greek_participant) as greek_participant,
    SUM(p.has_greek_beneficiary) as greek_beneficiary,
    SUM(p.has_greek_any_role) as greek_any_role,
    COUNT(*) as total_projects
    FROM projects p
    WHERE " . $where['sql'];

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($where['params']);
    $result = $stmt->fetch();

    // Since these flags are not mutually exclusive (a project can have both),
    // and usually "Participant" includes "Coordinator",
    // displaying them in a Doughnut chart is statistically weird but requested.
    // We will translate labels.

    $data = [
        'labels' => ['Συντονιστής', 'Συμμετέχων', 'Δικαιούχος'],
        'data' => [
            (int)($result['greek_coordinator'] ?? 0),
            (int)($result['greek_participant'] ?? 0),
            (int)($result['greek_beneficiary'] ?? 0)
        ],
        'total_greek' => (int)($result['greek_any_role'] ?? 0),
        'total_projects' => (int)($result['total_projects'] ?? 0)
    ];

    echo json_encode($data);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
