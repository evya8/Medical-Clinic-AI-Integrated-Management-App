<?php

/**
 * Cron Job Script for Processing Reminders
 * 
 * This script should be run every 5-15 minutes via cron to process pending reminders
 * 
 * Example crontab entry (runs every 10 minutes):
 * */10 * * * * /usr/bin/php /path/to/your/backend/scripts/process_reminders.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use MedicalClinic\Services\ReminderService;

try {
    // Load environment variables
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();

    // Initialize reminder service
    $reminderService = new ReminderService();

    echo "[" . date('Y-m-d H:i:s') . "] Starting reminder processing...\n";

    // Process pending reminders
    $results = $reminderService->processPendingReminders();

    echo "[" . date('Y-m-d H:i:s') . "] Reminder processing completed:\n";
    echo "  - Processed: {$results['processed']}\n";
    echo "  - Successful: {$results['successful']}\n";
    echo "  - Failed: {$results['failed']}\n";

    if (!empty($results['details'])) {
        echo "\nDetails:\n";
        foreach ($results['details'] as $detail) {
            $status = $detail['success'] ? 'SUCCESS' : 'FAILED';
            echo "  - [{$status}] {$detail['type']} to {$detail['patient']}\n";
        }
    }

    echo "\n";

} catch (Exception $e) {
    error_log("Reminder processing error: " . $e->getMessage());
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
