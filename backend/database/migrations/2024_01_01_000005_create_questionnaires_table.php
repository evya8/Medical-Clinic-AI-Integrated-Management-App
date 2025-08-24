<?php

use MedicalClinic\Utils\Database;

class CreateQuestionnairesTable
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS questionnaires (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            questions JSON NOT NULL COMMENT 'Array of questions with branching logic',
            category VARCHAR(100),
            estimated_duration INT COMMENT 'Estimated completion time in minutes',
            is_active BOOLEAN DEFAULT TRUE,
            requires_doctor_review BOOLEAN DEFAULT FALSE,
            auto_assign_conditions JSON COMMENT 'Conditions for auto-assignment to patients',
            version VARCHAR(10) DEFAULT '1.0',
            created_by INT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
            
            INDEX idx_category (category),
            INDEX idx_active (is_active),
            INDEX idx_title (title),
            INDEX idx_created_by (created_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->query($sql);
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS questionnaires";
        $this->db->query($sql);
    }
}
