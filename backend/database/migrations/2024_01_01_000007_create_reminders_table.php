<?php

use MedicalClinic\Utils\Database;

class CreateRemindersTable
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS reminders (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            appointment_id INT UNSIGNED NOT NULL,
            reminder_type ENUM('email', 'sms', 'phone', 'push') NOT NULL,
            reminder_template VARCHAR(100),
            scheduled_time TIMESTAMP NOT NULL,
            sent_at TIMESTAMP NULL,
            status ENUM('pending', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
            attempts INT DEFAULT 0,
            max_attempts INT DEFAULT 3,
            message_content TEXT,
            recipient_contact VARCHAR(255) COMMENT 'Email or phone number',
            error_message TEXT,
            delivery_status VARCHAR(50) COMMENT 'Provider-specific delivery status',
            provider_message_id VARCHAR(100) COMMENT 'External provider message ID',
            cost_cents INT COMMENT 'Cost in cents for SMS/calls',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
            
            INDEX idx_appointment (appointment_id),
            INDEX idx_scheduled (scheduled_time),
            INDEX idx_status (status),
            INDEX idx_type (reminder_type),
            INDEX idx_pending (status, scheduled_time),
            INDEX idx_sent (sent_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->query($sql);
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS reminders";
        $this->db->query($sql);
    }
}
