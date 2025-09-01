<?php

namespace MedicalClinic\Models;

class Doctor extends BaseModel
{
    protected static string $table = 'doctors';

    // Relationships
    public function user(): ?User
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function appointments(): array
    {
        return $this->hasMany(Appointment::class, 'doctor_id');
    }

    // Static methods
    public static function findByUserId(int $userId): ?static
    {
        $results = static::where('user_id', $userId);
        return $results[0] ?? null;
    }

    public static function findBySpecialty(string $specialty): array
    {
        return static::where('specialty', $specialty);
    }

    public static function getAvailableDoctors(): array
    {
        static::initializeDatabase();
        $results = static::$db->fetchAll(
            "SELECT d.*, u.first_name, u.last_name, u.email, u.phone
             FROM doctors d
             JOIN users u ON d.user_id = u.id
             WHERE u.is_active = 1
             ORDER BY u.last_name, u.first_name"
        );
        
        return array_map(fn($row) => new static($row, true), $results);
    }

    // Working schedule methods
    public function getWorkingDays(): array
    {
        $workingDays = json_decode($this->working_days, true);
        return is_array($workingDays) ? $workingDays : [];
    }

    public function setWorkingDays(array $days): void
    {
        $this->working_days = json_encode($days);
    }

    public function getWorkingHours(): array
    {
        $workingHours = json_decode($this->working_hours, true);
        return is_array($workingHours) ? $workingHours : ['start' => '09:00', 'end' => '17:00'];
    }

    public function setWorkingHours(array $hours): void
    {
        $this->working_hours = json_encode($hours);
    }

    public function isWorkingOn(string $day): bool
    {
        $workingDays = $this->getWorkingDays();
        return in_array(strtolower($day), array_map('strtolower', $workingDays));
    }

    public function isWorkingAt(string $time): bool
    {
        $hours = $this->getWorkingHours();
        return $time >= $hours['start'] && $time <= $hours['end'];
    }

    // Appointment management
    public function getAppointmentsForDate(string $date): array
    {
        static::initializeDatabase();
        $results = static::$db->fetchAll(
            "SELECT * FROM appointments 
             WHERE doctor_id = :doctor_id AND appointment_date = :date 
             ORDER BY start_time",
            ['doctor_id' => $this->id, 'date' => $date]
        );
        
        return array_map(fn($row) => new Appointment($row, true), $results);
    }

    public function getAppointmentsForDateRange(string $startDate, string $endDate): array
    {
        static::initializeDatabase();
        $results = static::$db->fetchAll(
            "SELECT * FROM appointments 
             WHERE doctor_id = :doctor_id 
             AND appointment_date BETWEEN :start_date AND :end_date 
             ORDER BY appointment_date, start_time",
            [
                'doctor_id' => $this->id, 
                'start_date' => $startDate, 
                'end_date' => $endDate
            ]
        );
        
        return array_map(fn($row) => new Appointment($row, true), $results);
    }

    public function isAvailableAt(string $date, string $startTime, string $endTime): bool
    {
        // Check if working on this day
        $dayOfWeek = strtolower(date('l', strtotime($date)));
        if (!$this->isWorkingOn($dayOfWeek)) {
            return false;
        }

        // Check if within working hours
        if (!$this->isWorkingAt($startTime) || !$this->isWorkingAt($endTime)) {
            return false;
        }

        // Check for conflicting appointments
        $conflicts = static::$db->fetchAll(
            "SELECT id FROM appointments 
             WHERE doctor_id = :doctor_id 
             AND appointment_date = :date 
             AND (
                 (start_time <= :start_time AND end_time > :start_time) OR
                 (start_time < :end_time AND end_time >= :end_time) OR
                 (start_time >= :start_time AND end_time <= :end_time)
             )
             AND status NOT IN ('cancelled', 'no_show')",
            [
                'doctor_id' => $this->id,
                'date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime
            ]
        );

        return empty($conflicts);
    }

    public function getAvailableSlots(string $date, int $durationMinutes = 30): array
    {
        $dayOfWeek = strtolower(date('l', strtotime($date)));
        if (!$this->isWorkingOn($dayOfWeek)) {
            return [];
        }

        $hours = $this->getWorkingHours();
        $slots = [];
        
        // Generate all possible slots
        $startTime = new \DateTime($date . ' ' . $hours['start']);
        $endTime = new \DateTime($date . ' ' . $hours['end']);
        
        while ($startTime < $endTime) {
            $slotStart = $startTime->format('H:i:s');
            $slotEnd = (clone $startTime)->modify("+{$durationMinutes} minutes")->format('H:i:s');
            
            if ($this->isAvailableAt($date, $slotStart, $slotEnd)) {
                $slots[] = [
                    'start_time' => $slotStart,
                    'end_time' => $slotEnd,
                    'available' => true
                ];
            }
            
            $startTime->modify("+{$durationMinutes} minutes");
        }
        
        return $slots;
    }

    // Display methods
    public function getDisplayName(): string
    {
        $user = $this->user();
        return $user ? "Dr. " . $user->getFullName() : "Unknown Doctor";
    }

    public function getDisplayInfo(): array
    {
        $user = $this->user();
        return [
            'id' => $this->id,
            'name' => $this->getDisplayName(),
            'specialty' => $this->specialty,
            'license_number' => $this->license_number,
            'email' => $user?->email,
            'phone' => $user?->phone,
            'working_days' => $this->getWorkingDays(),
            'working_hours' => $this->getWorkingHours(),
            'consultation_duration' => $this->consultation_duration
        ];
    }

    // Statistics
    public function getAppointmentCount(): int
    {
        return Appointment::count(['doctor_id' => $this->id]);
    }

    public function getCompletedAppointmentCount(): int
    {
        return Appointment::count(['doctor_id' => $this->id, 'status' => 'completed']);
    }

    public function getTodayAppointmentCount(): int
    {
        return Appointment::count([
            'doctor_id' => $this->id, 
            'appointment_date' => date('Y-m-d')
        ]);
    }
}
