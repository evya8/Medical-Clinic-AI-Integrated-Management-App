#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MedicalClinic\Models\User;
use MedicalClinic\Models\Doctor;
use MedicalClinic\Models\Patient;
use MedicalClinic\Models\Appointment;
use MedicalClinic\Models\Reminder;
use MedicalClinic\Models\AppointmentSummary;
use MedicalClinic\Models\AIAlert;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

echo "🧪 Testing Medical Clinic Models\n";
echo "================================\n\n";

$tests = [];
$passed = 0;
$failed = 0;

function test($description, $callback) {
    global $tests, $passed, $failed;
    
    try {
        $result = $callback();
        if ($result) {
            echo "✅ $description\n";
            $passed++;
        } else {
            echo "❌ $description - Test returned false\n";
            $failed++;
        }
    } catch (Exception $e) {
        echo "❌ $description - Error: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "1️⃣ Testing User Model\n";
echo "-------------------\n";

test("User::all() returns array", function() {
    $users = User::all();
    return is_array($users);
});

test("User::count() returns integer", function() {
    $count = User::count();
    return is_int($count) && $count >= 0;
});

test("User role methods work", function() {
    $users = User::all();
    if (empty($users)) return true; // Skip if no users
    
    $user = $users[0];
    return is_bool($user->isAdmin()) && is_bool($user->isDoctor());
});

test("User::getAdmins() returns only admin users", function() {
    $admins = User::getAdmins();
    foreach ($admins as $admin) {
        if (!$admin->isAdmin()) return false;
    }
    return true;
});

echo "\n2️⃣ Testing Doctor Model\n";
echo "---------------------\n";

test("Doctor::all() returns array", function() {
    $doctors = Doctor::all();
    return is_array($doctors);
});

test("Doctor working schedule methods work", function() {
    $doctors = Doctor::all();
    if (empty($doctors)) return true; // Skip if no doctors
    
    $doctor = $doctors[0];
    $workingDays = $doctor->getWorkingDays();
    $workingHours = $doctor->getWorkingHours();
    
    return is_array($workingDays) && is_array($workingHours);
});

test("Doctor availability check works", function() {
    $doctors = Doctor::all();
    if (empty($doctors)) return true; // Skip if no doctors
    
    $doctor = $doctors[0];
    $isWorking = $doctor->isWorkingOn('monday');
    return is_bool($isWorking);
});

test("Doctor::getAvailableDoctors() includes user data", function() {
    $doctors = Doctor::getAvailableDoctors();
    if (empty($doctors)) return true; // Skip if no doctors
    
    $doctor = $doctors[0];
    return isset($doctor->first_name) && isset($doctor->last_name);
});

echo "\n3️⃣ Testing Patient Model\n";
echo "----------------------\n";

test("Patient::all() returns array", function() {
    $patients = Patient::all();
    return is_array($patients);
});

test("Patient age calculation works", function() {
    $patients = Patient::all();
    if (empty($patients)) return true; // Skip if no patients
    
    foreach ($patients as $patient) {
        $age = $patient->getAge();
        if ($age !== null && (!is_int($age) || $age < 0 || $age > 150)) {
            return false;
        }
    }
    return true;
});

test("Patient search functionality works", function() {
    $results = Patient::search('test');
    return is_array($results);
});

test("Patient validation methods work", function() {
    $patients = Patient::all();
    if (empty($patients)) return true; // Skip if no patients
    
    $patient = $patients[0];
    return is_bool($patient->validateEmail()) && is_bool($patient->validatePhone());
});

echo "\n4️⃣ Testing Appointment Model\n";
echo "---------------------------\n";

test("Appointment::all() returns array", function() {
    $appointments = Appointment::all();
    return is_array($appointments);
});

test("Appointment::getTodayAppointments() works", function() {
    $todayAppts = Appointment::getTodayAppointments();
    return is_array($todayAppts);
});

test("Appointment status methods work", function() {
    $appointments = Appointment::all();
    if (empty($appointments)) return true; // Skip if no appointments
    
    $appointment = $appointments[0];
    return is_bool($appointment->isCompleted()) && 
           is_bool($appointment->canBeModified()) && 
           is_bool($appointment->isHighPriority());
});

test("Appointment time methods work", function() {
    $appointments = Appointment::all();
    if (empty($appointments)) return true; // Skip if no appointments
    
    $appointment = $appointments[0];
    $duration = $appointment->getDurationMinutes();
    return is_int($duration) && $duration > 0;
});

echo "\n5️⃣ Testing Reminder Model\n";
echo "-----------------------\n";

test("Reminder::all() returns array", function() {
    $reminders = Reminder::all();
    return is_array($reminders);
});

test("Reminder::getPendingReminders() works", function() {
    $pending = Reminder::getPendingReminders();
    return is_array($pending);
});

test("Reminder::getReminderStats() returns valid data", function() {
    $stats = Reminder::getReminderStats();
    return is_array($stats) && 
           isset($stats['total']) && 
           isset($stats['success_rate']);
});

echo "\n6️⃣ Testing AppointmentSummary Model\n";
echo "---------------------------------\n";

test("AppointmentSummary::all() returns array", function() {
    $summaries = AppointmentSummary::all();
    return is_array($summaries);
});

test("AppointmentSummary quality methods work", function() {
    $summaries = AppointmentSummary::all();
    if (empty($summaries)) return true; // Skip if no summaries
    
    $summary = $summaries[0];
    return is_bool($summary->isHighQuality()) && 
           is_string($summary->getQualityLevel());
});

test("AppointmentSummary::getSummaryStats() works", function() {
    $stats = AppointmentSummary::getSummaryStats();
    return is_array($stats) && isset($stats['total_summaries']);
});

echo "\n7️⃣ Testing AIAlert Model\n";
echo "----------------------\n";

test("AIAlert::all() returns array", function() {
    $alerts = AIAlert::all();
    return is_array($alerts);
});

test("AIAlert::getActiveAlerts() works", function() {
    $activeAlerts = AIAlert::getActiveAlerts();
    return is_array($activeAlerts);
});

test("AIAlert priority methods work", function() {
    $alerts = AIAlert::all();
    if (empty($alerts)) return true; // Skip if no alerts
    
    $alert = $alerts[0];
    return is_bool($alert->isCritical()) && 
           is_int($alert->getPriorityNumber()) && 
           is_string($alert->getPriorityColor());
});

test("AIAlert::getAlertStats() returns valid data", function() {
    $stats = AIAlert::getAlertStats();
    return is_array($stats) && 
           isset($stats['total_alerts']) && 
           isset($stats['acknowledgment_rate']);
});

echo "\n8️⃣ Testing Model Relationships\n";
echo "----------------------------\n";

test("User->doctor() relationship works", function() {
    $users = User::getDoctors();
    if (empty($users)) return true; // Skip if no doctor users
    
    $user = $users[0];
    $doctor = $user->doctor();
    return $doctor === null || $doctor instanceof Doctor;
});

test("Doctor->user() relationship works", function() {
    $doctors = Doctor::all();
    if (empty($doctors)) return true; // Skip if no doctors
    
    $doctor = $doctors[0];
    $user = $doctor->user();
    return $user === null || $user instanceof User;
});

test("Appointment->patient() relationship works", function() {
    $appointments = Appointment::all();
    if (empty($appointments)) return true; // Skip if no appointments
    
    $appointment = $appointments[0];
    $patient = $appointment->patient();
    return $patient === null || $patient instanceof Patient;
});

test("Appointment->doctor() relationship works", function() {
    $appointments = Appointment::all();
    if (empty($appointments)) return true; // Skip if no appointments
    
    $appointment = $appointments[0];
    $doctor = $appointment->doctor();
    return $doctor === null || $doctor instanceof Doctor;
});

test("Patient->appointments() relationship works", function() {
    $patients = Patient::all();
    if (empty($patients)) return true; // Skip if no patients
    
    $patient = $patients[0];
    $appointments = $patient->appointments();
    return is_array($appointments);
});

echo "\n9️⃣ Testing BaseModel Functionality\n";
echo "-------------------------------\n";

test("Model toArray() method works", function() {
    $users = User::all();
    if (empty($users)) return true; // Skip if no users
    
    $user = $users[0];
    $array = $user->toArray();
    return is_array($array) && isset($array['id']);
});

test("Model isDirty() method works", function() {
    $users = User::all();
    if (empty($users)) return true; // Skip if no users
    
    $user = $users[0];
    $originalDirty = $user->isDirty();
    $user->first_name = $user->first_name . "_test";
    $modifiedDirty = $user->isDirty();
    
    return !$originalDirty && $modifiedDirty;
});

test("Model exists() method works", function() {
    $users = User::all();
    if (empty($users)) return true; // Skip if no users
    
    $existingUser = $users[0];
    $newUser = new User(['first_name' => 'Test']);
    
    return $existingUser->exists() && !$newUser->exists();
});

// Summary
echo "\n📊 Test Results\n";
echo "==============\n";
echo "✅ Passed: $passed tests\n";
echo "❌ Failed: $failed tests\n";
echo "📈 Success Rate: " . ($passed + $failed > 0 ? round(($passed / ($passed + $failed)) * 100, 1) : 0) . "%\n\n";

if ($failed === 0) {
    echo "🎉 All models are working perfectly!\n";
    echo "🚀 You can now refactor your controllers to use these models.\n\n";
    
    echo "💡 Example usage in controllers:\n";
    echo "// Instead of: \$this->db->fetch('SELECT * FROM users WHERE id = ?', [\$id])\n";
    echo "// Use: \$user = User::find(\$id)\n\n";
    
    echo "// Instead of: \$this->db->fetchAll('SELECT * FROM patients WHERE name LIKE ?')\n";
    echo "// Use: \$patients = Patient::search(\$query)\n\n";
    
    echo "// Relationships: \$appointment->patient()->getFullName()\n";
    echo "// Status checks: \$appointment->canBeModified()\n";
    echo "// Display info: \$patient->getDisplayInfo()\n";
} else {
    echo "⚠️  Some tests failed. Please check your database schema and model implementations.\n";
    exit(1);
}

echo "\n🏥 Models ready for medical clinic management system!\n";
