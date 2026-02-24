<?php

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class Importer {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function import($excelPath, $jsonlPath, $datasetName, $notes = '') {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $this->pdo->beginTransaction();

        try {
            // 1. Create Dataset Record
            $stmt = $this->pdo->prepare("INSERT INTO datasets (name, excel_filename, jsonl_filename, notes) VALUES (?, ?, ?, ?)");
            $stmt->execute([$datasetName, basename($excelPath), $jsonlPath ? basename($jsonlPath) : null, $notes]);
            $datasetId = $this->pdo->lastInsertId();

            // 2. Parse Excel
            $reader = IOFactory::createReaderForFile($excelPath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($excelPath);

            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            if (empty($rows)) {
                throw new Exception("Το αρχείο Excel είναι κενό");
            }

            // Map headers
            $headers = array_shift($rows);
            $headers = array_map(function($h) { return trim((string)$h); }, $headers);
            $headerMap = array_flip($headers);

            // Flexible Mapping
            $map = [];
            foreach ($headerMap as $h => $idx) {
                $cleanH = strtolower(trim($h));
                $map[$cleanH] = $idx;
                $map[str_replace(' ', '_', $cleanH)] = $idx;
            }

            // Check required ID column
            $pidIndex = $map['project_id'] ?? $map['project_number'] ?? $map['projectnumber'] ?? null;
            if ($pidIndex === null) {
                 throw new Exception("Λείπει η υποχρεωτική στήλη 'project_id' ή 'Project number' από το Excel");
            }

            // Index Excel Rows by Project ID
            $projectDataMap = [];
            foreach ($rows as $row) {
                $pid = $row[$pidIndex] ?? null;
                if (!$pid) continue;
                $projectDataMap[(string)$pid] = [
                    'row' => $row,
                    'map' => $map
                ];
            }

            // 3. Parse JSONL (if provided) and Merge
            if ($jsonlPath && file_exists($jsonlPath)) {
                $jsonlHandle = fopen($jsonlPath, 'r');
                if ($jsonlHandle) {
                    while (($line = fgets($jsonlHandle)) !== false) {
                        $jsonObj = json_decode($line, true);
                        if (!$jsonObj || !isset($jsonObj['project_id'])) continue;

                        $pid = (string)$jsonObj['project_id'];

                        if (isset($projectDataMap[$pid])) {
                            // Found in Excel, Insert with JSONL enrichment
                            $this->insertProject($datasetId, $projectDataMap[$pid]['row'], $projectDataMap[$pid]['map'], $jsonObj);
                            unset($projectDataMap[$pid]); // Remove processed
                        }
                    }
                    fclose($jsonlHandle);
                }
            }

            // 4. Insert Remaining Projects (Excel only)
            foreach ($projectDataMap as $pid => $data) {
                 $this->insertProject($datasetId, $data['row'], $data['map'], null);
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

    private function insertProject($datasetId, $row, $map, $jsonData) {
        // Helper to get value from Excel row
        $get = function($keys, $default = null) use ($row, $map) {
            if (!is_array($keys)) $keys = [$keys];
            foreach ($keys as $k) {
                $k = strtolower($k);
                if (isset($map[$k])) return $row[$map[$k]];
                $k_snake = str_replace(' ', '_', $k);
                if (isset($map[$k_snake])) return $row[$map[$k_snake]];
            }
            return $default;
        };

        // --- Core Identity ---
        $projectId = $get(['project_id', 'project_number']);
        $projectNumber = $get(['project_number', 'project_id']);
        $acronym = $get(['project_acronym', 'acronym', 'cordis_acronym']);
        $title = $get(['cordis_title', 'title']);
        $cordisUrl = $get(['cordis_url', 'cordis_link']);

        // --- Metadata ---
        $framework = $get(['framework_programme']);
        $pillar = $get(['pillar']);
        $thematicPriority = $get(['thematic_priority']);
        $typeOfAction = $get(['type_of_action']);
        $status = $get(['project_status', 'status']);
        $signatureDate = $get(['signature_date']);

        // Date parsing
        if ($signatureDate) {
             if (is_numeric($signatureDate)) {
                 $signatureDate = Date::excelToDateTimeObject($signatureDate)->format('Y-m-d');
             } else {
                 $ts = strtotime($signatureDate);
                 if ($ts) $signatureDate = date('Y-m-d', $ts);
                 else $signatureDate = null;
             }
        }

        // --- Financials ---
        $euContribution = $this->parseMoney($get(['project_eu_contribution_(eur)', 'eu_contribution']));
        $netEuContribution = $this->parseMoney($get(['project_net_eu_contribution_(eur)', 'net_eu_contribution']));
        $totalCost = $this->parseMoney($get(['project_total_cost_(eur)', 'total_cost']));

        // --- Coordinator ---
        $coordName = $get(['coordinator_name']);
        $coordCountry = $get(['coordinator_country']);

        // --- Greek Flags (Strict Boolean) ---
        $hasGreekPart = $this->parseBoolean($get(['has_greek_participant']));
        $hasGreekBen = $this->parseBoolean($get(['has_greek_beneficiary']));
        $hasGreekAny = $this->parseBoolean($get(['has_greek_any_role']));
        $isGreekCoord = $this->parseBoolean($get(['is_greek_coordinator']));

        // --- Enhanced Data ---
        $keywordsText = $get(['keywords']);
        $fieldsText = $get(['fields_of_science']);
        $investJson = $get(['invest_priorities_json'], '{}');
        $errorText = $get(['error']);

        // Enrichment overrides (if JSON provided and Excel missing)
        if ($jsonData) {
            if (empty($coordCountry) && isset($jsonData['coordinator_country'])) $coordCountry = $jsonData['coordinator_country'];
            // If Excel columns are missing, we could fallback to JSON here, but "Strict Excel" implies Excel is truth.
            // We'll trust Excel for the 24 columns.
        }

        $sql = "INSERT INTO projects (
            dataset_id, project_id, project_number, acronym, title, cordis_url,
            framework_programme, pillar, thematic_priority, type_of_action, status, signature_date,
            eu_contribution, net_eu_contribution, total_cost,
            coordinator_name, coordinator_country,
            has_greek_participant, has_greek_beneficiary, has_greek_any_role, is_greek_coordinator,
            keywords_text, fields_text, invest_priorities_json, error_text
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $datasetId, $projectId, $projectNumber, $acronym, $title, $cordisUrl,
            $framework, $pillar, $thematicPriority, $typeOfAction, $status, $signatureDate,
            $euContribution, $netEuContribution, $totalCost,
            $coordName, $coordCountry,
            $hasGreekPart, $hasGreekBen, $hasGreekAny, $isGreekCoord,
            $keywordsText, $fieldsText, $investJson, $errorText
        ]);

        $projectDbId = $this->pdo->lastInsertId();

        // --- Normalized Tables ---

        // 1. Coordinator Country (Always)
        if ($coordCountry) {
            $this->insertCountry($datasetId, $projectDbId, $coordCountry, 'coordinator');
        }

        // 2. Consortium Participants (From JSONL if available)
        if ($jsonData) {
            if (isset($jsonData['participants']) && is_array($jsonData['participants'])) {
                foreach ($jsonData['participants'] as $p) {
                    if (isset($p['country'])) {
                        // Avoid duplicating coordinator if it's in the list?
                        // "A country should only be counted once per project_id" -> handled in query usually,
                        // but here we store raw participants.
                        $this->insertCountry($datasetId, $projectDbId, $p['country'], 'participant');
                    }
                }
            }
        } else {
            // If NO JSONL, we can't populate 'participant' roles other than coordinator (who is also a participant).
            // But we should at least ensure 'coordinator' is in project_countries (done above).
        }

        // 3. Keywords (Semicolon separated)
        if ($keywordsText) {
            $keywords = explode(';', $keywordsText);
            foreach ($keywords as $k) {
                $k = trim($k);
                if ($k) $this->insertKeyword($datasetId, $projectDbId, $k);
            }
        }

        // 4. Fields (Semicolon separated paths)
        if ($fieldsText) {
             $fields = explode(';', $fieldsText);
             foreach ($fields as $f) {
                 $f = trim($f);
                 if ($f) $this->insertField($datasetId, $projectDbId, $f);
             }
        }

        // 5. Priorities (JSON)
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

    private function parseBoolean($value) {
        if (is_bool($value)) return $value ? 1 : 0;
        if (is_numeric($value)) return (int)$value ? 1 : 0;

        $s = strtoupper(trim((string)$value));
        if ($s === 'TRUE' || $s === 'YES' || $s === 'Y') return 1;

        return 0;
    }

    private function parseMoney($value) {
        if ($value === null || $value === '') return null;
        if (is_numeric($value)) return (float)$value;
        // Basic cleanup for strings like "1000.00" or "1000"
        return (float)filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
}
