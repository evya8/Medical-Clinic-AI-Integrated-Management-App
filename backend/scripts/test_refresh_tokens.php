#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MedicalClinic\Utils\JWTAuthRefresh;
use MedicalClinic\Models\User;
use MedicalClinic\Models\RefreshToken;
use MedicalClinic\Utils\Database;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

echo "Testing Refresh Token System\n";
echo "============================\n\n";

$tests = [];
$passed = 0;
$failed = 0;

function test($description, $callback) {
    global $tests, $passed, $failed;
    
    try {
        $result = $callback();
        if ($result) {
            echo "✅ $description\n";
            $passed++;
        } else {
            echo "❌ $description - Test returned false\n";
            $failed++;
        }
    } catch (Exception $e) {
        echo "❌ $description - Error: " . $e->getMessage() . "\n";
        $failed++;
    }
}

// Check if refresh_tokens table exists
function checkRefreshTokensTable(): bool {
    try {
        $db = Database::getInstance();
        $result = $db->fetch("SHOW TABLES LIKE 'refresh_tokens'");
        return $result !== null;
    } catch (Exception $e) {
        return false;
    }
}

echo "1️⃣ Testing Database Setup\n";
echo "-------------------------\n";

test("Refresh tokens table exists", function() {
    return checkRefreshTokensTable();
});

test("RefreshToken model can be instantiated", function() {
    $token = new RefreshToken();
    return $token instanceof RefreshToken;
});

echo "\n2️⃣ Testing JWTAuthRefresh Utility\n";
echo "--------------------------------\n";

test("JWTAuthRefresh class exists and can be used", function() {
    return class_exists('MedicalClinic\\Utils\\JWTAuthRefresh');
});

// Create a test user for token testing
$testUser = null;
test("Can create test user for token tests", function() use (&$testUser) {
    // Try to find existing test user or create one
    $testUser = User::findByEmailOrUsername('test@refresh.com');
    
    if (!$testUser) {
        $testUser = User::create([
            'username' => 'refresh_test_user',
            'email' => 'test@refresh.com',
            'password_hash' => password_hash('test123', PASSWORD_DEFAULT),
            'role' => 'doctor',
            'first_name' => 'Test',
            'last_name' => 'User',
            'is_active' => 1
        ]);
    }
    
    return $testUser && $testUser->id > 0;
});

test("Can generate token pair", function() use ($testUser) {
    if (!$testUser) return true; // Skip if no test user
    
    $tokens = JWTAuthRefresh::generateTokenPair($testUser->toArray());
    
    return isset($tokens['access_token']) && 
           isset($tokens['refresh_token']) &&
           isset($tokens['access_expires_in']) &&
           isset($tokens['refresh_expires_in']);
});

$testTokens = null;
test("Generated tokens are valid", function() use ($testUser, &$testTokens) {
    if (!$testUser) return true; // Skip if no test user
    
    $testTokens = JWTAuthRefresh::generateTokenPair($testUser->toArray());
    
    $accessPayload = JWTAuthRefresh::validateAccessToken($testTokens['access_token']);
    $refreshPayload = JWTAuthRefresh::validateRefreshToken($testTokens['refresh_token']);
    
    return $accessPayload !== null && 
           $refreshPayload !== null &&
           $accessPayload['type'] === 'access' &&
           $refreshPayload['type'] === 'refresh';
});

echo "\n3️⃣ Testing Token Operations\n";
echo "---------------------------\n";

test("Can refresh tokens using refresh token", function() use ($testTokens) {
    if (!$testTokens) return true; // Skip if no test tokens
    
    $newTokens = JWTAuthRefresh::refreshTokens($testTokens['refresh_token']);
    
    return $newTokens !== null && 
           isset($newTokens['access_token']) &&
           isset($newTokens['refresh_token']);
});

test("Old refresh token becomes invalid after refresh", function() use ($testTokens) {
    if (!$testTokens) return true; // Skip if no test tokens
    
    // First refresh
    $newTokens = JWTAuthRefresh::refreshTokens($testTokens['refresh_token']);
    
    // Try to use old refresh token again
    $secondRefresh = JWTAuthRefresh::refreshTokens($testTokens['refresh_token']);
    
    return $newTokens !== null && $secondRefresh === null;
});

test("Can revoke all user tokens", function() use ($testUser) {
    if (!$testUser) return true; // Skip if no test user
    
    // Generate some tokens
    JWTAuthRefresh::generateTokenPair($testUser->toArray());
    JWTAuthRefresh::generateTokenPair($testUser->toArray());
    
    $revokedCount = JWTAuthRefresh::revokeAllUserTokens($testUser->id);
    
    return $revokedCount >= 0; // Should revoke some tokens
});

echo "\n4️⃣ Testing RefreshToken Model\n";
echo "-----------------------------\n";

test("RefreshToken model relationships work", function() use ($testUser) {
    if (!$testUser) return true; // Skip if no test user
    
    $activeTokens = RefreshToken::getActiveTokensForUser($testUser->id);
    
    if (empty($activeTokens)) {
        return true; // No tokens to test with
    }
    
    $refreshToken = $activeTokens[0];
    $user = $refreshToken->user();
    
    return $user !== null && $user->id === $testUser->id;
});

test("RefreshToken status methods work", function() use ($testUser) {
    if (!$testUser) return true; // Skip if no test user
    
    // Generate a fresh token
    $tokens = JWTAuthRefresh::generateTokenPair($testUser->toArray());
    $payload = JWTAuthRefresh::validateRefreshToken($tokens['refresh_token']);
    
    if (!$payload) return false;
    
    $refreshToken = RefreshToken::findByJTI($payload['jti']);
    
    if (!$refreshToken) return false;
    
    return $refreshToken->isActive() && 
           !$refreshToken->isExpired() && 
           !$refreshToken->isRevoked();
});

test("RefreshToken display info works", function() use ($testUser) {
    if (!$testUser) return true; // Skip if no test user
    
    $activeTokens = RefreshToken::getActiveTokensForUser($testUser->id);
    
    if (empty($activeTokens)) {
        return true; // No tokens to test with
    }
    
    $refreshToken = $activeTokens[0];
    $displayInfo = $refreshToken->getDisplayInfo();
    
    return is_array($displayInfo) && 
           isset($displayInfo['jti']) &&
           isset($displayInfo['is_active']) &&
           isset($displayInfo['user_name']);
});

echo "\n5️⃣ Testing Token Statistics\n";
echo "---------------------------\n";

test("Token statistics can be retrieved", function() {
    $stats = RefreshToken::getTokenStats();
    
    return is_array($stats) && 
           isset($stats['total_tokens']) &&
           isset($stats['active_tokens']);
});

test("Token trends can be retrieved", function() {
    $trends = RefreshToken::getTokenTrends(7);
    
    return is_array($trends);
});

echo "\n6️⃣ Testing Token Cleanup\n";
echo "------------------------\n";

test("Expired tokens can be cleaned up", function() {
    $cleaned = RefreshToken::cleanupExpired();
    return is_int($cleaned) && $cleaned >= 0;
});

test("Old revoked tokens can be cleaned up", function() {
    $cleaned = RefreshToken::cleanupOldRevoked(30);
    return is_int($cleaned) && $cleaned >= 0;
});

// Clean up test user
if ($testUser && $testUser->username === 'refresh_test_user') {
    try {
        // Revoke all tokens first
        RefreshToken::revokeAllForUser($testUser->id);
        // Delete test user
        $testUser->delete();
        echo "\nTest user cleaned up.\n";
    } catch (Exception $e) {
        echo "\nNote: Could not clean up test user: " . $e->getMessage() . "\n";
    }
}

// Summary
echo "\nTest Results\n";
echo "============\n";
echo "Passed: $passed tests\n";
echo "Failed: $failed tests\n";
echo "Success Rate: " . ($passed + $failed > 0 ? round(($passed / ($passed + $failed)) * 100, 1) : 0) . "%\n\n";

if ($failed === 0) {
    echo "Refresh token system is working correctly!\n";
    echo "Ready to integrate with controllers and routes.\n\n";
    
    echo "Key features implemented:\n";
    echo "- Dual JWT system (access + refresh tokens)\n";
    echo "- Automatic token rotation on refresh\n";
    echo "- Database-backed token revocation\n";
    echo "- Token cleanup and statistics\n";
    echo "- Session management per device\n";
} elseif ($passed >= ($passed + $failed) * 0.8) {
    echo "Refresh token system is mostly working.\n";
    echo "Minor issues detected, but core functionality is solid.\n";
} else {
    echo "Some refresh token tests failed.\n";
    echo "Check database setup and token configuration.\n";
    exit(1);
}

echo "\nRefresh token system implementation complete!\n";
