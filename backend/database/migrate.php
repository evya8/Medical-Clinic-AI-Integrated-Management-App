<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use MedicalClinic\Utils\Migration;

try {
    // Load environment variables
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();

    $migration = new Migration();

    // Check command line arguments
    $command = $argv[1] ?? 'migrate';

    switch ($command) {
        case 'migrate':
            $migration->run();
            break;
            
        case 'rollback':
            $steps = (int) ($argv[2] ?? 1);
            $migration->rollback($steps);
            break;
            
        case 'fresh':
            echo "This would drop all tables and re-run migrations (not implemented for safety)\n";
            echo "To start fresh, manually drop your database and re-create it, then run migrate\n";
            break;
            
        default:
            echo "Usage:\n";
            echo "  php database/migrate.php migrate     - Run all pending migrations\n";
            echo "  php database/migrate.php rollback [n] - Rollback last n migrations (default: 1)\n";
            echo "  php database/migrate.php fresh       - Drop all tables and re-migrate (manual process)\n";
            break;
    }

} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
    exit(1);
}
