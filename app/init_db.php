<?php
require_once __DIR__ . '/db_connect.php';

echo "Initializing database (" . DB_DRIVER . ")...\n";

try {
    // Determine correct schema file
    if (defined('DB_DRIVER') && DB_DRIVER === 'mysql') {
        $schemaFile = __DIR__ . '/schema_mysql.sql';
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
    } else {
        $schemaFile = __DIR__ . '/schema.sql';
    }

    if (!file_exists($schemaFile)) {
        die("Error: Schema file not found: $schemaFile\n");
    }

    $sql = file_get_contents($schemaFile);

    // Split SQL by semicolon to execute individually
    $statements = explode(';', $sql);

    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if (!empty($stmt)) {
            try {
                $pdo->exec($stmt);
            } catch (PDOException $e) {
                echo "Warning or Error executing statement: " . substr($stmt, 0, 50) . "...\n";
                echo "Message: " . $e->getMessage() . "\n";
            }
        }
    }

    if (defined('DB_DRIVER') && DB_DRIVER === 'mysql') {
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
    }

    echo "Database initialized successfully.\n";

} catch (PDOException $e) {
    die("Error initializing database: " . $e->getMessage() . "\n");
}
