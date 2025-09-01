<?php

/**
 * Modern API Routes using RouteRegistry with Middleware Integration
 * 
 * This file demonstrates the new routing system with:
 * - Middleware-aware controllers
 * - Centralized authentication and authorization
 * - Input validation through middleware
 * - Clean route definitions
 * - Parameter extraction and injection
 */

use MedicalClinic\Routes\RouteRegistryEnhanced;
use MedicalClinic\Controllers\AuthController;
use MedicalClinic\Controllers\PatientController;
use MedicalClinic\Controllers\UserController;
use MedicalClinic\Controllers\AppointmentController;
use MedicalClinic\Controllers\DoctorController;
use MedicalClinic\Controllers\ReminderController;
use MedicalClinic\Controllers\AIDashboardController;
use MedicalClinic\Controllers\AITriageController;
use MedicalClinic\Controllers\AIAppointmentSummaryController;
use MedicalClinic\Controllers\AIAlertController;
use MedicalClinic\Middleware\MiddlewareManager;

// Initialize the enhanced route registry
$router = new RouteRegistryEnhanced();

// ================================
// PUBLIC ROUTES (No Authentication)
// ================================

// Health check
$router->get('/health', function($request) {
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Medical Clinic Management API is running',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '2.0.0',
        'middleware_enabled' => true
    ]);
});

// API info
$router->get('/', function($request) {
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Medical Clinic Management API v2.0',
        'documentation' => '/api/docs',
        'health_check' => '/api/health',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
});

// ================================
// AUTHENTICATION ROUTES
// ================================

// Login (public)
$router->post('/auth/login', [AuthController::class, 'login'], 
    MiddlewareManager::loginEndpoint()
)->name('auth.login');

// Logout (requires auth)
$router->post('/auth/logout', [AuthController::class, 'logout'],
    MiddlewareManager::authEndpoint()
)->name('auth.logout');

// Token refresh (public, but validates existing token)
$router->post('/auth/refresh', [AuthController::class, 'refreshToken'])->name('auth.refresh');

// Get current user profile (requires auth)
$router->authGet('/auth/me', [AuthController::class, 'getMe'])
       ->name('auth.me');

// Change password (requires auth)
$router->authPost('/auth/change-password', [AuthController::class, 'changePassword'])
       ->name('auth.change-password');

// Register new user (admin only)
$router->authPost('/auth/register', [AuthController::class, 'register'], ['admin'])
       ->name('auth.register');

// ================================
// USER MANAGEMENT ROUTES (Admin Only)
// ================================

$router->group(['prefix' => 'users', 'middleware' => MiddlewareManager::adminEndpoint()], function($router) {
    // List users
    $router->get('/', [UserController::class, 'getUsers'])->name('users.index');
    
    // Get specific user
    $router->get('/{id}', [UserController::class, 'getUserById'])->whereNumber('id')->name('users.show');
    
    // Create user
    $router->post('/', [UserController::class, 'createUser'])->name('users.store');
    
    // Update user
    $router->put('/{id}', [UserController::class, 'updateUser'])->whereNumber('id')->name('users.update');
    
    // Delete/deactivate user
    $router->delete('/{id}', [UserController::class, 'deleteUser'])->whereNumber('id')->name('users.destroy');
    
    // Activate user
    $router->post('/{id}/activate', [UserController::class, 'activateUser'])->whereNumber('id')->name('users.activate');
});

// Profile routes (any authenticated user can access their own profile)
$router->group(['prefix' => 'profile', 'middleware' => MiddlewareManager::authEndpoint()], function($router) {
    $router->get('/', [UserController::class, 'getProfile'])->name('profile.show');
    $router->put('/', [UserController::class, 'updateProfile'])->name('profile.update');
});

// ================================
// PATIENT ROUTES
// ================================

$router->group(['prefix' => 'patients', 'middleware' => MiddlewareManager::doctorEndpoint()], function($router) {
    // List patients with pagination and search
    $router->get('/', [PatientController::class, 'getPatients'])->name('patients.index');
    
    // Search patients
    $router->get('/search', [PatientController::class, 'searchPatients'])->name('patients.search');
    
    // Get specific patient
    $router->get('/{id}', [PatientController::class, 'getPatient'])->whereNumber('id')->name('patients.show');
    
    // Get patient appointments
    $router->get('/{id}/appointments', [PatientController::class, 'getPatientAppointments'])
           ->whereNumber('id')->name('patients.appointments');
    
    // Create patient
    $router->post('/', [PatientController::class, 'createPatient'])->name('patients.store');
    
    // Update patient
    $router->put('/{id}', [PatientController::class, 'updatePatient'])->whereNumber('id')->name('patients.update');
    
    // Delete patient (admin only)
    $router->delete('/{id}', [PatientController::class, 'deletePatient'])
           ->whereNumber('id')->name('patients.destroy');
});

// ================================
// DOCTOR ROUTES
// ================================

$router->group(['prefix' => 'doctors', 'middleware' => MiddlewareManager::authEndpoint()], function($router) {
    // List doctors
    $router->get('/', [DoctorController::class, 'getDoctors'])->name('doctors.index');
    
    // Get doctor specialties
    $router->get('/specialties', [DoctorController::class, 'getSpecialties'])->name('doctors.specialties');
    
    // Get specific doctor
    $router->get('/{id}', [DoctorController::class, 'getDoctor'])->whereNumber('id')->name('doctors.show');
    
    // Get doctor schedule
    $router->get('/{id}/schedule', [DoctorController::class, 'getDoctorSchedule'])
           ->whereNumber('id')->name('doctors.schedule');
    
    // Get doctor statistics
    $router->get('/{id}/stats', [DoctorController::class, 'getDoctorStats'])
           ->whereNumber('id')->name('doctors.stats');
    
    // Update doctor (admin only)
    $router->put('/{id}', [DoctorController::class, 'updateDoctor'])
           ->whereNumber('id')->name('doctors.update');
});

// ================================
// APPOINTMENT ROUTES
// ================================

$router->group(['prefix' => 'appointments', 'middleware' => MiddlewareManager::doctorEndpoint()], function($router) {
    // List appointments
    $router->get('/', [AppointmentController::class, 'getAppointments'])->name('appointments.index');
    
    // Get current user's appointments (doctors)
    $router->get('/my', [AppointmentController::class, 'getMyAppointments'])->name('appointments.my');
    
    // Get available time slots
    $router->get('/available-slots', [AppointmentController::class, 'getAvailableSlots'])->name('appointments.slots');
    
    // Get specific appointment
    $router->get('/{id}', [AppointmentController::class, 'getAppointment'])
           ->whereNumber('id')->name('appointments.show');
    
    // Create appointment
    $router->post('/', [AppointmentController::class, 'createAppointment'])->name('appointments.store');
    
    // Update appointment
    $router->put('/{id}', [AppointmentController::class, 'updateAppointment'])
           ->whereNumber('id')->name('appointments.update');
    
    // Cancel appointment
    $router->delete('/{id}', [AppointmentController::class, 'deleteAppointment'])
           ->whereNumber('id')->name('appointments.destroy');
});

// ================================
// AI FEATURES ROUTES
// ================================

// AI Dashboard
$router->group(['prefix' => 'ai/dashboard', 'middleware' => MiddlewareManager::doctorEndpoint()], function($router) {
    $router->get('/summary', [AIDashboardController::class, 'getDashboardSummary'])->name('ai.dashboard.summary');
    $router->get('/briefing', [AIDashboardController::class, 'getDailyBriefing'])->name('ai.dashboard.briefing');
    $router->get('/status', [AIDashboardController::class, 'getClinicStatus'])->name('ai.dashboard.status');
    $router->get('/tasks', [AIDashboardController::class, 'getPriorityTasks'])->name('ai.dashboard.tasks');
    $router->get('/metrics', [AIDashboardController::class, 'getPerformanceMetrics'])->name('ai.dashboard.metrics');
    $router->get('/test-ai', [AIDashboardController::class, 'testAIConnection'])->name('ai.dashboard.test');
    $router->get('/model-info', [AIDashboardController::class, 'getAIModelInfo'])->name('ai.dashboard.model');
    
    $router->post('/analyze', [AIDashboardController::class, 'generateCustomAnalysis'])->name('ai.dashboard.analyze');
    $router->post('/refresh', [AIDashboardController::class, 'refreshDashboardComponent'])->name('ai.dashboard.refresh');
});

// AI Triage
$router->group(['prefix' => 'ai/triage', 'middleware' => MiddlewareManager::doctorEndpoint()], function($router) {
    $router->get('/stats', [AITriageController::class, 'getTriageStats'])->name('ai.triage.stats');
    
    $router->post('/analyze', [AITriageController::class, 'analyzePatientCase'])->name('ai.triage.analyze');
    $router->post('/batch-analyze', [AITriageController::class, 'batchTriageAnalysis'])->name('ai.triage.batch');
    $router->post('/symptom-triage', [AITriageController::class, 'getSymptomTriage'])->name('ai.triage.symptom');
    $router->post('/referral-recommendations', [AITriageController::class, 'generateReferralRecommendations'])
           ->name('ai.triage.referral');
    $router->post('/quick-assessment', [AITriageController::class, 'quickTriageAssessment'])->name('ai.triage.quick');
    
    $router->put('/update-priority', [AITriageController::class, 'updateTriagePriority'])->name('ai.triage.priority');
});

// AI Summaries
$router->group(['prefix' => 'ai/summaries', 'middleware' => MiddlewareManager::doctorEndpoint()], function($router) {
    $router->get('/stats', [AIAppointmentSummaryController::class, 'getSummaryStats'])->name('ai.summaries.stats');
    $router->get('/{id}', [AIAppointmentSummaryController::class, 'getAppointmentSummary'])
           ->whereNumber('id')->name('ai.summaries.show');
    
    $router->post('/generate', [AIAppointmentSummaryController::class, 'generateAppointmentSummary'])
           ->name('ai.summaries.generate');
    $router->post('/soap', [AIAppointmentSummaryController::class, 'generateSOAPNote'])->name('ai.summaries.soap');
    $router->post('/billing', [AIAppointmentSummaryController::class, 'generateBillingSummary'])
           ->name('ai.summaries.billing');
    $router->post('/patient', [AIAppointmentSummaryController::class, 'generatePatientSummary'])
           ->name('ai.summaries.patient');
    $router->post('/batch', [AIAppointmentSummaryController::class, 'batchGenerateSummaries'])
           ->name('ai.summaries.batch');
    $router->post('/export', [AIAppointmentSummaryController::class, 'exportSummary'])->name('ai.summaries.export');
    
    $router->put('/update', [AIAppointmentSummaryController::class, 'updateSummary'])->name('ai.summaries.update');
});

// AI Alerts
$router->group(['prefix' => 'ai/alerts', 'middleware' => MiddlewareManager::doctorEndpoint()], function($router) {
    $router->get('/', [AIAlertController::class, 'getActiveAlerts'])->name('ai.alerts.index');
    $router->get('/dashboard', [AIAlertController::class, 'getAlertDashboard'])->name('ai.alerts.dashboard');
    $router->get('/active', [AIAlertController::class, 'getActiveAlerts'])->name('ai.alerts.active');
    $router->get('/patient', [AIAlertController::class, 'getPatientAlerts'])->name('ai.alerts.patient');
    $router->get('/analytics', [AIAlertController::class, 'getAlertAnalytics'])->name('ai.alerts.analytics');
    
    $router->post('/generate', [AIAlertController::class, 'generateIntelligentAlerts'])->name('ai.alerts.generate');
    $router->post('/safety', [AIAlertController::class, 'generatePatientSafetyAlerts'])->name('ai.alerts.safety');
    $router->post('/operational', [AIAlertController::class, 'generateOperationalAlerts'])
           ->name('ai.alerts.operational');
    $router->post('/quality', [AIAlertController::class, 'generateQualityAlerts'])->name('ai.alerts.quality');
    $router->post('/create', [AIAlertController::class, 'createManualAlert'])->name('ai.alerts.create');
    $router->post('/acknowledge', [AIAlertController::class, 'acknowledgeAlert'])->name('ai.alerts.acknowledge');
    $router->post('/bulk-acknowledge', [AIAlertController::class, 'bulkAcknowledgeAlerts'])
           ->name('ai.alerts.bulk-ack');
    
    $router->put('/update', [AIAlertController::class, 'updateAlert'])->name('ai.alerts.update');
});

// ================================
// REMINDER SYSTEM ROUTES
// ================================

$router->group(['prefix' => 'reminders', 'middleware' => MiddlewareManager::doctorEndpoint()], function($router) {
    $router->get('/', [ReminderController::class, 'getReminders'])->name('reminders.index');
    $router->get('/stats', [ReminderController::class, 'getReminderStats'])->name('reminders.stats');
    $router->get('/test-email', [ReminderController::class, 'testEmailService'])->name('reminders.test-email');
    $router->get('/test-sms', [ReminderController::class, 'testSMSService'])->name('reminders.test-sms');
    $router->get('/validate-phone', [ReminderController::class, 'validatePhoneNumber'])->name('reminders.validate-phone');
    $router->get('/message-status', [ReminderController::class, 'getMessageStatus'])->name('reminders.status');
    
    $router->post('/send', [ReminderController::class, 'sendManualReminder'])->name('reminders.send');
    $router->post('/schedule', [ReminderController::class, 'scheduleAppointmentReminders'])->name('reminders.schedule');
    $router->post('/process', [ReminderController::class, 'processReminders'])->name('reminders.process');
    $router->post('/test-email', [ReminderController::class, 'sendTestEmail'])->name('reminders.send-test-email');
    $router->post('/test-sms', [ReminderController::class, 'sendTestSMS'])->name('reminders.send-test-sms');
    
    $router->delete('/cancel', [ReminderController::class, 'cancelAppointmentReminders'])->name('reminders.cancel');
});

// ================================
// UTILITY ROUTES
// ================================

// Route debugging (admin only)
$router->get('/debug/routes', function($request) {
    global $router;
    $routes = [];
    
    foreach ($router->getRoutes() as $route) {
        $routes[] = $route->toArray();
    }
    
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Route information',
        'stats' => $router->getStats(),
        'routes' => $routes,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}, MiddlewareManager::adminEndpoint())->name('debug.routes');

// ================================
// ROUTE DISPATCH
// ================================

// Get current request info
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];

// Remove query string and normalize path
$requestPath = strtok($requestUri, '?');
$requestPath = '/' . trim($requestPath, '/');

// Remove /api prefix if present
if (strpos($requestPath, '/api') === 0) {
    $requestPath = substr($requestPath, 4);
    if ($requestPath === '') {
        $requestPath = '/';
    }
}

// Handle preflight OPTIONS requests for CORS
if ($requestMethod === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
    exit();
}

// Set CORS headers for actual requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

try {
    // Dispatch the request through the router
    $router->dispatch($requestMethod, $requestPath);
    
} catch (Exception $e) {
    // Global error handler
    $statusCode = $e->getCode() ?: 500;
    http_response_code($statusCode);
    header('Content-Type: application/json');
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'method' => $requestMethod,
        'path' => $requestPath,
        'timestamp' => date('Y-m-d H:i:s'),
        'trace' => $_ENV['APP_DEBUG'] ?? false ? $e->getTraceAsString() : null
    ]);
}
