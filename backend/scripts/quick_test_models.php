#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MedicalClinic\Models\User;
use MedicalClinic\Models\Doctor;
use MedicalClinic\Models\Patient;
use MedicalClinic\Models\Appointment;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

echo "🧪 Quick Model Test\n";
echo "==================\n\n";

function quickTest($description, $callback) {
    try {
        $result = $callback();
        if ($result) {
            echo "✅ $description\n";
            return true;
        } else {
            echo "❌ $description - Test returned false\n";
            return false;
        }
    } catch (Exception $e) {
        echo "❌ $description - Error: " . $e->getMessage() . "\n";
        return false;
    }
}

$passed = 0;
$total = 0;

echo "1️⃣ Testing Basic Model Operations\n";
echo "--------------------------------\n";

$total++; $passed += quickTest("User::count() works", function() {
    $count = User::count();
    return is_int($count) && $count >= 0;
});

$total++; $passed += quickTest("Patient::count() works", function() {
    $count = Patient::count();
    return is_int($count) && $count >= 0;
});

$total++; $passed += quickTest("Doctor::count() works", function() {
    $count = Doctor::count();
    return is_int($count) && $count >= 0;
});

$total++; $passed += quickTest("Appointment::count() works", function() {
    $count = Appointment::count();
    return is_int($count) && $count >= 0;
});

echo "\n2️⃣ Testing Model Creation\n";
echo "-----------------------\n";

$total++; $passed += quickTest("Can create User model instance", function() {
    $user = new User(['first_name' => 'Test', 'last_name' => 'User']);
    return $user->first_name === 'Test';
});

$total++; $passed += quickTest("Can create Patient model instance", function() {
    $patient = new Patient(['first_name' => 'Test', 'last_name' => 'Patient']);
    return $patient->getFullName() === 'Test Patient';
});

echo "\n3️⃣ Testing Model Methods\n";
echo "----------------------\n";

$total++; $passed += quickTest("User role methods work", function() {
    $user = new User(['role' => 'admin']);
    return $user->isAdmin() === true && $user->isDoctor() === false;
});

$total++; $passed += quickTest("Patient age calculation works", function() {
    $patient = new Patient(['date_of_birth' => '1990-01-01']);
    $age = $patient->getAge();
    return is_int($age) && $age > 30 && $age < 40;
});

echo "\n4️⃣ Testing Database Queries (if data exists)\n";
echo "-------------------------------------------\n";

$total++; $passed += quickTest("User::all() returns array", function() {
    $users = User::all();
    return is_array($users);
});

$total++; $passed += quickTest("Patient::search() works", function() {
    $results = Patient::search('test');
    return is_array($results);
});

echo "\n📊 Test Results\n";
echo "==============\n";
echo "✅ Passed: $passed tests\n";
echo "❌ Failed: " . ($total - $passed) . " tests\n";
echo "📈 Success Rate: " . round(($passed / $total) * 100, 1) . "%\n\n";

if ($passed === $total) {
    echo "🎉 All basic tests passed!\n";
    echo "🚀 Models are working correctly.\n";
    echo "💡 Try running the full test suite: php scripts/test_models.php\n";
} elseif ($passed >= ($total * 0.7)) {
    echo "✨ Most tests passed! Models are mostly working.\n";
    echo "🔧 Fix any remaining issues and run full tests.\n";
} else {
    echo "⚠️  Many tests failed. Check database connection and schema.\n";
    echo "💡 Make sure database is set up and migrations are run.\n";
}

echo "\n🏥 Quick model test complete!\n";
