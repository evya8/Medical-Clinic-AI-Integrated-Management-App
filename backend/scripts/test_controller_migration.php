<?php

/**
 * Controller Migration Test Script
 * 
 * This script validates that all controllers have been successfully migrated
 * to use BaseControllerMiddleware and the new middleware-aware methods.
 */

require_once __DIR__ . '/../bootstrap_simple.php';

use MedicalClinic\Controllers\PatientController;
use MedicalClinic\Controllers\UserController;
use MedicalClinic\Controllers\AppointmentController;
use MedicalClinic\Controllers\DoctorController;
use MedicalClinic\Controllers\AuthController;
use MedicalClinic\Controllers\BaseControllerMiddleware;

echo "🧪 Controller Migration Validation Test\n";
echo "=====================================\n\n";

$testResults = [];

/**
 * Test 1: Verify all controllers extend BaseControllerMiddleware
 */
echo "Test 1: Controller Inheritance\n";
echo "-----------------------------\n";

$controllers = [
    'PatientController' => PatientController::class,
    'UserController' => UserController::class,
    'AppointmentController' => AppointmentController::class,
    'DoctorController' => DoctorController::class,
    'AuthController' => AuthController::class,
];

$inheritanceResults = [];

foreach ($controllers as $name => $class) {
    $reflection = new ReflectionClass($class);
    $parent = $reflection->getParentClass();
    
    if ($parent && $parent->getName() === BaseControllerMiddleware::class) {
        echo "✅ {$name} extends BaseControllerMiddleware\n";
        $inheritanceResults[$name] = true;
    } else {
        echo "❌ {$name} does not extend BaseControllerMiddleware (parent: {$parent->getName()})\n";
        $inheritanceResults[$name] = false;
    }
}

$testResults['inheritance'] = $inheritanceResults;

/**
 * Test 2: Verify middleware-aware methods are available
 */
echo "\nTest 2: Middleware-Aware Methods\n";
echo "-------------------------------\n";

$requiredMethods = [
    'getUser',
    'getInput',
    'getParams',
    'getUserId',
    'getUserRole',
    'hasRole',
    'isAdmin',
    'isDoctor',
    'success',
    'error',
    'paginated'
];

$methodResults = [];

foreach ($controllers as $name => $class) {
    $reflection = new ReflectionClass($class);
    $missingMethods = [];
    
    foreach ($requiredMethods as $method) {
        if (!$reflection->hasMethod($method)) {
            $missingMethods[] = $method;
        }
    }
    
    if (empty($missingMethods)) {
        echo "✅ {$name} has all required middleware methods\n";
        $methodResults[$name] = true;
    } else {
        echo "❌ {$name} missing methods: " . implode(', ', $missingMethods) . "\n";
        $methodResults[$name] = false;
    }
}

$testResults['methods'] = $methodResults;

/**
 * Test 3: Check for removed legacy authentication calls
 */
echo "\nTest 3: Legacy Authentication Removal\n";
echo "------------------------------------\n";

$legacyMethods = ['requireAuth', 'requireRole'];
$legacyResults = [];

foreach ($controllers as $name => $class) {
    $reflection = new ReflectionClass($class);
    $filename = $reflection->getFileName();
    $content = file_get_contents($filename);
    
    $foundLegacy = [];
    foreach ($legacyMethods as $legacy) {
        if (strpos($content, "\$this->{$legacy}(") !== false) {
            $foundLegacy[] = $legacy;
        }
    }
    
    if (empty($foundLegacy)) {
        echo "✅ {$name} has no legacy authentication calls\n";
        $legacyResults[$name] = true;
    } else {
        echo "⚠️  {$name} still has legacy calls: " . implode(', ', $foundLegacy) . "\n";
        $legacyResults[$name] = false;
    }
}

$testResults['legacy'] = $legacyResults;

/**
 * Test 4: Check for new middleware-aware input handling
 */
echo "\nTest 4: Input Handling Migration\n";
echo "-------------------------------\n";

$inputResults = [];

foreach ($controllers as $name => $class) {
    $reflection = new ReflectionClass($class);
    $filename = $reflection->getFileName();
    $content = file_get_contents($filename);
    
    $hasGetInput = strpos($content, '$this->getInput()') !== false;
    $hasOldInput = strpos($content, '$this->input') !== false;
    
    if ($hasGetInput && !$hasOldInput) {
        echo "✅ {$name} uses new getInput() method\n";
        $inputResults[$name] = true;
    } elseif ($hasGetInput && $hasOldInput) {
        echo "⚠️  {$name} uses both new and old input methods\n";
        $inputResults[$name] = 'partial';
    } else {
        echo "❌ {$name} still uses old input handling\n";
        $inputResults[$name] = false;
    }
}

$testResults['input'] = $inputResults;

/**
 * Test 5: Verify new pagination methods
 */
echo "\nTest 5: Pagination Implementation\n";
echo "--------------------------------\n";

$paginationResults = [];

foreach ($controllers as $name => $class) {
    $reflection = new ReflectionClass($class);
    $filename = $reflection->getFileName();
    $content = file_get_contents($filename);
    
    $hasPaginated = strpos($content, '$this->paginated(') !== false;
    
    if ($hasPaginated) {
        echo "✅ {$name} implements pagination\n";
        $paginationResults[$name] = true;
    } else {
        echo "ℹ️  {$name} does not use pagination (may be intentional)\n";
        $paginationResults[$name] = 'not_applicable';
    }
}

$testResults['pagination'] = $paginationResults;

/**
 * Test Summary
 */
echo "\n📊 Migration Test Summary\n";
echo "========================\n";

$totalTests = 0;
$passedTests = 0;
$warningTests = 0;

foreach ($testResults as $testName => $results) {
    $testPassed = 0;
    $testWarning = 0;
    $testTotal = count($results);
    
    foreach ($results as $result) {
        if ($result === true) {
            $testPassed++;
        } elseif ($result === 'partial' || $result === 'not_applicable') {
            $testWarning++;
        }
    }
    
    $totalTests += $testTotal;
    $passedTests += $testPassed;
    $warningTests += $testWarning;
    
    echo "• " . ucfirst($testName) . ": {$testPassed}/{$testTotal} passed";
    if ($testWarning > 0) {
        echo " ({$testWarning} warnings)";
    }
    echo "\n";
}

$failedTests = $totalTests - $passedTests - $warningTests;
$successRate = round(($passedTests / $totalTests) * 100, 1);

echo "\n🎯 Overall Results:\n";
echo "• Total Tests: {$totalTests}\n";
echo "• Passed: {$passedTests} ({$successRate}%)\n";
echo "• Warnings: {$warningTests}\n";
echo "• Failed: {$failedTests}\n";

if ($failedTests === 0 && $warningTests === 0) {
    echo "\n🎉 MIGRATION SUCCESSFUL! All controllers properly migrated.\n";
    exit(0);
} elseif ($failedTests === 0) {
    echo "\n✅ MIGRATION MOSTLY SUCCESSFUL! Check warnings for optimization opportunities.\n";
    exit(0);
} else {
    echo "\n⚠️  MIGRATION NEEDS ATTENTION! Please fix failed tests.\n";
    exit(1);
}

/**
 * Test 6: Verify specific controller functionality
 */
echo "\nTest 6: Controller Instantiation Test\n";
echo "------------------------------------\n";

try {
    // Test that controllers can be instantiated with request data
    $mockRequest = [
        'auth_user' => null,
        'validated_input' => ['test' => 'data'],
        'params' => ['id' => 1]
    ];
    
    foreach ($controllers as $name => $class) {
        try {
            $instance = new $class($mockRequest);
            echo "✅ {$name} instantiates correctly\n";
        } catch (Exception $e) {
            echo "❌ {$name} instantiation failed: " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Controller instantiation test failed: " . $e->getMessage() . "\n";
}

echo "\n✨ Controller Migration Test Complete!\n";
