#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MedicalClinic\Models\RefreshToken;
use MedicalClinic\Utils\Database;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

echo "Running refresh token cleanup...\n";

try {
    $db = Database::getInstance();
    
    // Clean up expired tokens
    echo "Cleaning up expired tokens...\n";
    $expiredCleaned = RefreshToken::cleanupExpired();
    echo "Expired tokens cleaned: $expiredCleaned\n";
    
    // Clean up old revoked tokens (older than 30 days)
    echo "Cleaning up old revoked tokens...\n";
    $revokedCleaned = RefreshToken::cleanupOldRevoked(30);
    echo "Old revoked tokens cleaned: $revokedCleaned\n";
    
    // Show current statistics
    echo "\nCurrent token statistics:\n";
    $stats = RefreshToken::getTokenStats();
    echo "- Total tokens: " . $stats['total_tokens'] . "\n";
    echo "- Active tokens: " . $stats['active_tokens'] . "\n";
    echo "- Revoked tokens: " . $stats['revoked_tokens'] . "\n";
    echo "- Expired tokens: " . $stats['expired_tokens'] . "\n";
    echo "- Users with tokens: " . $stats['unique_users'] . "\n";
    
    $totalCleaned = $expiredCleaned + $revokedCleaned;
    echo "\nCleanup completed successfully!\n";
    echo "Total tokens cleaned: $totalCleaned\n";
    
} catch (Exception $e) {
    echo "Cleanup failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Add to crontab:
// # Run every hour
// 0 * * * * cd /path/to/backend && php scripts/cleanup_refresh_tokens.php
//
// # Run daily at 2 AM
// 0 2 * * * cd /path/to/backend && php scripts/cleanup_refresh_tokens.php
