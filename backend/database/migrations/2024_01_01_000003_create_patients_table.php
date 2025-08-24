<?php

use MedicalClinic\Utils\Database;

class CreatePatientsTable
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS patients (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            phone VARCHAR(20) NOT NULL,
            date_of_birth DATE NOT NULL,
            gender ENUM('male', 'female', 'other', 'prefer_not_to_say'),
            address TEXT,
            emergency_contact_name VARCHAR(100),
            emergency_contact_phone VARCHAR(20),
            medical_notes TEXT,
            allergies TEXT,
            blood_type VARCHAR(5),
            insurance_provider VARCHAR(100),
            insurance_policy_number VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_name (last_name, first_name),
            INDEX idx_email (email),
            INDEX idx_phone (phone),
            INDEX idx_dob (date_of_birth),
            FULLTEXT idx_search (first_name, last_name, email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->query($sql);
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS patients";
        $this->db->query($sql);
    }
}
