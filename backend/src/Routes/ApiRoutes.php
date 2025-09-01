<?php

namespace MedicalClinic\Routes;

use MedicalClinic\Routes\RouteRegistry;
use MedicalClinic\Routes\RouteHelper;
use MedicalClinic\Middleware\MiddlewareManager;

/**
 * Modern API Routes Registration
 * This file demonstrates how to register routes using the new RouteRegistry system
 */

class ApiRoutes
{
    private RouteRegistry $router;
    private RouteHelper $helper;

    public function __construct(RouteRegistry $router)
    {
        $this->router = $router;
        $this->helper = new RouteHelper($router);
    }

    /**
     * Register all API routes
     */
    public function register(): void
    {
        // Add global middleware for all API routes
        $this->router->middleware(
            MiddlewareManager::create()
                // Add CORS middleware here if needed
                // Add rate limiting middleware here if needed
        );

        // Register route groups
        $this->registerApiV1Routes();
        $this->registerHealthRoutes();
        $this->registerWebhookRoutes();
    }

    /**
     * Register API v1 routes
     */
    private function registerApiV1Routes(): void
    {
        $this->helper->apiVersion('v1', function($router) {
            // Authentication routes
            $this->helper->authRoutes();

            // Admin routes
            $this->helper->adminRoutes()->group(function($router) {
                // User management
                $this->helper->resource('users', 'MedicalClinic\\Controllers\\UserController');
                
                // System stats and token management
                $router->get('/token-stats', ['MedicalClinic\\Controllers\\AuthControllerRefresh', 'tokenStats']);
                $router->post('/cleanup-tokens', ['MedicalClinic\\Controllers\\AuthControllerRefresh', 'cleanupTokens']);
                
                // System configuration
                $router->get('/config', ['MedicalClinic\\Controllers\\ConfigController', 'index']);
                $router->put('/config', ['MedicalClinic\\Controllers\\ConfigController', 'update']);
            });

            // Doctor routes
            $this->helper->doctorRoutes()->group(function($router) {
                // Patient management
                $this->helper->resource('patients', 'MedicalClinic\\Controllers\\PatientController');
                
                // Appointment management
                $this->helper->resourceWithActions('appointments', 'MedicalClinic\\Controllers\\AppointmentController', [
                    'search' => ['method' => 'GET', 'path' => '/search'],
                    'schedule' => ['method' => 'POST', 'path' => '/schedule'],
                    'reschedule' => ['method' => 'PUT', 'path' => '/{id:int}/reschedule'],
                    'cancel' => ['method' => 'DELETE', 'path' => '/{id:int}/cancel'],
                    'complete' => ['method' => 'POST', 'path' => '/{id:int}/complete'],
                    'summary' => ['method' => 'GET', 'path' => '/{id:int}/summary'],
                ]);
                
                // Doctor-specific routes
                $router->get('/schedule', ['MedicalClinic\\Controllers\\DoctorController', 'schedule']);
                $router->get('/availability', ['MedicalClinic\\Controllers\\DoctorController', 'availability']);
                $router->put('/availability', ['MedicalClinic\\Controllers\\DoctorController', 'updateAvailability']);
                
                // Reminders
                $this->helper->resource('reminders', 'MedicalClinic\\Controllers\\ReminderController');
                $router->post('/reminders/send-batch', ['MedicalClinic\\Controllers\\ReminderController', 'sendBatch']);
                
                // Inventory (if implemented)
                $this->helper->resource('inventory', 'MedicalClinic\\Controllers\\InventoryController');
            });

            // Authenticated user routes (any role)
            $this->router->group(['middleware' => MiddlewareManager::authEndpoint()], function($router) {
                // Dashboard
                $router->get('/dashboard', ['MedicalClinic\\Controllers\\DashboardController', 'index']);
                
                // Profile
                $router->get('/profile', ['MedicalClinic\\Controllers\\ProfileController', 'show']);
                $router->put('/profile', ['MedicalClinic\\Controllers\\ProfileController', 'update']);
                $router->post('/profile/avatar', ['MedicalClinic\\Controllers\\ProfileController', 'updateAvatar']);
                
                // Notifications
                $router->get('/notifications', ['MedicalClinic\\Controllers\\NotificationController', 'index']);
                $router->put('/notifications/{id:int}/read', ['MedicalClinic\\Controllers\\NotificationController', 'markAsRead']);
                $router->delete('/notifications/{id:int}', ['MedicalClinic\\Controllers\\NotificationController', 'destroy']);
            });

            // Public routes within API v1
            $this->helper->publicRoutes()->group(function($router) {
                $router->get('/status', function() {
                    return json_encode([
                        'status' => 'healthy',
                        'version' => '1.0.0',
                        'timestamp' => date('Y-m-d H:i:s'),
                        'environment' => $_ENV['APP_ENV'] ?? 'production'
                    ]);
                });
                
                // Public appointment booking (if enabled)
                $router->post('/book-appointment', ['MedicalClinic\\Controllers\\PublicController', 'bookAppointment']);
                $router->get('/available-slots', ['MedicalClinic\\Controllers\\PublicController', 'availableSlots']);
            });
        });
    }

    /**
     * Register health check routes
     */
    private function registerHealthRoutes(): void
    {
        $this->helper->publicRoutes('/health')->group(function($router) {
            $router->get('/', ['MedicalClinic\\Controllers\\HealthController', 'check']);
            $router->get('/database', ['MedicalClinic\\Controllers\\HealthController', 'database']);
            $router->get('/cache', ['MedicalClinic\\Controllers\\HealthController', 'cache']);
            $router->get('/storage', ['MedicalClinic\\Controllers\\HealthController', 'storage']);
        });
    }

    /**
     * Register webhook routes
     */
    private function registerWebhookRoutes(): void
    {
        $this->helper->publicRoutes('/webhooks')->group(function($router) {
            // Twilio SMS webhooks
            $router->post('/twilio/sms', ['MedicalClinic\\Controllers\\WebhookController', 'twilioSms']);
            $router->post('/twilio/status', ['MedicalClinic\\Controllers\\WebhookController', 'twilioStatus']);
            
            // Email webhooks
            $router->post('/email/bounce', ['MedicalClinic\\Controllers\\WebhookController', 'emailBounce']);
            $router->post('/email/delivery', ['MedicalClinic\\Controllers\\WebhookController', 'emailDelivery']);
            
            // Payment webhooks (if implemented)
            $router->post('/stripe', ['MedicalClinic\\Controllers\\WebhookController', 'stripe']);
        });
    }

    /**
     * Get route statistics
     */
    public function getStats(): array
    {
        return [
            'total_routes' => $this->router->countRoutes(),
            'routes_by_method' => $this->getRoutesByMethod(),
            'routes_summary' => $this->router->getRoutesSummary()
        ];
    }

    /**
     * Get routes grouped by HTTP method
     */
    private function getRoutesByMethod(): array
    {
        $routes = $this->router->getRoutes();
        $byMethod = [];
        
        foreach ($routes as $route) {
            $method = $route['method'];
            if (!isset($byMethod[$method])) {
                $byMethod[$method] = 0;
            }
            $byMethod[$method]++;
        }
        
        return $byMethod;
    }
}

/**
 * Legacy Route Migration Helper
 * Helps migrate from old route format to new RouteRegistry
 */
class RouteMigrator
{
    private RouteRegistry $router;
    
    public function __construct(RouteRegistry $router)
    {
        $this->router = $router;
    }
    
    /**
     * Convert legacy route array to RouteRegistry registration
     */
    public function migrateLegacyRoutes(array $legacyRoutes): void
    {
        foreach ($legacyRoutes as $pattern => $config) {
            [$method, $path] = explode(' ', $pattern, 2);
            
            $controller = $config['controller'] ?? null;
            $action = $config['action'] ?? 'index';
            $middleware = $this->convertLegacyMiddleware($config['middleware'] ?? []);
            
            if ($controller) {
                $handler = [$controller, $action];
                
                switch (strtoupper($method)) {
                    case 'GET':
                        $this->router->get($path, $handler, $middleware);
                        break;
                    case 'POST':
                        $this->router->post($path, $handler, $middleware);
                        break;
                    case 'PUT':
                        $this->router->put($path, $handler, $middleware);
                        break;
                    case 'DELETE':
                        $this->router->delete($path, $handler, $middleware);
                        break;
                }
            }
        }
    }
    
    /**
     * Convert legacy middleware configuration to MiddlewareManager
     */
    private function convertLegacyMiddleware(array $legacyMiddleware): MiddlewareManager
    {
        $manager = MiddlewareManager::create();
        
        foreach ($legacyMiddleware as $middleware) {
            if ($middleware === 'auth') {
                $manager->auth();
            } elseif ($middleware === 'admin') {
                $manager->adminOnly();
            } elseif ($middleware === 'doctor') {
                $manager->doctorAccess();
            } elseif (is_array($middleware) && isset($middleware['validate'])) {
                $manager->validate($middleware['validate']);
            }
        }
        
        return $manager;
    }
}
