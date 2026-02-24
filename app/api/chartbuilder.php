<?php
// app/api/chartbuilder.php
header('Content-Type: application/json');
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../src/FilterHelper.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$datasetId = $input['dataset_id'] ?? null;
if (!$datasetId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing dataset_id']);
    exit;
}

$xDim = $input['x_dimension'] ?? 'coordinator_country';
$yMetric = $input['y_metric'] ?? 'count';
$topN = (int)($input['top_n'] ?? 10);
$filters = $input['filters'] ?? [];
$filters['dataset_id'] = $datasetId;

// Build WHERE
$where = FilterHelper::buildWhereClause($filters, 'p');

// Determine table/join/column
$table = "projects p";
$groupBy = "";
$selectX = "";
$orderBy = "y_value DESC";
$limit = "LIMIT $topN";

// Whitelist checks
$allowedX = ['coordinator_country', 'has_greek_any_role', 'is_greek_coordinator', 'fields_of_science', 'keyword', 'invest_priority', 'consortium_country', 'year'];
if (!in_array($xDim, $allowedX)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid x_dimension']);
    exit;
}

if ($xDim === 'coordinator_country') {
    $selectX = "p.coordinator_country as x_label";
    $groupBy = "p.coordinator_country";
    $where['sql'] .= " AND (p.coordinator_country IS NOT NULL AND p.coordinator_country != '')";

} elseif ($xDim === 'fields_of_science') {
    $table .= " JOIN project_fields pf ON p.id = pf.project_db_id";
    $selectX = "pf.level1 as x_label";
    $groupBy = "pf.level1";
    $where['sql'] .= " AND (pf.level1 IS NOT NULL AND pf.level1 != '')";

} elseif ($xDim === 'keyword') {
    $table .= " JOIN project_keywords pk ON p.id = pk.project_db_id";
    $selectX = "pk.keyword as x_label";
    $groupBy = "pk.keyword";
    $where['sql'] .= " AND (pk.keyword IS NOT NULL AND pk.keyword != '')";

} elseif ($xDim === 'invest_priority') {
    $table .= " JOIN invest_priorities ip ON p.id = ip.project_db_id";
    $selectX = "ip.label as x_label";
    $groupBy = "ip.label";
    $where['sql'] .= " AND (ip.label IS NOT NULL AND ip.label != '')";

} elseif ($xDim === 'consortium_country') {
    $table .= " JOIN project_countries pc ON p.id = pc.project_db_id";
    $selectX = "pc.country as x_label";
    $groupBy = "pc.country";
    $where['sql'] .= " AND (pc.country IS NOT NULL AND pc.country != '')";

} elseif ($xDim === 'year') {
    if (DB_DRIVER === 'sqlite') {
        $selectX = "strftime('%Y', p.signature_date) as x_label";
    } else {
        $selectX = "YEAR(p.signature_date) as x_label";
    }
    $groupBy = "x_label";
    $where['sql'] .= " AND p.signature_date IS NOT NULL";
    $orderBy = "x_label ASC";
    $limit = "";

} elseif ($xDim === 'has_greek_any_role') {
    $selectX = "CASE WHEN p.has_greek_any_role = 1 THEN 'Yes' ELSE 'No' END as x_label";
    $groupBy = "p.has_greek_any_role";
} elseif ($xDim === 'is_greek_coordinator') {
    $selectX = "CASE WHEN p.is_greek_coordinator = 1 THEN 'Yes' ELSE 'No' END as x_label";
    $groupBy = "p.is_greek_coordinator";
}

// Handle Y Metric
$selectY = "";
if ($yMetric === 'count') {
    if (strpos($table, 'JOIN') !== false) {
        $selectY = "COUNT(DISTINCT p.id) as y_value";
    } else {
        $selectY = "COUNT(*) as y_value";
    }
} elseif ($yMetric === 'sum_eu_contribution') {
    $selectY = "SUM(p.eu_contribution) as y_value";
} elseif ($yMetric === 'average_invest_priority') {
    if ($xDim === 'invest_priority') {
        $selectY = "AVG(ip.percent) as y_value";
    } else {
        // Fallback or error? Return 0
        $selectY = "0 as y_value";
    }
} else {
    $selectY = "COUNT(*) as y_value";
}

$sql = "SELECT $selectX, $selectY FROM $table WHERE " . $where['sql'] . " GROUP BY $groupBy ORDER BY $orderBy $limit";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($where['params']);
    $data = $stmt->fetchAll();

    echo json_encode([
        'labels' => array_column($data, 'x_label'),
        'series' => array_column($data, 'y_value'),
        'table_rows' => $data
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
