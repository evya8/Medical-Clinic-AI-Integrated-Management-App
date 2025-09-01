<?php

/**
 * Medical Clinic API - Main Entry Point
 * 
 * This file replaces or works alongside your existing api.php
 * Integrates the Route Registry system with existing middleware and controllers
 */

require_once __DIR__ . '/vendor/autoload.php';

use MedicalClinic\Routes\RouteRegistry;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Set error reporting
if ($_ENV['APP_DEBUG'] ?? false) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Set default timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');

// CORS Headers (if needed)
$allowedOrigins = [
    'http://localhost:3000',
    'http://localhost:8080',
    'http://127.0.0.1:3000',
    'http://127.0.0.1:8080'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Set JSON content type for API responses
header('Content-Type: application/json');

try {
    // Load the route registry
    $registry = require_once __DIR__ . '/routes/api_routes.php';
    
    if (!$registry instanceof RouteRegistry) {
        throw new Exception('Failed to load route registry');
    }
    
    // Get request details
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    // Remove script name from path if present
    $scriptName = dirname($_SERVER['SCRIPT_NAME']);
    if ($scriptName !== '/' && strpos($path, $scriptName) === 0) {
        $path = substr($path, strlen($scriptName));
    }
    
    // Ensure path starts with /
    $path = '/' . ltrim($path, '/');
    
    // Log request for debugging (if enabled)
    if ($_ENV['APP_DEBUG'] ?? false) {
        error_log("API Request: $method $path");
    }
    
    // Dispatch the request through the route registry
    $registry->dispatch($method, $path);
    
} catch (Throwable $e) {
    // Log the error
    error_log("API Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    
    // Determine error response based on environment
    $debug = $_ENV['APP_DEBUG'] ?? false;
    $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
    
    // Send appropriate HTTP status code
    http_response_code($statusCode);
    
    // Create error response
    $errorResponse = [
        'success' => false,
        'message' => $debug ? $e->getMessage() : 'An error occurred while processing your request',
        'timestamp' => date('Y-m-d H:i:s'),
        'status_code' => $statusCode
    ];
    
    // Add debug information if in debug mode
    if ($debug) {
        $errorResponse['debug'] = [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => array_slice($e->getTrace(), 0, 5) // Limit trace for readability
        ];
    }
    
    // Send error response
    echo json_encode($errorResponse);
}
