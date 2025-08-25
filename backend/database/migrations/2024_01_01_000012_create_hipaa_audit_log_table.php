<?php

use MedicalClinic\Utils\Database;

class CreateHIPAAAuditLogTable
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS hipaa_audit_log (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            event_type VARCHAR(100) NOT NULL,
            event_details JSON,
            user_id INT UNSIGNED NULL,
            patient_id INT UNSIGNED NULL,
            appointment_id INT UNSIGNED NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL,
            FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
            
            INDEX idx_event_type (event_type),
            INDEX idx_created_at (created_at),
            INDEX idx_user_id (user_id),
            INDEX idx_patient_id (patient_id),
            INDEX idx_appointment_id (appointment_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->query($sql);
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS hipaa_audit_log";
        $this->db->query($sql);
    }
}
