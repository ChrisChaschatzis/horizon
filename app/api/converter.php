<?php

header('Content-Type: application/json');
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Configuration
$UPLOAD_DIR = __DIR__ . '/../storage/temp';
$PACKS_DIR = __DIR__ . '/../storage/packs';
if (!file_exists($UPLOAD_DIR)) mkdir($UPLOAD_DIR, 0777, true);
if (!file_exists($PACKS_DIR)) mkdir($PACKS_DIR, 0777, true);

$action = $_GET['action'] ?? null;

if ($action === 'upload') {
    handleUpload($UPLOAD_DIR);
} elseif ($action === 'merge') {
    handleMerge($UPLOAD_DIR, $PACKS_DIR);
} elseif ($action === 'download') {
    handleDownload($PACKS_DIR);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}

/**
 * Handle File Uploads
 */
function handleUpload($dir) {
    if (empty($_FILES['files'])) {
        http_response_code(400);
        echo json_encode(['error' => 'No files uploaded']);
        return;
    }

    $uploaded = [];
    $files = $_FILES['files'];

    // Normalize $_FILES structure
    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $tmpName = $files['tmp_name'][$i];
            $origName = $files['name'][$i];
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

            if ($ext !== 'xlsx') {
                continue; // Skip non-excel
            }

            $newFilename = uniqid('upload_') . '_' . $origName;
            $dest = $dir . '/' . $newFilename;

            if (move_uploaded_file($tmpName, $dest)) {
                $type = detectFileType($dest);
                $uploaded[] = [
                    'file_name' => $origName,
                    'server_file' => $newFilename,
                    'detected_type' => $type,
                    'status' => $type === 'UNKNOWN' ? 'warning' : 'ok'
                ];
            }
        }
    }

    echo json_encode(['files' => $uploaded]);
}

/**
 * Detect File Type based on Headers
 */
function detectFileType($filepath) {
    try {
        $reader = IOFactory::createReaderForFile($filepath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filepath);
        $sheet = $spreadsheet->getActiveSheet();

        // Check first row
        $row1 = $sheet->rangeToArray('A1:E1')[0]; // Read first few columns
        $headers = array_map(function($h) { return strtolower(trim((string)$h)); }, $row1);

        // Helper to check containment
        $has = function($colName) use ($headers) {
            foreach ($headers as $h) {
                if (strpos($h, strtolower($colName)) !== false) return true;
            }
            return false;
        };

        // 1. Pillar Participation
        // Columns: "Pillar Descr", "Participation"
        if ($has('pillar desc') && $has('participation')) return 'PILLAR_PARTICIPATION';

        // 2. Programme Participation
        // Columns: "Framework Programme", "Participation"
        // Need to distinguish from Programme EU
        if ($has('framework programme') && $has('participation')) return 'PROGRAMME_PARTICIPATION';

        // 3. Programme EU Contribution
        // Columns: "Framework Programme", "EU Contribution"
        if ($has('framework programme') && $has('eu contribution')) return 'PROGRAMME_EU_CONTRIB';

        // 4. Mission EU Contribution
        // Columns: "Missions", "EU Contribution"
        if (($has('mission') || $has('missions')) && $has('eu contribution')) return 'MISSION_EU_CONTRIB';

        // 5. Country Net EU
        // Columns: "Country/Territory", "Net EU Contribution"
        if ($has('country') && $has('net eu contribution')) return 'COUNTRY_NET_EU_CONTRIB';

        // 6. Country Ranks
        // Columns: "Country/Territory", "Rank Contribution..."
        if ($has('country') && ($has('rank contribution') || $has('rank participations'))) return 'COUNTRY_RANKS';

        // 7. Participation Rank
        // Columns: "Participation rank"
        if ($has('participation rank')) return 'PARTICIPATION_RANK';

        // 8. Budget Share Rank
        // Columns: "Budget share rank"
        if ($has('budget share rank')) return 'BUDGET_SHARE_RANK';

        return 'UNKNOWN';

    } catch (Exception $e) {
        return 'UNKNOWN';
    }
}

/**
 * Merge Logic
 */
function handleMerge($uploadDir, $packsDir) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['groups'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid merge request']);
        return;
    }

    // groups structure:
    // [ { label: 'Greece', files: [ { server_file: '...', type: '...' }, ... ] }, ... ]

    $spreadsheet = new Spreadsheet();
    $spreadsheet->removeSheetByIndex(0); // Remove default sheet

    // Aggregators for numeric data to handle duplicates
    // Structure: $aggregates['sheet_name']['group_label']['key'] = value
    $aggregates = [
        'pillar_participation' => [],
        'programme_participation' => [],
        'programme_eu_contribution' => [],
        'mission_eu_contribution' => [],
        'country_net_eu_contribution' => []
    ];

    // Raw data holders for non-aggregatable or complex structures
    $raw_data = [
        'country_ranks' => [['group_label', 'country_territory', 'rank_contribution_per_inhabitant', 'rank_participations', 'rank_budget_share', 'rank_participations_eic_pilot', 'rank_seal_of_excellence', 'rank_participations_sme_instrument']],
        'single_ranks' => [['group_label', 'metric_key', 'rank_position', 'rank_total', 'raw_text']]
    ];

    $groupsPresent = [];

    foreach ($input['groups'] as $group) {
        $label = trim($group['label']);
        if (!$label) continue;
        $groupsPresent[] = $label;

        foreach ($group['files'] as $fileInfo) {
            $path = $uploadDir . '/' . basename($fileInfo['server_file']);
            $type = $fileInfo['type'];

            if (!file_exists($path)) continue;

            try {
                processFile($path, $type, $label, $aggregates, $raw_data);
            } catch (Exception $e) {
                // Log error but continue
            }
        }
    }

    // Convert aggregated data back to rows
    $final_sheets = [];

    // 1. Pillar
    $rows = [['group_label', 'pillar_descr', 'participation']];
    foreach ($aggregates['pillar_participation'] as $grp => $items) {
        foreach ($items as $key => $val) $rows[] = [$grp, $key, $val];
    }
    $final_sheets['pillar_participation'] = $rows;

    // 2. Prog Part
    $rows = [['group_label', 'framework_programme', 'participation']];
    foreach ($aggregates['programme_participation'] as $grp => $items) {
        foreach ($items as $key => $val) $rows[] = [$grp, $key, $val];
    }
    $final_sheets['programme_participation'] = $rows;

    // 3. Prog EU
    $rows = [['group_label', 'framework_programme', 'eu_contribution_eur']];
    foreach ($aggregates['programme_eu_contribution'] as $grp => $items) {
        foreach ($items as $key => $val) $rows[] = [$grp, $key, $val];
    }
    $final_sheets['programme_eu_contribution'] = $rows;

    // 4. Mission EU
    $rows = [['group_label', 'mission', 'eu_contribution_eur']];
    foreach ($aggregates['mission_eu_contribution'] as $grp => $items) {
        foreach ($items as $key => $val) $rows[] = [$grp, $key, $val];
    }
    $final_sheets['mission_eu_contribution'] = $rows;

    // 5. Country Net EU
    $rows = [['group_label', 'country_territory', 'net_eu_contribution_eur']];
    foreach ($aggregates['country_net_eu_contribution'] as $grp => $items) {
        foreach ($items as $key => $val) $rows[] = [$grp, $key, $val];
    }
    $final_sheets['country_net_eu_contribution'] = $rows;

    // Add raw sheets
    foreach($raw_data as $key => $rows) {
        $final_sheets[$key] = $rows;
    }

    // Create Sheets
    foreach ($final_sheets as $sheetName => $rows) {
        if (count($rows) > 1) { // If has data (more than header)
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle(substr($sheetName, 0, 31)); // Max 31 chars
            $sheet->fromArray($rows);
        }
    }

    // Create META Sheet
    $metaSheet = $spreadsheet->createSheet(0);
    $metaSheet->setTitle('meta');
    $metaSheet->fromArray([
        ['key', 'value'],
        ['created_at', date('Y-m-d H:i:s')],
        ['version', 'summary_merge_v1'],
        ['groups_present', implode(', ', $groupsPresent)],
        ['notes', 'Generated by CORDIS BI Converter']
    ]);

    // Save
    $filename = 'pack_' . date('Ymd_His') . '.xlsx';
    $writer = new Xlsx($spreadsheet);
    $writer->save($packsDir . '/' . $filename);

    echo json_encode([
        'success' => true,
        'filename' => $filename,
        'download_url' => "api/converter.php?action=download&file=$filename"
    ]);
}

function processFile($path, $type, $label, &$aggregates, &$raw_data) {
    $reader = IOFactory::createReaderForFile($path);
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($path);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    if (empty($rows)) return;

    $headers = array_shift($rows); // Skip header row

    // Helper to safely add to aggregate
    // Structure: $aggregates[type][group][key] += val
    $add = function($typeKey, $grp, $key, $val) use (&$aggregates) {
        if (!isset($aggregates[$typeKey][$grp])) $aggregates[$typeKey][$grp] = [];
        if (!isset($aggregates[$typeKey][$grp][$key])) $aggregates[$typeKey][$grp][$key] = 0;
        $aggregates[$typeKey][$grp][$key] += $val;
    };

    foreach ($rows as $row) {
        // Skip empty rows
        if (implode('', $row) === '') continue;

        switch ($type) {
            case 'PILLAR_PARTICIPATION':
                // A: Pillar, B: Part
                $add('pillar_participation', $label, trim($row[0]), (int)$row[1]);
                break;
            case 'PROGRAMME_PARTICIPATION':
                // A: Prog, B: Part
                $add('programme_participation', $label, trim($row[0]), (int)$row[1]);
                break;
            case 'PROGRAMME_EU_CONTRIB':
                // A: Prog, B: EU
                $add('programme_eu_contribution', $label, trim($row[0]), (float)$row[1]);
                break;
            case 'MISSION_EU_CONTRIB':
                // A: Mission, B: EU
                $add('mission_eu_contribution', $label, trim($row[0]), (float)$row[1]);
                break;
            case 'COUNTRY_NET_EU_CONTRIB':
                // A: Country, B: Net EU
                $add('country_net_eu_contribution', $label, trim($row[0]), (float)$row[1]);
                break;
             case 'COUNTRY_RANKS':
                // Not aggregated, just appended
                $c = $row;
                $raw_data['country_ranks'][] = [
                    $label,
                    $c[0] ?? '',
                    $c[1] ?? '',
                    $c[2] ?? '',
                    $c[3] ?? '',
                    $c[4] ?? '',
                    $c[5] ?? '',
                    $c[6] ?? ''
                ];
                break;
            case 'PARTICIPATION_RANK':
                // Not aggregated
                parseRank($label, 'participation_rank', $row[0] ?? '', $raw_data);
                break;
            case 'BUDGET_SHARE_RANK':
                // Not aggregated
                parseRank($label, 'budget_share_rank', $row[0] ?? '', $raw_data);
                break;
        }
    }
}

function parseRank($label, $key, $text, &$raw_data) {
    // text: "7 out of 27"
    $pos = null;
    $total = null;
    if (preg_match('/(\d+)\s*out\s*of\s*(\d+)/i', $text, $matches)) {
        $pos = (int)$matches[1];
        $total = (int)$matches[2];
    }
    // Check if we should override or append? Ranks are usually single per entity.
    // Let's append, user can see duplicates if they uploaded multiple.
    $raw_data['single_ranks'][] = [$label, $key, $pos, $total, $text];
}


/**
 * Handle Download
 */
function handleDownload($dir) {
    $file = basename($_GET['file'] ?? '');
    $path = $dir . '/' . $file;

    if (!$file || !file_exists($path)) {
        http_response_code(404);
        die("File not found");
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $file . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}
