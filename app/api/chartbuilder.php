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

$datasetType = $input['dataset_type'] ?? 'projects';
$xDim = $input['x_dimension'] ?? 'coordinator_country';
$yMetric = $input['y_metric'] ?? 'count';
$topN = (int)($input['top_n'] ?? 10);
$filters = $input['filters'] ?? [];
$filters['dataset_id'] = $datasetId;

// =========================================================
// SUMMARY DATASET LOGIC
// =========================================================
if ($datasetType === 'summary') {
    // Determine Table
    $table = "";
    $colLabel = "";
    $colValue = "";

    // Map X Dimension to Table/Column
    switch ($xDim) {
        case 'pillar_descr':
            $table = "summary_pillar_participation";
            $colLabel = "pillar_descr";
            $colValue = "participation"; // Default unless overridden
            break;
        case 'framework_programme':
             // Could be Participation or EU. Check Y Metric.
            if ($yMetric === 'sum_eu_contribution') {
                $table = "summary_programme_eu_contribution";
                $colValue = "eu_contribution_eur";
            } else {
                $table = "summary_programme_participation";
                $colValue = "participation";
            }
            $colLabel = "framework_programme";
            break;
        case 'mission':
            $table = "summary_mission_eu_contribution";
            $colLabel = "mission";
            $colValue = "eu_contribution_eur"; // Missions are usually EU contrib in our files
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => "Invalid x_dimension for summary: $xDim"]);
            exit;
    }

    // Force Y Metric Column (if not already deduced)
    if ($yMetric === 'sum_eu_contribution' && strpos($table, 'participation') !== false) {
        // User asked for EU but selected Pillar Participation (which has no EU).
        // Fail or fallback?
        // Let's error strictly.
        http_response_code(400);
        echo json_encode(['error' => "Metric EU Contribution not available for dimension $xDim"]);
        exit;
    }

    // Groups Filter
    $groupFilter = "";
    $params = [];
    if (!empty($filters['groups'])) {
        $placeholders = implode(',', array_fill(0, count($filters['groups']), '?'));
        $groupFilter = " AND g.group_label IN ($placeholders)";
        $params = $filters['groups'];
    }

    // 1. Get All Labels (Top N)
    // We aggregate across all selected groups to find Top N overall.
    $sqlTop = "SELECT t.$colLabel, SUM(t.$colValue) as total
               FROM $table t
               JOIN summary_groups g ON t.group_id = g.id
               WHERE t.dataset_id = ? $groupFilter
               GROUP BY t.$colLabel
               ORDER BY total DESC
               LIMIT $topN";

    $stmtTop = $pdo->prepare($sqlTop);
    $stmtTop->execute(array_merge([$datasetId], $params));
    $topLabels = $stmtTop->fetchAll(PDO::FETCH_COLUMN, 0);

    // 2. Get Data Grouped
    // If no labels found, return empty
    if (empty($topLabels)) {
        echo json_encode(['labels' => [], 'datasets' => []]);
        exit;
    }

    $inLabels = implode(',', array_fill(0, count($topLabels), '?'));

    $sqlData = "SELECT g.group_label, t.$colLabel, t.$colValue
                FROM $table t
                JOIN summary_groups g ON t.group_id = g.id
                WHERE t.dataset_id = ?
                AND t.$colLabel IN ($inLabels)
                $groupFilter";

    $stmtData = $pdo->prepare($sqlData);
    $stmtData->execute(array_merge([$datasetId], $topLabels, $params));
    $rows = $stmtData->fetchAll();

    // Organize
    $map = []; // Group -> Label -> Val
    foreach ($rows as $r) {
        $map[$r['group_label']][$r[$colLabel]] = $r[$colValue];
    }

    $datasets = [];
    foreach ($map as $groupLabel => $data) {
        $dataPoint = [];
        foreach ($topLabels as $lbl) {
            $dataPoint[] = $data[$lbl] ?? 0;
        }
        $datasets[] = [
            'label' => $groupLabel,
            'data' => $dataPoint
        ];
    }

    echo json_encode([
        'labels' => $topLabels,
        'datasets' => $datasets
    ]);
    exit;
}

// =========================================================
// PROJECTS DATASET LOGIC (Existing)
// =========================================================

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

// ... (Existing Switch Case from previous step) ...
// Copied and pasted logic for existing behavior:

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
        $limit = "";
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

switch ($yMetric) {
    case 'count':
        if ($join) {
            $selectY = "COUNT(DISTINCT p.id) as y_value";
        } else {
            $selectY = "COUNT(*) as y_value";
        }
        break;

    case 'sum_eu_contribution':
        $selectY = "SUM(p.eu_contribution) as y_value";
        break;

    case 'average_invest_priority':
        if ($xDim === 'invest_priority') {
            $selectY = "AVG(ip.percent) as y_value";
        } else {
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

    foreach ($data as &$row) {
        if (isset($row['y_value'])) $row['y_value'] = (float)$row['y_value'];
    }

    // Return Single Series (Legacy Format, compatible with updated JS)
    echo json_encode([
        'labels' => array_column($data, 'x_label'),
        'series' => array_column($data, 'y_value')
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage(), 'sql' => $sql]);
}
