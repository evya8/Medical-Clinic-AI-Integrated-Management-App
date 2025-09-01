<?php

namespace MedicalClinic\Models;

class AIAlert extends BaseModel
{
    protected static string $table = 'ai_alerts';

    // Relationships
    public function patient(): ?Patient
    {
        return $this->patient_id ? $this->belongsTo(Patient::class, 'patient_id') : null;
    }

    public function appointment(): ?Appointment
    {
        return $this->appointment_id ? $this->belongsTo(Appointment::class, 'appointment_id') : null;
    }

    public function acknowledgedByUser(): ?User
    {
        return $this->acknowledged_by ? $this->belongsTo(User::class, 'acknowledged_by') : null;
    }

    // Static query methods
    public static function getActiveAlerts(): array
    {
        return static::where('status', 'active');
    }

    public static function getAlertsByType(string $alertType): array
    {
        return static::where('type', $alertType);
    }

    public static function getAlertsByPriority(int $priority): array
    {
        return static::where('priority', $priority);
    }

    public static function getPatientAlerts(int $patientId): array
    {
        return static::where('patient_id', $patientId);
    }

    public static function getCriticalAlerts(): array
    {
        return static::where('priority', 5); // Critical = priority 5
    }

    public static function getUnacknowledgedAlerts(): array
    {
        static::initializeDatabase();
        $results = static::$db->fetchAll(
            "SELECT * FROM ai_alerts 
             WHERE status = 'active' 
             AND acknowledged_at IS NULL
             ORDER BY priority DESC, created_at ASC"
        );
        return array_map(fn($row) => new static($row, true), $results);
    }

    public static function getRecentAlerts(int $hours = 24): array
    {
        static::initializeDatabase();
        $results = static::$db->fetchAll(
            "SELECT * FROM ai_alerts 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)
             ORDER BY created_at DESC",
            ['hours' => $hours]
        );
        return array_map(fn($row) => new static($row, true), $results);
    }

    // Status management
    public function acknowledge(int $userId): bool
    {
        $this->acknowledged_by = $userId;
        $this->acknowledged_at = date('Y-m-d H:i:s');
        return $this->save();
    }

    public function resolve(): bool
    {
        $this->status = 'resolved';
        return $this->save();
    }

    public function dismiss(): bool
    {
        $this->status = 'dismissed';
        return $this->save();
    }

    public function reactivate(): bool
    {
        $this->status = 'active';
        $this->acknowledged_by = null;
        $this->acknowledged_at = null;
        return $this->save();
    }

    // Status checks
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isAcknowledged(): bool
    {
        return !empty($this->acknowledged_at);
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    public function isDismissed(): bool
    {
        return $this->status === 'dismissed';
    }

    // Priority assessment
    public function isCritical(): bool
    {
        return $this->priority >= 5;
    }

    public function isHigh(): bool
    {
        return $this->priority == 4;
    }

    public function isMedium(): bool
    {
        return $this->priority == 3;
    }

    public function isLow(): bool
    {
        return $this->priority <= 2;
    }

    public function getPriorityNumber(): int
    {
        return (int) $this->priority;
    }

    public function getPriorityLevel(): string
    {
        return match(true) {
            $this->priority >= 5 => 'critical',
            $this->priority == 4 => 'high',
            $this->priority == 3 => 'medium',
            $this->priority <= 2 => 'low',
            default => 'low'
        };
    }

    public function getPriorityColor(): string
    {
        return match(true) {
            $this->priority >= 5 => 'red',
            $this->priority == 4 => 'orange',
            $this->priority == 3 => 'yellow',
            $this->priority <= 2 => 'blue',
            default => 'gray'
        };
    }

    // Alert type classification
    public function isPatientSafetyAlert(): bool
    {
        return $this->type === 'patient_safety';
    }

    public function isOperationalAlert(): bool
    {
        return $this->type === 'operational';
    }

    public function isQualityAlert(): bool
    {
        return $this->type === 'quality';
    }

    // Time-based methods
    public function getAge(): int
    {
        $created = new \DateTime($this->created_at);
        $now = new \DateTime();
        return $now->diff($created)->days;
    }

    public function getAgeHours(): int
    {
        $created = new \DateTime($this->created_at);
        $now = new \DateTime();
        $diff = $now->diff($created);
        return ($diff->days * 24) + $diff->h;
    }

    public function isStale(int $hoursThreshold = 24): bool
    {
        return $this->getAgeHours() > $hoursThreshold;
    }

    public function getFormattedAge(): string
    {
        $hours = $this->getAgeHours();
        
        if ($hours < 1) {
            return 'Just now';
        } elseif ($hours < 24) {
            return $hours . ' hour' . ($hours === 1 ? '' : 's') . ' ago';
        } else {
            $days = floor($hours / 24);
            return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
        }
    }

    // AI reasoning and data
    public function hasActionRequired(): bool
    {
        return !empty($this->action_required);
    }

    public function getActionRequiredArray(): array
    {
        if (!$this->action_required) {
            return [];
        }
        
        $decoded = json_decode($this->action_required, true);
        return is_array($decoded) ? $decoded : ['action' => $this->action_required];
    }

    // Display and export methods
    public function getDisplayInfo(): array
    {
        $patient = $this->patient();
        $appointment = $this->appointment();
        $acknowledgedBy = $this->acknowledgedByUser();
        
        return [
            'id' => $this->id,
            'alert_type' => $this->type,
            'priority_level' => $this->getPriorityLevel(),
            'priority_number' => $this->getPriorityNumber(),
            'priority_color' => $this->getPriorityColor(),
            'message' => $this->description,
            'title' => $this->title,
            'status' => $this->status,
            'is_acknowledged' => $this->isAcknowledged(),
            'is_resolved' => $this->isResolved(),
            'patient_name' => $patient?->getFullName(),
            'appointment_info' => $appointment?->getDisplayInfo(),
            'acknowledged_by' => $acknowledgedBy?->getFullName(),
            'acknowledged_at' => $this->acknowledged_at,
            'age' => $this->getFormattedAge(),
            'is_stale' => $this->isStale(),
            'has_action_required' => $this->hasActionRequired(),
            'timeline' => $this->timeline,
            'created_at' => $this->created_at
        ];
    }

    // Statistics and analytics
    public static function getAlertStats(): array
    {
        static::initializeDatabase();
        $stats = static::$db->fetch(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                SUM(CASE WHEN status = 'dismissed' THEN 1 ELSE 0 END) as dismissed,
                SUM(CASE WHEN acknowledged_at IS NOT NULL THEN 1 ELSE 0 END) as acknowledged,
                SUM(CASE WHEN priority >= 5 THEN 1 ELSE 0 END) as critical,
                SUM(CASE WHEN priority = 4 THEN 1 ELSE 0 END) as high,
                AVG(CASE WHEN acknowledged_at IS NOT NULL 
                    THEN TIMESTAMPDIFF(MINUTE, created_at, acknowledged_at) 
                    ELSE NULL END) as avg_acknowledgment_time
             FROM ai_alerts 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            []
        );

        return [
            'total_alerts' => (int) $stats['total'],
            'active_alerts' => (int) $stats['active'],
            'resolved_alerts' => (int) $stats['resolved'],
            'dismissed_alerts' => (int) $stats['dismissed'],
            'acknowledged_alerts' => (int) $stats['acknowledged'],
            'critical_alerts' => (int) $stats['critical'],
            'high_priority_alerts' => (int) $stats['high'],
            'acknowledgment_rate' => $stats['total'] > 0 ? 
                round(($stats['acknowledged'] / $stats['total']) * 100, 1) : 0,
            'resolution_rate' => $stats['total'] > 0 ? 
                round(($stats['resolved'] / $stats['total']) * 100, 1) : 0,
            'avg_acknowledgment_time_minutes' => round((float) $stats['avg_acknowledgment_time'], 1)
        ];
    }

    public static function getAlertTrends(int $days = 7): array
    {
        static::initializeDatabase();
        $results = static::$db->fetchAll(
            "SELECT 
                DATE(created_at) as date,
                COUNT(*) as count,
                SUM(CASE WHEN priority >= 4 THEN 1 ELSE 0 END) as high_priority_count
             FROM ai_alerts 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
             GROUP BY DATE(created_at)
             ORDER BY date",
            ['days' => $days]
        );

        return $results;
    }

    // Cleanup methods
    public static function cleanupOldAlerts(int $daysOld = 90): int
    {
        static::initializeDatabase();
        static::$db->query(
            "DELETE FROM ai_alerts 
             WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)
             AND status IN ('resolved', 'dismissed')",
            ['days' => $daysOld]
        );
        
        return static::$db->affectedRows();
    }
}
