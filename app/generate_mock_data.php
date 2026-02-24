<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$projectCount = 50;
$projects = [];
$countries = ['EL', 'DE', 'FR', 'IT', 'ES', 'NL', 'BE'];
$keywordsList = ['Artificial Intelligence', 'Climate Change', 'Green Energy', 'Health', 'Digital', 'Space', 'Security'];
$priorities = ['Digital Europe', 'Green Deal', 'Health Union'];

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Projects_enriched');

// Header
$headers = [
    'project_id', 'cordis_url', 'cordis_title', 'cordis_acronym',
    'coordinator_name', 'coordinator_country',
    'has_greek_participant', 'has_greek_beneficiary', 'has_greek_any_role', 'is_greek_coordinator',
    'keywords', 'invest_priorities_json', 'fields_of_science', 'error',
    'pillar', 'type_of_action', 'signature_date', 'eu_contribution', 'total_cost', 'status'
];
$sheet->fromArray($headers, NULL, 'A1');

$jsonlData = [];

for ($i = 1; $i <= $projectCount; $i++) {
    $projectId = 100000 + $i;
    $coordCountry = $countries[array_rand($countries)];
    $isGreekCoord = ($coordCountry === 'EL');
    $hasGreekPart = (rand(0, 10) > 6); // 40% chance
    $hasGreekBen = (rand(0, 10) > 7); // 30% chance

    // Ensure logical consistency for flags
    if ($isGreekCoord) {
        $hasGreekAny = true;
    } else {
        $hasGreekAny = ($hasGreekPart || $hasGreekBen);
    }

    $selectedKeywords = [];
    $kCount = rand(1, 3);
    for($k=0; $k<$kCount; $k++) {
        $selectedKeywords[] = $keywordsList[array_rand($keywordsList)];
    }
    $selectedKeywords = array_unique($selectedKeywords);

    $priorityData = [];
    foreach($priorities as $p) {
        if (rand(0,1)) {
            $priorityData[$p] = rand(10, 50);
        }
    }

    $fields = ["Natural sciences > Computer and information sciences > Artificial intelligence", "Engineering and technology > Electrical engineering, electronic engineering, information engineering"];

    $row = [
        $projectId,
        "https://cordis.europa.eu/project/id/$projectId",
        "Project Title $projectId",
        "ACRONYM-$projectId",
        "Coordinator Org $projectId",
        $coordCountry,
        $hasGreekPart ? 1 : 0,
        $hasGreekBen ? 1 : 0,
        $hasGreekAny ? 1 : 0,
        $isGreekCoord ? 1 : 0,
        implode('; ', $selectedKeywords),
        json_encode($priorityData),
        implode('; ', $fields),
        '', // error
        'Pillar II',
        'RIA',
        date('Y-m-d', strtotime("-".rand(0, 1000)." days")),
        rand(100000, 5000000),
        rand(100000, 6000000),
        'SIGNED'
    ];

    $sheet->fromArray($row, NULL, 'A' . ($i + 1));

    // JSONL Data
    $participants = [];
    $pCount = rand(2, 5);
    for($p=0; $p<$pCount; $p++) {
        $pCtry = $countries[array_rand($countries)];
        $participants[] = ['name' => "Participant $p", 'country' => $pCtry];
    }
    if ($hasGreekPart) {
        $participants[] = ['name' => "Greek Participant", 'country' => 'EL'];
    }

    $jsonObj = [
        'project_id' => (string)$projectId, // Ensure string match
        'coordinator_country' => $coordCountry,
        'participants' => $participants,
        'beneficiaries' => [],
        'keywords' => $selectedKeywords,
        'invest_priorities' => $priorityData,
        'fields_of_science' => $fields,
        'error' => null
    ];
    $jsonlData[] = json_encode($jsonObj);
}

// Save Excel
$writer = new Xlsx($spreadsheet);
$timestamp = date('Ymd_His');
$xlsxFile = __DIR__ . "/cordis_enriched_{$timestamp}.xlsx";
$writer->save($xlsxFile);

// Save JSONL
$jsonlFile = __DIR__ . "/cordis_results_{$timestamp}.jsonl";
file_put_contents($jsonlFile, implode("\n", $jsonlData));

echo "Generated mock data:\n";
echo "Excel: $xlsxFile\n";
echo "JSONL: $jsonlFile\n";
