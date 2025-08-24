<?php

/**
 * AI System Validation Script
 * Comprehensive test to verify all AI components are properly configured
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use MedicalClinic\Utils\Database;
use MedicalClinic\Services\GroqAIService;
use MedicalClinic\Services\AIStaffDashboardService;
use MedicalClinic\Services\AITriageService;
use MedicalClinic\Services\AIAppointmentSummaryService;
use MedicalClinic\Services\AIAlertService;

class SystemValidator
{
    private array $errors = [];
    private array $warnings = [];
    private Database $db;

    public function __construct()
    {
        // Load environment variables
        try {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
            $dotenv->load();
        } catch (Exception $e) {
            $this->errors[] = "Failed to load .env file: " . $e->getMessage();
        }
    }

    public function runValidation(): bool
    {
        echo "🔍 AI System Validation\n";
        echo "======================\n\n";

        // Test each component
        $this->validateEnvironment();
        $this->validateDatabase();
        $this->validateServices();
        $this->validateControllers();
        $this->validateRoutes();
        $this->validateMigrations();

        // Report results
        $this->reportResults();

        return empty($this->errors);
    }

    private function validateEnvironment(): void
    {
        echo "🔧 Validating Environment Configuration...\n";

        $requiredEnvVars = [
            'DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 
            'JWT_SECRET', 'GROQ_API_KEY'
        ];

        foreach ($requiredEnvVars as $var) {
            if (empty($_ENV[$var])) {
                $this->errors[] = "Missing required environment variable: {$var}";
            }
        }

        if (strlen($_ENV['JWT_SECRET'] ?? '') < 32) {
            $this->warnings[] = "JWT_SECRET should be at least 32 characters for security";
        }

        if (empty($_ENV['GROQ_API_KEY'])) {
            $this->errors[] = "GROQ_API_KEY is required for AI features";
        }

        echo "   Environment check completed\n\n";
    }

    private function validateDatabase(): void
    {
        echo "💾 Validating Database Connection...\n";

        try {
            $this->db = Database::getInstance();
            $this->db->query("SELECT 1");
            echo "   ✅ Database connection successful\n";

            // Check if tables exist
            $requiredTables = [
                'users', 'patients', 'doctors', 'appointments', 
                'appointment_summaries', 'ai_alerts'
            ];

            foreach ($requiredTables as $table) {
                try {
                    if (!$this->db->tableExists($table)) {
                        $this->warnings[] = "Table '{$table}' doesn't exist yet (may need migration)";
                    } else {
                        echo "   ✅ Table '{$table}' exists\n";
                    }
                } catch (Exception $e) {
                    $this->warnings[] = "Could not check table '{$table}': " . $e->getMessage();
                }
            }

        } catch (Exception $e) {
            $this->errors[] = "Database connection failed: " . $e->getMessage();
        }

        echo "\n";
    }

    private function validateServices(): void
    {
        echo "🧠 Validating AI Services...\n";

        // Test GroqAIService
        try {
            $groqService = new GroqAIService();
            $testResult = $groqService->testConnection();
            
            if ($testResult['success']) {
                echo "   ✅ GroqAIService working\n";
            } else {
                $this->errors[] = "GroqAIService test failed: " . $testResult['message'];
            }
        } catch (Exception $e) {
            $this->errors[] = "GroqAIService error: " . $e->getMessage();
        }

        // Test other services (instantiation only since they need data)
        $services = [
            'AIStaffDashboardService' => AIStaffDashboardService::class,
            'AITriageService' => AITriageService::class,
            'AIAppointmentSummaryService' => AIAppointmentSummaryService::class,
            'AIAlertService' => AIAlertService::class
        ];

        foreach ($services as $name => $class) {
            try {
                new $class();
                echo "   ✅ {$name} instantiated successfully\n";
            } catch (Exception $e) {
                $this->errors[] = "{$name} instantiation failed: " . $e->getMessage();
            }
        }

        echo "\n";
    }

    private function validateControllers(): void
    {
        echo "🎮 Validating Controllers...\n";

        $controllers = [
            'AIDashboardController' => 'MedicalClinic\\Controllers\\AIDashboardController',
            'AITriageController' => 'MedicalClinic\\Controllers\\AITriageController',
            'AIAppointmentSummaryController' => 'MedicalClinic\\Controllers\\AIAppointmentSummaryController',
            'AIAlertController' => 'MedicalClinic\\Controllers\\AIAlertController'
        ];

        foreach ($controllers as $name => $class) {
            if (class_exists($class)) {
                echo "   ✅ {$name} class exists\n";
                
                // Check if class can be instantiated
                try {
                    new $class();
                    echo "   ✅ {$name} instantiated successfully\n";
                } catch (Exception $e) {
                    $this->warnings[] = "{$name} instantiation warning: " . $e->getMessage();
                }
            } else {
                $this->errors[] = "Controller class not found: {$class}";
            }
        }

        echo "\n";
    }

    private function validateRoutes(): void
    {
        echo "🛣️ Validating Route Definitions...\n";

        $routeFile = __DIR__ . '/../routes/api.php';
        
        if (!file_exists($routeFile)) {
            $this->errors[] = "Route file not found: {$routeFile}";
            return;
        }

        $routeContent = file_get_contents($routeFile);
        
        $expectedRoutes = [
            'ai-dashboard', 'ai-triage', 'ai-summaries', 'ai-alerts'
        ];

        foreach ($expectedRoutes as $route) {
            if (strpos($routeContent, "case '{$route}':") !== false) {
                echo "   ✅ Route '{$route}' defined\n";
            } else {
                $this->errors[] = "Route not found: {$route}";
            }
        }

        echo "\n";
    }

    private function validateMigrations(): void
    {
        echo "🏗️ Validating Migration Files...\n";

        $migrationDir = __DIR__ . '/../database/migrations/';
        
        if (!is_dir($migrationDir)) {
            $this->errors[] = "Migration directory not found: {$migrationDir}";
            return;
        }

        $requiredMigrations = [
            '2024_01_01_000010_create_appointment_summaries_table.php',
            '2024_01_01_000011_create_ai_alerts_table.php'
        ];

        foreach ($requiredMigrations as $migration) {
            $migrationPath = $migrationDir . $migration;
            if (file_exists($migrationPath)) {
                echo "   ✅ Migration exists: {$migration}\n";
                
                // Validate migration class
                $className = $this->getMigrationClassName($migration);
                require_once $migrationPath;
                
                if (class_exists($className)) {
                    echo "   ✅ Migration class '{$className}' found\n";
                } else {
                    $this->errors[] = "Migration class not found: {$className} in {$migration}";
                }
            } else {
                $this->errors[] = "Migration file not found: {$migration}";
            }
        }

        echo "\n";
    }

    private function getMigrationClassName(string $filename): string
    {
        $parts = explode('_', pathinfo($filename, PATHINFO_FILENAME));
        $classParts = array_slice($parts, 4);
        return implode('', array_map('ucfirst', $classParts));
    }

    private function reportResults(): void
    {
        echo "📊 Validation Results\n";
        echo "===================\n\n";

        if (empty($this->errors) && empty($this->warnings)) {
            echo "🎉 ALL SYSTEMS OPERATIONAL!\n";
            echo "✅ No errors or warnings found.\n";
            echo "✅ All AI features are properly configured.\n";
            echo "✅ System ready for production use.\n\n";
        } else {
            if (!empty($this->errors)) {
                echo "❌ ERRORS FOUND (" . count($this->errors) . "):\n";
                foreach ($this->errors as $i => $error) {
                    echo "   " . ($i + 1) . ". {$error}\n";
                }
                echo "\n";
            }

            if (!empty($this->warnings)) {
                echo "⚠️  WARNINGS (" . count($this->warnings) . "):\n";
                foreach ($this->warnings as $i => $warning) {
                    echo "   " . ($i + 1) . ". {$warning}\n";
                }
                echo "\n";
            }

            if (!empty($this->errors)) {
                echo "🔧 REQUIRED ACTIONS:\n";
                echo "   1. Fix all errors listed above\n";
                echo "   2. Run database migrations if needed\n";
                echo "   3. Ensure all environment variables are set\n";
                echo "   4. Re-run this validation script\n\n";
            }
        }

        echo "📈 AI System Status:\n";
        echo "   - Services: " . (empty($this->errors) ? "✅ Ready" : "❌ Issues Found") . "\n";
        echo "   - Database: " . ($this->db ? "✅ Connected" : "❌ Connection Failed") . "\n";
        echo "   - Configuration: " . (empty($_ENV['GROQ_API_KEY']) ? "❌ Incomplete" : "✅ Complete") . "\n";
        echo "   - Overall Status: " . (empty($this->errors) ? "🟢 OPERATIONAL" : "🔴 NEEDS ATTENTION") . "\n\n";
    }
}

// Run validation
echo "🏥 Medical Clinic AI System Validation\n";
echo "This script will check all AI components...\n\n";

try {
    $validator = new SystemValidator();
    $success = $validator->runValidation();
    
    exit($success ? 0 : 1);
    
} catch (Exception $e) {
    echo "❌ Validation failed with error: " . $e->getMessage() . "\n";
    exit(1);
}
