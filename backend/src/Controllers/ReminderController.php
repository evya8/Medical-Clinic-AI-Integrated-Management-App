<?php

namespace MedicalClinic\Controllers;

use MedicalClinic\Services\ReminderService;
use MedicalClinic\Services\EmailService;
use MedicalClinic\Services\SMSService;

class ReminderController extends BaseController
{
    private ReminderService $reminderService;
    private EmailService $emailService;
    private SMSService $smsService;

    public function __construct()
    {
        parent::__construct();
        $this->reminderService = new ReminderService();
        $this->emailService = new EmailService();
        $this->smsService = new SMSService();
    }

    public function getReminders(): void
    {
        $this->requireAuth();

        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $status = $_GET['status'] ?? null;
        $type = $_GET['type'] ?? null;

        $sql = "SELECT r.*, a.appointment_date, a.start_time,
                       p.first_name as patient_first_name, p.last_name as patient_last_name,
                       u.first_name as doctor_first_name, u.last_name as doctor_last_name
                FROM reminders r
                JOIN appointments a ON r.appointment_id = a.id
                JOIN patients p ON a.patient_id = p.id
                JOIN doctors d ON a.doctor_id = d.id
                JOIN users u ON d.user_id = u.id
                WHERE 1=1";

        $params = [];

        if ($startDate) {
            $sql .= " AND DATE(r.created_at) >= :start_date";
            $params['start_date'] = $startDate;
        }

        if ($endDate) {
            $sql .= " AND DATE(r.created_at) <= :end_date";
            $params['end_date'] = $endDate;
        }

        if ($status) {
            $sql .= " AND r.status = :status";
            $params['status'] = $status;
        }

        if ($type) {
            $sql .= " AND r.reminder_type = :type";
            $params['type'] = $type;
        }

        $sql .= " ORDER BY r.scheduled_time DESC LIMIT 100";

        $reminders = $this->db->fetchAll($sql, $params);

        $this->success($reminders, 'Reminders retrieved successfully');
    }

    public function getReminderStats(): void
    {
        $this->requireRole(['admin', 'receptionist']);

        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;

        $stats = $this->reminderService->getReminderStats($startDate, $endDate);

        $this->success($stats, 'Reminder statistics retrieved successfully');
    }

    public function processReminders(): void
    {
        $this->requireRole(['admin']);

        $results = $this->reminderService->processPendingReminders();

        $this->success($results, 'Reminder processing completed');
    }

    public function sendManualReminder(): void
    {
        $this->requireRole(['admin', 'receptionist', 'nurse']);

        $this->validateRequired(['appointment_id', 'type'], $this->input);

        $appointmentId = (int) $this->input['appointment_id'];
        $type = $this->sanitizeString($this->input['type']);
        $customMessage = isset($this->input['custom_message']) ? 
                        $this->sanitizeString($this->input['custom_message']) : null;

        if (!in_array($type, ['email', 'sms'])) {
            $this->error('Invalid reminder type. Must be email or sms', 400);
        }

        $result = $this->reminderService->sendManualReminder($appointmentId, $type, $customMessage);

        if ($result['success']) {
            $this->success($result, 'Manual reminder sent successfully');
        } else {
            $this->error($result['message'], 400);
        }
    }

    public function scheduleAppointmentReminders(): void
    {
        $this->requireRole(['admin', 'receptionist', 'nurse']);

        $this->validateRequired(['appointment_id'], $this->input);

        $appointmentId = (int) $this->input['appointment_id'];

        $result = $this->reminderService->scheduleAppointmentReminders($appointmentId);

        if ($result['success']) {
            $this->success($result, 'Appointment reminders scheduled successfully');
        } else {
            $this->error($result['message'], 400);
        }
    }

    public function cancelAppointmentReminders(): void
    {
        $this->requireRole(['admin', 'receptionist']);

        $this->validateRequired(['appointment_id'], $this->input);

        $appointmentId = (int) $this->input['appointment_id'];

        $result = $this->reminderService->cancelAppointmentReminders($appointmentId);

        if ($result) {
            $this->success(null, 'Appointment reminders cancelled successfully');
        } else {
            $this->error('Failed to cancel reminders', 400);
        }
    }

    public function testEmailService(): void
    {
        $this->requireRole(['admin']);

        $result = $this->emailService->testConnection();

        if ($result['success']) {
            $this->success($result, 'Email service test successful');
        } else {
            $this->error($result['message'], 500);
        }
    }

    public function testSMSService(): void
    {
        $this->requireRole(['admin']);

        $result = $this->smsService->testConnection();

        if ($result['success']) {
            $this->success($result, 'SMS service test successful');
        } else {
            $this->error($result['message'], 500);
        }
    }

    public function sendTestEmail(): void
    {
        $this->requireRole(['admin']);

        $this->validateRequired(['email', 'name'], $this->input);

        $email = $this->sanitizeString($this->input['email']);
        $name = $this->sanitizeString($this->input['name']);

        if (!$this->validateEmail($email)) {
            $this->error('Invalid email format', 400);
        }

        $subject = 'Test Email from Medical Clinic Management System';
        $message = '
        <h2>Email Service Test</h2>
        <p>Hello ' . $name . ',</p>
        <p>This is a test email to verify that the email service is working correctly.</p>
        <p>If you received this email, the system is properly configured!</p>
        <p>Time sent: ' . date('Y-m-d H:i:s') . '</p>
        <hr>
        <p><small>This is an automated test email from the Medical Clinic Management System.</small></p>
        ';

        $result = $this->emailService->sendCustomEmail($email, $name, $subject, $message);

        if ($result) {
            $this->success(null, 'Test email sent successfully');
        } else {
            $this->error('Failed to send test email', 500);
        }
    }

    public function sendTestSMS(): void
    {
        $this->requireRole(['admin']);

        $this->validateRequired(['phone'], $this->input);

        $phone = $this->sanitizeString($this->input['phone']);
        $customMessage = isset($this->input['message']) ? 
                        $this->sanitizeString($this->input['message']) : null;

        $message = $customMessage ?? '🏥 Medical Clinic Test SMS\n\nThis is a test message to verify SMS functionality.\n\nTime: ' . date('M j, Y g:i A');

        $result = $this->smsService->sendCustomSMS($phone, $message);

        if ($result['success']) {
            $this->success([
                'message_id' => $result['provider_message_id'],
                'cost_cents' => $result['cost_cents'],
                'status' => $result['delivery_status'] ?? 'sent'
            ], 'Test SMS sent successfully');
        } else {
            $this->error($result['message'], 500);
        }
    }

    public function validatePhoneNumber(): void
    {
        $this->requireAuth();

        $this->validateRequired(['phone'], $_GET);

        $phone = $this->sanitizeString($_GET['phone']);

        $result = $this->smsService->validatePhoneNumber($phone);

        if ($result['success']) {
            $this->success($result, 'Phone number validation successful');
        } else {
            $this->error($result['message'], 400);
        }
    }

    public function getMessageStatus(): void
    {
        $this->requireAuth();

        $this->validateRequired(['message_id'], $_GET);

        $messageId = $this->sanitizeString($_GET['message_id']);

        $result = $this->smsService->getMessageStatus($messageId);

        if ($result['success']) {
            $this->success($result, 'Message status retrieved successfully');
        } else {
            $this->error($result['message'], 400);
        }
    }
}
