<?php
// app/api/kpis.php
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
    COUNT(*) as total_projects,
    SUM(p.has_greek_any_role) as projects_with_greece_any_role,
    SUM(p.is_greek_coordinator) as projects_with_greek_coordinator,
    SUM(CASE WHEN p.keywords_text IS NULL OR p.keywords_text = '' THEN 1 ELSE 0 END) as projects_no_keywords,
    SUM(CASE WHEN p.invest_priorities_json IS NULL OR p.invest_priorities_json = '{}' OR p.invest_priorities_json = '' THEN 1 ELSE 0 END) as projects_no_priorities,
    SUM(CASE WHEN p.error_text IS NOT NULL AND p.error_text != '' THEN 1 ELSE 0 END) as projects_with_errors
    FROM projects p
    WHERE " . $where['sql'];

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($where['params']);
    $result = $stmt->fetch();

    $total = (int)($result['total_projects'] ?? 0);
    $greekAny = (int)($result['projects_with_greece_any_role'] ?? 0);
    $greekCoord = (int)($result['projects_with_greek_coordinator'] ?? 0);

    $kpis = [
        'total_projects' => $total,
        'projects_with_greece_any_role' => $greekAny,
        'percent_with_greece_any_role' => $total > 0 ? round(($greekAny / $total) * 100, 2) : 0,
        'projects_with_greek_coordinator' => $greekCoord,
        'percent_with_greek_coordinator' => $total > 0 ? round(($greekCoord / $total) * 100, 2) : 0,

        // Data Quality
        'projects_no_keywords' => (int)($result['projects_no_keywords'] ?? 0),
        'projects_no_priorities' => (int)($result['projects_no_priorities'] ?? 0),
        'projects_with_errors' => (int)($result['projects_with_errors'] ?? 0)
    ];

    echo json_encode($kpis);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
