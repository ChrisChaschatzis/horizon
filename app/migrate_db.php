<?php

require_once __DIR__ . '/db_connect.php';

try {
    echo "Starting migration...\n";

    // check if we are using MySQL or SQLite
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "Database driver: $driver\n";

    // Function to get columns
    function getColumns($pdo, $driver, $table) {
        $cols = [];
        if ($driver === 'mysql') {
            try {
                $stmt = $pdo->query("SHOW COLUMNS FROM $table");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $cols[] = strtolower($row['Field']);
                }
            } catch (Exception $e) { /* Table might not exist */ }
        } else {
            try {
                $stmt = $pdo->query("PRAGMA table_info($table)");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $cols[] = strtolower($row['name']);
                }
            } catch (Exception $e) { /* Table might not exist */ }
        }
        return $cols;
    }

    // 1. Ensure 'datasets' table has 'notes'
    $datasetCols = getColumns($pdo, $driver, 'datasets');

    if (!empty($datasetCols) && !in_array('notes', $datasetCols)) {
        echo "Adding 'notes' to datasets table...\n";
        $pdo->exec("ALTER TABLE datasets ADD COLUMN notes TEXT");
    } else {
        echo "'notes' column already exists in datasets (or table missing).\n";
    }

    // 2. Ensure 'projects' table has all new columns
    $expectedColumns = [
        'project_number' => 'VARCHAR(50)',
        'framework_programme' => 'VARCHAR(50)',
        'pillar' => 'VARCHAR(255)',
        'thematic_priority' => 'VARCHAR(255)',
        'type_of_action' => 'VARCHAR(100)',
        'status' => 'VARCHAR(50)',
        'signature_date' => 'DATE',
        'eu_contribution' => 'DECIMAL(15,2)',
        'net_eu_contribution' => 'DECIMAL(15,2)',
        'total_cost' => 'DECIMAL(15,2)',
        'coordinator_name' => 'VARCHAR(255)',
        'coordinator_country' => 'VARCHAR(10)',
        'has_greek_participant' => 'TINYINT(1) DEFAULT 0',
        'has_greek_beneficiary' => 'TINYINT(1) DEFAULT 0',
        'has_greek_any_role' => 'TINYINT(1) DEFAULT 0',
        'is_greek_coordinator' => 'TINYINT(1) DEFAULT 0',
        'keywords_text' => 'TEXT',
        'fields_text' => 'TEXT',
        'invest_priorities_json' => 'TEXT',
        'error_text' => 'TEXT'
    ];

    $projectCols = getColumns($pdo, $driver, 'projects');

    foreach ($expectedColumns as $col => $def) {
        if (!in_array($col, $projectCols)) {
            echo "Adding '$col' to projects table...\n";
            try {
                if ($driver === 'sqlite') {
                    if (strpos($def, 'JSON') !== false) $def = 'TEXT';
                    // Simplify types for SQLite
                    if (strpos($def, 'VARCHAR') !== false) $def = 'TEXT';
                    if (strpos($def, 'DECIMAL') !== false) $def = 'NUMERIC';
                    if (strpos($def, 'DATE') !== false) $def = 'TEXT';
                    if (strpos($def, 'TINYINT') !== false) $def = 'INTEGER DEFAULT 0';

                    $pdo->exec("ALTER TABLE projects ADD COLUMN $col $def");
                } else {
                    $pdo->exec("ALTER TABLE projects ADD COLUMN $col $def");
                }
            } catch (Exception $e) {
                echo "Error adding $col: " . $e->getMessage() . "\n";
            }
        }
    }

    // 3. Create helper tables if they don't exist
    // Using simple CREATE IF NOT EXISTS which is supported by both

    $tables = [
        "project_countries" => "CREATE TABLE IF NOT EXISTS project_countries (
            id INTEGER PRIMARY KEY " . ($driver === 'mysql' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT') . ",
            dataset_id INTEGER,
            project_db_id INTEGER,
            country VARCHAR(10),
            role VARCHAR(50)
            " . ($driver === 'mysql' ? ", FOREIGN KEY (project_db_id) REFERENCES projects(id) ON DELETE CASCADE" : "") . "
        )",
        "project_keywords" => "CREATE TABLE IF NOT EXISTS project_keywords (
            id INTEGER PRIMARY KEY " . ($driver === 'mysql' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT') . ",
            dataset_id INTEGER,
            project_db_id INTEGER,
            keyword VARCHAR(255)
            " . ($driver === 'mysql' ? ", FOREIGN KEY (project_db_id) REFERENCES projects(id) ON DELETE CASCADE" : "") . "
        )",
        "project_fields" => "CREATE TABLE IF NOT EXISTS project_fields (
            id INTEGER PRIMARY KEY " . ($driver === 'mysql' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT') . ",
            dataset_id INTEGER,
            project_db_id INTEGER,
            path_text TEXT,
            level1 VARCHAR(255),
            level2 VARCHAR(255),
            level3 VARCHAR(255)
            " . ($driver === 'mysql' ? ", FOREIGN KEY (project_db_id) REFERENCES projects(id) ON DELETE CASCADE" : "") . "
        )",
        "invest_priorities" => "CREATE TABLE IF NOT EXISTS invest_priorities (
            id INTEGER PRIMARY KEY " . ($driver === 'mysql' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT') . ",
            dataset_id INTEGER,
            project_db_id INTEGER,
            label VARCHAR(255),
            percent DECIMAL(5,2)
            " . ($driver === 'mysql' ? ", FOREIGN KEY (project_db_id) REFERENCES projects(id) ON DELETE CASCADE" : "") . "
        )"
    ];

    foreach ($tables as $name => $sql) {
        try {
            $pdo->exec($sql);
        } catch (Exception $e) {
             echo "Table $name creation warning: " . $e->getMessage() . "\n";
        }
    }

    echo "Migration completed.\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
