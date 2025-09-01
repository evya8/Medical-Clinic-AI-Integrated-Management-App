<?php

use MedicalClinic\Routes\RouteRegistry;
use MedicalClinic\Routes\ApiRoutes;
use Dotenv\Dotenv;

/**
 * Modern API Bootstrap
 * This file replaces or integrates with the existing api.php
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Set error reporting
error_reporting($_ENV['APP_DEBUG'] ? E_ALL : 0);
ini_set('display_errors', $_ENV['APP_DEBUG'] ? '1' : '0');

// CORS Headers (if needed)
if (isset($_ENV['CORS_ENABLED']) && $_ENV['CORS_ENABLED']) {
    header('Access-Control-Allow-Origin: ' . ($_ENV['CORS_ORIGIN'] ?? '*'));
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Credentials: true');
    
    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

// Set JSON content type for API responses
header('Content-Type: application/json; charset=utf-8');

try {
    // Initialize route registry
    $router = new RouteRegistry();
    
    // Register all routes
    $apiRoutes = new ApiRoutes($router);
    $apiRoutes->register();
    
    // Get request method and path
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    
    // Remove base path if API is in a subdirectory
    $basePath = $_ENV['API_BASE_PATH'] ?? '';
    if ($basePath && strpos($path, $basePath) === 0) {
        $path = substr($path, strlen($basePath));
    }
    
    // Dispatch request
    $router->dispatch($method, $path);
    
} catch (Throwable $e) {
    // Global error handler
    $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
    http_response_code($statusCode);
    
    $response = [
        'success' => false,
        'message' => 'Internal server error',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Add debug information in development
    if (($_ENV['APP_DEBUG'] ?? false) || ($_ENV['APP_ENV'] ?? 'production') === 'development') {
        $response['debug'] = [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => explode("\n", $e->getTraceAsString())
        ];
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
