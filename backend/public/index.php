<?php

header('Content-Type: application/json');

// Enable CORS
$corsConfig = require_once __DIR__ . '/../config/cors.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (in_array($origin, $corsConfig['allowed_origins'])) {
        header("Access-Control-Allow-Origin: $origin");
    }
    header('Access-Control-Allow-Methods: ' . implode(', ', $corsConfig['allowed_methods']));
    header('Access-Control-Allow-Headers: ' . implode(', ', $corsConfig['allowed_headers']));
    header('Access-Control-Max-Age: ' . $corsConfig['max_age']);
    if ($corsConfig['supports_credentials']) {
        header('Access-Control-Allow-Credentials: true');
    }
    http_response_code(200);
    exit;
}

// Set CORS headers for actual requests
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $corsConfig['allowed_origins'])) {
    header("Access-Control-Allow-Origin: $origin");
}
if ($corsConfig['supports_credentials']) {
    header('Access-Control-Allow-Credentials: true');
}

// Load autoloader and environment
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use MedicalClinic\Utils\Database;

try {
    // Load environment variables
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
    
    // Load application config
    $config = require_once __DIR__ . '/../config/app.php';
    
    // Initialize database connection
    Database::getInstance();
    
    // Load routes
    require_once __DIR__ . '/../routes/api.php';
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $config['debug'] ? $e->getMessage() : 'Something went wrong'
    ]);
    exit;
}
