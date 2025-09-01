#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MedicalClinic\Routes\Route;
use MedicalClinic\Routes\RouteRegistry;

echo "Route Registry Fix Verification\n";
echo "==============================\n\n";

// Test 1: Basic Route Matching
echo "1. Testing basic route matching...\n";
$route = new Route('GET', '/users', function() { return 'users'; });

$test1a = $route->matches('GET', '/users');
$test1b = !$route->matches('get', '/users'); // Should be case sensitive
$test1c = !$route->matches('POST', '/users');

echo $test1a ? "✅ GET /users matches\n" : "❌ GET /users should match\n";
echo $test1b ? "✅ Method is case sensitive\n" : "❌ Method should be case sensitive\n";
echo $test1c ? "✅ POST /users doesn't match\n" : "❌ POST /users shouldn't match\n";

// Test 2: Parameter Extraction
echo "\n2. Testing parameter extraction...\n";
$paramRoute = new Route('GET', '/users/{id}/posts/{postId}', function() {});

$test2a = $paramRoute->matches('GET', '/users/123/posts/456');
$params = $paramRoute->extractParameters('/users/123/posts/456');
$test2b = isset($params['id']) && $params['id'] === '123';
$test2c = isset($params['postId']) && $params['postId'] === '456';

echo $test2a ? "✅ Parameter route matches\n" : "❌ Parameter route should match\n";
echo $test2b ? "✅ ID parameter extracted correctly\n" : "❌ ID parameter not extracted: " . print_r($params, true) . "\n";
echo $test2c ? "✅ PostID parameter extracted correctly\n" : "❌ PostID parameter not extracted\n";

// Test 3: Registry Functionality
echo "\n3. Testing route registry...\n";
$registry = new RouteRegistry();

$registry->get('/test', function() { return 'test'; });
$registry->post('/users/{id}', function() { return 'user'; });

$test3a = $registry->hasRoute('GET', '/test');
$test3b = $registry->hasRoute('POST', '/users/123');
$test3c = !$registry->hasRoute('PUT', '/test');

echo $test3a ? "✅ Registry finds GET /test\n" : "❌ Registry should find GET /test\n";
echo $test3b ? "✅ Registry finds POST /users/{id}\n" : "❌ Registry should find POST /users/{id}\n";
echo $test3c ? "✅ Registry correctly rejects PUT /test\n" : "❌ Registry should reject PUT /test\n";

// Test 4: Handler String Method
echo "\n4. Testing handler string method...\n";
$handlerRoute = new Route('GET', '/test', [TestController::class, 'index']);

try {
    $handlerString = $handlerRoute->getHandlerString();
    $test4 = is_string($handlerString);
    echo $test4 ? "✅ getHandlerString() works\n" : "❌ getHandlerString() returned non-string\n";
} catch (Exception $e) {
    echo "❌ getHandlerString() threw exception: " . $e->getMessage() . "\n";
}

// Test 5: Constraints
echo "\n5. Testing parameter constraints...\n";
$constraintRoute = new Route('GET', '/users/{id}', function() {});
$constraintRoute->whereNumber('id');

$test5a = $constraintRoute->matchesConstraint('id', '123');
$test5b = !$constraintRoute->matchesConstraint('id', 'abc');

echo $test5a ? "✅ Numeric constraint accepts numbers\n" : "❌ Numeric constraint should accept numbers\n";
echo $test5b ? "✅ Numeric constraint rejects letters\n" : "❌ Numeric constraint should reject letters\n";

echo "\n" . str_repeat("=", 40) . "\n";

$totalTests = 12;
$passedTests = ($test1a ? 1 : 0) + ($test1b ? 1 : 0) + ($test1c ? 1 : 0) + 
               ($test2a ? 1 : 0) + ($test2b ? 1 : 0) + ($test2c ? 1 : 0) + 
               ($test3a ? 1 : 0) + ($test3b ? 1 : 0) + ($test3c ? 1 : 0) + 
               ($test4 ? 1 : 0) + ($test5a ? 1 : 0) + ($test5b ? 1 : 0);

echo "Results: {$passedTests}/{$totalTests} tests passed\n";

if ($passedTests === $totalTests) {
    echo "🎉 All core fixes are working!\n";
    echo "You can now run the full test suite: php scripts/test_route_registry.php\n";
} else {
    echo "⚠️ Some issues remain. Check the output above.\n";
}

// Create a dummy controller class for testing
class TestController {
    public function index() {
        return 'test';
    }
}
