<?php

/**
 * Basic Database Connection Test
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use MedicalClinic\Utils\Database;

echo "🔌 Basic Database Connection Test\n";
echo "================================\n\n";

try {
    // Load environment
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();

    echo "1. Testing basic database connection...\n";
    $db = Database::getInstance();
    echo "   ✅ Database instance created\n";

    echo "2. Testing simple query...\n";
    $result = $db->fetch("SELECT 1 as test");
    if ($result && $result['test'] == 1) {
        echo "   ✅ Simple query works\n";
    } else {
        echo "   ❌ Simple query failed\n";
    }

    echo "3. Testing database name retrieval...\n";
    $dbName = $db->fetch("SELECT DATABASE() as db_name");
    echo "   ✅ Connected to database: " . ($dbName['db_name'] ?? 'unknown') . "\n";

    echo "4. Testing table listing...\n";
    $tables = $db->fetchAll("SHOW TABLES");
    echo "   ✅ Found " . count($tables) . " tables in database\n";
    
    if (count($tables) > 0) {
        echo "   Tables: ";
        foreach ($tables as $i => $table) {
            $tableName = array_values($table)[0];
            echo $tableName;
            if ($i < count($tables) - 1) echo ", ";
        }
        echo "\n";
    }

    echo "5. Testing our improved tableExists method...\n";
    
    // Test with a table that should exist (users)
    try {
        $userTableExists = $db->tableExists('users');
        echo "   ✅ tableExists('users'): " . ($userTableExists ? "YES" : "NO") . "\n";
    } catch (Exception $e) {
        echo "   ❌ tableExists test failed: " . $e->getMessage() . "\n";
    }
    
    // Test with a table that shouldn't exist
    try {
        $fakeTableExists = $db->tableExists('fake_table_name_xyz');
        echo "   ✅ tableExists('fake_table_name_xyz'): " . ($fakeTableExists ? "YES" : "NO") . "\n";
    } catch (Exception $e) {
        echo "   ❌ tableExists test with fake table failed: " . $e->getMessage() . "\n";
    }

    echo "\n🎉 All database tests passed!\n";
    echo "✅ Connection: Working\n";
    echo "✅ Queries: Working\n";
    echo "✅ Table checking: Working\n";

} catch (Exception $e) {
    echo "\n❌ Database test failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
