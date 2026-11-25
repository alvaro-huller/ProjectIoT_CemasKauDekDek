<?php
require_once __DIR__ . '/../public/config.php';

try {
    $pdo = getDbConnection();
    $cols = $pdo->query("SHOW COLUMNS FROM sensor_log LIKE 'status_tutup'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE sensor_log ADD COLUMN status_tutup TINYINT(1) NOT NULL DEFAULT 0 AFTER status_bau");
        echo "MIGRATION_SUCCESS: Column status_tutup added successfully.\n";
    } else {
        echo "MIGRATION_INFO: Column status_tutup already exists.\n";
    }
} catch (Exception $e) {
    echo "MIGRATION_ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
