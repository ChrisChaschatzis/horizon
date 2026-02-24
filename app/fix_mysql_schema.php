<?php
// app/fix_mysql_schema.php

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/src/SchemaFixer.php';

// Check if running from browser
$isBrowser = isset($_SERVER['HTTP_USER_AGENT']);

if ($isBrowser) {
    echo "<!DOCTYPE html><html><head><title>Fix Schema</title></head><body style='font-family: monospace; background: #f4f4f4; padding: 20px;'>";
    echo "<h3>Database Schema Fixer</h3>";
    echo "<div style='background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>";
    echo "<pre>";
}

try {
    SchemaFixer::fix($pdo);
    echo "\nSuccess! Database schema is up to date.\n";
} catch (Exception $e) {
    echo "\nError: " . $e->getMessage() . "\n";
}

if ($isBrowser) {
    echo "</pre>";
    echo "<br><a href='index.php' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px;'>Go to Dashboard</a>";
    echo "</div></body></html>";
}
