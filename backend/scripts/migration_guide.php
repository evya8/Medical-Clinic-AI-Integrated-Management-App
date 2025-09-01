#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MedicalClinic\Models\User;
use MedicalClinic\Models\Doctor;
use MedicalClinic\Models\Patient;
use MedicalClinic\Models\Appointment;
use MedicalClinic\Utils\Database;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

echo "🔄 Migrating Controllers to Use Models\n";
echo "====================================\n\n";

function showControllerExample(string $controllerName, string $oldCode, string $newCode) {
    echo "📁 {$controllerName}\n";
    echo str_repeat('-', strlen($controllerName) + 2) . "\n\n";
    
    echo "❌ OLD (Direct SQL):\n";
    echo "```php\n{$oldCode}\n```\n\n";
    
    echo "✅ NEW (Using Models):\n";
    echo "```php\n{$newCode}\n```\n\n";
    echo str_repeat('=', 60) . "\n\n";
}

// UserController Examples
showControllerExample(
    "UserController.php",
    'public function getUser(int $id): void
{
    $user = $this->db->fetch(
        "SELECT u.*, d.specialty, d.license_number 
         FROM users u 
         LEFT JOIN doctors d ON u.id = d.user_id 
         WHERE u.id = :id",
        [\'id\' => $id]
    );

    if (!$user) {
        $this->error(\'User not found\', 404);
    }

    unset($user[\'password_hash\']);
    $this->success($user);
}',
    'public function getUser(int $id): void
{
    try {
        $user = User::findOrFail($id);
        $displayInfo = $user->toArray(); // Automatically excludes password_hash
        
        // Add doctor info if user is a doctor
        if ($user->isDoctor() && $doctor = $user->doctor()) {
            $displayInfo = array_merge($displayInfo, $doctor->toArray());
        }
        
        $this->success($displayInfo);
    } catch (Exception $e) {
        $this->error($e->getMessage(), $e->getCode());
    }
}'
);

// PatientController Examples
showControllerExample(
    "PatientController.php",
    'public function getPatients(): void
{
    $search = $_GET[\'search\'] ?? null;
    $limit = $_GET[\'limit\'] ?? 50;
    $offset = $_GET[\'offset\'] ?? 0;

    $sql = "SELECT * FROM patients WHERE 1=1";
    $params = [];

    if ($search) {
        $sql .= " AND (first_name LIKE :search OR last_name LIKE :search)";
        $params[\'search\'] = "%{$search}%";
    }

    $sql .= " ORDER BY last_name, first_name LIMIT :limit OFFSET :offset";
    $params[\'limit\'] = (int) $limit;
    $params[\'offset\'] = (int) $offset;

    $patients = $this->db->fetchAll($sql, $params);
    $this->success($patients);
}',
    'public function getPatients(): void
{
    $search = $_GET[\'search\'] ?? null;
    $limit = (int) ($_GET[\'limit\'] ?? 50);
    $offset = (int) ($_GET[\'offset\'] ?? 0);

    if ($search) {
        $patients = Patient::search($search);
        
        // Apply pagination to search results
        $patients = array_slice($patients, $offset, $limit);
    } else {
        // For all patients, we\'d need to add pagination to the model
        $patients = Patient::all([\'last_name\' => \'ASC\', \'first_name\' => \'ASC\']);
        $patients = array_slice($patients, $offset, $limit);
    }

    // Convert to display format
    $patientsData = array_map(fn($p) => $p->getDisplayInfo(), $patients);
    $this->success($patientsData);
}'
);

// AppointmentController Examples
showControllerExample(
    "AppointmentController.php",
    'public function updateAppointment(int $id): void
{
    $appointment = $this->db->fetch(
        "SELECT * FROM appointments WHERE id = :id",
        [\'id\' => $id]
    );

    if (!$appointment) {
        $this->error(\'Appointment not found\', 404);
    }

    $updateData = [];
    $allowedFields = [\'status\', \'notes\', \'diagnosis\', \'treatment_notes\'];
    
    foreach ($allowedFields as $field) {
        if (isset($this->input[$field])) {
            $updateData[$field] = $this->input[$field];
        }
    }

    if (!empty($updateData)) {
        $updateData[\'updated_at\'] = date(\'Y-m-d H:i:s\');
        
        $setPairs = array_map(fn($key) => "{$key} = :{$key}", array_keys($updateData));
        $sql = "UPDATE appointments SET " . implode(\', \', $setPairs) . " WHERE id = :id";
        $updateData[\'id\'] = $id;

        $this->db->query($sql, $updateData);
    }

    $this->success(null, \'Appointment updated successfully\');
}',
    'public function updateAppointment(int $id): void
{
    try {
        $appointment = Appointment::findOrFail($id);
        
        // Update basic fields
        $allowedFields = [\'status\', \'notes\', \'diagnosis\', \'treatment_notes\', 
                          \'follow_up_required\', \'follow_up_date\'];
        
        foreach ($allowedFields as $field) {
            if (isset($this->input[$field])) {
                $appointment->$field = $this->input[$field];
            }
        }

        // Handle status-specific actions
        if (isset($this->input[\'status\'])) {
            switch ($this->input[\'status\']) {
                case \'completed\':
                    $medicalData = [];
                    if (isset($this->input[\'diagnosis\'])) {
                        $medicalData[\'diagnosis\'] = $this->input[\'diagnosis\'];
                    }
                    if (isset($this->input[\'treatment_notes\'])) {
                        $medicalData[\'treatment_notes\'] = $this->input[\'treatment_notes\'];
                    }
                    $appointment->complete($medicalData);
                    break;
                case \'cancelled\':
                    $reason = $this->input[\'cancellation_reason\'] ?? \'\';
                    $appointment->cancel($reason);
                    break;
                default:
                    $appointment->save();
            }
        } else {
            $appointment->save();
        }

        $this->success($appointment->getDisplayInfo(), \'Appointment updated successfully\');
    } catch (Exception $e) {
        $this->error($e->getMessage(), $e->getCode());
    }
}'
);

// DoctorController Examples
showControllerExample(
    "DoctorController.php",
    'public function getDoctors(): void
{
    $doctors = $this->db->fetchAll(
        "SELECT d.*, u.first_name, u.last_name, u.email 
         FROM doctors d
         JOIN users u ON d.user_id = u.id
         WHERE u.is_active = 1
         ORDER BY u.last_name, u.first_name"
    );

    $this->success($doctors);
}',
    'public function getDoctors(): void
{
    $doctors = Doctor::getAvailableDoctors();
    $doctorsData = array_map(fn($d) => $d->getDisplayInfo(), $doctors);
    $this->success($doctorsData);
}'
);

echo "📝 Migration Guidelines\n";
echo "=====================\n\n";

echo "1️⃣ **Replace Basic CRUD Operations**\n";
echo "   - find() instead of SELECT WHERE id = ?\n";
echo "   - all() instead of SELECT * FROM table\n";
echo "   - create() instead of INSERT INTO\n";
echo "   - save() instead of UPDATE\n";
echo "   - delete() instead of DELETE FROM\n\n";

echo "2️⃣ **Use Relationship Methods**\n";
echo "   - \$appointment->patient() instead of JOIN queries\n";
echo "   - \$user->doctor() for doctor profile data\n";
echo "   - \$patient->appointments() for patient history\n\n";

echo "3️⃣ **Use Business Logic Methods**\n";
echo "   - \$appointment->canBeModified() for business rules\n";
echo "   - \$user->isAdmin() for role checking\n";
echo "   - \$patient->getAge() for calculated fields\n\n";

echo "4️⃣ **Use Display Methods**\n";
echo "   - getDisplayInfo() for formatted data\n";
echo "   - toArray() for clean array output\n";
echo "   - Status and priority helper methods\n\n";

echo "5️⃣ **Error Handling**\n";
echo "   - Use findOrFail() for automatic 404 responses\n";
echo "   - Try-catch blocks for proper error handling\n";
echo "   - Model exceptions provide meaningful messages\n\n";

echo "🚀 **Benefits After Migration**\n";
echo "============================\n\n";
echo "✅ **Cleaner Code**: 50-70% less code in controllers\n";
echo "✅ **Better Testing**: Models can be unit tested independently\n";
echo "✅ **Maintainability**: Business logic centralized in models\n";
echo "✅ **Type Safety**: Proper return types and validation\n";
echo "✅ **Performance**: Optimized queries with relationship loading\n";
echo "✅ **Consistency**: Standardized data access patterns\n\n";

echo "🔧 **Next Steps**\n";
echo "===============\n\n";
echo "1. Test models: php scripts/test_models.php\n";
echo "2. Update one controller at a time\n";
echo "3. Test each controller after migration\n";
echo "4. Remove old SQL queries once models are working\n";
echo "5. Add custom business logic methods to models as needed\n\n";

echo "💡 **Pro Tips**\n";
echo "=============\n\n";
echo "- Start with simple controllers (User, Patient)\n";
echo "- Use getDisplayInfo() methods for API responses\n";
echo "- Leverage relationship methods to avoid complex JOINs\n";
echo "- Add validation methods to models for data integrity\n";
echo "- Use status management methods for workflow control\n\n";

echo "🏥 Ready to modernize your medical clinic backend! ✨\n";
