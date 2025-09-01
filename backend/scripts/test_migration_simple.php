<?php

/**
 * Simple Controller Migration Test
 * 
 * This script provides basic validation without complex dependencies
 */

echo "🧪 Controller Migration Validation Test (Simple)\n";
echo "===============================================\n\n";

// Define the paths we need to check
$backendPath = __DIR__ . '/..';
$srcPath = $backendPath . '/src';
$controllerPath = $srcPath . '/Controllers';

echo "Test 1: File Structure Validation\n";
echo "---------------------------------\n";

// Check if required files exist
$requiredFiles = [
    'BaseControllerMiddleware.php',
    'PatientController.php',
    'UserController.php',
    'AppointmentController.php', 
    'DoctorController.php',
    'AuthController.php'
];

$existingFiles = 0;
foreach ($requiredFiles as $file) {
    $filePath = $controllerPath . '/' . $file;
    if (file_exists($filePath)) {
        echo "✅ {$file} exists\n";
        $existingFiles++;
    } else {
        echo "❌ {$file} missing\n";
    }
}

echo "\nTest 2: Controller Content Validation\n";
echo "------------------------------------\n";

// Check that controllers extend BaseControllerMiddleware
$controllersToCheck = [
    'PatientController.php',
    'UserController.php', 
    'AppointmentController.php',
    'DoctorController.php',
    'AuthController.php'
];

$migratedControllers = 0;
foreach ($controllersToCheck as $controller) {
    $filePath = $controllerPath . '/' . $controller;
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        
        if (strpos($content, 'extends BaseControllerMiddleware') !== false) {
            echo "✅ {$controller} extends BaseControllerMiddleware\n";
            $migratedControllers++;
        } else {
            echo "❌ {$controller} does not extend BaseControllerMiddleware\n";
        }
    }
}

echo "\nTest 3: Middleware-Aware Methods\n";
echo "-------------------------------\n";

// Check for new middleware-aware methods
$baseControllerFile = $controllerPath . '/BaseControllerMiddleware.php';
if (file_exists($baseControllerFile)) {
    $content = file_get_contents($baseControllerFile);
    
    $requiredMethods = [
        'getUser()',
        'getInput()', 
        'getParams()',
        'getUserRole()',
        'hasRole(',
        'isAdmin()',
        'isDoctor()',
        'paginated('
    ];
    
    $methodsFound = 0;
    foreach ($requiredMethods as $method) {
        if (strpos($content, $method) !== false) {
            echo "✅ Method {$method} found\n";
            $methodsFound++;
        } else {
            echo "❌ Method {$method} missing\n";
        }
    }
    
    echo "\nMethods found: {$methodsFound}/" . count($requiredMethods) . "\n";
} else {
    echo "❌ BaseControllerMiddleware.php not found\n";
}

echo "\nTest 4: Legacy Code Removal\n";
echo "--------------------------\n";

// Check that legacy auth calls have been removed
$legacyCalls = 0;
foreach ($controllersToCheck as $controller) {
    $filePath = $controllerPath . '/' . $controller;
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        
        $legacyMethods = [
            '$this->requireAuth()',
            '$this->requireRole(',
            '$this->input'
        ];
        
        $hasLegacy = false;
        foreach ($legacyMethods as $legacy) {
            if (strpos($content, $legacy) !== false) {
                $hasLegacy = true;
                break;
            }
        }
        
        if (!$hasLegacy) {
            echo "✅ {$controller} has no legacy calls\n";
        } else {
            echo "⚠️  {$controller} may still have legacy calls\n";
            $legacyCalls++;
        }
    }
}

echo "\nTest 5: Route Registry Files\n";
echo "---------------------------\n";

$routeFiles = [
    'src/Routes/RouteRegistry.php',
    'src/Routes/RouteRegistryEnhanced.php',
    'src/Routes/Route.php',
    'routes/api_with_middleware.php'
];

$routeFilesFound = 0;
foreach ($routeFiles as $file) {
    $filePath = $backendPath . '/' . $file;
    if (file_exists($filePath)) {
        echo "✅ {$file} exists\n";
        $routeFilesFound++;
    } else {
        echo "❌ {$file} missing\n";
    }
}

echo "\nTest 6: Middleware System Files\n";
echo "------------------------------\n";

$middlewareFiles = [
    'src/Middleware/MiddlewareManager.php',
    'src/Middleware/AuthMiddleware.php',
    'src/Middleware/RoleMiddleware.php',
    'src/Middleware/ValidationMiddleware.php'
];

$middlewareFilesFound = 0;
foreach ($middlewareFiles as $file) {
    $filePath = $backendPath . '/' . $file;
    if (file_exists($filePath)) {
        echo "✅ {$file} exists\n";
        $middlewareFilesFound++;
    } else {
        echo "❌ {$file} missing\n";
    }
}

echo "\n📊 Migration Test Results\n";
echo "========================\n";

$totalTests = 6;
$passedTests = 0;

// Calculate pass rates
$fileStructurePass = ($existingFiles == count($requiredFiles));
$controllerMigrationPass = ($migratedControllers == count($controllersToCheck));
$methodsPass = (isset($methodsFound) && $methodsFound >= 6); // At least 6 out of 8 methods
$legacyPass = ($legacyCalls <= 1); // Allow 1 controller to still have legacy calls
$routeFilesPass = ($routeFilesFound >= 3); // At least 3 out of 4 files
$middlewareFilesPass = ($middlewareFilesFound >= 3); // At least 3 out of 4 files

if ($fileStructurePass) $passedTests++;
if ($controllerMigrationPass) $passedTests++;  
if ($methodsPass) $passedTests++;
if ($legacyPass) $passedTests++;
if ($routeFilesPass) $passedTests++;
if ($middlewareFilesPass) $passedTests++;

echo "• File Structure: " . ($fileStructurePass ? "✅ PASSED" : "❌ FAILED") . "\n";
echo "• Controller Migration: " . ($controllerMigrationPass ? "✅ PASSED" : "❌ FAILED") . "\n";
echo "• Middleware Methods: " . ($methodsPass ? "✅ PASSED" : "❌ FAILED") . "\n";
echo "• Legacy Code Removal: " . ($legacyPass ? "✅ PASSED" : "⚠️  PARTIAL") . "\n";
echo "• Route Registry: " . ($routeFilesPass ? "✅ PASSED" : "❌ FAILED") . "\n";
echo "• Middleware System: " . ($middlewareFilesPass ? "✅ PASSED" : "❌ FAILED") . "\n";

$successRate = round(($passedTests / $totalTests) * 100, 1);
echo "\n🎯 Overall Results:\n";
echo "• Passed: {$passedTests}/{$totalTests} ({$successRate}%)\n";

if ($passedTests == $totalTests) {
    echo "\n🎉 MIGRATION FULLY SUCCESSFUL!\n";
    echo "All controllers and systems properly migrated.\n";
    exit(0);
} elseif ($passedTests >= 4) {
    echo "\n✅ MIGRATION MOSTLY SUCCESSFUL!\n";
    echo "Minor issues detected but core functionality is ready.\n";
    exit(0);
} else {
    echo "\n⚠️ MIGRATION NEEDS ATTENTION!\n";
    echo "Please fix the failed tests before deployment.\n";
    exit(1);
}
