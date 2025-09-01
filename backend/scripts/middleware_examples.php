<?php

namespace MedicalClinic\Examples;

use MedicalClinic\Middleware\MiddlewareManager;
use MedicalClinic\Controllers\PatientController;
use MedicalClinic\Controllers\UserController;
use MedicalClinic\Controllers\AuthController;

/**
 * Example of how to integrate middleware with existing routes
 * This demonstrates the new middleware-aware routing approach
 */

class MiddlewareIntegrationExample
{
    public static function demonstrateMiddlewareUsage()
    {
        echo "🔐 Middleware Integration Examples\n";
        echo "=================================\n\n";

        // Example 1: Public endpoint (no authentication)
        echo "1️⃣ Public Endpoint Example:\n";
        echo "// Health check - no authentication required\n";
        echo "GET /api/health\n";
        
        $publicMiddleware = MiddlewareManager::publicEndpoint();
        echo "Middleware count: " . $publicMiddleware->getMiddlewareCount() . " (no auth required)\n\n";

        // Example 2: Login endpoint
        echo "2️⃣ Login Endpoint Example:\n";
        echo "// User login - validation only, no auth required\n";
        echo "POST /api/auth/login\n";
        
        $loginMiddleware = MiddlewareManager::loginEndpoint();
        echo "Middleware count: " . $loginMiddleware->getMiddlewareCount() . " (validation only)\n\n";

        // Example 3: Doctor endpoint
        echo "3️⃣ Doctor Access Endpoint Example:\n";
        echo "// Get patients - requires doctor or admin role\n";
        echo "GET /api/patients\n";
        
        $doctorMiddleware = MiddlewareManager::doctorEndpoint();
        echo "Middleware count: " . $doctorMiddleware->getMiddlewareCount() . " (auth + role check)\n\n";

        // Example 4: Admin-only endpoint
        echo "4️⃣ Admin-Only Endpoint Example:\n";
        echo "// Create user - requires admin role only\n";
        echo "POST /api/users\n";
        
        $adminMiddleware = MiddlewareManager::userCreationEndpoint();
        echo "Middleware count: " . $adminMiddleware->getMiddlewareCount() . " (auth + admin + validation)\n\n";

        // Example 5: Complex endpoint with custom validation
        echo "5️⃣ Custom Validation Example:\n";
        echo "// Create appointment - custom validation rules\n";
        echo "POST /api/appointments\n";
        
        $appointmentMiddleware = MiddlewareManager::appointmentEndpoint();
        echo "Middleware count: " . $appointmentMiddleware->getMiddlewareCount() . " (auth + role + validation)\n\n";
    }

    /**
     * Example of how a route would look with middleware
     */
    public static function exampleRouteWithMiddleware()
    {
        // This is how you would handle a route with the new middleware system
        
        $request = [
            'method' => 'POST',
            'path' => '/api/patients',
            'params' => []
        ];

        $middleware = MiddlewareManager::create()
            ->auth()                    // Require authentication
            ->doctorAccess()           // Require doctor or admin role
            ->validatePatient();       // Validate patient data

        // Execute the middleware pipeline
        $middleware->handle($request, function($request) {
            // This is where your controller method would execute
            $controller = new PatientController($request);
            
            // The request now contains:
            // - $request['auth_user'] - The authenticated user
            // - $request['validated_input'] - Validated input data
            // - $request['user_role'] - User's role
            
            return $controller->store(); // Create patient
        });
    }

    /**
     * Example of migrating an existing controller method to use middleware
     */
    public static function migrationExample()
    {
        echo "📝 Controller Migration Example\n";
        echo "==============================\n\n";

        echo "BEFORE (manual auth/validation in controller):\n";
        echo "```php\n";
        echo "public function store() {\n";
        echo "    // Manual auth check\n";
        echo "    \$user = \$this->requireAuth();\n";
        echo "    \$this->requireRole(['admin', 'doctor']);\n";
        echo "    \n";
        echo "    // Manual validation\n";
        echo "    \$this->validateRequired(['first_name', 'last_name'], \$this->input);\n";
        echo "    if (!filter_var(\$this->input['email'], FILTER_VALIDATE_EMAIL)) {\n";
        echo "        \$this->error('Invalid email');\n";
        echo "    }\n";
        echo "    \n";
        echo "    // Business logic\n";
        echo "    \$patient = Patient::create(\$this->input);\n";
        echo "    \$this->success(\$patient->toArray());\n";
        echo "}\n";
        echo "```\n\n";

        echo "AFTER (using middleware):\n";
        echo "```php\n";
        echo "public function store() {\n";
        echo "    // Middleware already handled auth, roles, and validation\n";
        echo "    \$user = \$this->getUser();           // From AuthMiddleware\n";
        echo "    \$input = \$this->getInput();         // From ValidationMiddleware\n";
        echo "    \n";
        echo "    // Pure business logic\n";
        echo "    \$patient = Patient::create(\$input);\n";
        echo "    \$this->success(\$patient->toArray());\n";
        echo "}\n";
        echo "```\n\n";

        echo "✨ Benefits:\n";
        echo "- 70% less controller code\n";
        echo "- Automatic auth/validation\n";
        echo "- Consistent error handling\n";
        echo "- Reusable middleware components\n";
        echo "- Clean separation of concerns\n";
    }
}

// Run the examples if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    MiddlewareIntegrationExample::demonstrateMiddlewareUsage();
    echo "\n" . str_repeat("=", 50) . "\n\n";
    MiddlewareIntegrationExample::migrationExample();
}
