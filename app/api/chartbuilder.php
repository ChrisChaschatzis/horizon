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
$whereClause = FilterHelper::buildWhereClause($filters, 'p');
$whereSql = $whereClause['sql'];
$params = $whereClause['params'];

// Query Construction
$selectX = "";
$selectY = "";
$table = "projects p";
$join = "";
$groupBy = "";
$orderBy = "y_value DESC";
$limit = $topN > 0 ? "LIMIT $topN" : "";

// Validate X Dimension
$allowedX = [
    'coordinator_country', 'consortium_country',
    'fields_of_science', 'keyword',
    'invest_priority',
    'pillar', 'type_of_action',
    'year',
    'has_greek_any_role', 'is_greek_coordinator'
];

if (!in_array($xDim, $allowedX)) {
    http_response_code(400);
    echo json_encode(['error' => "Invalid x_dimension: $xDim"]);
    exit;
}

// Configure X Dimension
switch ($xDim) {
    case 'coordinator_country':
        $selectX = "p.coordinator_country as x_label";
        $groupBy = "p.coordinator_country";
        $whereSql .= " AND (p.coordinator_country IS NOT NULL AND p.coordinator_country != '')";
        break;

    case 'consortium_country':
        $join .= " JOIN project_countries pc ON p.id = pc.project_db_id ";
        $selectX = "pc.country as x_label";
        $groupBy = "pc.country";
        $whereSql .= " AND (pc.country IS NOT NULL AND pc.country != '')";
        break;

    case 'fields_of_science':
        $join .= " JOIN project_fields pf ON p.id = pf.project_db_id ";
        $selectX = "pf.level1 as x_label";
        $groupBy = "pf.level1";
        $whereSql .= " AND (pf.level1 IS NOT NULL AND pf.level1 != '')";
        break;

    case 'keyword':
        $join .= " JOIN project_keywords pk ON p.id = pk.project_db_id ";
        $selectX = "pk.keyword as x_label";
        $groupBy = "pk.keyword";
        $whereSql .= " AND (pk.keyword IS NOT NULL AND pk.keyword != '')";
        break;

    case 'invest_priority':
        $join .= " JOIN invest_priorities ip ON p.id = ip.project_db_id ";
        $selectX = "ip.label as x_label";
        $groupBy = "ip.label";
        $whereSql .= " AND (ip.label IS NOT NULL AND ip.label != '')";
        break;

    case 'pillar':
        $selectX = "p.pillar as x_label";
        $groupBy = "p.pillar";
        $whereSql .= " AND (p.pillar IS NOT NULL AND p.pillar != '')";
        break;

    case 'type_of_action':
        $selectX = "p.type_of_action as x_label";
        $groupBy = "p.type_of_action";
        $whereSql .= " AND (p.type_of_action IS NOT NULL AND p.type_of_action != '')";
        break;

    case 'year':
        if (defined('DB_DRIVER') && DB_DRIVER === 'sqlite') {
            $selectX = "strftime('%Y', p.signature_date) as x_label";
        } else {
            $selectX = "YEAR(p.signature_date) as x_label";
        }
        $groupBy = "x_label";
        $whereSql .= " AND p.signature_date IS NOT NULL";
        $orderBy = "x_label ASC";
        $limit = ""; // Show all years usually
        break;

    case 'has_greek_any_role':
        $selectX = "CASE WHEN p.has_greek_any_role = 1 THEN 'Ναι' ELSE 'Όχι' END as x_label";
        $groupBy = "p.has_greek_any_role";
        break;

    case 'is_greek_coordinator':
        $selectX = "CASE WHEN p.is_greek_coordinator = 1 THEN 'Ναι' ELSE 'Όχι' END as x_label";
        $groupBy = "p.is_greek_coordinator";
        break;
}

// Configure Y Metric
switch ($yMetric) {
    case 'count':
        // If we are joining 1:M tables (like keywords), COUNT(*) counts rows, not projects.
        // We want project count.
        if ($join) {
            $selectY = "COUNT(DISTINCT p.id) as y_value";
        } else {
            $selectY = "COUNT(*) as y_value";
        }
        break;

    case 'sum_eu_contribution':
        // If joining, distinct project sum is tricky in SQL directly without subquery or advanced logic.
        // But usually "Sum of contribution for projects with Keyword X" allows double counting (if project has Keyword X and Y, its budget counts for both).
        // So SUM(p.eu_contribution) is correct for "Attributed Budget".
        $selectY = "SUM(p.eu_contribution) as y_value";
        break;

    case 'average_invest_priority':
        if ($xDim === 'invest_priority') {
            $selectY = "AVG(ip.percent) as y_value";
        } else {
            // Invalid combination, return 0
            $selectY = "0 as y_value";
        }
        break;

    default:
        $selectY = "COUNT(*) as y_value";
}

$sql = "SELECT $selectX, $selectY
        FROM $table
        $join
        WHERE $whereSql
        GROUP BY $groupBy
        ORDER BY $orderBy
        $limit";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    // Convert float strings to float
    foreach ($data as &$row) {
        if (isset($row['y_value'])) $row['y_value'] = (float)$row['y_value'];
    }

    echo json_encode([
        'labels' => array_column($data, 'x_label'),
        'series' => array_column($data, 'y_value'),
        'debug_sql' => $sql // Optional debugging
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage(), 'sql' => $sql]);
}
