<?php

use MedicalClinic\Utils\Database;

class CreateBillsTable
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS bills (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            bill_number VARCHAR(50) UNIQUE NOT NULL,
            patient_id INT UNSIGNED NOT NULL,
            appointment_id INT UNSIGNED NULL,
            bill_date DATE NOT NULL,
            due_date DATE NOT NULL,
            subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            balance_due DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            status ENUM('draft', 'sent', 'pending', 'paid', 'partial', 'overdue', 'cancelled') DEFAULT 'draft',
            payment_method ENUM('cash', 'card', 'check', 'bank_transfer', 'insurance', 'other') NULL,
            payment_reference VARCHAR(100),
            services JSON NOT NULL COMMENT 'Array of services with codes and prices',
            insurance_claim_number VARCHAR(100),
            insurance_paid_amount DECIMAL(10,2) DEFAULT 0.00,
            notes TEXT,
            late_fee DECIMAL(10,2) DEFAULT 0.00,
            currency VARCHAR(3) DEFAULT 'USD',
            paid_at TIMESTAMP NULL,
            sent_at TIMESTAMP NULL,
            created_by INT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
            FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
            
            INDEX idx_patient (patient_id),
            INDEX idx_appointment (appointment_id),
            INDEX idx_bill_number (bill_number),
            INDEX idx_status (status),
            INDEX idx_bill_date (bill_date),
            INDEX idx_due_date (due_date),
            INDEX idx_balance (balance_due),
            INDEX idx_overdue (due_date, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->query($sql);
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS bills";
        $this->db->query($sql);
    }
}
