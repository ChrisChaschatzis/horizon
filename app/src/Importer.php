<?php

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

class Importer {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function import($excelPath, $jsonlPath, $datasetName, $notes = '') {
        // Increase limits for large imports
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $this->pdo->beginTransaction();

        try {
            // 1. Create Dataset Record
            $stmt = $this->pdo->prepare("INSERT INTO datasets (name, excel_filename, jsonl_filename, notes) VALUES (?, ?, ?, ?)");
            $stmt->execute([$datasetName, basename($excelPath), $jsonlPath ? basename($jsonlPath) : null, $notes]);
            $datasetId = $this->pdo->lastInsertId();

            // 2. Parse Excel
            // Using ReadDataOnly for memory efficiency since we don't need formatting
            $reader = IOFactory::createReaderForFile($excelPath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($excelPath);

            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            if (empty($rows)) {
                throw new Exception("Excel file is empty");
            }

            // Map headers
            $headers = array_shift($rows);
            // Clean headers (trim)
            $headers = array_map(function($h) { return trim((string)$h); }, $headers);
            $headerMap = array_flip($headers);

            // Map headers - Handle variations
            $map = [];
            foreach ($headerMap as $h => $idx) {
                $cleanH = strtolower(trim($h));
                $map[$cleanH] = $idx;
                // Also map snake_case versions
                $map[str_replace(' ', '_', $cleanH)] = $idx;
            }

            // Check for required column project_id (or variations)
            $pidIndex = $map['project_id'] ?? $map['project_number'] ?? $map['projectnumber'] ?? null;

            if ($pidIndex === null) {
                 // Try to find it by value? No, header is safer.
                 // Debug:
                 // throw new Exception("Excel missing required column 'project_id' or 'Project number'. Found: " . implode(', ', array_keys($headerMap)));
                 throw new Exception("Excel missing required column 'project_id' or 'Project number'");
            }

            $projectDataMap = [];

            foreach ($rows as $row) {
                $pid = $row[$pidIndex] ?? null;
                if (!$pid) continue;
                $projectDataMap[(string)$pid] = [
                    'row' => $row,
                    'map' => $map
                ];
            }

            // 3. Parse JSONL (if provided)
            if ($jsonlPath && file_exists($jsonlPath)) {
                $jsonlHandle = fopen($jsonlPath, 'r');
                if ($jsonlHandle) {
                    while (($line = fgets($jsonlHandle)) !== false) {
                        $jsonObj = json_decode($line, true);
                        if (!$jsonObj || !isset($jsonObj['project_id'])) continue;

                        $pid = (string)$jsonObj['project_id'];

                        if (isset($projectDataMap[$pid])) {
                            $this->insertProject($datasetId, $projectDataMap[$pid], $jsonObj);
                            unset($projectDataMap[$pid]);
                        }
                    }
                    fclose($jsonlHandle);
                }
            }

            // Insert remaining projects
            foreach ($projectDataMap as $pid => $data) {
                 $this->insertProject($datasetId, $data, null);
            }

            $this->pdo->commit();
            return ['success' => true, 'dataset_id' => $datasetId];

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function insertProject($datasetId, $excelData, $jsonData) {
        $row = $excelData['row'];
        $map = $excelData['map'];

        $projectId = $row[$map['project_id'] ?? $map['project_number'] ?? $map['projectnumber']];
        $cordisUrl = $row[$map['cordis_url'] ?? $map['cordis_link'] ?? $map['cordislink'] ?? -1] ?? '';
        $title = $row[$map['cordis_title'] ?? $map['title'] ?? -1] ?? '';
        $acronym = $row[$map['cordis_acronym'] ?? $map['project_acronym'] ?? $map['acronym'] ?? -1] ?? '';
        $coordName = $row[$map['coordinator_name'] ?? -1] ?? '';
        $coordCountry = $row[$map['coordinator_country'] ?? -1] ?? '';

        $hasGreekPart = $row[$map['has_greek_participant'] ?? -1] ?? 0;
        $hasGreekBen = $row[$map['has_greek_beneficiary'] ?? -1] ?? 0;
        $hasGreekAny = $row[$map['has_greek_any_role'] ?? -1] ?? 0;
        $isGreekCoord = $row[$map['is_greek_coordinator'] ?? -1] ?? 0;

        $keywordsText = $row[$map['keywords'] ?? -1] ?? '';
        $fieldsText = $row[$map['fields_of_science'] ?? -1] ?? '';
        $investJson = $row[$map['invest_priorities_json'] ?? -1] ?? '{}';

        // Handle explicit column names from user if they differ
        // "Project Net EU Contribution (EUR)" might be eu_contribution
        $euContribution = $row[$map['eu_contribution'] ?? $map['project_eu_contribution_(eur)'] ?? $map['project_net_eu_contribution_(eur)'] ?? -1] ?? null;
        $totalCost = $row[$map['total_cost'] ?? $map['project_total_cost_(eur)'] ?? -1] ?? null;
        $status = $row[$map['status'] ?? $map['project_status'] ?? -1] ?? null;
        $signatureDate = $row[$map['signature_date'] ?? -1] ?? null;
        $pillar = $row[$map['pillar'] ?? -1] ?? null;
        $typeOfAction = $row[$map['type_of_action'] ?? -1] ?? null;

        // Enrichment
        if ($jsonData) {
            if (empty($coordCountry) && isset($jsonData['coordinator_country'])) {
                $coordCountry = $jsonData['coordinator_country'];
            }
            if (isset($jsonData['keywords']) && is_array($jsonData['keywords'])) {
                $keywordsText = implode('; ', $jsonData['keywords']);
            }
            if (isset($jsonData['fields_of_science']) && is_array($jsonData['fields_of_science'])) {
                $fieldsText = implode('; ', $jsonData['fields_of_science']);
            }
            if (isset($jsonData['invest_priorities']) && (is_array($jsonData['invest_priorities']) || is_object($jsonData['invest_priorities']))) {
                $investJson = json_encode($jsonData['invest_priorities']);
            }
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO projects (
                dataset_id, project_id, cordis_url, title, acronym,
                coordinator_name, coordinator_country,
                has_greek_participant, has_greek_beneficiary, has_greek_any_role, is_greek_coordinator,
                keywords_text, fields_text, invest_priorities_json,
                eu_contribution, total_cost, status, signature_date, pillar, type_of_action
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $datasetId, $projectId, $cordisUrl, $title, $acronym,
            $coordName, $coordCountry,
            $hasGreekPart, $hasGreekBen, $hasGreekAny, $isGreekCoord,
            $keywordsText, $fieldsText, $investJson,
            $euContribution, $totalCost, $status, $signatureDate, $pillar, $typeOfAction
        ]);

        $projectDbId = $this->pdo->lastInsertId();

        // Normalized Tables

        // Coordinator
        if ($coordCountry) {
            $this->insertCountry($datasetId, $projectDbId, $coordCountry, 'coordinator');
        }

        // JSONL Participants/Beneficiaries
        if ($jsonData) {
            if (isset($jsonData['participants']) && is_array($jsonData['participants'])) {
                foreach ($jsonData['participants'] as $p) {
                    if (isset($p['country'])) {
                        $this->insertCountry($datasetId, $projectDbId, $p['country'], 'participant');
                    }
                }
            }
            if (isset($jsonData['beneficiaries']) && is_array($jsonData['beneficiaries'])) {
                foreach ($jsonData['beneficiaries'] as $b) {
                    if (isset($b['country'])) {
                         $this->insertCountry($datasetId, $projectDbId, $b['country'], 'beneficiary');
                    }
                }
            }
        }

        // Keywords
        if ($keywordsText) {
            $keywords = explode(';', $keywordsText);
            foreach ($keywords as $k) {
                $k = trim($k);
                if ($k) {
                    $this->insertKeyword($datasetId, $projectDbId, $k);
                }
            }
        }

        // Fields
        if ($fieldsText) {
             $fields = explode(';', $fieldsText);
             foreach ($fields as $f) {
                 $f = trim($f);
                 if ($f) {
                     $this->insertField($datasetId, $projectDbId, $f);
                 }
             }
        }

        // Priorities
        $priorities = json_decode($investJson, true);
        if ($priorities) {
            foreach ($priorities as $label => $percent) {
                $this->insertPriority($datasetId, $projectDbId, $label, $percent);
            }
        }
    }

    private function insertCountry($datasetId, $projectDbId, $country, $role) {
        $stmt = $this->pdo->prepare("INSERT INTO project_countries (dataset_id, project_db_id, country, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$datasetId, $projectDbId, $country, $role]);
    }

    private function insertKeyword($datasetId, $projectDbId, $keyword) {
        $stmt = $this->pdo->prepare("INSERT INTO project_keywords (dataset_id, project_db_id, keyword) VALUES (?, ?, ?)");
        $stmt->execute([$datasetId, $projectDbId, $keyword]);
    }

    private function insertField($datasetId, $projectDbId, $path) {
        $parts = explode('>', $path);
        $l1 = isset($parts[0]) ? trim($parts[0]) : null;
        $l2 = isset($parts[1]) ? trim($parts[1]) : null;
        $l3 = isset($parts[2]) ? trim($parts[2]) : null;

        $stmt = $this->pdo->prepare("INSERT INTO project_fields (dataset_id, project_db_id, path_text, level1, level2, level3) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$datasetId, $projectDbId, $path, $l1, $l2, $l3]);
    }

    private function insertPriority($datasetId, $projectDbId, $label, $percent) {
        $stmt = $this->pdo->prepare("INSERT INTO invest_priorities (dataset_id, project_db_id, label, percent) VALUES (?, ?, ?, ?)");
        $stmt->execute([$datasetId, $projectDbId, $label, $percent]);
    }
}
