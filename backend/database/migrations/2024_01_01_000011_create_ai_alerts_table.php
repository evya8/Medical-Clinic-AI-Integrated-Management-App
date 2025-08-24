<?php

use MedicalClinic\Utils\Database;

class CreateAiAlertsTable
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS ai_alerts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            type ENUM('patient_safety', 'operational', 'quality', 'revenue', 'inventory') NOT NULL,
            priority TINYINT UNSIGNED NOT NULL DEFAULT 3,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            action_required TEXT,
            timeline VARCHAR(100),
            patient_id INT UNSIGNED NULL,
            source ENUM('ai', 'system', 'manual') DEFAULT 'ai',
            status ENUM('active', 'acknowledged', 'dismissed', 'resolved') DEFAULT 'active',
            is_active BOOLEAN DEFAULT TRUE,
            ai_confidence TINYINT UNSIGNED DEFAULT 3,
            created_by INT UNSIGNED NULL,
            acknowledged_by INT UNSIGNED NULL,
            acknowledged_at TIMESTAMP NULL,
            resolved_by INT UNSIGNED NULL,
            resolved_at TIMESTAMP NULL,
            resolution_notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            updated_by INT UNSIGNED NULL,
            
            FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (acknowledged_by) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
            
            INDEX idx_type (type),
            INDEX idx_priority (priority),
            INDEX idx_status (status),
            INDEX idx_patient (patient_id),
            INDEX idx_active (is_active),
            INDEX idx_source (source),
            INDEX idx_created (created_at),
            INDEX idx_timeline (timeline),
            INDEX idx_priority_active (priority, is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->query($sql);
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS ai_alerts";
        $this->db->query($sql);
    }
}
