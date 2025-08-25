<?php

namespace MedicalClinic\Services;

use MedicalClinic\Utils\Database;

class HIPAAComplianceService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Log HIPAA compliance events
     */
    public function logHIPAAEvent(string $eventType, array $details, ?int $userId = null, ?int $patientId = null, ?int $appointmentId = null): void
    {
        try {
            $this->db->insert('hipaa_audit_log', [
                'event_type' => $eventType,
                'event_details' => json_encode($details),
                'user_id' => $userId,
                'patient_id' => $patientId,
                'appointment_id' => $appointmentId,
                'ip_address' => $this->getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'system',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            error_log("HIPAA logging failed: " . $e->getMessage());
        }
    }

    /**
     * Get comprehensive audit report
     */
    public function getAuditReport(string $startDate, string $endDate, ?string $eventType = null): array
    {
        $sql = "SELECT h.*, u.username, u.first_name, u.last_name,
                       p.first_name as patient_first_name, p.last_name as patient_last_name
                FROM hipaa_audit_log h
                LEFT JOIN users u ON h.user_id = u.id
                LEFT JOIN patients p ON h.patient_id = p.id
                WHERE DATE(h.created_at) BETWEEN :start_date AND :end_date";

        $params = [
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        if ($eventType) {
            $sql .= " AND h.event_type = :event_type";
            $params['event_type'] = $eventType;
        }

        $sql .= " ORDER BY h.created_at DESC";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get audit statistics
     */
    public function getAuditStats(string $startDate, string $endDate): array
    {
        $stats = $this->db->fetch(
            "SELECT 
                COUNT(*) as total_events,
                COUNT(DISTINCT user_id) as unique_users,
                COUNT(DISTINCT patient_id) as patients_accessed,
                COUNT(DISTINCT appointment_id) as appointments_accessed,
                SUM(CASE WHEN event_type LIKE 'reminder_%' THEN 1 ELSE 0 END) as reminder_events,
                SUM(CASE WHEN event_type LIKE 'patient_%' THEN 1 ELSE 0 END) as patient_events,
                SUM(CASE WHEN event_type LIKE 'appointment_%' THEN 1 ELSE 0 END) as appointment_events
             FROM hipaa_audit_log 
             WHERE DATE(created_at) BETWEEN :start_date AND :end_date",
            ['start_date' => $startDate, 'end_date' => $endDate]
        );

        return [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'total_events' => (int) $stats['total_events'],
            'unique_users' => (int) $stats['unique_users'],
            'patients_accessed' => (int) $stats['patients_accessed'],
            'appointments_accessed' => (int) $stats['appointments_accessed'],
            'reminder_events' => (int) $stats['reminder_events'],
            'patient_events' => (int) $stats['patient_events'],
            'appointment_events' => (int) $stats['appointment_events']
        ];
    }

    /**
     * Get client IP address safely
     */
    private function getClientIP(): string
    {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Check for compliance violations
     */
    public function checkComplianceViolations(): array
    {
        $violations = [];

        // Check for unusual access patterns (example: too many patient record accesses)
        $suspiciousActivity = $this->db->fetchAll(
            "SELECT user_id, COUNT(*) as access_count,
                    COUNT(DISTINCT patient_id) as unique_patients
             FROM hipaa_audit_log 
             WHERE event_type LIKE 'patient_%' 
             AND DATE(created_at) = CURDATE()
             GROUP BY user_id
             HAVING access_count > 100 OR unique_patients > 50"
        );

        foreach ($suspiciousActivity as $activity) {
            $violations[] = [
                'type' => 'excessive_access',
                'user_id' => $activity['user_id'],
                'description' => "User accessed {$activity['unique_patients']} patient records {$activity['access_count']} times today",
                'severity' => 'high'
            ];
        }

        return $violations;
    }
}
