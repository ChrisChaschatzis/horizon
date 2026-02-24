<?php

class SchemaFixer {

    public static function fix($pdo, $silent = false) {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if (!$silent) echo "Checking database schema ($driver)...\n";

        // 1. Ensure Tables Exist
        self::ensureTablesExist($pdo, $driver, $silent);

        // 2. Ensure Columns in 'projects'
        $projectColumns = [
            'project_id' => ($driver === 'mysql' ? "VARCHAR(255) NOT NULL" : "TEXT NOT NULL"),
            'project_number' => ($driver === 'mysql' ? "VARCHAR(255)" : "TEXT"),
            'acronym' => ($driver === 'mysql' ? "VARCHAR(255)" : "TEXT"),
            'title' => "TEXT",
            'cordis_url' => "TEXT",
            'framework_programme' => ($driver === 'mysql' ? "VARCHAR(255)" : "TEXT"),
            'pillar' => ($driver === 'mysql' ? "VARCHAR(255)" : "TEXT"),
            'thematic_priority' => ($driver === 'mysql' ? "VARCHAR(255)" : "TEXT"),
            'type_of_action' => ($driver === 'mysql' ? "VARCHAR(255)" : "TEXT"),
            'status' => ($driver === 'mysql' ? "VARCHAR(100)" : "TEXT"),
            'signature_date' => ($driver === 'mysql' ? "DATE" : "TEXT"),
            'eu_contribution' => ($driver === 'mysql' ? "DECIMAL(15,2)" : "REAL"),
            'net_eu_contribution' => ($driver === 'mysql' ? "DECIMAL(15,2)" : "REAL"),
            'total_cost' => ($driver === 'mysql' ? "DECIMAL(15,2)" : "REAL"),
            'coordinator_name' => "TEXT",
            'coordinator_country' => ($driver === 'mysql' ? "VARCHAR(100)" : "TEXT"),
            'has_greek_participant' => ($driver === 'mysql' ? "TINYINT(1) DEFAULT 0" : "INTEGER DEFAULT 0"),
            'has_greek_beneficiary' => ($driver === 'mysql' ? "TINYINT(1) DEFAULT 0" : "INTEGER DEFAULT 0"),
            'has_greek_any_role' => ($driver === 'mysql' ? "TINYINT(1) DEFAULT 0" : "INTEGER DEFAULT 0"),
            'is_greek_coordinator' => ($driver === 'mysql' ? "TINYINT(1) DEFAULT 0" : "INTEGER DEFAULT 0"),
            'keywords_text' => "TEXT",
            'fields_text' => "TEXT",
            'invest_priorities_json' => ($driver === 'mysql' ? "JSON" : "TEXT"),
            'error_text' => "TEXT"
        ];

        self::ensureColumns($pdo, $driver, 'projects', $projectColumns, $silent);

        // 3. Ensure 'notes' in 'datasets'
        $datasetColumns = [
            'notes' => "TEXT"
        ];
        self::ensureColumns($pdo, $driver, 'datasets', $datasetColumns, $silent);

        if (!$silent) echo "Schema check completed.\n";
    }

    private static function ensureTablesExist($pdo, $driver, $silent) {
        // Basic creation if missing completely
        $queries = [];

        if ($driver === 'mysql') {
            $queries[] = "CREATE TABLE IF NOT EXISTS datasets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                excel_filename VARCHAR(255),
                jsonl_filename VARCHAR(255),
                notes TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

            $queries[] = "CREATE TABLE IF NOT EXISTS projects (
                id INT AUTO_INCREMENT PRIMARY KEY,
                dataset_id INT NOT NULL,
                project_id VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (dataset_id) REFERENCES datasets(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        } else {
             $queries[] = "CREATE TABLE IF NOT EXISTS datasets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                excel_filename TEXT,
                jsonl_filename TEXT,
                notes TEXT
            )";

            $queries[] = "CREATE TABLE IF NOT EXISTS projects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                dataset_id INTEGER NOT NULL,
                project_id TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (dataset_id) REFERENCES datasets(id) ON DELETE CASCADE
            )";
        }

        foreach ($queries as $sql) {
            try {
                $pdo->exec($sql);
            } catch (PDOException $e) {
                if (!$silent) echo "Error creating table: " . $e->getMessage() . "\n";
            }
        }
    }

    private static function ensureColumns($pdo, $driver, $table, $columns, $silent) {
        $existing = self::getColumns($pdo, $driver, $table);

        foreach ($columns as $col => $def) {
            if (!in_array($col, $existing)) {
                if (!$silent) echo "Adding column '$col' to '$table'...\n";
                try {
                    // Special handling for JSON in older MySQL or MariaDB if needed?
                    // Usually JSON is supported in 5.7+. If fails, fallback to TEXT?
                    // Let's try direct add first.
                    $sql = "ALTER TABLE $table ADD COLUMN $col $def";
                    $pdo->exec($sql);
                } catch (PDOException $e) {
                    if (!$silent) echo "Error adding column $col: " . $e->getMessage() . "\n";

                    // Fallback for JSON -> TEXT if JSON fails
                    if (strpos($def, 'JSON') !== false) {
                        if (!$silent) echo "Retrying with TEXT for $col...\n";
                        try {
                            $def = "TEXT";
                            $pdo->exec("ALTER TABLE $table ADD COLUMN $col $def");
                        } catch (PDOException $e2) {
                            if (!$silent) echo "Failed fallback: " . $e2->getMessage() . "\n";
                        }
                    }
                }
            }
        }
    }

    private static function getColumns($pdo, $driver, $table) {
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
}
