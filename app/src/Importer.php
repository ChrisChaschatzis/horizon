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
        $this->pdo->beginTransaction();

        try {
            // 1. Create Dataset Record
            $stmt = $this->pdo->prepare("INSERT INTO datasets (name, excel_filename, jsonl_filename, notes) VALUES (?, ?, ?, ?)");
            $stmt->execute([$datasetName, basename($excelPath), basename($jsonlPath), $notes]);
            $datasetId = $this->pdo->lastInsertId();

            // 2. Parse Excel
            $spreadsheet = IOFactory::load($excelPath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            if (empty($rows)) {
                throw new Exception("Excel file is empty");
            }

            // Map headers
            $headers = array_shift($rows);
            // Clean headers (trim)
            $headers = array_map('trim', $headers);
            $headerMap = array_flip($headers);

            // Check for required column project_id
            if (!isset($headerMap['project_id'])) {
                throw new Exception("Excel missing required column 'project_id'");
            }

            $projectDataMap = [];

            foreach ($rows as $row) {
                $pid = $row[$headerMap['project_id']] ?? null;
                if (!$pid) continue;
                $projectDataMap[(string)$pid] = [
                    'row' => $row,
                    'map' => $headerMap
                ];
            }

            // 3. Parse JSONL
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

        $projectId = $row[$map['project_id']];
        $cordisUrl = $row[$map['cordis_url'] ?? -1] ?? '';
        $title = $row[$map['cordis_title'] ?? -1] ?? '';
        $acronym = $row[$map['cordis_acronym'] ?? -1] ?? '';
        $coordName = $row[$map['coordinator_name'] ?? -1] ?? '';
        $coordCountry = $row[$map['coordinator_country'] ?? -1] ?? '';

        $hasGreekPart = $row[$map['has_greek_participant'] ?? -1] ?? 0;
        $hasGreekBen = $row[$map['has_greek_beneficiary'] ?? -1] ?? 0;
        $hasGreekAny = $row[$map['has_greek_any_role'] ?? -1] ?? 0;
        $isGreekCoord = $row[$map['is_greek_coordinator'] ?? -1] ?? 0;

        $keywordsText = $row[$map['keywords'] ?? -1] ?? '';
        $fieldsText = $row[$map['fields_of_science'] ?? -1] ?? '';
        $investJson = $row[$map['invest_priorities_json'] ?? -1] ?? '{}';

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
                keywords_text, fields_text, invest_priorities_json
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $datasetId, $projectId, $cordisUrl, $title, $acronym,
            $coordName, $coordCountry,
            $hasGreekPart, $hasGreekBen, $hasGreekAny, $isGreekCoord,
            $keywordsText, $fieldsText, $investJson
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
