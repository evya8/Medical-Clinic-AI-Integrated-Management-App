<?php

/**
 * Controller Migration Examples
 * 
 * This script demonstrates how the migrated controllers work with
 * the middleware system and shows the improved patterns.
 */

require_once __DIR__ . '/../bootstrap.php';

use MedicalClinic\Controllers\PatientController;
use MedicalClinic\Controllers\UserController;
use MedicalClinic\Controllers\AppointmentController;
use MedicalClinic\Middleware\MiddlewareManager;
use MedicalClinic\Middleware\AuthMiddleware;
use MedicalClinic\Middleware\RoleMiddleware;
use MedicalClinic\Middleware\ValidationMiddleware;
use MedicalClinic\Models\User;

echo "📚 Controller Migration Usage Examples\n";
echo "=====================================\n\n";

/**
 * Example 1: How to use controllers with middleware (conceptual)
 */
echo "Example 1: Middleware-Aware Controller Usage\n";
echo "-------------------------------------------\n";

// This is how the controllers would be used with middleware in a real request
function demonstrateMiddlewareFlow() {
    echo "🔄 Simulated Request Flow:\n\n";
    
    // 1. Request comes in
    echo "1. 📨 Request: GET /api/patients\n";
    
    // 2. Middleware processes request
    echo "2. 🔐 AuthMiddleware: Validates JWT token\n";
    echo "3. 👮 RoleMiddleware: Checks user role permissions\n";
    echo "4. ✅ ValidationMiddleware: Validates input data\n";
    
    // 3. Middleware creates processed request
    $processedRequest = [
        'auth_user' => createMockUser(), // Would be real User from middleware
        'validated_input' => [
            'search' => 'John',
            'limit' => 10,
            'offset' => 0
        ],
        'params' => [],
        'user_role' => 'doctor'
    ];
    
    echo "5. 🎯 Controller instantiated with processed request\n";
    
    // 4. Controller processes request
    $controller = new PatientController($processedRequest);
    
    echo "6. 📊 Controller has access to:\n";
    echo "   - Authenticated user via getUser()\n";
    echo "   - Validated input via getInput()\n";  
    echo "   - User role via getUserRole()\n";
    echo "   - Clean response methods\n\n";
}

/**
 * Example 2: Before vs After Migration
 */
echo "Example 2: Before vs After Migration\n";
echo "-----------------------------------\n";

echo "❌ BEFORE (Manual Auth in Controller):\n";
echo "```php\n";
echo "public function getPatients(): void\n";
echo "{\n";
echo "    \$this->requireAuth();\n";
echo "    \$search = \$_GET['search'] ?? null;\n";
echo "    // ... business logic\n";
echo "    \$this->success(\$patients);\n";
echo "}\n";
echo "```\n\n";

echo "✅ AFTER (Middleware-Aware):\n";
echo "```php\n";
echo "public function getPatients(): void\n";
echo "{\n";
echo "    // Auth handled by middleware automatically\n";
echo "    \$input = \$this->getInput(); // Validated input\n";
echo "    \$search = \$input['search'] ?? null;\n";
echo "    // ... business logic\n";
echo "    \$this->paginated(\$patients, \$total, \$page, \$limit);\n";
echo "}\n";
echo "```\n\n";

/**
 * Example 3: New Features Added
 */
echo "Example 3: Enhanced Features\n";
echo "---------------------------\n";

echo "🆕 NEW FEATURES ADDED:\n\n";

echo "1. 📄 **Automatic Pagination**:\n";
echo "   - All list endpoints now support pagination\n";
echo "   - Consistent pagination format across all controllers\n";
echo "   - Total count and page info included\n\n";

echo "2. 🔍 **Enhanced Search & Filtering**:\n";
echo "   - Better search capabilities\n";
echo "   - Multiple filter options\n";
echo "   - Case-insensitive searching\n\n";

echo "3. ✨ **Additional Endpoints**:\n";
echo "   PatientController:\n";
echo "     - getPatientAppointments()\n";
echo "     - searchPatients()\n\n";
echo "   UserController:\n";  
echo "     - getProfile()\n";
echo "     - updateProfile()\n\n";
echo "   DoctorController:\n";
echo "     - getDoctorSchedule()\n";
echo "     - getSpecialties()\n";
echo "     - getDoctorStats()\n\n";
echo "   AppointmentController:\n";
echo "     - getMyAppointments()\n\n";

echo "4. 🛡️ **Better Error Handling**:\n";
echo "   - Try-catch blocks around database operations\n";
echo "   - More descriptive error messages\n";
echo "   - Proper HTTP status codes\n\n";

/**
 * Example 4: Response Format Improvements
 */
echo "Example 4: Improved Response Formats\n";
echo "-----------------------------------\n";

echo "📊 PAGINATED RESPONSE EXAMPLE:\n";
echo "```json\n";
echo "{\n";
echo "  \"success\": true,\n";
echo "  \"message\": \"Patients retrieved successfully\",\n";
echo "  \"timestamp\": \"2025-09-01 10:30:00\",\n";
echo "  \"data\": {\n";
echo "    \"items\": [...],\n";
echo "    \"pagination\": {\n";
echo "      \"current_page\": 1,\n";
echo "      \"per_page\": 10,\n";
echo "      \"total\": 150,\n";
echo "      \"total_pages\": 15,\n";
echo "      \"has_next\": true,\n";
echo "      \"has_prev\": false\n";
echo "    }\n";
echo "  }\n";
echo "}\n";
echo "```\n\n";

/**
 * Example 5: Security Improvements
 */
echo "Example 5: Security Enhancements\n";
echo "-------------------------------\n";

echo "🔒 SECURITY IMPROVEMENTS:\n\n";

echo "1. **Centralized Authentication**:\n";
echo "   - No controller can forget to check auth\n";
echo "   - Consistent auth logic across all endpoints\n";
echo "   - Middleware ensures proper JWT validation\n\n";

echo "2. **Role-Based Access Control**:\n";
echo "   - Role validation handled by middleware\n";
echo "   - Fine-grained permission control\n";
echo "   - Automatic role checking\n\n";

echo "3. **Input Validation**:\n";
echo "   - All input validated by middleware\n";
echo "   - Sanitization handled consistently\n";
echo "   - XSS and injection prevention\n\n";

/**
 * Example 6: Testing the Migration
 */
echo "Example 6: How to Test the Migration\n";
echo "----------------------------------\n";

echo "🧪 RUN TESTS:\n";
echo "```bash\n";
echo "# Test the controller migration\n";
echo "php scripts/test_controller_migration.php\n\n";
echo "# Test specific controller functionality\n";
echo "php scripts/test_middleware.php\n\n";
echo "# Test with actual HTTP requests\n";
echo "curl -H \"Authorization: Bearer \$JWT_TOKEN\" \\\n";
echo "     -H \"Content-Type: application/json\" \\\n";
echo "     http://localhost/api/patients\n";
echo "```\n\n";

demonstrateMiddlewareFlow();

echo "🎯 Key Benefits of the Migration:\n";
echo "=================================\n";
echo "✅ Reduced code duplication\n";
echo "✅ Improved security and consistency\n";
echo "✅ Better error handling\n";
echo "✅ Enhanced functionality (pagination, search, etc.)\n";
echo "✅ Cleaner, more maintainable code\n";
echo "✅ Standardized response formats\n";
echo "✅ Better separation of concerns\n\n";

echo "🔄 Next Steps:\n";
echo "==============\n";
echo "1. 🛣️  Integrate with Route Registry system\n";
echo "2. 🔗 Update API routes to use middleware bindings\n";
echo "3. 🧪 Run comprehensive integration tests\n";
echo "4. 📝 Update API documentation\n";
echo "5. 🚀 Deploy with feature flags for safe rollout\n\n";

echo "✨ Controller Migration Examples Complete!\n";

// Helper function to create a mock user for demonstration
function createMockUser(): User {
    // This would be a real User object from the middleware
    $userData = [
        'id' => 1,
        'username' => 'doctor1',
        'email' => 'doctor@clinic.com',
        'role' => 'doctor',
        'first_name' => 'John',
        'last_name' => 'Smith',
        'is_active' => true
    ];
    
    // Create and populate User model
    $user = new User();
    foreach ($userData as $key => $value) {
        $user->$key = $value;
    }
    
    return $user;
}
