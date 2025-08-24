<?php

namespace MedicalClinic\Services;

use MedicalClinic\Utils\Database;

class ReminderService
{
    private Database $db;
    private EmailService $emailService;
    private SMSService $smsService;
    private array $config;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->emailService = new EmailService();
        $this->smsService = new SMSService();
        $this->config = require __DIR__ . '/../../config/app.php';
    }

    /**
     * Schedule reminders for a new appointment
     */
    public function scheduleAppointmentReminders(int $appointmentId): array
    {
        try {
            // Get appointment details
            $appointment = $this->getAppointmentDetails($appointmentId);
            
            if (!$appointment) {
                return [
                    'success' => false,
                    'message' => 'Appointment not found'
                ];
            }

            $reminders = [];
            $appointmentDateTime = strtotime($appointment['appointment_date'] . ' ' . $appointment['start_time']);

            // Schedule 24-hour email reminder
            $email24h = $this->scheduleReminder(
                $appointmentId,
                'email',
                $appointmentDateTime - (24 * 3600), // 24 hours before
                'appointment_reminder_24h'
            );
            if ($email24h) $reminders[] = $email24h;

            // Schedule 2-hour SMS reminder
            $sms2h = $this->scheduleReminder(
                $appointmentId,
                'sms',
                $appointmentDateTime - (2 * 3600), // 2 hours before
                'appointment_reminder_2h'
            );
            if ($sms2h) $reminders[] = $sms2h;

            // Optional: Schedule confirmation email immediately
            $confirmationEmail = $this->scheduleReminder(
                $appointmentId,
                'email',
                time(), // Send now
                'appointment_confirmation'
            );
            if ($confirmationEmail) $reminders[] = $confirmationEmail;

            return [
                'success' => true,
                'message' => 'Reminders scheduled successfully',
                'scheduled_count' => count($reminders),
                'reminders' => $reminders
            ];

        } catch (\Exception $e) {
            error_log("Failed to schedule reminders: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to schedule reminders: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Schedule a single reminder
     */
    private function scheduleReminder(int $appointmentId, string $type, int $scheduledTime, string $template): ?int
    {
        // Don't schedule reminders in the past
        if ($scheduledTime <= time()) {
            return null;
        }

        $reminderData = [
            'appointment_id' => $appointmentId,
            'reminder_type' => $type,
            'reminder_template' => $template,
            'scheduled_time' => date('Y-m-d H:i:s', $scheduledTime),
            'status' => 'pending',
            'attempts' => 0,
            'max_attempts' => 3,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $this->db->insert('reminders', $reminderData);
    }

    /**
     * Process pending reminders (call this from a cron job)
     */
    public function processPendingReminders(): array
    {
        $results = [
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'details' => []
        ];

        try {
            // Get pending reminders that are due
            $pendingReminders = $this->db->fetchAll(
                "SELECT r.*, a.appointment_date, a.start_time, a.appointment_type,
                        p.first_name, p.last_name, p.email, p.phone,
                        u.first_name as doctor_first_name, u.last_name as doctor_last_name,
                        d.specialty
                 FROM reminders r
                 JOIN appointments a ON r.appointment_id = a.id
                 JOIN patients p ON a.patient_id = p.id
                 JOIN doctors doc ON a.doctor_id = doc.id
                 JOIN users u ON doc.user_id = u.id
                 LEFT JOIN doctors d ON doc.id = d.id
                 WHERE r.status = 'pending' 
                 AND r.scheduled_time <= NOW()
                 AND r.attempts < r.max_attempts
                 ORDER BY r.scheduled_time ASC
                 LIMIT 50"
            );

            foreach ($pendingReminders as $reminder) {
                $results['processed']++;
                
                $appointmentData = $this->formatAppointmentData($reminder);
                $success = false;

                if ($reminder['reminder_type'] === 'email') {
                    $success = $this->sendEmailReminder($reminder, $appointmentData);
                } elseif ($reminder['reminder_type'] === 'sms') {
                    $success = $this->sendSMSReminder($reminder, $appointmentData);
                }

                if ($success) {
                    $results['successful']++;
                    $this->updateReminderStatus($reminder['id'], 'sent');
                } else {
                    $results['failed']++;
                    $this->incrementReminderAttempt($reminder['id']);
                }

                $results['details'][] = [
                    'id' => $reminder['id'],
                    'type' => $reminder['reminder_type'],
                    'patient' => $reminder['first_name'] . ' ' . $reminder['last_name'],
                    'success' => $success
                ];
            }

        } catch (\Exception $e) {
            error_log("Failed to process reminders: " . $e->getMessage());
        }

        return $results;
    }

    /**
     * Send a manual reminder
     */
    public function sendManualReminder(int $appointmentId, string $type, ?string $customMessage = null): array
    {
        try {
            $appointment = $this->getAppointmentDetails($appointmentId);
            
            if (!$appointment) {
                return [
                    'success' => false,
                    'message' => 'Appointment not found'
                ];
            }

            $appointmentData = $this->formatAppointmentData($appointment);
            
            if ($customMessage) {
                $appointmentData['custom_message'] = $customMessage;
            }

            $success = false;
            $message = '';

            if ($type === 'email') {
                $success = $this->emailService->sendAppointmentReminder($appointmentData);
                $message = $success ? 'Email sent successfully' : 'Email sending failed';
            } elseif ($type === 'sms') {
                $result = $this->smsService->sendAppointmentReminder($appointmentData);
                $success = $result['success'];
                $message = $result['message'];
            }

            // Log the manual reminder
            $this->logManualReminder($appointmentId, $type, $success, $message);

            return [
                'success' => $success,
                'message' => $message
            ];

        } catch (\Exception $e) {
            error_log("Failed to send manual reminder: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send reminder: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cancel scheduled reminders for an appointment
     */
    public function cancelAppointmentReminders(int $appointmentId): bool
    {
        try {
            $this->db->update(
                'reminders',
                [
                    'status' => 'cancelled',
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'appointment_id = :appointment_id AND status = :status',
                [
                    'appointment_id' => $appointmentId,
                    'status' => 'pending'
                ]
            );

            return true;
        } catch (\Exception $e) {
            error_log("Failed to cancel reminders: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get reminder statistics
     */
    public function getReminderStats(?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $endDate ?? date('Y-m-d');

        $stats = $this->db->fetch(
            "SELECT 
                COUNT(*) as total_reminders,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN reminder_type = 'email' THEN 1 ELSE 0 END) as emails,
                SUM(CASE WHEN reminder_type = 'sms' THEN 1 ELSE 0 END) as sms_messages,
                SUM(COALESCE(cost_cents, 0)) as total_cost_cents
             FROM reminders 
             WHERE DATE(created_at) BETWEEN :start_date AND :end_date",
            ['start_date' => $startDate, 'end_date' => $endDate]
        );

        return [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'total_reminders' => (int) $stats['total_reminders'],
            'sent' => (int) $stats['sent'],
            'failed' => (int) $stats['failed'],
            'pending' => (int) $stats['pending'],
            'success_rate' => $stats['total_reminders'] > 0 ? 
                round(($stats['sent'] / $stats['total_reminders']) * 100, 2) : 0,
            'email_count' => (int) $stats['emails'],
            'sms_count' => (int) $stats['sms_messages'],
            'total_cost_dollars' => round($stats['total_cost_cents'] / 100, 2)
        ];
    }

    // Private helper methods

    private function getAppointmentDetails(int $appointmentId): ?array
    {
        return $this->db->fetch(
            "SELECT a.*, 
                    p.first_name, p.last_name, p.email, p.phone,
                    u.first_name as doctor_first_name, u.last_name as doctor_last_name,
                    d.specialty
             FROM appointments a
             JOIN patients p ON a.patient_id = p.id
             JOIN doctors doc ON a.doctor_id = doc.id
             JOIN users u ON doc.user_id = u.id
             LEFT JOIN doctors d ON doc.id = d.id
             WHERE a.id = :id",
            ['id' => $appointmentId]
        );
    }

    private function formatAppointmentData(array $appointment): array
    {
        return [
            'appointment_id' => $appointment['id'],
            'patient_name' => $appointment['first_name'] . ' ' . $appointment['last_name'],
            'patient_email' => $appointment['email'],
            'patient_phone' => $appointment['phone'],
            'doctor_name' => $appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name'],
            'doctor_specialty' => $appointment['specialty'] ?? 'General Practice',
            'appointment_date' => $appointment['appointment_date'],
            'start_time' => $appointment['start_time'],
            'appointment_type' => $appointment['appointment_type'],
            'clinic_name' => $this->config['name'],
            'clinic_phone' => '+1-555-CLINIC',
            'clinic_email' => 'info@clinic.com',
            'clinic_address' => '123 Medical Center Dr, Healthcare City, HC 12345'
        ];
    }

    private function sendEmailReminder(array $reminder, array $appointmentData): bool
    {
        if (str_contains($reminder['reminder_template'], 'confirmation')) {
            return $this->emailService->sendAppointmentConfirmation($appointmentData);
        } else {
            return $this->emailService->sendAppointmentReminder($appointmentData);
        }
    }

    private function sendSMSReminder(array $reminder, array $appointmentData): bool
    {
        if (str_contains($reminder['reminder_template'], 'confirmation')) {
            $result = $this->smsService->sendAppointmentConfirmation($appointmentData);
        } else {
            $result = $this->smsService->sendAppointmentReminder($appointmentData);
        }

        // Update reminder with provider details
        if ($result['success']) {
            $this->db->update(
                'reminders',
                [
                    'provider_message_id' => $result['provider_message_id'],
                    'cost_cents' => $result['cost_cents'],
                    'delivery_status' => $result['delivery_status'] ?? 'sent'
                ],
                'id = :id',
                ['id' => $reminder['id']]
            );
        }

        return $result['success'];
    }

    private function updateReminderStatus(int $reminderId, string $status): void
    {
        $this->db->update(
            'reminders',
            [
                'status' => $status,
                'sent_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            'id = :id',
            ['id' => $reminderId]
        );
    }

    private function incrementReminderAttempt(int $reminderId): void
    {
        $this->db->query(
            "UPDATE reminders 
             SET attempts = attempts + 1,
                 status = CASE 
                     WHEN attempts + 1 >= max_attempts THEN 'failed'
                     ELSE 'pending'
                 END,
                 updated_at = NOW()
             WHERE id = :id",
            ['id' => $reminderId]
        );
    }

    private function logManualReminder(int $appointmentId, string $type, bool $success, string $message): void
    {
        $this->db->insert('reminders', [
            'appointment_id' => $appointmentId,
            'reminder_type' => $type,
            'reminder_template' => 'manual_reminder',
            'scheduled_time' => date('Y-m-d H:i:s'),
            'sent_at' => date('Y-m-d H:i:s'),
            'status' => $success ? 'sent' : 'failed',
            'attempts' => 1,
            'max_attempts' => 1,
            'message_content' => 'Manual reminder sent',
            'error_message' => $success ? null : $message,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function testServices(): array
    {
        return [
            'email' => $this->emailService->testConnection(),
            'sms' => $this->smsService->testConnection()
        ];
    }
}
