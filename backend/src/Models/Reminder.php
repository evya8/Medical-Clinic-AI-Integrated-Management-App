<?php

namespace MedicalClinic\Models;

class Reminder extends BaseModel
{
    protected static string $table = 'reminders';

    // Relationships
    public function appointment(): ?Appointment
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    // Static methods for reminder management
    public static function getPendingReminders(): array
    {
        static::initializeDatabase();
        $results = static::$db->fetchAll(
            "SELECT * FROM reminders 
             WHERE scheduled_time <= NOW() 
             AND sent_at IS NULL 
             AND status = 'pending'
             ORDER BY scheduled_time",
            []
        );
        return array_map(fn($row) => new static($row, true), $results);
    }

    public static function getRemindersForAppointment(int $appointmentId): array
    {
        return static::where('appointment_id', $appointmentId);
    }

    public static function createAppointmentReminders(Appointment $appointment): array
    {
        $reminders = [];
        $appointmentDateTime = $appointment->getDateTime();
        
        // 24 hour reminder
        $reminder24h = new static([
            'appointment_id' => $appointment->id,
            'reminder_type' => 'email',
            'scheduled_time' => (clone $appointmentDateTime)->modify('-24 hours')->format('Y-m-d H:i:s'),
            'status' => 'pending',
            'message_content' => static::generateReminderMessage($appointment, '24 hours')
        ]);
        $reminder24h->save();
        $reminders[] = $reminder24h;

        // 2 hour reminder (SMS if phone available)
        $patient = $appointment->patient();
        $reminderType = ($patient && $patient->phone) ? 'sms' : 'email';
        
        $reminder2h = new static([
            'appointment_id' => $appointment->id,
            'reminder_type' => $reminderType,
            'scheduled_time' => (clone $appointmentDateTime)->modify('-2 hours')->format('Y-m-d H:i:s'),
            'status' => 'pending',
            'message_content' => static::generateReminderMessage($appointment, '2 hours')
        ]);
        $reminder2h->save();
        $reminders[] = $reminder2h;

        return $reminders;
    }

    private static function generateReminderMessage(Appointment $appointment, string $timeframe): string
    {
        $patient = $appointment->patient();
        $doctor = $appointment->doctor();
        
        $patientName = $patient ? $patient->getFullName() : 'Patient';
        $doctorName = $doctor ? $doctor->getDisplayName() : 'Doctor';
        $dateTime = $appointment->getFormattedDateTime();
        
        return "Reminder: {$patientName}, you have an appointment with {$doctorName} on {$dateTime}. " .
               "Appointment type: {$appointment->appointment_type}. " .
               "Please arrive 15 minutes early.";
    }

    // Status management
    public function markAsSent(): bool
    {
        $this->sent_at = date('Y-m-d H:i:s');
        $this->status = 'sent';
        return $this->save();
    }

    public function markAsFailed(string $errorMessage): bool
    {
        $this->status = 'failed';
        $this->message_content = $this->message_content . "\n\nError: " . $errorMessage;
        return $this->save();
    }

    public function markAsSkipped(string $reason): bool
    {
        $this->status = 'skipped';
        $this->message_content = $this->message_content . "\n\nSkipped: " . $reason;
        return $this->save();
    }

    // Status checks
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isDue(): bool
    {
        return $this->isPending() && 
               $this->scheduled_time && 
               strtotime($this->scheduled_time) <= time();
    }

    public function isEmail(): bool
    {
        return $this->reminder_type === 'email';
    }

    public function isSMS(): bool
    {
        return $this->reminder_type === 'sms';
    }

    // Utility methods
    public function getRecipientInfo(): array
    {
        $appointment = $this->appointment();
        $patient = $appointment?->patient();
        
        if (!$patient) {
            return ['error' => 'Patient not found'];
        }

        return [
            'name' => $patient->getFullName(),
            'email' => $patient->email,
            'phone' => $patient->phone,
            'appointment_id' => $this->appointment_id
        ];
    }

    public function getScheduledTimeFormatted(): string
    {
        return date('M j, Y g:i A', strtotime($this->scheduled_time));
    }

    public function getSentTimeFormatted(): ?string
    {
        return $this->sent_at ? date('M j, Y g:i A', strtotime($this->sent_at)) : null;
    }

    public function getTimeUntilDue(): int
    {
        if (!$this->scheduled_time) {
            return 0;
        }
        
        $scheduledTimestamp = strtotime($this->scheduled_time);
        $currentTimestamp = time();
        
        return max(0, $scheduledTimestamp - $currentTimestamp);
    }

    // Statistics methods
    public static function getReminderStats(): array
    {
        $stats = static::$db->fetch(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN reminder_type = 'email' THEN 1 ELSE 0 END) as email_count,
                SUM(CASE WHEN reminder_type = 'sms' THEN 1 ELSE 0 END) as sms_count
             FROM reminders 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            []
        );

        return [
            'total' => (int) $stats['total'],
            'pending' => (int) $stats['pending'],
            'sent' => (int) $stats['sent'],
            'failed' => (int) $stats['failed'],
            'success_rate' => $stats['total'] > 0 ? round(($stats['sent'] / $stats['total']) * 100, 1) : 0,
            'email_count' => (int) $stats['email_count'],
            'sms_count' => (int) $stats['sms_count']
        ];
    }

    // Cleanup methods
    public static function cleanupOldReminders(int $daysOld = 90): int
    {
        $result = static::initializeDatabase(); static::$db->query(
            "DELETE FROM reminders 
             WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)
             AND status IN ('sent', 'failed', 'skipped')",
            ['days' => $daysOld]
        );
        
        return static::$db->affectedRows();
    }
}
