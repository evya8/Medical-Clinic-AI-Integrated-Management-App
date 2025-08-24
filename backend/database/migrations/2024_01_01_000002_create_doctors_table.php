<?php

use MedicalClinic\Utils\Database;

class CreateDoctorsTable
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS doctors (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            specialty VARCHAR(100) NOT NULL,
            license_number VARCHAR(50) UNIQUE NOT NULL,
            consultation_duration INT DEFAULT 30 COMMENT 'Duration in minutes',
            working_days JSON NOT NULL COMMENT 'Array of working days',
            working_hours JSON NOT NULL COMMENT 'Start and end times',
            bio TEXT,
            qualifications TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_specialty (specialty),
            INDEX idx_license (license_number),
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->query($sql);
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS doctors";
        $this->db->query($sql);
    }
}
