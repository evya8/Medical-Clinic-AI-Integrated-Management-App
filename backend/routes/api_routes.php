<?php

use MedicalClinic\Routes\RouteRegistry;
use MedicalClinic\Routes\RouteBuilder;
use MedicalClinic\Middleware\MiddlewareManager;
use MedicalClinic\Controllers\AuthControllerRefresh;
use MedicalClinic\Controllers\PatientController;
use MedicalClinic\Controllers\UserController;
use MedicalClinic\Controllers\AppointmentController;
use MedicalClinic\Controllers\DoctorController;

/**
 * Medical Clinic API Routes
 * 
 * This file replaces the legacy api.php routing system with the new
 * Route Registry system supporting middleware and parameter extraction.
 */

// Initialize route registry and builder
$registry = new RouteRegistry();
$builder = new RouteBuilder($registry);

// Add CORS support
$builder->corsRoutes();

// Add health check routes
$builder->healthRoutes();

// =============================================================================
// Public Routes (No Authentication Required)
// =============================================================================

$builder->publicRoutes(function(RouteRegistry $routes) {
    // Health and status endpoints
    $routes->get('/status', function() {
        echo json_encode([
            'status' => 'online',
            'service' => 'Medical Clinic API',
            'version' => '2.0.0',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    })->setName('api.status');
    
    // API documentation endpoint
    $routes->get('/docs', function() {
        echo json_encode([
            'message' => 'Medical Clinic API Documentation',
            'endpoints' => '/api/routes',
            'authentication' => 'Bearer token required for most endpoints',
            'refresh_token' => 'Supported for seamless authentication'
        ]);
    })->setName('api.docs');
});

// =============================================================================
// Authentication Routes
// =============================================================================

$registry->group(['prefix' => 'auth'], function(RouteRegistry $routes) {
    
    // Login - No auth required
    $routes->post('/login', [AuthControllerRefresh::class, 'login'], 
        MiddlewareManager::loginEndpoint())
        ->setName('auth.login');
    
    // Refresh Token - No auth required (uses refresh token)
    $routes->post('/refresh', [AuthControllerRefresh::class, 'refreshToken'],
        MiddlewareManager::publicEndpoint())
        ->setName('auth.refresh');
        
    // Logout (all devices) - Requires access token
    $routes->post('/logout', [AuthControllerRefresh::class, 'logout'],
        MiddlewareManager::authEndpoint())
        ->setName('auth.logout');
        
    // Logout single device - Uses refresh token
    $routes->post('/logout-single', [AuthControllerRefresh::class, 'logoutSingle'],
        MiddlewareManager::publicEndpoint())
        ->setName('auth.logout.single');
    
    // Get current user info - Requires access token
    $routes->get('/me', [AuthControllerRefresh::class, 'me'],
        MiddlewareManager::authEndpoint())
        ->setName('auth.me');
        
    // Get active sessions - Requires access token
    $routes->get('/sessions', [AuthControllerRefresh::class, 'activeSessions'],
        MiddlewareManager::authEndpoint())
        ->setName('auth.sessions');
        
    // Revoke specific session - Requires access token
    $routes->delete('/sessions/{jti}', [AuthControllerRefresh::class, 'revokeSession'],
        MiddlewareManager::authEndpoint())
        ->whereAlphaNumeric('jti')
        ->setName('auth.sessions.revoke');
});

// =============================================================================
// Admin Only Routes
// =============================================================================

$registry->group(['prefix' => 'admin'], function(RouteRegistry $routes) {
    
    // User Management (Admin only)
    $routes->get('/users', [UserController::class, 'index'],
        MiddlewareManager::adminEndpoint())
        ->setName('admin.users.index');
        
    $routes->post('/users', [UserController::class, 'store'],
        MiddlewareManager::userCreationEndpoint())
        ->setName('admin.users.store');
        
    $routes->get('/users/{id}', [UserController::class, 'show'],
        MiddlewareManager::adminEndpoint())
        ->whereNumber('id')
        ->setName('admin.users.show');
        
    $routes->put('/users/{id}', [UserController::class, 'update'],
        MiddlewareManager::adminEndpoint())
        ->whereNumber('id')
        ->setName('admin.users.update');
        
    $routes->delete('/users/{id}', [UserController::class, 'destroy'],
        MiddlewareManager::adminEndpoint())
        ->whereNumber('id')
        ->setName('admin.users.destroy');
    
    // Token Management (Admin only)
    $routes->get('/token-stats', [AuthControllerRefresh::class, 'tokenStats'],
        MiddlewareManager::adminEndpoint())
        ->setName('admin.tokens.stats');
        
    $routes->post('/cleanup-tokens', [AuthControllerRefresh::class, 'cleanupTokens'],
        MiddlewareManager::adminEndpoint())
        ->setName('admin.tokens.cleanup');
        
    // System Routes
    $routes->get('/routes', function() use ($registry) {
        $builder = new RouteBuilder($registry);
        echo json_encode([
            'routes' => $builder->generateRouteList(),
            'stats' => $builder->getStats()
        ], JSON_PRETTY_PRINT);
    }, MiddlewareManager::adminEndpoint())
        ->setName('admin.routes');
});

// =============================================================================
// Doctor/Medical Staff Routes
// =============================================================================

$registry->group(['prefix' => 'api'], function(RouteRegistry $routes) {
    
    // Patient Management (Doctor + Admin access)
    $patientMiddleware = MiddlewareManager::create()
        ->auth()
        ->doctorAccess();
        
    $routes->get('/patients', [PatientController::class, 'index'], $patientMiddleware)
        ->setName('patients.index');
        
    $routes->post('/patients', [PatientController::class, 'store'],
        MiddlewareManager::patientEndpoint())
        ->setName('patients.store');
        
    $routes->get('/patients/{id}', [PatientController::class, 'show'], $patientMiddleware)
        ->whereNumber('id')
        ->setName('patients.show');
        
    $routes->put('/patients/{id}', [PatientController::class, 'update'],
        MiddlewareManager::patientEndpoint())
        ->whereNumber('id')
        ->setName('patients.update');
        
    $routes->delete('/patients/{id}', [PatientController::class, 'destroy'], $patientMiddleware)
        ->whereNumber('id')
        ->setName('patients.destroy');
        
    // Patient search
    $routes->get('/patients/search/{query}', [PatientController::class, 'search'], $patientMiddleware)
        ->where(['query' => '[^/]+'])
        ->setName('patients.search');
    
    // Appointment Management (Doctor + Admin access)
    $appointmentMiddleware = MiddlewareManager::create()
        ->auth()
        ->doctorAccess();
        
    $routes->get('/appointments', [AppointmentController::class, 'index'], $appointmentMiddleware)
        ->setName('appointments.index');
        
    $routes->post('/appointments', [AppointmentController::class, 'store'],
        MiddlewareManager::appointmentEndpoint())
        ->setName('appointments.store');
        
    $routes->get('/appointments/{id}', [AppointmentController::class, 'show'], $appointmentMiddleware)
        ->whereNumber('id')
        ->setName('appointments.show');
        
    $routes->put('/appointments/{id}', [AppointmentController::class, 'update'],
        MiddlewareManager::appointmentEndpoint())
        ->whereNumber('id')
        ->setName('appointments.update');
        
    $routes->delete('/appointments/{id}', [AppointmentController::class, 'destroy'], $appointmentMiddleware)
        ->whereNumber('id')
        ->setName('appointments.destroy');
    
    // Appointment by date range
    $routes->get('/appointments/range/{start_date}/{end_date}', 
        [AppointmentController::class, 'getByDateRange'], $appointmentMiddleware)
        ->where([
            'start_date' => '\d{4}-\d{2}-\d{2}',
            'end_date' => '\d{4}-\d{2}-\d{2}'
        ])
        ->setName('appointments.date-range');
    
    // Doctor schedule
    $routes->get('/doctors/{id}/appointments/{date}', 
        [DoctorController::class, 'getAppointments'], $appointmentMiddleware)
        ->whereNumber('id')
        ->where(['date' => '\d{4}-\d{2}-\d{2}'])
        ->setName('doctors.appointments');
        
    // Doctor availability
    $routes->get('/doctors/{id}/availability/{date}', 
        [DoctorController::class, 'getAvailability'], $appointmentMiddleware)
        ->whereNumber('id')
        ->where(['date' => '\d{4}-\d{2}-\d{2}'])
        ->setName('doctors.availability');
});

// =============================================================================
// API Information Routes
// =============================================================================

$registry->get('/api', function() use ($registry) {
    $builder = new RouteBuilder($registry);
    $stats = $builder->getStats();
    
    echo json_encode([
        'message' => 'Medical Clinic API v2.0',
        'documentation' => '/api/docs',
        'routes' => '/admin/routes',
        'authentication' => [
            'type' => 'Bearer Token',
            'refresh_supported' => true,
            'access_token_expiry' => '15 minutes',
            'refresh_token_expiry' => '7 days'
        ],
        'statistics' => $stats
    ], JSON_PRETTY_PRINT);
})->setName('api.info');

// =============================================================================
// Route Dispatch Handler
// =============================================================================

/**
 * Main routing dispatch function
 * Call this from your main index.php or api endpoint
 */
function handleRequest(RouteRegistry $registry): void
{
    // Get request method and path
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    // Remove script name from path if present
    $scriptName = $_SERVER['SCRIPT_NAME'];
    if (strpos($path, $scriptName) === 0) {
        $path = substr($path, strlen($scriptName));
    }
    
    // Handle the request
    try {
        $registry->dispatch($method, $path);
    } catch (Throwable $e) {
        // Log error
        error_log("Route dispatch error: " . $e->getMessage());
        
        // Return error response
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Internal server error',
            'error' => $_ENV['APP_DEBUG'] ? $e->getMessage() : 'An error occurred',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

// Export the registry for use in index.php
return $registry;
