<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;
use MedicalClinic\Utils\Database;

try {
    // Load environment variables
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();

    $db = Database::getInstance();

    echo "Starting database seeding...\n";

    // Seed users
    echo "Seeding users...\n";
    seedUsers($db);

    // Seed doctors
    echo "Seeding doctors...\n";
    seedDoctors($db);

    // Seed patients
    echo "Seeding patients...\n";
    seedPatients($db);

    // Seed questionnaires
    echo "Seeding questionnaires...\n";
    seedQuestionnaires($db);

    // Seed inventory
    echo "Seeding inventory...\n";
    seedInventory($db);

    echo "All seeding completed successfully!\n";

} catch (Exception $e) {
    echo "Seeding error: " . $e->getMessage() . "\n";
    exit(1);
}

function seedUsers(Database $db): void
{
    // Check if users already exist
    $existingUsers = $db->fetch("SELECT COUNT(*) as count FROM users");
    if ($existingUsers['count'] > 0) {
        echo "Users already exist, skipping user seeding.\n";
        return;
    }

    $users = [
        [
            'username' => 'admin',
            'email' => 'admin@clinic.com',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            'role' => 'admin',
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'phone' => '+1-555-0100',
            'is_active' => 1
        ],
        [
            'username' => 'dr.smith',
            'email' => 'smith@clinic.com',
            'password_hash' => password_hash('doctor123', PASSWORD_DEFAULT),
            'role' => 'doctor',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'phone' => '+1-555-0101',
            'is_active' => 1
        ],
        [
            'username' => 'dr.johnson',
            'email' => 'johnson@clinic.com',
            'password_hash' => password_hash('doctor123', PASSWORD_DEFAULT),
            'role' => 'doctor',
            'first_name' => 'Sarah',
            'last_name' => 'Johnson',
            'phone' => '+1-555-0102',
            'is_active' => 1
        ],
        [
            'username' => 'nurse.williams',
            'email' => 'williams@clinic.com',
            'password_hash' => password_hash('nurse123', PASSWORD_DEFAULT),
            'role' => 'nurse',
            'first_name' => 'Emily',
            'last_name' => 'Williams',
            'phone' => '+1-555-0103',
            'is_active' => 1
        ],
        [
            'username' => 'reception',
            'email' => 'reception@clinic.com',
            'password_hash' => password_hash('reception123', PASSWORD_DEFAULT),
            'role' => 'receptionist',
            'first_name' => 'Mary',
            'last_name' => 'Davis',
            'phone' => '+1-555-0104',
            'is_active' => 1
        ]
    ];

    foreach ($users as $user) {
        $db->insert('users', $user);
    }

    echo "Created " . count($users) . " users.\n";
}

function seedDoctors(Database $db): void
{
    // Check if doctors already exist
    $existingDoctors = $db->fetch("SELECT COUNT(*) as count FROM doctors");
    if ($existingDoctors['count'] > 0) {
        echo "Doctors already exist, skipping doctor seeding.\n";
        return;
    }

    // Get doctor user IDs
    $doctorUsers = $db->fetchAll("SELECT id, first_name, last_name FROM users WHERE role = 'doctor'");
    
    if (empty($doctorUsers)) {
        echo "No doctor users found, skipping doctor seeding.\n";
        return;
    }

    $doctors = [
        [
            'user_id' => $doctorUsers[0]['id'],
            'specialty' => 'Cardiology',
            'license_number' => 'MD001234',
            'consultation_duration' => 45,
            'working_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
            'working_hours' => json_encode(['start' => '09:00', 'end' => '17:00']),
            'bio' => 'Dr. Smith is a board-certified cardiologist with over 15 years of experience.',
            'qualifications' => 'MD from Harvard Medical School, Fellowship in Cardiology from Johns Hopkins'
        ],
        [
            'user_id' => $doctorUsers[1]['id'],
            'specialty' => 'Pediatrics',
            'license_number' => 'MD005678',
            'consultation_duration' => 30,
            'working_days' => json_encode(['monday', 'wednesday', 'thursday', 'friday']),
            'working_hours' => json_encode(['start' => '08:00', 'end' => '16:00']),
            'bio' => 'Dr. Johnson specializes in pediatric care and adolescent medicine.',
            'qualifications' => 'MD from Stanford Medical School, Pediatric Residency at Children\'s Hospital'
        ]
    ];

    foreach ($doctors as $doctor) {
        $db->insert('doctors', $doctor);
    }

    echo "Created " . count($doctors) . " doctors.\n";
}

function seedPatients(Database $db): void
{
    // Check if patients already exist
    $existingPatients = $db->fetch("SELECT COUNT(*) as count FROM patients");
    if ($existingPatients['count'] > 0) {
        echo "Patients already exist, skipping patient seeding.\n";
        return;
    }

    $patients = [
        [
            'first_name' => 'Alice',
            'last_name' => 'Brown',
            'email' => 'alice.brown@email.com',
            'phone' => '+1-555-1001',
            'date_of_birth' => '1985-03-15',
            'gender' => 'female',
            'address' => '123 Main St, Anytown, ST 12345',
            'emergency_contact_name' => 'Bob Brown',
            'emergency_contact_phone' => '+1-555-1002',
            'blood_type' => 'A+',
            'allergies' => 'Penicillin'
        ],
        [
            'first_name' => 'Robert',
            'last_name' => 'Wilson',
            'email' => 'robert.wilson@email.com',
            'phone' => '+1-555-1003',
            'date_of_birth' => '1978-07-22',
            'gender' => 'male',
            'address' => '456 Oak Ave, Somewhere, ST 67890',
            'emergency_contact_name' => 'Lisa Wilson',
            'emergency_contact_phone' => '+1-555-1004',
            'blood_type' => 'O-',
            'medical_notes' => 'History of hypertension'
        ],
        [
            'first_name' => 'Emma',
            'last_name' => 'Taylor',
            'email' => 'emma.taylor@email.com',
            'phone' => '+1-555-1005',
            'date_of_birth' => '2010-11-08',
            'gender' => 'female',
            'address' => '789 Pine St, Elsewhere, ST 54321',
            'emergency_contact_name' => 'Michael Taylor',
            'emergency_contact_phone' => '+1-555-1006',
            'blood_type' => 'B+',
            'allergies' => 'Shellfish, Nuts'
        ]
    ];

    foreach ($patients as $patient) {
        $db->insert('patients', $patient);
    }

    echo "Created " . count($patients) . " patients.\n";
}

function seedQuestionnaires(Database $db): void
{
    // Check if questionnaires already exist
    $existingQuestionnaires = $db->fetch("SELECT COUNT(*) as count FROM questionnaires");
    if ($existingQuestionnaires['count'] > 0) {
        echo "Questionnaires already exist, skipping questionnaire seeding.\n";
        return;
    }

    // Get admin user ID
    $admin = $db->fetch("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    if (!$admin) {
        echo "No admin user found, skipping questionnaire seeding.\n";
        return;
    }

    $questionnaires = [
        [
            'title' => 'General Health Assessment',
            'description' => 'Basic health screening questionnaire for new patients',
            'questions' => json_encode([
                [
                    'id' => 1,
                    'type' => 'text',
                    'question' => 'What is your primary concern today?',
                    'required' => true
                ],
                [
                    'id' => 2,
                    'type' => 'multiple_choice',
                    'question' => 'Do you smoke?',
                    'options' => ['Yes', 'No', 'Occasionally'],
                    'required' => true,
                    'next_question_logic' => [
                        'Yes' => 3,
                        'Occasionally' => 3,
                        'No' => 4
                    ]
                ],
                [
                    'id' => 3,
                    'type' => 'number',
                    'question' => 'How many cigarettes per day?',
                    'required' => true
                ],
                [
                    'id' => 4,
                    'type' => 'multiple_choice',
                    'question' => 'Do you exercise regularly?',
                    'options' => ['Daily', 'Weekly', 'Monthly', 'Rarely', 'Never'],
                    'required' => true
                ]
            ]),
            'category' => 'General',
            'estimated_duration' => 5,
            'is_active' => 1,
            'requires_doctor_review' => 0,
            'created_by' => $admin['id']
        ],
        [
            'title' => 'Pre-Surgical Assessment',
            'description' => 'Required questionnaire for patients scheduled for surgical procedures',
            'questions' => json_encode([
                [
                    'id' => 1,
                    'type' => 'multiple_choice',
                    'question' => 'Have you had surgery before?',
                    'options' => ['Yes', 'No'],
                    'required' => true,
                    'next_question_logic' => [
                        'Yes' => 2,
                        'No' => 3
                    ]
                ],
                [
                    'id' => 2,
                    'type' => 'text',
                    'question' => 'Please describe your previous surgeries',
                    'required' => true
                ],
                [
                    'id' => 3,
                    'type' => 'multiple_choice',
                    'question' => 'Do you take any medications?',
                    'options' => ['Yes', 'No'],
                    'required' => true
                ]
            ]),
            'category' => 'Surgical',
            'estimated_duration' => 10,
            'is_active' => 1,
            'requires_doctor_review' => 1,
            'created_by' => $admin['id']
        ]
    ];

    foreach ($questionnaires as $questionnaire) {
        $db->insert('questionnaires', $questionnaire);
    }

    echo "Created " . count($questionnaires) . " questionnaires.\n";
}

function seedInventory(Database $db): void
{
    // Check if inventory already exists
    $existingInventory = $db->fetch("SELECT COUNT(*) as count FROM inventory");
    if ($existingInventory['count'] > 0) {
        echo "Inventory already exists, skipping inventory seeding.\n";
        return;
    }

    // Get admin user ID
    $admin = $db->fetch("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    if (!$admin) {
        echo "No admin user found, skipping inventory seeding.\n";
        return;
    }

    $inventory = [
        [
            'item_name' => 'Paracetamol 500mg',
            'item_code' => 'MED001',
            'item_type' => 'medication',
            'description' => 'Pain reliever and fever reducer',
            'quantity' => 500,
            'unit' => 'tablets',
            'unit_price' => 0.25,
            'supplier' => 'PharmaCorp',
            'batch_number' => 'PC2024001',
            'expiry_date' => '2025-12-31',
            'minimum_stock_level' => 100,
            'prescription_required' => 0,
            'controlled_substance' => 0,
            'last_updated_by' => $admin['id']
        ],
        [
            'item_name' => 'Digital Thermometer',
            'item_code' => 'EQP001',
            'item_type' => 'equipment',
            'description' => 'Non-contact infrared thermometer',
            'quantity' => 10,
            'unit' => 'pieces',
            'unit_price' => 45.99,
            'supplier' => 'MedEquip Solutions',
            'minimum_stock_level' => 2,
            'storage_location' => 'Equipment Room A',
            'manufacturer' => 'ThermoTech',
            'prescription_required' => 0,
            'controlled_substance' => 0,
            'last_updated_by' => $admin['id']
        ],
        [
            'item_name' => 'Disposable Gloves (Nitrile)',
            'item_code' => 'SUP001',
            'item_type' => 'supply',
            'description' => 'Powder-free nitrile examination gloves',
            'quantity' => 2000,
            'unit' => 'pieces',
            'unit_price' => 0.15,
            'supplier' => 'SafeGuard Medical',
            'minimum_stock_level' => 500,
            'storage_location' => 'Supply Room B',
            'prescription_required' => 0,
            'controlled_substance' => 0,
            'last_updated_by' => $admin['id']
        ]
    ];

    foreach ($inventory as $item) {
        $db->insert('inventory', $item);
    }

    echo "Created " . count($inventory) . " inventory items.\n";
}
