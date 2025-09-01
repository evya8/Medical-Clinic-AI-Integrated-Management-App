#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MedicalClinic\Utils\Database;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

echo "Running refresh tokens migration...\n";

try {
    $db = Database::getInstance();
    
    // Include and run the migration
    require_once __DIR__ . '/../database/migrations/2024_01_01_000013_create_refresh_tokens_table.php';
    
    $migration = new CreateRefreshTokensTable($db);
    $migration->up();
    
    echo "Refresh tokens table created successfully!\n";
    echo "Database now supports dual JWT system (access + refresh tokens).\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nRefresh token system ready!\n";
