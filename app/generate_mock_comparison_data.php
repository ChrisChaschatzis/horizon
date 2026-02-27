<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$outputDir = __DIR__ . '/data/mock_comparison';
if (!file_exists($outputDir)) mkdir($outputDir, 0777, true);

$entities = [
    'Greece' => 1.0,  // Base Multiplier
    'Germany' => 5.0,
    'Belgium' => 1.5,
    'EU_Total' => 20.0
];

echo "Generating comparison mock data in $outputDir...\n";

foreach ($entities as $entityName => $multiplier) {
    echo "Processing $entityName...\n";
    $entityDir = $outputDir . '/' . $entityName;
    if (!file_exists($entityDir)) mkdir($entityDir, 0777, true);

    // 1. Pillar Participation
    createExcel(
        $entityDir . '/pillar_participation.xlsx',
        ['Pillar Descr', 'Participation'],
        [
            ['Excellent Science', (int)(150 * $multiplier)],
            ['Global Challenges and European Industrial Competitiveness', (int)(300 * $multiplier)],
            ['Innovative Europe', (int)(80 * $multiplier)],
            ['Widening Participation and Strengthening the ERA', (int)(20 * $multiplier)]
        ]
    );

    // 2. Programme Participation
    createExcel(
        $entityDir . '/programme_participation.xlsx',
        ['Framework Programme', 'Participation'],
        [
            ['Horizon Europe', (int)(450 * $multiplier)],
            ['Erasmus+', (int)(50 * $multiplier)],
            ['Euratom', (int)(10 * $multiplier)],
            ['Digital Europe', (int)(40 * $multiplier)]
        ]
    );

    // 3. Programme EU Contribution
    createExcel(
        $entityDir . '/programme_eu_contribution.xlsx',
        ['Framework Programme', 'EU Contribution (eur)'],
        [
            ['Horizon Europe', 15000000 * $multiplier],
            ['Erasmus+', 2000000 * $multiplier],
            ['Euratom', 500000 * $multiplier],
            ['Digital Europe', 3000000 * $multiplier]
        ]
    );

    // 4. Mission EU Contribution
    createExcel(
        $entityDir . '/mission_eu_contribution.xlsx',
        ['Missions', 'EU Contribution (eur)'],
        [
            ['Cancer', 2000000 * $multiplier],
            ['Adaptation to Climate Change', 3500000 * $multiplier],
            ['Restore our Ocean and Waters', 1500000 * $multiplier],
            ['Climate-Neutral and Smart Cities', 4000000 * $multiplier],
            ['A Soil Deal for Europe', 1000000 * $multiplier]
        ]
    );
}

echo "Done! Use these files in the 'Converter' page.\n";

function createExcel($filename, $headers, $data) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray($headers, NULL, 'A1');
    $sheet->fromArray($data, NULL, 'A2');

    $writer = new Xlsx($spreadsheet);
    $writer->save($filename);
}
