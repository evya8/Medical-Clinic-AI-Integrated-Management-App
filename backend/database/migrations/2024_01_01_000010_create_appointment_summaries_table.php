<?php

use MedicalClinic\Utils\Database;

class CreateAppointmentSummariesTable
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS appointment_summaries (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            appointment_id INT UNSIGNED NOT NULL,
            summary_type ENUM('standard', 'soap', 'billing', 'patient') DEFAULT 'standard',
            structured_summary JSON,
            raw_ai_response TEXT,
            ai_model_used VARCHAR(50),
            tokens_used INT UNSIGNED DEFAULT 0,
            response_time_ms DECIMAL(10,2) DEFAULT 0,
            word_count INT UNSIGNED DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
            INDEX idx_appointment (appointment_id),
            INDEX idx_type (summary_type),
            INDEX idx_active (is_active),
            INDEX idx_model (ai_model_used),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->query($sql);
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS appointment_summaries";
        $this->db->query($sql);
    }
}
