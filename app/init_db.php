<?php
require_once __DIR__ . '/db_connect.php';

echo "Initializing database...\n";

try {
    $sql = file_get_contents(__DIR__ . '/schema.sql');

    // Split SQL by semicolon
    $statements = explode(';', $sql);

    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if (!empty($stmt)) {
            $pdo->exec($stmt);
        }
    }

    echo "Database initialized successfully.\n";

} catch (PDOException $e) {
    die("Error initializing database: " . $e->getMessage() . "\n");
}
