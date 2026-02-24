<?php
// app/fix_mysql_schema.php

require_once __DIR__ . '/db_connect.php';

try {
    echo "Starting Schema Fix...\n";
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "Driver: $driver\n";

    // Helper to get columns
    function getColumns($pdo, $driver, $table) {
        $cols = [];
        if ($driver === 'mysql') {
            try {
                $stmt = $pdo->query("SHOW COLUMNS FROM $table");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $cols[] = strtolower($row['Field']);
                }
            } catch (Exception $e) {}
        } else {
            try {
                $stmt = $pdo->query("PRAGMA table_info($table)");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $cols[] = strtolower($row['name']);
                }
            } catch (Exception $e) {}
        }
        return $cols;
    }

    // 1. Projects Columns
    $columns = [
        'project_number' => "VARCHAR(50)",
        'acronym' => "VARCHAR(255)",
        'title' => "TEXT",
        'cordis_url' => "VARCHAR(255)",
        'framework_programme' => "VARCHAR(100)",
        'pillar' => "VARCHAR(255)",
        'thematic_priority' => "VARCHAR(255)",
        'type_of_action' => "VARCHAR(100)",
        'status' => "VARCHAR(50)",
        'signature_date' => "DATE",
        'eu_contribution' => "DECIMAL(15,2)",
        'net_eu_contribution' => "DECIMAL(15,2)",
        'total_cost' => "DECIMAL(15,2)",
        'coordinator_name' => "VARCHAR(255)",
        'coordinator_country' => "VARCHAR(10)",
        'has_greek_participant' => "TINYINT(1) DEFAULT 0",
        'has_greek_beneficiary' => "TINYINT(1) DEFAULT 0",
        'has_greek_any_role' => "TINYINT(1) DEFAULT 0",
        'is_greek_coordinator' => "TINYINT(1) DEFAULT 0",
        'keywords_text' => "TEXT",
        'fields_text' => "TEXT",
        'invest_priorities_json' => "TEXT",
        'error_text' => "TEXT"
    ];

    $existing = getColumns($pdo, $driver, 'projects');

    // Create Table if missing
    if (empty($existing)) {
        echo "Creating table 'projects'...\n";
        $sql = "CREATE TABLE projects (
            id " . ($driver === 'mysql' ? 'INT AUTO_INCREMENT' : 'INTEGER') . " PRIMARY KEY,
            dataset_id INT NOT NULL,
            project_id VARCHAR(50),
            created_at " . ($driver === 'mysql' ? 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP' : 'DATETIME DEFAULT CURRENT_TIMESTAMP') . "
        )";
        $pdo->exec($sql);
        $existing = ['id', 'dataset_id', 'project_id', 'created_at'];
    }

    foreach ($columns as $col => $def) {
        if (!in_array($col, $existing)) {
            echo "Adding '$col'...\n";
            try {
                if ($driver === 'sqlite') {
                    // SQLite specific types
                    if (strpos($def, 'VARCHAR') !== false) $def = 'TEXT';
                    if (strpos($def, 'DECIMAL') !== false) $def = 'NUMERIC';
                    if (strpos($def, 'DATE') !== false) $def = 'TEXT';
                    if (strpos($def, 'TINYINT') !== false) $def = 'INTEGER DEFAULT 0';
                }
                $pdo->exec("ALTER TABLE projects ADD COLUMN $col $def");
            } catch (Exception $e) {
                echo "Error adding $col: " . $e->getMessage() . "\n";
            }
        }
    }

    // 2. Datasets 'notes'
    $dCols = getColumns($pdo, $driver, 'datasets');
    if (!empty($dCols) && !in_array('notes', $dCols)) {
        echo "Adding 'notes' to datasets...\n";
        $pdo->exec("ALTER TABLE datasets ADD COLUMN notes TEXT");
    }

    echo "Fix completed.\n";

} catch (PDOException $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
}
