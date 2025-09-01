<?php

/**
 * Simple Bootstrap for Testing
 * This provides basic autoloading and setup without complex routing
 */

// Autoload composer dependencies
require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables if .env exists
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Set timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');

// Simple error handler for testing
function handleError($errno, $errstr, $errfile, $errline) {
    if (error_reporting() & $errno) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
    return false;
}

set_error_handler('handleError');

// Database configuration helper
function getDatabaseConfig(): array {
    return [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'database' => $_ENV['DB_DATABASE'] ?? 'medical_clinic',
        'username' => $_ENV['DB_USERNAME'] ?? 'root',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'port' => $_ENV['DB_PORT'] ?? 3306,
        'charset' => 'utf8mb4'
    ];
}

// Environment helper
function isDebugMode(): bool {
    return ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
}

function getEnvironment(): string {
    return $_ENV['APP_ENV'] ?? 'production';
}

// Basic JSON response helper
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
}

// Success message for bootstrap
if (php_sapi_name() === 'cli') {
    echo "✅ Bootstrap loaded successfully for CLI environment\n";
} else {
    // Only set headers if we're in a web context and no output has been sent
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
}
