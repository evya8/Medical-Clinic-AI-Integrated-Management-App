<?php

use MedicalClinic\Utils\Database;

class CreateAppointmentsTable
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS appointments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            patient_id INT UNSIGNED NOT NULL,
            doctor_id INT UNSIGNED NOT NULL,
            appointment_date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            status ENUM('scheduled', 'confirmed', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
            appointment_type VARCHAR(100) NOT NULL,
            priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
            notes TEXT,
            diagnosis TEXT,
            treatment_notes TEXT,
            follow_up_required BOOLEAN DEFAULT FALSE,
            follow_up_date DATE NULL,
            created_by INT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
            FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
            
            INDEX idx_patient (patient_id),
            INDEX idx_doctor (doctor_id),
            INDEX idx_date (appointment_date),
            INDEX idx_datetime (appointment_date, start_time),
            INDEX idx_status (status),
            INDEX idx_priority (priority),
            
            UNIQUE KEY unique_doctor_time (doctor_id, appointment_date, start_time)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->query($sql);
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS appointments";
        $this->db->query($sql);
    }
}
