<?php

namespace MedicalClinic\Services;

use MedicalClinic\Utils\Database;

class EnhancedReminderService extends ReminderService
{
    private EmailService $emailService;
    private HIPAAComplianceService $hipaaService;
    private array $config;

    public function __construct()
    {
        parent::__construct();
        $this->emailService = new EmailService();
        $this->hipaaService = new HIPAAComplianceService();
        $this->config = require __DIR__ . '/../../config/app.php';
    }

    /**
     * Process pending reminders with enhanced logging and staff notifications
     */
    public function processPendingReminders(): array
    {
        $this->hipaaService->logHIPAAEvent('reminder_batch_started', [
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        $results = parent::processPendingReminders();
        
        // Log HIPAA compliance event
        $this->hipaaService->logHIPAAEvent('reminder_batch_completed', [
            'processed_count' => $results['processed'],
            'successful_count' => $results['successful'],
            'failed_count' => $results['failed']
        ]);

        // Notify staff if there are failures
        if ($results['failed'] > 0) {
            $this->notifyStaffOfFailures($results);
        }

        return $results;
    }

    /**
     * Send manual reminder with enhanced logging
     */
    public function sendManualReminder(int $appointmentId, string $type, ?string $customMessage = null, ?int $staffUserId = null): array
    {
        // Get appointment and patient info for logging
        $appointment = $this->getAppointmentDetails($appointmentId);
        
        // Log the manual reminder attempt
        $this->hipaaService->logHIPAAEvent('manual_reminder_initiated', [
            'appointment_id' => $appointmentId,
            'reminder_type' => $type,
            'custom_message_provided' => !empty($customMessage),
            'staff_initiated' => true
        ], $staffUserId, $appointment['patient_id'] ?? null, $appointmentId);

        $result = parent::sendManualReminder($appointmentId, $type, $customMessage);

        // Log the result
        $this->hipaaService->logHIPAAEvent('manual_reminder_completed', [
            'appointment_id' => $appointmentId,
            'reminder_type' => $type,
            'success' => $result['success'],
            'error_message' => $result['success'] ? null : $result['message']
        ], $staffUserId, $appointment['patient_id'] ?? null, $appointmentId);

        return $result;
    }

    /**
     * Enhanced reminder scheduling with compliance logging
     */
    public function scheduleAppointmentReminders(int $appointmentId): array
    {
        $appointment = $this->getAppointmentDetails($appointmentId);
        
        $result = parent::scheduleAppointmentReminders($appointmentId);

        // Log scheduling event
        $this->hipaaService->logHIPAAEvent('reminders_scheduled', [
            'appointment_id' => $appointmentId,
            'scheduled_count' => $result['scheduled_count'] ?? 0,
            'success' => $result['success']
        ], null, $appointment['patient_id'] ?? null, $appointmentId);

        return $result;
    }

    /**
     * Notify staff members about failed reminders
     */
    private function notifyStaffOfFailures(array $results): void
    {
        try {
            // Get admin and receptionist emails
            $staffEmails = $this->db->fetchAll(
                "SELECT u.email, u.first_name, u.last_name 
                 FROM users u 
                 WHERE u.role IN ('admin', 'receptionist') 
                 AND u.is_active = 1 
                 AND u.email IS NOT NULL"
            );

            if (empty($staffEmails)) {
                return; // No staff to notify
            }

            $failureDetails = array_filter($results['details'], function($detail) {
                return !$detail['success'];
            });

            $subject = "🚨 Reminder System Alert - {$results['failed']} Failed Reminders";
            $message = $this->generateStaffNotificationEmail($results, $failureDetails);

            $notificationsSent = 0;
            foreach ($staffEmails as $staff) {
                if ($this->emailService->sendCustomEmail(
                    $staff['email'],
                    $staff['first_name'] . ' ' . $staff['last_name'],
                    $subject,
                    $message
                )) {
                    $notificationsSent++;
                }
            }

            // Log notification sent
            $this->hipaaService->logHIPAAEvent('staff_failure_notification_sent', [
                'failed_count' => $results['failed'],
                'staff_notified' => $notificationsSent,
                'failure_types' => array_unique(array_column($failureDetails, 'type'))
            ]);

        } catch (\Exception $e) {
            error_log("Staff notification failed: " . $e->getMessage());
            
            $this->hipaaService->logHIPAAEvent('staff_notification_failed', [
                'error_message' => $e->getMessage(),
                'failed_reminder_count' => $results['failed']
            ]);
        }
    }

    /**
     * Generate professional staff notification email content
     */
    private function generateStaffNotificationEmail(array $results, array $failures): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $failureList = '';
        
        foreach ($failures as $failure) {
            $status = $failure['success'] ? '✅' : '❌';
            $failureList .= "<li>{$status} {$failure['type']} reminder for patient: {$failure['patient']}</li>";
        }

        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .alert { background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
                .summary { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .failed-list { margin-left: 20px; }
                .header { color: #721c24; font-size: 24px; margin-bottom: 15px; }
                .actions { background-color: #e7f3ff; padding: 15px; border-radius: 5px; margin-top: 20px; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='alert'>
                <div class='header'>🚨 Medical Clinic Reminder System Alert</div>
                <p><strong>Alert Time:</strong> {$timestamp}</p>
                <p><strong>Issue:</strong> {$results['failed']} reminder(s) failed to send</p>
            </div>
            
            <div class='summary'>
                <h3>📊 Processing Summary</h3>
                <ul>
                    <li><strong>Total Processed:</strong> {$results['processed']}</li>
                    <li><strong>✅ Successful:</strong> {$results['successful']}</li>
                    <li><strong>❌ Failed:</strong> {$results['failed']}</li>
                    <li><strong>Success Rate:</strong> " . (
                        $results['processed'] > 0 ? 
                        round(($results['successful'] / $results['processed']) * 100, 1) . '%' : 
                        'N/A'
                    ) . "</li>
                </ul>
            </div>
            
            <h3>❌ Failed Reminders:</h3>
            <ul class='failed-list'>
                {$failureList}
            </ul>
            
            <div class='actions'>
                <h3>🔧 Recommended Actions:</h3>
                <ul>
                    <li><strong>Immediate:</strong> Check system logs for detailed error messages</li>
                    <li><strong>Verify Services:</strong> Test email/SMS service configurations</li>
                    <li><strong>Patient Care:</strong> Consider calling affected patients directly</li>
                    <li><strong>Manual Reminders:</strong> Use the system to send manual reminders if needed</li>
                    <li><strong>Monitor:</strong> Watch for patterns in failures</li>
                </ul>
            </div>
            
            <div class='footer'>
                <p><strong>System Information:</strong></p>
                <p>• This alert was automatically generated by the Medical Clinic Management System</p>
                <p>• HIPAA compliance event logged with ID: " . uniqid() . "</p>
                <p>• For technical support, check system documentation or contact IT support</p>
                <hr>
                <p><small>Medical Clinic Management System - Automated Reminder Service</small></p>
            </div>
        </body>
        </html>";
    }

    /**
     * Get detailed reminder statistics with compliance data
     */
    public function getDetailedStats(?string $startDate = null, ?string $endDate = null): array
    {
        $stats = parent::getReminderStats($startDate, $endDate);
        
        // Add compliance tracking
        $startDate = $startDate ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $endDate ?? date('Y-m-d');

        $complianceStats = $this->hipaaService->getAuditStats($startDate, $endDate);

        $stats['compliance'] = [
            'audit_events' => $complianceStats['total_events'],
            'unique_users_tracked' => $complianceStats['unique_users'],
            'reminder_events_logged' => $complianceStats['reminder_events'],
            'patients_accessed' => $complianceStats['patients_accessed']
        ];

        return $stats;
    }

    /**
     * Get audit report for reminders
     */
    public function getAuditReport(string $startDate, string $endDate): array
    {
        return $this->hipaaService->getAuditReport($startDate, $endDate, 'reminder_%');
    }
}
