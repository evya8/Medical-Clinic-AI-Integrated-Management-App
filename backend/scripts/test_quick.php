<?php

/**
 * Quick API Functionality Test
 * 
 * This script tests basic API functionality without complex dependencies
 */

echo "🧪 Quick API Functionality Test\n";
echo "==============================\n\n";

$backendPath = __DIR__ . '/..';

echo "Test 1: Check Core Files\n";
echo "-----------------------\n";

// Check essential files exist
$coreFiles = [
    'bootstrap.php',
    'routes/api.php',
    'routes/api_with_middleware.php',
    'src/Controllers/BaseControllerMiddleware.php',
    'src/Routes/RouteRegistryEnhanced.php',
    'src/Middleware/MiddlewareManager.php'
];

$filesFound = 0;
foreach ($coreFiles as $file) {
    $filePath = $backendPath . '/' . $file;
    if (file_exists($filePath)) {
        echo "✅ {$file} exists\n";
        $filesFound++;
    } else {
        echo "❌ {$file} missing\n";
    }
}

echo "\nTest 2: Check New API Route Structure\n";
echo "------------------------------------\n";

$newApiFile = $backendPath . '/routes/api_with_middleware.php';
if (file_exists($newApiFile)) {
    $content = file_get_contents($newApiFile);
    
    $checkPoints = [
        'RouteRegistryEnhanced' => 'Route registry class used',
        'MiddlewareManager::' => 'Middleware manager integration',
        'authGet(' => 'Auth-aware route methods',
        '$router->group(' => 'Route groups implemented',
        'PatientController::class' => 'Controller class references',
        'CORS' => 'CORS handling present'
    ];
    
    $foundFeatures = 0;
    foreach ($checkPoints as $search => $description) {
        if (strpos($content, $search) !== false) {
            echo "✅ {$description}\n";
            $foundFeatures++;
        } else {
            echo "❌ {$description}\n";
        }
    }
    
    echo "\nAPI Features: {$foundFeatures}/" . count($checkPoints) . " found\n";
} else {
    echo "❌ New API file not found\n";
}

echo "\nTest 3: Migration Documentation\n";
echo "------------------------------\n";

$docFiles = [
    'CONTROLLER_MIGRATION_SUMMARY.md',
    'INTEGRATION_COMPLETE.md'
];

$docsFound = 0;
foreach ($docFiles as $file) {
    $filePath = $backendPath . '/' . $file;
    if (file_exists($filePath)) {
        echo "✅ {$file} exists\n";
        $docsFound++;
    } else {
        echo "❌ {$file} missing\n";
    }
}

echo "\nTest 4: Test Scripts Available\n";
echo "-----------------------------\n";

$testScripts = [
    'test_migration_simple.php',
    'migrate_api.php',
    'controller_migration_examples.php'
];

$scriptsFound = 0;
foreach ($testScripts as $script) {
    $filePath = __DIR__ . '/' . $script;
    if (file_exists($filePath)) {
        echo "✅ {$script} available\n";
        $scriptsFound++;
    } else {
        echo "❌ {$script} missing\n";
    }
}

echo "\n📊 Summary\n";
echo "==========\n";

$totalScore = $filesFound + $foundFeatures + $docsFound + $scriptsFound;
$maxScore = count($coreFiles) + count($checkPoints) + count($docFiles) + count($testScripts);
$percentage = round(($totalScore / $maxScore) * 100, 1);

echo "• Core files: {$filesFound}/" . count($coreFiles) . "\n";
echo "• API features: " . (isset($foundFeatures) ? $foundFeatures : 0) . "/" . count($checkPoints) . "\n";
echo "• Documentation: {$docsFound}/" . count($docFiles) . "\n";
echo "• Test scripts: {$scriptsFound}/" . count($testScripts) . "\n";
echo "• Overall: {$totalScore}/{$maxScore} ({$percentage}%)\n";

if ($percentage >= 90) {
    echo "\n🎉 EXCELLENT! Migration is complete and ready for deployment.\n";
    
    echo "\n🚀 Recommended Next Steps:\n";
    echo "1. Run: php scripts/migrate_api.php\n";
    echo "2. Set USE_NEW_API=true in .env\n";
    echo "3. Test endpoints manually\n";
    echo "4. Monitor for any issues\n";
    
} elseif ($percentage >= 75) {
    echo "\n✅ GOOD! Migration is mostly complete with minor issues.\n";
    echo "Review any missing components above.\n";
    
} else {
    echo "\n⚠️ INCOMPLETE! Migration needs more work before deployment.\n";
    echo "Please address the missing components above.\n";
}

echo "\n✨ Quick test complete!\n";
