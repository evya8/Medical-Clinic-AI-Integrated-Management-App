<?php
/**
 * Medical Clinic Management API v2.0
 * With Middleware Integration and Route Registry
 */

// Feature flag to switch between old and new API
$useNewAPI = $_ENV["USE_NEW_API"] ?? true;

if ($useNewAPI && file_exists(__DIR__ . "/api_with_middleware.php")) {
    // Use new middleware-aware API
    include __DIR__ . "/api_with_middleware.php";
} else {
    // Fallback to backup API
    $backupFile = glob(__DIR__ . "/api_backup_*.php");
    if (!empty($backupFile)) {
        include end($backupFile); // Use most recent backup
    } else {
        http_response_code(503);
        header("Content-Type: application/json");
        echo json_encode([
            "success" => false,
            "message" => "API temporarily unavailable",
            "timestamp" => date("Y-m-d H:i:s")
        ]);
    }
}
