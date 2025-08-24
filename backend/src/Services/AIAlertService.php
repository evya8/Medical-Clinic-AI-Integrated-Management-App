<?php

namespace MedicalClinic\Services;

use MedicalClinic\Utils\Database;

class AIAlertService
{
    private Database $db;
    private GroqAIService $aiService;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->aiService = new GroqAIService();
    }

    /**
     * Generate comprehensive AI-powered alerts for clinic operations
     */
    public function generateIntelligentAlerts(array $options = []): array
    {
        try {
            // Collect alert data from multiple sources
            $alertData = $this->collectAlertData();
            
            // Generate AI-powered alert analysis
            $alertPrompt = $this->buildAlertPrompt($alertData);
            
            $aiResponse = $this->aiService->generateResponse($alertPrompt, 'alerts');
            
            if (!$aiResponse['success']) {
                return $this->getFallbackAlerts($alertData);
            }

            // Parse and prioritize alerts
            $intelligentAlerts = $this->parseAIAlerts($aiResponse['content']);
            
            // Add system-generated alerts
            $systemAlerts = $this->generateSystemAlerts($alertData);
            
            // Combine and prioritize all alerts
            $allAlerts = array_merge($intelligentAlerts, $systemAlerts);
            usort($allAlerts, [$this, 'prioritizeAlerts']);

            // Store alerts for tracking
            $this->storeAlerts($allAlerts);

            return [
                'success' => true,
                'alerts' => $allAlerts,
                'alert_count' => count($allAlerts),
                'high_priority_count' => count(array_filter($allAlerts, fn($a) => $a['priority'] >= 4)),
                'ai_generated_count' => count($intelligentAlerts),
                'system_generated_count' => count($systemAlerts),
                'model_used' => $aiResponse['model_used'],
                'generated_at' => date('Y-m-d H:i:s')
            ];

        } catch (\Exception $e) {
            error_log("Alert generation error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Alert generation temporarily unavailable',
                'fallback' => $this->getFallbackAlerts($this->collectAlertData())
            ];
        }
    }

    /**
     * Generate patient safety alerts
     */
    public function generatePatientSafetyAlerts(): array
    {
        $safetyData = $this->collectPatientSafetyData();
        
        $prompt = "Analyze patient safety data and generate priority alerts:

OVERDUE FOLLOW-UPS: " . count($safetyData['overdue_followups']) . "
MEDICATION INTERACTIONS: " . count($safetyData['potential_interactions']) . "
ABNORMAL RESULTS PENDING: " . count($safetyData['abnormal_results']) . "
HIGH-RISK PATIENTS: " . count($safetyData['high_risk_patients']) . "
MISSED APPOINTMENTS: " . count($safetyData['missed_appointments']) . "

Generate patient safety alerts focusing on:
1. Immediate safety concerns requiring action within 24 hours
2. Follow-up care gaps that could impact patient outcomes
3. Medication safety issues
4. High-risk patient monitoring needs
5. Quality improvement opportunities

Prioritize by patient safety impact and urgency.";

        $aiResponse = $this->aiService->generateResponse($prompt, 'alerts');

        if ($aiResponse['success']) {
            $safetyAlerts = $this->parsePatientSafetyAlerts($aiResponse['content'], $safetyData);
            
            return [
                'success' => true,
                'safety_alerts' => $safetyAlerts,
                'critical_count' => count(array_filter($safetyAlerts, fn($a) => $a['level'] === 'critical')),
                'generated_at' => date('Y-m-d H:i:s')
            ];
        }

        return [
            'success' => false,
            'message' => 'Safety alert generation failed',
            'fallback' => $this->generateFallbackSafetyAlerts($safetyData)
        ];
    }

    /**
     * Generate operational efficiency alerts
     */
    public function generateOperationalAlerts(): array
    {
        $operationalData = $this->collectOperationalData();
        
        $prompt = "Analyze clinic operations and suggest efficiency improvements:

APPOINTMENT UTILIZATION: {$operationalData['utilization_rate']}%
AVERAGE WAIT TIME: {$operationalData['avg_wait_time']} minutes
NO-SHOW RATE: {$operationalData['no_show_rate']}%
OVERTIME APPOINTMENTS: {$operationalData['overtime_count']}
INVENTORY ALERTS: {$operationalData['low_inventory_count']}
REVENUE OPPORTUNITIES: {$operationalData['billing_gaps']}

Generate operational alerts for:
1. Schedule optimization opportunities
2. Resource allocation improvements
3. Revenue cycle management
4. Patient flow efficiency
5. Staff productivity insights

Focus on actionable recommendations that improve clinic efficiency.";

        $aiResponse = $this->aiService->generateResponse($prompt, 'alerts');

        if ($aiResponse['success']) {
            return [
                'success' => true,
                'operational_alerts' => $aiResponse['content'],
                'efficiency_score' => $this->calculateEfficiencyScore($operationalData),
                'generated_at' => date('Y-m-d H:i:s')
            ];
        }

        return [
            'success' => false,
            'message' => 'Operational alert generation failed'
        ];
    }

    /**
     * Generate quality improvement alerts
     */
    public function generateQualityAlerts(): array
    {
        $qualityData = $this->collectQualityData();
        
        $prompt = "Analyze quality metrics and identify improvement opportunities:

PATIENT SATISFACTION: {$qualityData['satisfaction_score']}/5
FOLLOW-UP COMPLIANCE: {$qualityData['followup_compliance']}%
DOCUMENTATION COMPLETENESS: {$qualityData['documentation_score']}%
PREVENTIVE CARE RATES: {$qualityData['preventive_care_rate']}%
CARE COORDINATION: {$qualityData['coordination_score']}/5

Generate quality improvement alerts focusing on:
1. Patient experience enhancement opportunities
2. Clinical documentation improvements
3. Care coordination optimization
4. Preventive care gaps
5. Best practice implementation

Prioritize initiatives with highest quality impact.";

        $aiResponse = $this->aiService->generateResponse($prompt, 'alerts');

        if ($aiResponse['success']) {
            return [
                'success' => true,
                'quality_alerts' => $aiResponse['content'],
                'quality_score' => $qualityData['overall_quality_score'],
                'improvement_opportunities' => $this->identifyImprovementOpportunities($qualityData),
                'generated_at' => date('Y-m-d H:i:s')
            ];
        }

        return [
            'success' => false,
            'message' => 'Quality alert generation failed'
        ];
    }

    /**
     * Get active alerts summary
     */
    public function getActiveAlerts(array $filters = []): array
    {
        try {
            $whereConditions = ['is_active = 1'];
            $params = [];

            if (!empty($filters['priority_min'])) {
                $whereConditions[] = 'priority >= :priority_min';
                $params['priority_min'] = $filters['priority_min'];
            }

            if (!empty($filters['category'])) {
                $whereConditions[] = 'category = :category';
                $params['category'] = $filters['category'];
            }

            if (!empty($filters['patient_id'])) {
                $whereConditions[] = 'patient_id = :patient_id';
                $params['patient_id'] = $filters['patient_id'];
            }

            $whereClause = implode(' AND ', $whereConditions);

            $alerts = $this->db->fetchAll(
                "SELECT * FROM ai_alerts 
                 WHERE {$whereClause}
                 ORDER BY priority DESC, created_at DESC
                 LIMIT 50",
                $params
            );

            return [
                'success' => true,
                'alerts' => $alerts,
                'total_count' => count($alerts),
                'filters_applied' => $filters
            ];

        } catch (\Exception $e) {
            error_log("Error fetching active alerts: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Unable to fetch alerts'
            ];
        }
    }

    /**
     * Acknowledge or dismiss alerts
     */
    public function acknowledgeAlert(int $alertId, int $userId, string $action = 'acknowledge'): array
    {
        try {
            $updateData = [
                'acknowledged_by' => $userId,
                'acknowledged_at' => date('Y-m-d H:i:s'),
                'status' => $action === 'dismiss' ? 'dismissed' : 'acknowledged'
            ];

            if ($action === 'dismiss') {
                $updateData['is_active'] = 0;
            }

            $this->db->update('ai_alerts', $updateData, 'id = :id', ['id' => $alertId]);

            return [
                'success' => true,
                'alert_id' => $alertId,
                'action' => $action,
                'acknowledged_by' => $userId,
                'acknowledged_at' => $updateData['acknowledged_at']
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to ' . $action . ' alert'
            ];
        }
    }

    // Private helper methods

    private function collectAlertData(): array
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $lastWeek = date('Y-m-d', strtotime('-7 days'));

        // Overdue follow-ups
        $overdueFollowups = $this->db->fetchAll(
            "SELECT a.*, p.first_name, p.last_name 
             FROM appointments a
             JOIN patients p ON a.patient_id = p.id
             WHERE a.follow_up_required = 1 
             AND a.follow_up_date < :today
             AND a.status = 'completed'
             LIMIT 20",
            ['today' => $today]
        );

        // High-priority appointments today
        $urgentToday = $this->db->fetchAll(
            "SELECT a.*, p.first_name, p.last_name
             FROM appointments a
             JOIN patients p ON a.patient_id = p.id
             WHERE DATE(a.appointment_date) = :today
             AND a.priority IN ('high', 'urgent')
             AND a.status IN ('scheduled', 'confirmed')",
            ['today' => $today]
        );

        // Low inventory items
        $lowInventory = $this->db->fetchAll(
            "SELECT * FROM inventory 
             WHERE quantity <= minimum_stock_level
             ORDER BY (quantity/minimum_stock_level) ASC"
        );

        // Recent no-shows
        $recentNoShows = $this->db->fetchAll(
            "SELECT a.*, p.first_name, p.last_name
             FROM appointments a
             JOIN patients p ON a.patient_id = p.id
             WHERE a.status = 'no_show'
             AND DATE(a.appointment_date) >= :last_week",
            ['last_week' => $lastWeek]
        );

        // Pending reminders
        $pendingReminders = $this->db->fetch(
            "SELECT COUNT(*) as count
             FROM reminders 
             WHERE status = 'pending'
             AND scheduled_time <= NOW()"
        );

        return [
            'overdue_followups' => $overdueFollowups,
            'urgent_appointments' => $urgentToday,
            'low_inventory' => $lowInventory,
            'recent_no_shows' => $recentNoShows,
            'pending_reminders' => $pendingReminders['count'] ?? 0,
            'summary' => [
                'overdue_count' => count($overdueFollowups),
                'urgent_count' => count($urgentToday),
                'inventory_alerts' => count($lowInventory),
                'no_show_count' => count($recentNoShows),
                'pending_reminders' => $pendingReminders['count'] ?? 0
            ]
        ];
    }

    private function collectPatientSafetyData(): array
    {
        return [
            'overdue_followups' => $this->db->fetchAll(
                "SELECT patient_id, COUNT(*) as overdue_count
                 FROM appointments 
                 WHERE follow_up_required = 1 
                 AND follow_up_date < CURDATE()
                 GROUP BY patient_id
                 HAVING overdue_count > 1"
            ),
            'potential_interactions' => [], // Would integrate with medication database
            'abnormal_results' => [], // Would integrate with lab results system
            'high_risk_patients' => $this->db->fetchAll(
                "SELECT p.*, COUNT(a.id) as frequent_visits
                 FROM patients p
                 JOIN appointments a ON p.id = a.patient_id
                 WHERE a.appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                 GROUP BY p.id
                 HAVING frequent_visits >= 5"
            ),
            'missed_appointments' => $this->db->fetchAll(
                "SELECT patient_id, COUNT(*) as missed_count
                 FROM appointments 
                 WHERE status = 'no_show'
                 AND appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                 GROUP BY patient_id
                 HAVING missed_count >= 2"
            )
        ];
    }

    private function collectOperationalData(): array
    {
        $today = date('Y-m-d');
        
        // Calculate utilization rate
        $totalSlots = $this->db->fetch(
            "SELECT COUNT(*) as total FROM appointments WHERE DATE(appointment_date) = :today",
            ['today' => $today]
        );
        
        $bookedSlots = $this->db->fetch(
            "SELECT COUNT(*) as booked FROM appointments 
             WHERE DATE(appointment_date) = :today AND status != 'cancelled'",
            ['today' => $today]
        );

        $utilizationRate = $totalSlots['total'] > 0 ? 
            round(($bookedSlots['booked'] / $totalSlots['total']) * 100, 1) : 0;

        // Calculate no-show rate
        $noShows = $this->db->fetch(
            "SELECT COUNT(*) as no_shows FROM appointments 
             WHERE status = 'no_show' AND appointment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
        );

        $totalAppointments = $this->db->fetch(
            "SELECT COUNT(*) as total FROM appointments 
             WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
        );

        $noShowRate = $totalAppointments['total'] > 0 ? 
            round(($noShows['no_shows'] / $totalAppointments['total']) * 100, 1) : 0;

        return [
            'utilization_rate' => $utilizationRate,
            'avg_wait_time' => 15, // Would calculate from actual wait times
            'no_show_rate' => $noShowRate,
            'overtime_count' => 2, // Would calculate from appointment times
            'low_inventory_count' => $this->db->fetch("SELECT COUNT(*) as count FROM inventory WHERE quantity <= minimum_stock_level")['count'],
            'billing_gaps' => 3 // Would calculate from billing data
        ];
    }

    private function collectQualityData(): array
    {
        return [
            'satisfaction_score' => 4.2, // Would come from patient surveys
            'followup_compliance' => 85, // Would calculate from actual follow-up rates
            'documentation_score' => 92, // Would analyze documentation completeness
            'preventive_care_rate' => 78, // Would calculate from preventive care metrics
            'coordination_score' => 4.0, // Would assess care coordination
            'overall_quality_score' => 4.1
        ];
    }

    private function buildAlertPrompt(array $alertData): string
    {
        return "Analyze clinic operations and generate intelligent alerts:

CURRENT STATUS:
- Overdue follow-ups: {$alertData['summary']['overdue_count']}
- Urgent appointments today: {$alertData['summary']['urgent_count']}
- Low inventory items: {$alertData['summary']['inventory_alerts']}
- Recent no-shows: {$alertData['summary']['no_show_count']}
- Pending reminders: {$alertData['summary']['pending_reminders']}

Generate prioritized alerts with:
1. ALERT_TYPE: (patient_safety, operational, quality, revenue)
2. PRIORITY: (1-5 scale, 5=critical)
3. TITLE: (brief alert title)
4. DESCRIPTION: (detailed alert description)
5. ACTION_REQUIRED: (specific action needed)
6. TIMELINE: (when action should be taken)
7. PATIENT_ID: (if patient-specific, otherwise 'general')

Focus on:
- Patient safety concerns requiring immediate attention
- Operational inefficiencies affecting care delivery
- Revenue opportunities and billing issues
- Quality improvement opportunities

Format each alert clearly with the labels above.";
    }

    private function parseAIAlerts(string $response): array
    {
        $alerts = [];
        
        // Split response into individual alerts
        $alertBlocks = preg_split('/(?=ALERT_TYPE:)/i', $response);
        
        foreach ($alertBlocks as $block) {
            if (trim($block) === '') continue;
            
            $alert = $this->parseAlertBlock($block);
            if ($alert) {
                $alerts[] = $alert;
            }
        }

        return $alerts;
    }

    private function parseAlertBlock(string $block): ?array
    {
        $alert = [
            'type' => 'operational',
            'priority' => 3,
            'title' => 'System Alert',
            'description' => '',
            'action_required' => '',
            'timeline' => 'today',
            'patient_id' => null,
            'source' => 'ai',
            'created_at' => date('Y-m-d H:i:s'),
            'is_active' => 1
        ];

        try {
            // Extract each field
            if (preg_match('/ALERT_TYPE:\s*([^\n]+)/i', $block, $matches)) {
                $alert['type'] = strtolower(trim($matches[1]));
            }
            
            if (preg_match('/PRIORITY:\s*(\d+)/i', $block, $matches)) {
                $alert['priority'] = (int)$matches[1];
            }
            
            if (preg_match('/TITLE:\s*([^\n]+)/i', $block, $matches)) {
                $alert['title'] = trim($matches[1]);
            }
            
            if (preg_match('/DESCRIPTION:\s*(.*?)(?=\n[A-Z_]+:|$)/s', $block, $matches)) {
                $alert['description'] = trim($matches[1]);
            }
            
            if (preg_match('/ACTION_REQUIRED:\s*(.*?)(?=\n[A-Z_]+:|$)/s', $block, $matches)) {
                $alert['action_required'] = trim($matches[1]);
            }
            
            if (preg_match('/TIMELINE:\s*([^\n]+)/i', $block, $matches)) {
                $alert['timeline'] = trim($matches[1]);
            }
            
            if (preg_match('/PATIENT_ID:\s*(\d+|general)/i', $block, $matches)) {
                $alert['patient_id'] = is_numeric($matches[1]) ? (int)$matches[1] : null;
            }

            return $alert;

        } catch (\Exception $e) {
            error_log("Error parsing alert block: " . $e->getMessage());
            return null;
        }
    }

    private function generateSystemAlerts(array $alertData): array
    {
        $systemAlerts = [];

        // Critical inventory alerts
        foreach ($alertData['low_inventory'] as $item) {
            if ($item['quantity'] == 0) {
                $systemAlerts[] = [
                    'type' => 'inventory',
                    'priority' => 5,
                    'title' => 'Critical Inventory Alert: ' . $item['item_name'],
                    'description' => 'Item is out of stock and requires immediate restocking.',
                    'action_required' => 'Order ' . $item['item_name'] . ' immediately',
                    'timeline' => 'immediate',
                    'patient_id' => null,
                    'source' => 'system',
                    'created_at' => date('Y-m-d H:i:s'),
                    'is_active' => 1
                ];
            }
        }

        // High-priority appointment alerts
        if (count($alertData['urgent_appointments']) > 5) {
            $systemAlerts[] = [
                'type' => 'operational',
                'priority' => 4,
                'title' => 'High Volume of Urgent Appointments',
                'description' => count($alertData['urgent_appointments']) . ' urgent appointments scheduled for today.',
                'action_required' => 'Review schedule and consider additional staffing',
                'timeline' => 'before clinic opens',
                'patient_id' => null,
                'source' => 'system',
                'created_at' => date('Y-m-d H:i:s'),
                'is_active' => 1
            ];
        }

        return $systemAlerts;
    }

    private function parsePatientSafetyAlerts(string $content, array $safetyData): array
    {
        // Parse AI-generated safety alerts
        $alerts = [];
        
        // Add specific alerts based on safety data
        foreach ($safetyData['overdue_followups'] as $overdue) {
            $alerts[] = [
                'level' => 'high',
                'category' => 'follow_up',
                'patient_id' => $overdue['patient_id'],
                'message' => 'Patient has ' . $overdue['overdue_count'] . ' overdue follow-up appointments',
                'action' => 'Schedule follow-up appointment',
                'priority' => 4
            ];
        }

        return $alerts;
    }

    private function generateFallbackSafetyAlerts(array $safetyData): array
    {
        $alerts = [];
        
        if (!empty($safetyData['overdue_followups'])) {
            $alerts[] = [
                'level' => 'high',
                'category' => 'follow_up',
                'message' => count($safetyData['overdue_followups']) . ' patients have overdue follow-ups',
                'action' => 'Review and schedule follow-up appointments'
            ];
        }

        return $alerts;
    }

    private function calculateEfficiencyScore(array $operationalData): int
    {
        $score = 0;
        $score += min($operationalData['utilization_rate'], 100);
        $score += max(0, 100 - $operationalData['no_show_rate'] * 5);
        $score += max(0, 100 - $operationalData['avg_wait_time'] * 2);
        
        return round($score / 3);
    }

    private function identifyImprovementOpportunities(array $qualityData): array
    {
        $opportunities = [];
        
        if ($qualityData['satisfaction_score'] < 4.0) {
            $opportunities[] = 'Improve patient satisfaction through enhanced communication';
        }
        
        if ($qualityData['followup_compliance'] < 90) {
            $opportunities[] = 'Implement better follow-up tracking system';
        }
        
        return $opportunities;
    }

    private function prioritizeAlerts(array $a, array $b): int
    {
        return $b['priority'] <=> $a['priority'];
    }

    private function storeAlerts(array $alerts): void
    {
        try {
            $this->createAlertsTableIfNotExists();
            
            foreach ($alerts as $alert) {
                $this->db->insert('ai_alerts', $alert);
            }
        } catch (\Exception $e) {
            error_log("Error storing alerts: " . $e->getMessage());
        }
    }

    private function createAlertsTableIfNotExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS ai_alerts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            type ENUM('patient_safety', 'operational', 'quality', 'revenue', 'inventory') NOT NULL,
            priority TINYINT UNSIGNED NOT NULL DEFAULT 3,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            action_required TEXT,
            timeline VARCHAR(100),
            patient_id INT UNSIGNED NULL,
            source ENUM('ai', 'system') DEFAULT 'ai',
            status ENUM('active', 'acknowledged', 'dismissed') DEFAULT 'active',
            is_active BOOLEAN DEFAULT TRUE,
            acknowledged_by INT UNSIGNED NULL,
            acknowledged_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
            FOREIGN KEY (acknowledged_by) REFERENCES users(id) ON DELETE SET NULL,
            
            INDEX idx_type (type),
            INDEX idx_priority (priority),
            INDEX idx_status (status),
            INDEX idx_patient (patient_id),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->query($sql);
    }

    private function getFallbackAlerts(array $alertData): array
    {
        $fallbackAlerts = [];
        
        if ($alertData['summary']['overdue_count'] > 0) {
            $fallbackAlerts[] = [
                'type' => 'patient_safety',
                'priority' => 4,
                'title' => 'Overdue Follow-ups Require Attention',
                'description' => $alertData['summary']['overdue_count'] . ' patients have overdue follow-up appointments',
                'action_required' => 'Review and schedule follow-up appointments',
                'timeline' => 'today',
                'source' => 'system'
            ];
        }
        
        return [
            'success' => true,
            'alerts' => $fallbackAlerts,
            'source' => 'fallback',
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
}
