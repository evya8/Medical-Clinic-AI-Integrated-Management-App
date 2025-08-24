<?php

use MedicalClinic\Utils\Database;

class CreateQuestionnaireResponsesTable
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS questionnaire_responses (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            questionnaire_id INT UNSIGNED NOT NULL,
            patient_id INT UNSIGNED NOT NULL,
            appointment_id INT UNSIGNED NULL,
            responses JSON NOT NULL COMMENT 'Array of question-answer pairs',
            completion_time_minutes INT,
            risk_score DECIMAL(5,2) COMMENT 'Calculated risk score if applicable',
            flags JSON COMMENT 'Auto-detected flags or concerns',
            doctor_notes TEXT,
            reviewed_by INT UNSIGNED NULL,
            reviewed_at TIMESTAMP NULL,
            status ENUM('draft', 'submitted', 'reviewed', 'archived') DEFAULT 'draft',
            ip_address VARCHAR(45),
            user_agent TEXT,
            started_at TIMESTAMP NULL,
            completed_at TIMESTAMP NULL,
            submitted_by INT UNSIGNED NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (questionnaire_id) REFERENCES questionnaires(id) ON DELETE CASCADE,
            FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
            FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
            FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE SET NULL,
            
            INDEX idx_questionnaire (questionnaire_id),
            INDEX idx_patient (patient_id),
            INDEX idx_appointment (appointment_id),
            INDEX idx_status (status),
            INDEX idx_completed (completed_at),
            INDEX idx_reviewed (reviewed_by, reviewed_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->query($sql);
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS questionnaire_responses";
        $this->db->query($sql);
    }
}
