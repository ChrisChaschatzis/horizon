<?php

require_once __DIR__ . '/db_connect.php';

function fixSchema($pdo) {
    echo "Updating schema for Summary Datasets...\n";

    // Detect DB driver
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isMysql = ($driver === 'mysql');

    $pk = $isMysql ? "INT AUTO_INCREMENT PRIMARY KEY" : "INTEGER PRIMARY KEY AUTOINCREMENT";
    $text = $isMysql ? "TEXT" : "TEXT"; // Same
    $int = $isMysql ? "INT" : "INTEGER";
    $real = $isMysql ? "DOUBLE" : "REAL";

    // 1. Update datasets table
    try {
        $pdo->exec("ALTER TABLE datasets ADD COLUMN dataset_type $text DEFAULT 'projects'");
        echo "Added 'dataset_type' to datasets.\n";
    } catch (PDOException $e) {
        echo "Column 'dataset_type' already exists or error: " . $e->getMessage() . "\n";
    }

    try {
        $pdo->exec("ALTER TABLE datasets ADD COLUMN entities_count $int DEFAULT 0");
        echo "Added 'entities_count' to datasets.\n";
    } catch (PDOException $e) {
        echo "Column 'entities_count' already exists or error: " . $e->getMessage() . "\n";
    }

    // Helper for FK
    $fk = function($col, $refTable) use ($isMysql) {
        if ($isMysql) {
            return "FOREIGN KEY ($col) REFERENCES $refTable(id) ON DELETE CASCADE";
        } else {
            return "FOREIGN KEY ($col) REFERENCES $refTable(id) ON DELETE CASCADE";
        }
    };

    // 2. Create summary_groups
    $sql = "CREATE TABLE IF NOT EXISTS summary_groups (
        id $pk,
        dataset_id $int NOT NULL,
        group_label $text NOT NULL,
        " . $fk('dataset_id', 'datasets') . "
    )";
    $pdo->exec($sql);

    // Unique Index
    try {
        if ($isMysql) {
            $pdo->exec("CREATE UNIQUE INDEX idx_summary_groups_label ON summary_groups(dataset_id, group_label(191))");
        } else {
            $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_summary_groups_label ON summary_groups(dataset_id, group_label)");
        }
    } catch (Exception $e) {}


    // 3. Create summary_pillar_participation
    $sql = "CREATE TABLE IF NOT EXISTS summary_pillar_participation (
        id $pk,
        dataset_id $int NOT NULL,
        group_id $int NOT NULL,
        pillar_descr $text,
        participation $int,
        " . $fk('dataset_id', 'datasets') . ",
        " . $fk('group_id', 'summary_groups') . "
    )";
    $pdo->exec($sql);
    try { $pdo->exec("CREATE INDEX idx_summ_pillar_dataset ON summary_pillar_participation(dataset_id)"); } catch (Exception $e) {}


    // 4. Create summary_programme_participation
    $sql = "CREATE TABLE IF NOT EXISTS summary_programme_participation (
        id $pk,
        dataset_id $int NOT NULL,
        group_id $int NOT NULL,
        framework_programme $text,
        participation $int,
        " . $fk('dataset_id', 'datasets') . ",
        " . $fk('group_id', 'summary_groups') . "
    )";
    $pdo->exec($sql);
    try { $pdo->exec("CREATE INDEX idx_summ_prog_part_dataset ON summary_programme_participation(dataset_id)"); } catch (Exception $e) {}

    // 5. Create summary_programme_eu_contribution
    $sql = "CREATE TABLE IF NOT EXISTS summary_programme_eu_contribution (
        id $pk,
        dataset_id $int NOT NULL,
        group_id $int NOT NULL,
        framework_programme $text,
        eu_contribution_eur $real,
        " . $fk('dataset_id', 'datasets') . ",
        " . $fk('group_id', 'summary_groups') . "
    )";
    $pdo->exec($sql);
    try { $pdo->exec("CREATE INDEX idx_summ_prog_eu_dataset ON summary_programme_eu_contribution(dataset_id)"); } catch (Exception $e) {}

    // 6. Create summary_mission_eu_contribution
    $sql = "CREATE TABLE IF NOT EXISTS summary_mission_eu_contribution (
        id $pk,
        dataset_id $int NOT NULL,
        group_id $int NOT NULL,
        mission $text,
        eu_contribution_eur $real,
        " . $fk('dataset_id', 'datasets') . ",
        " . $fk('group_id', 'summary_groups') . "
    )";
    $pdo->exec($sql);
    try { $pdo->exec("CREATE INDEX idx_summ_mission_eu_dataset ON summary_mission_eu_contribution(dataset_id)"); } catch (Exception $e) {}

    // 7. Create summary_country_net_eu_contribution (Optional)
    $sql = "CREATE TABLE IF NOT EXISTS summary_country_net_eu_contribution (
        id $pk,
        dataset_id $int NOT NULL,
        group_id $int NOT NULL,
        country_territory $text,
        net_eu_contribution_eur $real,
        " . $fk('dataset_id', 'datasets') . ",
        " . $fk('group_id', 'summary_groups') . "
    )";
    $pdo->exec($sql);
    try { $pdo->exec("CREATE INDEX idx_summ_country_net_dataset ON summary_country_net_eu_contribution(dataset_id)"); } catch (Exception $e) {}


    // 8. Create summary_ranks (Optional - Consolidated)
    $sql = "CREATE TABLE IF NOT EXISTS summary_ranks (
        id $pk,
        dataset_id $int NOT NULL,
        group_id $int NOT NULL,
        metric_key $text,
        rank_position $int,
        rank_total $int,
        raw_text $text,
        " . $fk('dataset_id', 'datasets') . ",
        " . $fk('group_id', 'summary_groups') . "
    )";
    $pdo->exec($sql);
    try { $pdo->exec("CREATE INDEX idx_summ_ranks_dataset ON summary_ranks(dataset_id)"); } catch (Exception $e) {}


    echo "Schema update completed.\n";
}

fixSchema($pdo);
