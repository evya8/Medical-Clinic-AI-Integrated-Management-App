<?php

use MedicalClinic\Controllers\AuthController;
use MedicalClinic\Controllers\AppointmentController;
use MedicalClinic\Controllers\PatientController;
use MedicalClinic\Controllers\DoctorController;
use MedicalClinic\Controllers\QuestionnaireController;
use MedicalClinic\Controllers\ReminderController;
use MedicalClinic\Controllers\InventoryController;
use MedicalClinic\Controllers\BillController;
use MedicalClinic\Controllers\AIDashboardController;
use MedicalClinic\Controllers\AITriageController;
use MedicalClinic\Controllers\AIAppointmentSummaryController;
use MedicalClinic\Controllers\AIAlertController;

// Simple router implementation
$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];

// Remove query string and leading slash
$path = strtok($request_uri, '?');
$path = trim($path, '/');

// Remove 'api' prefix if present
if (strpos($path, 'api/') === 0) {
    $path = substr($path, 4);
}

// Split path into segments
$segments = explode('/', $path);
$resource = $segments[0] ?? '';
$id = $segments[1] ?? null;
$action = $segments[2] ?? null;

// Route matching
try {
    switch ($resource) {
        case 'auth':
            $controller = new AuthController();
            switch ($request_method) {
                case 'POST':
                    if ($id === 'login') {
                        $controller->login();
                    } elseif ($id === 'logout') {
                        $controller->logout();
                    } elseif ($id === 'refresh') {
                        $controller->refreshToken();
                    } else {
                        throw new Exception('Route not found', 404);
                    }
                    break;
                default:
                    throw new Exception('Method not allowed', 405);
            }
            break;

        case 'ai-dashboard':
            $controller = new AIDashboardController();
            switch ($request_method) {
                case 'GET':
                    if ($id === 'briefing') {
                        $controller->getDailyBriefing();
                    } elseif ($id === 'status') {
                        $controller->getClinicStatus();
                    } elseif ($id === 'tasks') {
                        $controller->getPriorityTasks();
                    } elseif ($id === 'metrics') {
                        $controller->getPerformanceMetrics();
                    } elseif ($id === 'summary') {
                        $controller->getDashboardSummary();
                    } elseif ($id === 'test-ai') {
                        $controller->testAIConnection();
                    } elseif ($id === 'model-info') {
                        $controller->getAIModelInfo();
                    } else {
                        $controller->getDashboardSummary();
                    }
                    break;
                case 'POST':
                    if ($id === 'analyze') {
                        $controller->generateCustomAnalysis();
                    } elseif ($id === 'refresh') {
                        $controller->refreshDashboardComponent();
                    } else {
                        throw new Exception('Route not found', 404);
                    }
                    break;
                default:
                    throw new Exception('Method not allowed', 405);
            }
            break;

        case 'ai-triage':
            $controller = new AITriageController();
            switch ($request_method) {
                case 'GET':
                    if ($id === 'stats') {
                        $controller->getTriageStats();
                    } else {
                        throw new Exception('Route not found', 404);
                    }
                    break;
                case 'POST':
                    if ($id === 'analyze') {
                        $controller->analyzePatientCase();
                    } elseif ($id === 'batch-analyze') {
                        $controller->batchTriageAnalysis();
                    } elseif ($id === 'symptom-triage') {
                        $controller->getSymptomTriage();
                    } elseif ($id === 'referral-recommendations') {
                        $controller->generateReferralRecommendations();
                    } elseif ($id === 'quick-assessment') {
                        $controller->quickTriageAssessment();
                    } else {
                        throw new Exception('Route not found', 404);
                    }
                    break;
                case 'PUT':
                    if ($id === 'update-priority') {
                        $controller->updateTriagePriority();
                    } else {
                        throw new Exception('Route not found', 404);
                    }
                    break;
                default:
                    throw new Exception('Method not allowed', 405);
            }
            break;

        case 'ai-summaries':
            $controller = new AIAppointmentSummaryController();
            switch ($request_method) {
                case 'GET':
                    if ($id === 'stats') {
                        $controller->getSummaryStats();
                    } elseif ($id && is_numeric($id)) {
                        // Get specific appointment summary
                        $controller->getAppointmentSummary((int)$id);
                    } else {
                        throw new Exception('Route not found', 404);
                    }
                    break;
                case 'POST':
                    if ($id === 'generate') {
                        $controller->generateAppointmentSummary();
                    } elseif ($id === 'soap') {
                        $controller->generateSOAPNote();
                    } elseif ($id === 'billing') {
                        $controller->generateBillingSummary();
                    } elseif ($id === 'patient') {
                        $controller->generatePatientSummary();
                    } elseif ($id === 'batch') {
                        $controller->batchGenerateSummaries();
                    } elseif ($id === 'export') {
                        $controller->exportSummary();
                    } else {
                        throw new Exception('Route not found', 404);
                    }
                    break;
                case 'PUT':
                    if ($id === 'update') {
                        $controller->updateSummary();
                    } else {
                        throw new Exception('Route not found', 404);
                    }
                    break;
                default:
                    throw new Exception('Method not allowed', 405);
            }
            break;

        case 'ai-alerts':
            $controller = new AIAlertController();
            switch ($request_method) {
                case 'GET':
                    if ($id === 'dashboard') {
                        $controller->getAlertDashboard();
                    } elseif ($id === 'active') {
                        $controller->getActiveAlerts();
                    } elseif ($id === 'patient') {
                        $controller->getPatientAlerts();
                    } elseif ($id === 'analytics') {
                        $controller->getAlertAnalytics();
                    } else {
                        $controller->getActiveAlerts();
                    }
                    break;
                case 'POST':
                    if ($id === 'generate') {
                        $controller->generateIntelligentAlerts();
                    } elseif ($id === 'safety') {
                        $controller->generatePatientSafetyAlerts();
                    } elseif ($id === 'operational') {
                        $controller->generateOperationalAlerts();
                    } elseif ($id === 'quality') {
                        $controller->generateQualityAlerts();
                    } elseif ($id === 'create') {
                        $controller->createManualAlert();
                    } elseif ($id === 'acknowledge') {
                        $controller->acknowledgeAlert();
                    } elseif ($id === 'bulk-acknowledge') {
                        $controller->bulkAcknowledgeAlerts();
                    } else {
                        throw new Exception('Route not found', 404);
                    }
                    break;
                case 'PUT':
                    if ($id === 'update') {
                        $controller->updateAlert();
                    } else {
                        throw new Exception('Route not found', 404);
                    }
                    break;
                default:
                    throw new Exception('Method not allowed', 405);
            }
            break;

        case 'appointments':
            $controller = new AppointmentController();
            switch ($request_method) {
                case 'GET':
                    if ($id === 'available-slots') {
                        $controller->getAvailableSlots();
                    } elseif ($id && is_numeric($id)) {
                        $controller->getAppointment($id);
                    } else {
                        $controller->getAppointments();
                    }
                    break;
                case 'POST':
                    $controller->createAppointment();
                    break;
                case 'PUT':
                    if ($id && is_numeric($id)) {
                        $controller->updateAppointment($id);
                    } else {
                        throw new Exception('Invalid appointment ID', 400);
                    }
                    break;
                case 'DELETE':
                    if ($id && is_numeric($id)) {
                        $controller->deleteAppointment($id);
                    } else {
                        throw new Exception('Invalid appointment ID', 400);
                    }
                    break;
                default:
                    throw new Exception('Method not allowed', 405);
            }
            break;

        case 'patients':
            $controller = new PatientController();
            switch ($request_method) {
                case 'GET':
                    if ($id && is_numeric($id)) {
                        $controller->getPatient($id);
                    } else {
                        $controller->getPatients();
                    }
                    break;
                case 'POST':
                    $controller->createPatient();
                    break;
                case 'PUT':
                    if ($id && is_numeric($id)) {
                        $controller->updatePatient($id);
                    } else {
                        throw new Exception('Invalid patient ID', 400);
                    }
                    break;
                case 'DELETE':
                    if ($id && is_numeric($id)) {
                        $controller->deletePatient($id);
                    } else {
                        throw new Exception('Invalid patient ID', 400);
                    }
                    break;
                default:
                    throw new Exception('Method not allowed', 405);
            }
            break;

        case 'doctors':
            $controller = new DoctorController();
            switch ($request_method) {
                case 'GET':
                    if ($id && is_numeric($id)) {
                        $controller->getDoctor($id);
                    } else {
                        $controller->getDoctors();
                    }
                    break;
                case 'POST':
                    $controller->createDoctor();
                    break;
                case 'PUT':
                    if ($id && is_numeric($id)) {
                        $controller->updateDoctor($id);
                    } else {
                        throw new Exception('Invalid doctor ID', 400);
                    }
                    break;
                default:
                    throw new Exception('Method not allowed', 405);
            }
            break;

        case 'questionnaires':
            $controller = new QuestionnaireController();
            switch ($request_method) {
                case 'GET':
                    if ($id && is_numeric($id)) {
                        if ($action === 'responses') {
                            $controller->getQuestionnaireResponses($id);
                        } else {
                            $controller->getQuestionnaire($id);
                        }
                    } else {
                        $controller->getQuestionnaires();
                    }
                    break;
                case 'POST':
                    if ($id && is_numeric($id) && $action === 'responses') {
                        $controller->submitResponse($id);
                    } else {
                        $controller->createQuestionnaire();
                    }
                    break;
                case 'PUT':
                    if ($id && is_numeric($id)) {
                        $controller->updateQuestionnaire($id);
                    } else {
                        throw new Exception('Invalid questionnaire ID', 400);
                    }
                    break;
                default:
                    throw new Exception('Method not allowed', 405);
            }
            break;

        case 'reminders':
            $controller = new ReminderController();
            switch ($request_method) {
                case 'GET':
                    if ($id === 'stats') {
                        $controller->getReminderStats();
                    } elseif ($id === 'test-email') {
                        $controller->testEmailService();
                    } elseif ($id === 'test-sms') {
                        $controller->testSMSService();
                    } elseif ($id === 'validate-phone') {
                        $controller->validatePhoneNumber();
                    } elseif ($id === 'message-status') {
                        $controller->getMessageStatus();
                    } else {
                        $controller->getReminders();
                    }
                    break;
                case 'POST':
                    if ($id === 'send') {
                        $controller->sendManualReminder();
                    } elseif ($id === 'schedule') {
                        $controller->scheduleAppointmentReminders();
                    } elseif ($id === 'process') {
                        $controller->processReminders();
                    } elseif ($id === 'test-email') {
                        $controller->sendTestEmail();
                    } elseif ($id === 'test-sms') {
                        $controller->sendTestSMS();
                    } else {
                        throw new Exception('Route not found', 404);
                    }
                    break;
                case 'DELETE':
                    if ($id === 'cancel') {
                        $controller->cancelAppointmentReminders();
                    } else {
                        throw new Exception('Route not found', 404);
                    }
                    break;
                default:
                    throw new Exception('Method not allowed', 405);
            }
            break;

        case 'inventory':
            $controller = new InventoryController();
            switch ($request_method) {
                case 'GET':
                    if ($id && is_numeric($id)) {
                        $controller->getInventoryItem($id);
                    } else {
                        $controller->getInventory();
                    }
                    break;
                case 'POST':
                    $controller->createInventoryItem();
                    break;
                case 'PUT':
                    if ($id && is_numeric($id)) {
                        $controller->updateInventoryItem($id);
                    } else {
                        throw new Exception('Invalid inventory item ID', 400);
                    }
                    break;
                case 'DELETE':
                    if ($id && is_numeric($id)) {
                        $controller->deleteInventoryItem($id);
                    } else {
                        throw new Exception('Invalid inventory item ID', 400);
                    }
                    break;
                default:
                    throw new Exception('Method not allowed', 405);
            }
            break;

        case 'bills':
            $controller = new BillController();
            switch ($request_method) {
                case 'GET':
                    if ($id && is_numeric($id)) {
                        $controller->getBill($id);
                    } else {
                        $controller->getBills();
                    }
                    break;
                case 'POST':
                    $controller->createBill();
                    break;
                case 'PUT':
                    if ($id && is_numeric($id)) {
                        $controller->updateBill($id);
                    } else {
                        throw new Exception('Invalid bill ID', 400);
                    }
                    break;
                default:
                    throw new Exception('Method not allowed', 405);
            }
            break;

        case '':
        case 'health':
            // Health check endpoint
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Medical Clinic Management API is running',
                'timestamp' => date('Y-m-d H:i:s'),
                'version' => '1.0.0'
            ]);
            break;

        default:
            throw new Exception('Route not found', 404);
    }

} catch (Exception $e) {
    $statusCode = $e->getCode() ?: 500;
    http_response_code($statusCode);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
