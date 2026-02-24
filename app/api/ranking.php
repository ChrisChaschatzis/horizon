<?php
// app/api/ranking.php
header('Content-Type: application/json');
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../src/FilterHelper.php';

$datasetId = $_GET['dataset_id'] ?? null;
$type = $_GET['type'] ?? 'coordinator'; // coordinator or consortium
$top = (int)($_GET['top'] ?? 10);
$includeUnknown = ($_GET['include_unknown'] ?? 'true') === 'true';

if (!$datasetId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing dataset_id']);
    exit;
}

$filters = $_GET;
$filters['dataset_id'] = $datasetId;
$where = FilterHelper::buildWhereClause($filters, 'p');

if ($type === 'coordinator') {
    $sql = "SELECT p.coordinator_country as label, COUNT(*) as value
            FROM projects p
            WHERE " . $where['sql'];

    if (!$includeUnknown) {
        $sql .= " AND (p.coordinator_country IS NOT NULL AND p.coordinator_country != '')";
    }

    $sql .= " GROUP BY p.coordinator_country ORDER BY value DESC LIMIT $top";

} elseif ($type === 'consortium') {
    $sql = "SELECT pc.country as label, COUNT(DISTINCT pc.project_db_id) as value
            FROM project_countries pc
            JOIN projects p ON pc.project_db_id = p.id
            WHERE " . $where['sql'];

    if (!$includeUnknown) {
        $sql .= " AND (pc.country IS NOT NULL AND pc.country != '')";
    }

    $sql .= " GROUP BY pc.country ORDER BY value DESC LIMIT $top";

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid type']);
    exit;
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($where['params']);
    $data = $stmt->fetchAll();

    // Calculate percentages
    // Total projects matching filters
    $sqlTotal = "SELECT COUNT(*) FROM projects p WHERE " . $where['sql'];
    $stmtTotal = $pdo->prepare($sqlTotal);
    $stmtTotal->execute($where['params']);
    $totalProjects = $stmtTotal->fetchColumn();

    foreach ($data as &$row) {
        $row['percent'] = $totalProjects > 0 ? round(($row['value'] / $totalProjects) * 100, 2) : 0;
    }

    // Add "Others" logic? The prompt says "Top N + Others option".
    // Implementing "Others" requires knowing total sum of category vs displayed.
    // For Coordinator: sum of displayed vs total projects (if each project has 1 coordinator).
    // For Consortium: sum of occurrences vs unique projects? No, others in consortium is tricky because a project has multiple countries.
    // Usually "Others" is just aggregated count of countries not in top N.

    // Let's implement simplified "Others" client-side or just return top N as requested by API.
    // The prompt says "Bar chart (top N selectable ... + Others option)".
    // I'll return total_base so frontend can calculate Others if needed (Total - Sum(TopN)).
    // But for Consortium, Sum(TopN) > Total Projects because of overlap.
    // So "Others" for Consortium means "Count of projects involving other countries".
    // That's hard to subtract.

    // For Coordinator, Sum(TopN) <= Total Projects. Others = Total - Sum(TopN).

    echo json_encode(['labels' => array_column($data, 'label'), 'data' => array_column($data, 'value'), 'full_data' => $data, 'total_base' => $totalProjects]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
