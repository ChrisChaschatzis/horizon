<?php
require_once __DIR__ . '/../db_connect.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? null;
$datasetId = $_GET['dataset_id'] ?? null;

if (!$datasetId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing dataset_id']);
    exit;
}

// Helper for filtering groups - returns [sql_fragment, params_array]
function getGroupFilterParams($datasetId) {
    $groups = isset($_GET['groups']) ? $_GET['groups'] : [];
    if (!is_array($groups)) {
        // Handle case where groups is not an array (e.g. string)
        $groups = [];
    }

    if (empty($groups)) return ["", []];

    $placeholders = implode(',', array_fill(0, count($groups), '?'));
    $sql = " AND g.group_label IN ($placeholders)";
    return [$sql, $groups];
}

if ($action === 'groups') {
    $stmt = $pdo->prepare("SELECT id, group_label FROM summary_groups WHERE dataset_id = ? ORDER BY group_label");
    $stmt->execute([$datasetId]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($action === 'kpis') {
    list($groupSql, $groupParams) = getGroupFilterParams($datasetId);
    $params = array_merge([$datasetId], $groupParams);

    // Total Participation
    $sqlPart = "SELECT SUM(participation) as total
                FROM summary_programme_participation sp
                JOIN summary_groups g ON sp.group_id = g.id
                WHERE sp.dataset_id = ?" . $groupSql;
    $stmt = $pdo->prepare($sqlPart);
    $stmt->execute($params);
    $totalPart = $stmt->fetchColumn() ?: 0;

    // Total EU
    $sqlEu = "SELECT SUM(eu_contribution_eur) as total
              FROM summary_programme_eu_contribution sp
              JOIN summary_groups g ON sp.group_id = g.id
              WHERE sp.dataset_id = ?" . $groupSql;
    $stmt = $pdo->prepare($sqlEu);
    $stmt->execute($params);
    $totalEu = $stmt->fetchColumn() ?: 0;

    // Group Count
    $sqlGroups = "SELECT COUNT(DISTINCT g.id)
                  FROM summary_groups g
                  WHERE g.dataset_id = ?" . $groupSql;
    $stmt = $pdo->prepare($sqlGroups);
    $stmt->execute($params);
    $groupCount = $stmt->fetchColumn() ?: 0;

    echo json_encode([
        'total_participation' => number_format($totalPart),
        'total_eu_contribution' => number_format($totalEu, 2) . ' €',
        'group_count' => $groupCount
    ]);
    exit;
}

if ($action === 'chart') {
    $type = $_GET['type'] ?? 'pillar'; // pillar, prog_part, prog_eu, mission_eu

    $table = "";
    $colLabel = "";
    $colValue = "";

    switch ($type) {
        case 'pillar':
            $table = "summary_pillar_participation";
            $colLabel = "pillar_descr";
            $colValue = "participation";
            break;
        case 'prog_part':
            $table = "summary_programme_participation";
            $colLabel = "framework_programme";
            $colValue = "participation";
            break;
        case 'prog_eu':
            $table = "summary_programme_eu_contribution";
            $colLabel = "framework_programme";
            $colValue = "eu_contribution_eur";
            break;
        case 'mission_eu':
            $table = "summary_mission_eu_contribution";
            $colLabel = "mission";
            $colValue = "eu_contribution_eur";
            break;
        default:
            echo json_encode(['error' => 'Invalid chart type']);
            exit;
    }

    // Get all unique labels first (X-axis) for this dataset
    // Safe to interpolate $table and $colLabel as they are hardcoded/whitelisted above
    $stmtL = $pdo->prepare("SELECT DISTINCT $colLabel FROM $table WHERE dataset_id = ? ORDER BY $colLabel");
    $stmtL->execute([$datasetId]);
    $allLabels = $stmtL->fetchAll(PDO::FETCH_COLUMN);

    // Get Data Grouped
    list($groupSql, $groupParams) = getGroupFilterParams($datasetId);
    $params = array_merge([$datasetId], $groupParams);

    $sql = "SELECT g.group_label, t.$colLabel, t.$colValue
            FROM $table t
            JOIN summary_groups g ON t.group_id = g.id
            WHERE t.dataset_id = ?" . $groupSql;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organize data
    // Map: Group -> Label -> Value
    $map = [];
    foreach ($rows as $r) {
        $map[$r['group_label']][$r[$colLabel]] = $r[$colValue];
    }

    $series = [];
    foreach ($map as $groupLabel => $data) {
        $dataPoint = [];
        foreach ($allLabels as $lbl) {
            $dataPoint[] = $data[$lbl] ?? 0;
        }
        $series[] = [
            'label' => $groupLabel,
            'data' => $dataPoint
        ];
    }

    echo json_encode([
        'labels' => $allLabels,
        'datasets' => $series
    ]);
    exit;
}
