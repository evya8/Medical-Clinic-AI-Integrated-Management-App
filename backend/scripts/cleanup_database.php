#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MedicalClinic\Utils\Database;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

try {
    echo "🗑️  Starting database cleanup...\n\n";
    
    $db = Database::getInstance();
    
    // Include the cleanup migration
    require_once __DIR__ . '/../database/migrations/2024_01_01_000014_cleanup_unused_tables.php';
    
    $migration = new CleanupUnusedTables($db);
    
    echo "📋 Dropping unused tables:\n";
    echo "   - questionnaire_responses\n";
    echo "   - questionnaires\n";
    echo "   - bills\n";
    echo "   - inventory\n\n";
    
    $migration->up();
    
    echo "✅ Database cleanup completed successfully!\n\n";
    
    // Verify tables were dropped
    $tables = $db->fetchAll("SHOW TABLES");
    $tableNames = array_column($tables, array_values($tables[0])[0]);
    
    $droppedTables = ['questionnaires', 'questionnaire_responses', 'bills', 'inventory'];
    $stillExists = array_intersect($droppedTables, $tableNames);
    
    if (empty($stillExists)) {
        echo "🎉 All target tables successfully removed!\n";
        echo "📊 Remaining tables (" . count($tableNames) . "):\n";
        foreach ($tableNames as $table) {
            echo "   - $table\n";
        }
    } else {
        echo "⚠️  Some tables still exist: " . implode(', ', $stillExists) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
    exit(1);
}

echo "\n🚀 Database is now cleaned and optimized!\n";
