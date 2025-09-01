<?php

namespace MedicalClinic\Models;

class Patient extends BaseModel
{
    protected static string $table = 'patients';

    // Relationships
    public function appointments(): array
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }

    public function aiAlerts(): array
    {
        return $this->hasMany(AIAlert::class, 'patient_id');
    }

    public function auditLogs(): array
    {
        return $this->hasMany(HIPAAAuditLog::class, 'patient_id');
    }

    // Static search and filter methods
    public static function search(string $query): array
    {
        static::initializeDatabase();
        $searchTerm = "%{$query}%";
        $results = static::$db->fetchAll(
            "SELECT * FROM patients 
             WHERE first_name LIKE :search1 
             OR last_name LIKE :search2 
             OR email LIKE :search3 
             OR phone LIKE :search4
             ORDER BY last_name, first_name",
            [
                'search1' => $searchTerm,
                'search2' => $searchTerm,
                'search3' => $searchTerm,
                'search4' => $searchTerm
            ]
        );
        return array_map(fn($row) => new static($row, true), $results);
    }

    public static function findByEmail(string $email): ?static
    {
        $results = static::where('email', $email);
        return $results[0] ?? null;
    }

    public static function findByPhone(string $phone): ?static
    {
        $results = static::where('phone', $phone);
        return $results[0] ?? null;
    }

    public static function getRecentPatients(int $limit = 10): array
    {
        static::initializeDatabase();
        $results = static::$db->fetchAll(
            "SELECT * FROM patients ORDER BY created_at DESC LIMIT :limit",
            ['limit' => $limit]
        );
        return array_map(fn($row) => new static($row, true), $results);
    }

    public static function getPatientsWithUpcomingAppointments(): array
    {
        static::initializeDatabase();
        $results = static::$db->fetchAll(
            "SELECT DISTINCT p.* FROM patients p
             JOIN appointments a ON p.id = a.patient_id
             WHERE a.appointment_date >= CURDATE()
             AND a.status IN ('scheduled', 'confirmed')
             ORDER BY p.last_name, p.first_name"
        );
        return array_map(fn($row) => new static($row, true), $results);
    }

    // Patient information methods
    public function getFullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getAge(): ?int
    {
        if (!$this->date_of_birth) {
            return null;
        }
        
        $dob = new \DateTime($this->date_of_birth);
        $now = new \DateTime();
        return $now->diff($dob)->y;
    }

    public function getAgeGroup(): string
    {
        $age = $this->getAge();
        if ($age === null) return 'Unknown';
        
        if ($age < 18) return 'Pediatric';
        if ($age < 65) return 'Adult';
        return 'Senior';
    }

    public function getContactInfo(): array
    {
        return [
            'email' => $this->email,
            'phone' => $this->phone,
            'emergency_contact' => $this->emergency_contact_name,
            'emergency_phone' => $this->emergency_contact_phone
        ];
    }

    public function getMedicalInfo(): array
    {
        return [
            'allergies' => $this->allergies,
            'medical_notes' => $this->medical_notes,
            'age' => $this->getAge(),
            'age_group' => $this->getAgeGroup()
        ];
    }

    // Appointment-related methods
    public function getUpcomingAppointments(): array
    {
        $results = static::$db->fetchAll(
            "SELECT * FROM appointments 
             WHERE patient_id = :patient_id 
             AND appointment_date >= CURDATE()
             AND status IN ('scheduled', 'confirmed')
             ORDER BY appointment_date, start_time",
            ['patient_id' => $this->id]
        );
        
        return array_map(fn($row) => new Appointment($row, true), $results);
    }

    public function getRecentAppointments(int $limit = 5): array
    {
        $results = static::$db->fetchAll(
            "SELECT * FROM appointments 
             WHERE patient_id = :patient_id 
             ORDER BY appointment_date DESC, start_time DESC 
             LIMIT :limit",
            ['patient_id' => $this->id, 'limit' => $limit]
        );
        
        return array_map(fn($row) => new Appointment($row, true), $results);
    }

    public function getNextAppointment(): ?Appointment
    {
        $result = static::$db->fetch(
            "SELECT * FROM appointments 
             WHERE patient_id = :patient_id 
             AND appointment_date >= CURDATE()
             AND status IN ('scheduled', 'confirmed')
             ORDER BY appointment_date, start_time 
             LIMIT 1",
            ['patient_id' => $this->id]
        );
        
        return $result ? new Appointment($result, true) : null;
    }

    public function getLastVisit(): ?Appointment
    {
        $result = static::$db->fetch(
            "SELECT * FROM appointments 
             WHERE patient_id = :patient_id 
             AND status = 'completed'
             ORDER BY appointment_date DESC, start_time DESC 
             LIMIT 1",
            ['patient_id' => $this->id]
        );
        
        return $result ? new Appointment($result, true) : null;
    }

    public function getAppointmentHistory(): array
    {
        $results = static::$db->fetchAll(
            "SELECT a.*, u.first_name as doctor_first_name, u.last_name as doctor_last_name, d.specialty
             FROM appointments a
             JOIN doctors d ON a.doctor_id = d.id
             JOIN users u ON d.user_id = u.id
             WHERE a.patient_id = :patient_id
             ORDER BY a.appointment_date DESC, a.start_time DESC",
            ['patient_id' => $this->id]
        );
        
        return array_map(fn($row) => new Appointment($row, true), $results);
    }

    // Medical alerts
    public function getActiveAlerts(): array
    {
        $results = static::$db->fetchAll(
            "SELECT * FROM ai_alerts 
             WHERE patient_id = :patient_id 
             AND status = 'active'
             ORDER BY priority_level DESC, created_at DESC",
            ['patient_id' => $this->id]
        );
        
        return array_map(fn($row) => new AIAlert($row, true), $results);
    }

    // Validation methods
    public function validateEmail(): bool
    {
        return $this->email ? filter_var($this->email, FILTER_VALIDATE_EMAIL) !== false : true;
    }

    public function validatePhone(): bool
    {
        if (!$this->phone) return true;
        return preg_match('/^[\+]?[1-9][\d]{0,15}$/', preg_replace('/[^\d\+]/', '', $this->phone));
    }

    public function validateDateOfBirth(): bool
    {
        if (!$this->date_of_birth) return true;
        
        $dob = \DateTime::createFromFormat('Y-m-d', $this->date_of_birth);
        if (!$dob) return false;
        
        $today = new \DateTime();
        return $dob <= $today;
    }

    // Statistics
    public function getTotalAppointments(): int
    {
        return Appointment::count(['patient_id' => $this->id]);
    }

    public function getCompletedAppointments(): int
    {
        return Appointment::count(['patient_id' => $this->id, 'status' => 'completed']);
    }

    public function getCancelledAppointments(): int
    {
        return static::$db->fetch(
            "SELECT COUNT(*) as count FROM appointments 
             WHERE patient_id = :patient_id 
             AND status IN ('cancelled', 'no_show')",
            ['patient_id' => $this->id]
        )['count'];
    }

    // Display formatting
    public function getDisplayInfo(): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->getFullName(),
            'age' => $this->getAge(),
            'age_group' => $this->getAgeGroup(),
            'contact_info' => $this->getContactInfo(),
            'medical_info' => $this->getMedicalInfo(),
            'next_appointment' => $this->getNextAppointment()?->toArray(),
            'last_visit' => $this->getLastVisit()?->toArray(),
            'total_appointments' => $this->getTotalAppointments(),
            'active_alerts' => count($this->getActiveAlerts())
        ];
    }
}
