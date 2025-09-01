<?php

namespace MedicalClinic\Models;

class Appointment extends BaseModel
{
    protected static string $table = 'appointments';

    // Relationships
    public function patient(): ?Patient
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function doctor(): ?Doctor
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function createdBy(): ?User
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reminders(): array
    {
        return $this->hasMany(Reminder::class, 'appointment_id');
    }

    public function summary(): ?AppointmentSummary
    {
        static::initializeDatabase();
        $results = static::$db->fetchAll(
            "SELECT * FROM appointment_summaries WHERE appointment_id = :appointment_id",
            ['appointment_id' => $this->id]
        );
        return !empty($results) ? new AppointmentSummary($results[0], true) : null;
    }

    public function aiAlerts(): array
    {
        return $this->hasMany(AIAlert::class, 'appointment_id');
    }

    // Static query methods
    public static function getByDateRange(string $startDate, string $endDate): array
    {
        static::initializeDatabase();
        $results = static::$db->fetchAll(
            "SELECT * FROM appointments 
             WHERE appointment_date BETWEEN :start AND :end 
             ORDER BY appointment_date, start_time",
            ['start' => $startDate, 'end' => $endDate]
        );
        return array_map(fn($row) => new static($row, true), $results);
    }

    public static function getDoctorAppointments(int $doctorId, string $date): array
    {
        static::initializeDatabase();
        $results = static::$db->fetchAll(
            "SELECT * FROM appointments 
             WHERE doctor_id = :doctor_id AND appointment_date = :date 
             ORDER BY start_time",
            ['doctor_id' => $doctorId, 'date' => $date]
        );
        return array_map(fn($row) => new static($row, true), $results);
    }

    public static function getPatientAppointments(int $patientId): array
    {
        static::initializeDatabase();
        $results = static::$db->fetchAll(
            "SELECT * FROM appointments 
             WHERE patient_id = :patient_id 
             ORDER BY appointment_date DESC, start_time DESC",
            ['patient_id' => $patientId]
        );
        return array_map(fn($row) => new static($row, true), $results);
    }

    public static function getTodayAppointments(): array
    {
        static::initializeDatabase();
        $results = static::$db->fetchAll(
            "SELECT a.*, 
                    p.first_name as patient_first_name, p.last_name as patient_last_name,
                    u.first_name as doctor_first_name, u.last_name as doctor_last_name,
                    d.specialty
             FROM appointments a
             JOIN patients p ON a.patient_id = p.id
             JOIN doctors d ON a.doctor_id = d.id
             JOIN users u ON d.user_id = u.id
             WHERE DATE(a.appointment_date) = CURDATE()
             ORDER BY a.start_time",
            []
        );
        return array_map(fn($row) => new static($row, true), $results);
    }

    public static function getUpcomingAppointments(int $limit = 10): array
    {
        static::initializeDatabase();
        $results = static::$db->fetchAll(
            "SELECT a.*, 
                    p.first_name as patient_first_name, p.last_name as patient_last_name,
                    u.first_name as doctor_first_name, u.last_name as doctor_last_name,
                    d.specialty
             FROM appointments a
             JOIN patients p ON a.patient_id = p.id
             JOIN doctors d ON a.doctor_id = d.id
             JOIN users u ON d.user_id = u.id
             WHERE a.appointment_date >= CURDATE()
             AND a.status IN ('scheduled', 'confirmed')
             ORDER BY a.appointment_date, a.start_time
             LIMIT :limit",
            ['limit' => $limit]
        );
        return array_map(fn($row) => new static($row, true), $results);
    }

    public static function getByStatus(string $status): array
    {
        return static::where('status', $status);
    }

    public static function getByPriority(string $priority): array
    {
        return static::where('priority', $priority);
    }

    // Time and scheduling methods
    public function getDateTime(): \DateTime
    {
        return new \DateTime($this->appointment_date . ' ' . $this->start_time);
    }

    public function getEndDateTime(): \DateTime
    {
        return new \DateTime($this->appointment_date . ' ' . $this->end_time);
    }

    public function getDurationMinutes(): int
    {
        $start = new \DateTime($this->start_time);
        $end = new \DateTime($this->end_time);
        return $end->diff($start)->i + ($end->diff($start)->h * 60);
    }

    public function isToday(): bool
    {
        return $this->appointment_date === date('Y-m-d');
    }

    public function isPast(): bool
    {
        $appointmentDateTime = $this->getDateTime();
        return $appointmentDateTime < new \DateTime();
    }

    public function isFuture(): bool
    {
        $appointmentDateTime = $this->getDateTime();
        return $appointmentDateTime > new \DateTime();
    }

    public function isWithinNextHour(): bool
    {
        $appointmentDateTime = $this->getDateTime();
        $oneHourFromNow = (new \DateTime())->modify('+1 hour');
        return $appointmentDateTime >= new \DateTime() && $appointmentDateTime <= $oneHourFromNow;
    }

    // Status check methods
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return in_array($this->status, ['cancelled', 'no_show']);
    }

    public function canBeModified(): bool
    {
        return !$this->isPast() && !$this->isCancelled() && !$this->isCompleted();
    }

    public function canBeCompleted(): bool
    {
        return !$this->isCancelled() && !$this->isCompleted() && $this->isToday();
    }

    public function canBeCancelled(): bool
    {
        return $this->isFuture() && !$this->isCancelled() && !$this->isCompleted();
    }

    // Medical information methods
    public function hasDiagnosis(): bool
    {
        return !empty($this->diagnosis);
    }

    public function hasTreatmentNotes(): bool
    {
        return !empty($this->treatment_notes);
    }

    public function requiresFollowUp(): bool
    {
        return (bool) $this->follow_up_required;
    }

    public function hasFollowUpScheduled(): bool
    {
        if (!$this->requiresFollowUp() || !$this->follow_up_date) {
            return false;
        }

        // Check if follow-up appointment exists
        $followUp = static::$db->fetch(
            "SELECT id FROM appointments 
             WHERE patient_id = :patient_id 
             AND appointment_date = :follow_up_date
             AND appointment_type LIKE '%follow%'
             AND status NOT IN ('cancelled', 'no_show')",
            [
                'patient_id' => $this->patient_id,
                'follow_up_date' => $this->follow_up_date
            ]
        );

        return $followUp !== null;
    }

    // Priority and urgency
    public function isHighPriority(): bool
    {
        return in_array($this->priority, ['high', 'urgent']);
    }

    public function isUrgent(): bool
    {
        return $this->priority === 'urgent';
    }

    public function getPriorityLevel(): int
    {
        return match($this->priority) {
            'low' => 1,
            'normal' => 2,
            'high' => 3,
            'urgent' => 4,
            default => 2
        };
    }

    public function getPriorityColor(): string
    {
        return match($this->priority) {
            'low' => 'gray',
            'normal' => 'blue',
            'high' => 'yellow',
            'urgent' => 'red',
            default => 'blue'
        };
    }

    // Status management
    public function confirm(): bool
    {
        if (!$this->canBeModified()) {
            return false;
        }
        
        $this->status = 'confirmed';
        return $this->save();
    }

    public function complete(array $medicalData = []): bool
    {
        if (!$this->canBeCompleted()) {
            return false;
        }
        
        $this->status = 'completed';
        
        // Add medical data if provided
        if (isset($medicalData['diagnosis'])) {
            $this->diagnosis = $medicalData['diagnosis'];
        }
        if (isset($medicalData['treatment_notes'])) {
            $this->treatment_notes = $medicalData['treatment_notes'];
        }
        if (isset($medicalData['follow_up_required'])) {
            $this->follow_up_required = $medicalData['follow_up_required'];
        }
        if (isset($medicalData['follow_up_date'])) {
            $this->follow_up_date = $medicalData['follow_up_date'];
        }
        
        return $this->save();
    }

    public function cancel(string $reason = ''): bool
    {
        if (!$this->canBeCancelled()) {
            return false;
        }
        
        $this->status = 'cancelled';
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n\n" : '') . "Cancelled: " . $reason;
        }
        
        return $this->save();
    }

    public function markNoShow(): bool
    {
        $this->status = 'no_show';
        return $this->save();
    }

    // Conflict detection
    public function hasConflicts(): bool
    {
        $conflicts = static::$db->fetchAll(
            "SELECT id FROM appointments 
             WHERE doctor_id = :doctor_id 
             AND appointment_date = :date 
             AND id != :id
             AND (
                 (start_time <= :start_time AND end_time > :start_time) OR
                 (start_time < :end_time AND end_time >= :end_time) OR
                 (start_time >= :start_time AND end_time <= :end_time)
             )
             AND status NOT IN ('cancelled', 'no_show')",
            [
                'doctor_id' => $this->doctor_id,
                'date' => $this->appointment_date,
                'id' => $this->id ?? 0,
                'start_time' => $this->start_time,
                'end_time' => $this->end_time
            ]
        );

        return !empty($conflicts);
    }

    // Display methods
    public function getDisplayInfo(): array
    {
        $patient = $this->patient();
        $doctor = $this->doctor();
        
        return [
            'id' => $this->id,
            'patient_name' => $patient?->getFullName() ?? 'Unknown Patient',
            'doctor_name' => $doctor?->getDisplayName() ?? 'Unknown Doctor',
            'specialty' => $doctor?->specialty ?? '',
            'date' => $this->appointment_date,
            'time' => substr($this->start_time, 0, 5) . ' - ' . substr($this->end_time, 0, 5),
            'duration' => $this->getDurationMinutes() . ' minutes',
            'type' => $this->appointment_type,
            'priority' => $this->priority,
            'priority_color' => $this->getPriorityColor(),
            'status' => $this->status,
            'can_modify' => $this->canBeModified(),
            'can_complete' => $this->canBeCompleted(),
            'can_cancel' => $this->canBeCancelled(),
            'has_diagnosis' => $this->hasDiagnosis(),
            'has_treatment_notes' => $this->hasTreatmentNotes(),
            'requires_follow_up' => $this->requiresFollowUp(),
            'follow_up_scheduled' => $this->hasFollowUpScheduled()
        ];
    }

    public function getTimeRange(): string
    {
        return substr($this->start_time, 0, 5) . ' - ' . substr($this->end_time, 0, 5);
    }

    public function getFormattedDate(): string
    {
        return date('M j, Y', strtotime($this->appointment_date));
    }

    public function getFormattedDateTime(): string
    {
        return $this->getFormattedDate() . ' at ' . substr($this->start_time, 0, 5);
    }
}
