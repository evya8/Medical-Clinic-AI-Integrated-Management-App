<?php
/**
 * Legacy API Backup Endpoint
 * This provides access to the old API system in case rollback is needed
 */

// Include the backed up API
$backupFile = __DIR__ . "/../routes/api_backup_2025-09-01_07-28-00.php";
if (file_exists($backupFile)) {
    include $backupFile;
} else {
    http_response_code(503);
    echo json_encode([
        "success" => false,
        "message" => "Legacy API backup not available",
        "timestamp" => date("Y-m-d H:i:s")
    ]);
}
