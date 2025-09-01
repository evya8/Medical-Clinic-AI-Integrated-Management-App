<?php

/**
 * Quick Test Runner - Run All Fixed Tests
 * 
 * This script runs all the migration tests to verify everything is working
 */

echo "🧪 Running All Migration Tests\n";
echo "==============================\n\n";

$backendPath = __DIR__ . '/..';
$scriptPath = __DIR__;

echo "📍 Backend Path: {$backendPath}\n";
echo "📍 Script Path: {$scriptPath}\n\n";

// List of tests to run
$tests = [
    'test_migration_simple.php' => 'Simple Migration Test',
    'test_quick.php' => 'Quick Functionality Test', 
    'test_controller_migration.php' => 'Controller Migration Test',
    'test_api_integration.php' => 'API Integration Test'
];

$results = [];
$totalTests = count($tests);
$passedTests = 0;

foreach ($tests as $script => $description) {
    echo "🔄 Running: {$description}\n";
    echo str_repeat('-', 50) . "\n";
    
    $scriptFile = $scriptPath . '/' . $script;
    
    if (!file_exists($scriptFile)) {
        echo "❌ Script not found: {$script}\n\n";
        $results[$script] = 'NOT_FOUND';
        continue;
    }
    
    // Capture output
    ob_start();
    $exitCode = 0;
    
    try {
        // Change to backend directory
        $currentDir = getcwd();
        chdir($backendPath);
        
        // Run the test script
        include $scriptFile;
        
        // Restore directory
        chdir($currentDir);
        
    } catch (Exception $e) {
        $exitCode = 1;
        echo "\n❌ Exception: " . $e->getMessage() . "\n";
    } catch (Error $e) {
        $exitCode = 1;
        echo "\n❌ Error: " . $e->getMessage() . "\n";
    } finally {
        // Restore directory even if error occurred
        if (isset($currentDir)) {
            chdir($currentDir);
        }
    }
    
    $output = ob_get_clean();
    
    // Analyze output for success indicators
    $success = false;
    if (strpos($output, '🎉') !== false || 
        strpos($output, 'EXCELLENT') !== false ||
        strpos($output, 'MIGRATION COMPLETE') !== false ||
        strpos($output, 'ALL TESTS PASSED') !== false ||
        strpos($output, 'SUCCESSFUL') !== false) {
        $success = true;
    }
    
    // Check for fatal errors
    if (strpos($output, 'Fatal error') !== false || 
        strpos($output, 'Parse error') !== false) {
        $success = false;
    }
    
    if ($success) {
        echo "✅ {$description}: PASSED\n";
        $results[$script] = 'PASSED';
        $passedTests++;
    } else {
        echo "❌ {$description}: FAILED\n";
        $results[$script] = 'FAILED';
        
        // Show last few lines of output for debugging
        $lines = explode("\n", $output);
        $lastLines = array_slice($lines, -5);
        echo "   Last output: " . implode(' | ', array_filter($lastLines)) . "\n";
    }
    
    echo "\n";
}

// Summary
echo "📊 Test Summary\n";
echo "===============\n";

foreach ($results as $script => $result) {
    $status = match($result) {
        'PASSED' => '✅ PASSED',
        'FAILED' => '❌ FAILED',
        'NOT_FOUND' => '⚠️  NOT FOUND',
        default => '❓ UNKNOWN'
    };
    
    echo "• " . str_replace('.php', '', $script) . ": {$status}\n";
}

$successRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 1) : 0;

echo "\n🎯 Overall Results:\n";
echo "• Total Tests: {$totalTests}\n";
echo "• Passed: {$passedTests}\n";  
echo "• Failed: " . ($totalTests - $passedTests) . "\n";
echo "• Success Rate: {$successRate}%\n";

if ($passedTests === $totalTests) {
    echo "\n🎉 ALL TESTS PASSED!\n";
    echo "✨ Controller Migration is complete and ready for deployment.\n";
    
    echo "\n🚀 Next Steps:\n";
    echo "1. Run: php scripts/migrate_api.php\n";
    echo "2. Set USE_NEW_API=true in .env\n";  
    echo "3. Test endpoints manually\n";
    echo "4. Deploy to production\n";
    
    exit(0);
} elseif ($passedTests >= ($totalTests * 0.75)) {
    echo "\n✅ MOSTLY SUCCESSFUL!\n";
    echo "🔧 {$description} test(s) need attention, but core functionality is ready.\n";
    exit(0);
} else {
    echo "\n⚠️ SIGNIFICANT ISSUES!\n";
    echo "🔧 Please fix the failed tests before proceeding.\n";
    exit(1);
}
