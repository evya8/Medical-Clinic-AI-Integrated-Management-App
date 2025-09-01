<?php

use MedicalClinic\Utils\Database;

class CleanupUnusedTables
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function up(): void
    {
        // Drop unused tables that have no frontend interface
        $sql = "SET FOREIGN_KEY_CHECKS = 0";
        $this->db->query($sql);

        // Drop questionnaire-related tables (feature removed)
        $sql = "DROP TABLE IF EXISTS questionnaire_responses";
        $this->db->query($sql);
        
        $sql = "DROP TABLE IF EXISTS questionnaires";
        $this->db->query($sql);

        // Drop bills table (feature removed)
        $sql = "DROP TABLE IF EXISTS bills";
        $this->db->query($sql);

        // Drop inventory table (no frontend interface)
        $sql = "DROP TABLE IF EXISTS inventory";
        $this->db->query($sql);

        $sql = "SET FOREIGN_KEY_CHECKS = 1";
        $this->db->query($sql);
    }

    public function down(): void
    {
        // Recreate tables if rollback is needed
        
        // Recreate questionnaires table
        $sql = "CREATE TABLE IF NOT EXISTS questionnaires (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            questions JSON NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_by INT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->db->query($sql);

        // Recreate questionnaire_responses table
        $sql = "CREATE TABLE IF NOT EXISTS questionnaire_responses (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            questionnaire_id INT UNSIGNED NOT NULL,
            patient_id INT UNSIGNED NOT NULL,
            responses JSON NOT NULL,
            completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            submitted_by INT UNSIGNED NOT NULL,
            
            FOREIGN KEY (questionnaire_id) REFERENCES questionnaires(id) ON DELETE CASCADE,
            FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
            FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE RESTRICT,
            INDEX idx_questionnaire (questionnaire_id),
            INDEX idx_patient (patient_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->db->query($sql);

        // Recreate bills table
        $sql = "CREATE TABLE IF NOT EXISTS bills (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            patient_id INT UNSIGNED NOT NULL,
            appointment_id INT UNSIGNED NULL,
            amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending', 'paid', 'overdue', 'cancelled') DEFAULT 'pending',
            services JSON,
            due_date DATE,
            paid_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
            FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
            INDEX idx_patient (patient_id),
            INDEX idx_status (status),
            INDEX idx_due_date (due_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->db->query($sql);

        // Recreate inventory table
        $sql = "CREATE TABLE IF NOT EXISTS inventory (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            item_name VARCHAR(100) NOT NULL,
            item_type VARCHAR(50) NOT NULL,
            quantity INT UNSIGNED NOT NULL DEFAULT 0,
            unit_price DECIMAL(10,2),
            supplier VARCHAR(100),
            expiry_date DATE,
            minimum_stock_level INT UNSIGNED DEFAULT 10,
            last_updated_by INT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (last_updated_by) REFERENCES users(id) ON DELETE RESTRICT,
            INDEX idx_item_name (item_name),
            INDEX idx_item_type (item_type),
            INDEX idx_expiry (expiry_date),
            INDEX idx_low_stock (quantity, minimum_stock_level)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->db->query($sql);
    }
}
