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

$filters = $_GET; // Use all query params
// Ensure dataset_id is in filters for helper
$filters['dataset_id'] = $datasetId;

$where = FilterHelper::buildWhereClause($filters, 'p');

$sql = "SELECT
    COUNT(*) as total_projects,
    SUM(p.has_greek_any_role) as projects_with_greece_any_role,
    SUM(p.is_greek_coordinator) as projects_with_greek_coordinator
    FROM projects p
    WHERE " . $where['sql'];

try {
    $stmt = $pdo->prepare($sql);

    // Bind parameters
    foreach ($where['params'] as $key => $val) {
        $stmt->bindValue(":$key", $val); // Explicit binding to be safe with types if needed, but execute handles strings well.
        // Actually simpler to just execute($params) but let's see.
    }
    $stmt->execute(); // execute() with bound params if using bindValue. Or pass array.
    // If I used bindValue, I call execute().
    // If I pass array to execute, I don't use bindValue.

    // Let's use array execution as it's cleaner with FilterHelper returning array.
    // But FilterHelper returned keys without colon.
    // PDO execute requires keys to match placeholder names (with or without colon).
    // Let's check FilterHelper logic.
    // keys: dataset_id, cc_0...
    // sql: :dataset_id, :cc_0...
    // This works fine.

    $stmt->execute($where['params']);

    $result = $stmt->fetch();

    $total = $result['total_projects'] ?? 0;
    $greekAny = $result['projects_with_greece_any_role'] ?? 0;
    $greekCoord = $result['projects_with_greek_coordinator'] ?? 0;

    $kpis = [
        'total_projects' => (int)$total,
        'projects_with_greece_any_role' => (int)$greekAny,
        'percent_with_greece_any_role' => $total > 0 ? round(($greekAny / $total) * 100, 2) : 0,
        'projects_with_greek_coordinator' => (int)$greekCoord,
        'percent_with_greek_coordinator' => $total > 0 ? round(($greekCoord / $total) * 100, 2) : 0
    ];

    echo json_encode($kpis);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
