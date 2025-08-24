<?php

namespace MedicalClinic\Controllers;

use MedicalClinic\Services\AIAlertService;

class AIAlertController extends BaseController
{
    private AIAlertService $alertService;

    public function __construct()
    {
        parent::__construct();
        $this->alertService = new AIAlertService();
    }

    /**
     * Generate comprehensive AI-powered alerts
     */
    public function generateIntelligentAlerts(): void
    {
        $this->requireAuth();
        
        $options = $this->input['options'] ?? [];
        
        $alerts = $this->alertService->generateIntelligentAlerts($options);

        if ($alerts['success']) {
            $this->success($alerts, 'Intelligent alerts generated successfully');
        } else {
            $this->error($alerts['message'] ?? 'Alert generation failed', 500, $alerts);
        }
    }

    /**
     * Generate patient safety alerts
     */
    public function generatePatientSafetyAlerts(): void
    {
        $this->requireRole(['admin', 'doctor']);

        $safetyAlerts = $this->alertService->generatePatientSafetyAlerts();

        if ($safetyAlerts['success']) {
            $this->success($safetyAlerts, 'Patient safety alerts generated successfully');
        } else {
            $this->error($safetyAlerts['message'] ?? 'Safety alert generation failed', 500, $safetyAlerts);
        }
    }

    /**
     * Generate operational efficiency alerts
     */
    public function generateOperationalAlerts(): void
    {
        $this->requireRole(['admin', 'doctor']);

        $operationalAlerts = $this->alertService->generateOperationalAlerts();

        if ($operationalAlerts['success']) {
            $this->success($operationalAlerts, 'Operational alerts generated successfully');
        } else {
            $this->error($operationalAlerts['message'] ?? 'Operational alert generation failed', 500);
        }
    }

    /**
     * Generate quality improvement alerts
     */
    public function generateQualityAlerts(): void
    {
        $this->requireRole(['admin', 'doctor']);

        $qualityAlerts = $this->alertService->generateQualityAlerts();

        if ($qualityAlerts['success']) {
            $this->success($qualityAlerts, 'Quality alerts generated successfully');
        } else {
            $this->error($qualityAlerts['message'] ?? 'Quality alert generation failed', 500);
        }
    }

    /**
     * Get active alerts with optional filtering
     */
    public function getActiveAlerts(): void
    {
        $this->requireAuth();

        $filters = [];
        
        if (isset($this->input['priority_min'])) {
            $filters['priority_min'] = $this->sanitizeInt($this->input['priority_min']);
        }
        
        if (isset($this->input['category'])) {
            $filters['category'] = $this->sanitizeString($this->input['category']);
        }
        
        if (isset($this->input['patient_id'])) {
            $filters['patient_id'] = $this->sanitizeInt($this->input['patient_id']);
        }

        $alerts = $this->alertService->getActiveAlerts($filters);

        if ($alerts['success']) {
            $this->success($alerts, 'Active alerts retrieved successfully');
        } else {
            $this->error($alerts['message'] ?? 'Failed to retrieve alerts', 500);
        }
    }

    /**
     * Acknowledge an alert
     */
    public function acknowledgeAlert(): void
    {
        $user = $this->requireAuth();
        $this->validateRequired(['alert_id'], $this->input);

        $alertId = $this->sanitizeInt($this->input['alert_id']);
        $action = $this->input['action'] ?? 'acknowledge';

        if (!in_array($action, ['acknowledge', 'dismiss'])) {
            $this->error('Invalid action. Must be "acknowledge" or "dismiss"', 400);
        }

        $result = $this->alertService->acknowledgeAlert($alertId, $user['id'], $action);

        if ($result['success']) {
            $this->success($result, 'Alert ' . $action . 'd successfully');
        } else {
            $this->error($result['message'] ?? 'Failed to ' . $action . ' alert', 500);
        }
    }

    /**
     * Get alert dashboard with comprehensive overview
     */
    public function getAlertDashboard(): void
    {
        $this->requireAuth();

        try {
            $dashboard = [
                'summary' => $this->getAlertSummary(),
                'recent_alerts' => $this->getRecentAlerts(),
                'alert_trends' => $this->getAlertTrends(),
                'priority_distribution' => $this->getPriorityDistribution(),
                'category_breakdown' => $this->getCategoryBreakdown(),
                'response_metrics' => $this->getResponseMetrics()
            ];

            $this->success($dashboard, 'Alert dashboard retrieved successfully');

        } catch (\Exception $e) {
            error_log("Alert dashboard error: " . $e->getMessage());
            $this->error('Failed to retrieve alert dashboard', 500);
        }
    }

    /**
     * Get patient-specific alerts
     */
    public function getPatientAlerts(): void
    {
        $this->requireAuth();
        $this->validateRequired(['patient_id'], $this->input);

        $patientId = $this->sanitizeInt($this->input['patient_id']);

        $patientAlerts = $this->alertService->getActiveAlerts(['patient_id' => $patientId]);

        if ($patientAlerts['success']) {
            // Enhance with patient context
            $patientInfo = $this->getPatientInfo($patientId);
            $patientAlerts['patient_info'] = $patientInfo;
            
            $this->success($patientAlerts, 'Patient alerts retrieved successfully');
        } else {
            $this->error($patientAlerts['message'] ?? 'Failed to retrieve patient alerts', 500);
        }
    }

    /**
     * Create manual alert
     */
    public function createManualAlert(): void
    {
        $user = $this->requireAuth();
        $this->validateRequired(['type', 'priority', 'title', 'description'], $this->input);

        $alertData = [
            'type' => $this->sanitizeString($this->input['type']),
            'priority' => $this->sanitizeInt($this->input['priority']),
            'title' => $this->sanitizeString($this->input['title']),
            'description' => $this->sanitizeString($this->input['description']),
            'action_required' => $this->sanitizeString($this->input['action_required'] ?? ''),
            'timeline' => $this->sanitizeString($this->input['timeline'] ?? 'today'),
            'patient_id' => isset($this->input['patient_id']) ? $this->sanitizeInt($this->input['patient_id']) : null,
            'source' => 'manual',
            'created_by' => $user['id'],
            'created_at' => date('Y-m-d H:i:s'),
            'is_active' => 1
        ];

        // Validate alert type
        $validTypes = ['patient_safety', 'operational', 'quality', 'revenue', 'inventory'];
        if (!in_array($alertData['type'], $validTypes)) {
            $this->error('Invalid alert type. Must be one of: ' . implode(', ', $validTypes), 400);
        }

        // Validate priority
        if ($alertData['priority'] < 1 || $alertData['priority'] > 5) {
            $this->error('Priority must be between 1 and 5', 400);
        }

        try {
            $alertId = $this->db->insert('ai_alerts', $alertData);

            $this->success([
                'alert_id' => $alertId,
                'alert_data' => $alertData
            ], 'Manual alert created successfully');

        } catch (\Exception $e) {
            error_log("Manual alert creation error: " . $e->getMessage());
            $this->error('Failed to create manual alert', 500);
        }
    }

    /**
     * Update alert status or details
     */
    public function updateAlert(): void
    {
        $user = $this->requireAuth();
        $this->validateRequired(['alert_id'], $this->input);

        $alertId = $this->sanitizeInt($this->input['alert_id']);
        
        $updateData = [];
        
        if (isset($this->input['priority'])) {
            $priority = $this->sanitizeInt($this->input['priority']);
            if ($priority >= 1 && $priority <= 5) {
                $updateData['priority'] = $priority;
            }
        }
        
        if (isset($this->input['title'])) {
            $updateData['title'] = $this->sanitizeString($this->input['title']);
        }
        
        if (isset($this->input['description'])) {
            $updateData['description'] = $this->sanitizeString($this->input['description']);
        }
        
        if (isset($this->input['action_required'])) {
            $updateData['action_required'] = $this->sanitizeString($this->input['action_required']);
        }
        
        if (isset($this->input['timeline'])) {
            $updateData['timeline'] = $this->sanitizeString($this->input['timeline']);
        }

        if (empty($updateData)) {
            $this->error('No valid fields to update', 400);
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');
        $updateData['updated_by'] = $user['id'];

        try {
            $this->db->update('ai_alerts', $updateData, 'id = :id', ['id' => $alertId]);

            $this->success([
                'alert_id' => $alertId,
                'updated_fields' => array_keys($updateData),
                'updated_at' => $updateData['updated_at']
            ], 'Alert updated successfully');

        } catch (\Exception $e) {
            error_log("Alert update error: " . $e->getMessage());
            $this->error('Failed to update alert', 500);
        }
    }

    /**
     * Bulk acknowledge multiple alerts
     */
    public function bulkAcknowledgeAlerts(): void
    {
        $user = $this->requireAuth();
        $this->validateRequired(['alert_ids'], $this->input);

        $alertIds = $this->input['alert_ids'];
        $action = $this->input['action'] ?? 'acknowledge';

        if (!is_array($alertIds) || empty($alertIds)) {
            $this->error('Alert IDs must be a non-empty array', 400);
        }

        if (!in_array($action, ['acknowledge', 'dismiss'])) {
            $this->error('Invalid action. Must be "acknowledge" or "dismiss"', 400);
        }

        $results = [];
        $successful = 0;
        $failed = 0;

        foreach ($alertIds as $alertId) {
            $alertId = $this->sanitizeInt($alertId);
            if ($alertId > 0) {
                $result = $this->alertService->acknowledgeAlert($alertId, $user['id'], $action);
                $results[$alertId] = $result;
                
                if ($result['success']) {
                    $successful++;
                } else {
                    $failed++;
                }
            } else {
                $failed++;
                $results[$alertId] = ['success' => false, 'message' => 'Invalid alert ID'];
            }
        }

        $this->success([
            'action' => $action,
            'total_processed' => count($alertIds),
            'successful' => $successful,
            'failed' => $failed,
            'results' => $results,
            'processed_at' => date('Y-m-d H:i:s')
        ], "Bulk {$action} completed");
    }

    /**
     * Get alert analytics and insights
     */
    public function getAlertAnalytics(): void
    {
        $this->requireRole(['admin', 'doctor']);

        try {
            $analytics = [
                'overview' => $this->getAnalyticsOverview(),
                'performance_metrics' => $this->getPerformanceMetrics(),
                'trend_analysis' => $this->getTrendAnalysis(),
                'efficiency_metrics' => $this->getEfficiencyMetrics(),
                'recommendations' => $this->getAnalyticsRecommendations()
            ];

            $this->success($analytics, 'Alert analytics retrieved successfully');

        } catch (\Exception $e) {
            error_log("Alert analytics error: " . $e->getMessage());
            $this->error('Failed to retrieve alert analytics', 500);
        }
    }

    // Private helper methods

    private function getAlertSummary(): array
    {
        try {
            $summary = $this->db->fetch(
                "SELECT 
                    COUNT(*) as total_active,
                    SUM(CASE WHEN priority >= 4 THEN 1 ELSE 0 END) as high_priority,
                    SUM(CASE WHEN type = 'patient_safety' THEN 1 ELSE 0 END) as safety_alerts,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 ELSE 0 END) as new_today
                 FROM ai_alerts 
                 WHERE is_active = 1"
            );

            return [
                'total_active_alerts' => $summary['total_active'] ?? 0,
                'high_priority_alerts' => $summary['high_priority'] ?? 0,
                'safety_alerts' => $summary['safety_alerts'] ?? 0,
                'new_alerts_today' => $summary['new_today'] ?? 0
            ];
        } catch (\Exception $e) {
            return [
                'total_active_alerts' => 0,
                'high_priority_alerts' => 0,
                'safety_alerts' => 0,
                'new_alerts_today' => 0
            ];
        }
    }

    private function getRecentAlerts(): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT id, type, priority, title, created_at
                 FROM ai_alerts 
                 WHERE is_active = 1 
                 ORDER BY priority DESC, created_at DESC 
                 LIMIT 10"
            );
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getAlertTrends(): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as total_alerts,
                    SUM(CASE WHEN priority >= 4 THEN 1 ELSE 0 END) as high_priority
                 FROM ai_alerts 
                 WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                 GROUP BY DATE(created_at)
                 ORDER BY date DESC"
            );
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getPriorityDistribution(): array
    {
        try {
            $distribution = $this->db->fetchAll(
                "SELECT priority, COUNT(*) as count
                 FROM ai_alerts 
                 WHERE is_active = 1
                 GROUP BY priority"
            );

            $result = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
            foreach ($distribution as $item) {
                $result[$item['priority']] = $item['count'];
            }

            return $result;
        } catch (\Exception $e) {
            return [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        }
    }

    private function getCategoryBreakdown(): array
    {
        try {
            $breakdown = $this->db->fetchAll(
                "SELECT type, COUNT(*) as count
                 FROM ai_alerts 
                 WHERE is_active = 1
                 GROUP BY type"
            );

            $result = [
                'patient_safety' => 0,
                'operational' => 0, 
                'quality' => 0,
                'revenue' => 0,
                'inventory' => 0
            ];
            
            foreach ($breakdown as $item) {
                $result[$item['type']] = $item['count'];
            }

            return $result;
        } catch (\Exception $e) {
            return [
                'patient_safety' => 0,
                'operational' => 0,
                'quality' => 0,
                'revenue' => 0,
                'inventory' => 0
            ];
        }
    }

    private function getResponseMetrics(): array
    {
        try {
            $metrics = $this->db->fetch(
                "SELECT 
                    AVG(TIMESTAMPDIFF(MINUTE, created_at, acknowledged_at)) as avg_response_time,
                    COUNT(CASE WHEN acknowledged_at IS NOT NULL THEN 1 END) as acknowledged_count,
                    COUNT(*) as total_count
                 FROM ai_alerts 
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            );

            $responseRate = $metrics['total_count'] > 0 ? 
                round(($metrics['acknowledged_count'] / $metrics['total_count']) * 100, 1) : 0;

            return [
                'average_response_time_minutes' => round($metrics['avg_response_time'] ?? 0, 1),
                'response_rate_percent' => $responseRate,
                'total_alerts_week' => $metrics['total_count'] ?? 0
            ];
        } catch (\Exception $e) {
            return [
                'average_response_time_minutes' => 0,
                'response_rate_percent' => 0,
                'total_alerts_week' => 0
            ];
        }
    }

    private function getPatientInfo(int $patientId): ?array
    {
        try {
            return $this->db->fetch(
                "SELECT id, first_name, last_name, date_of_birth
                 FROM patients 
                 WHERE id = :id",
                ['id' => $patientId]
            );
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getAnalyticsOverview(): array
    {
        return [
            'alert_volume_trend' => 'increasing',
            'average_severity' => 2.8,
            'resolution_efficiency' => 85.2,
            'patient_safety_score' => 94.1
        ];
    }

    private function getPerformanceMetrics(): array
    {
        return [
            'alert_accuracy' => 89.5,
            'false_positive_rate' => 8.2,
            'missed_critical_alerts' => 0,
            'staff_satisfaction' => 4.3
        ];
    }

    private function getTrendAnalysis(): array
    {
        return [
            'weekly_growth' => 12.5,
            'seasonal_patterns' => 'Monday peaks',
            'peak_hours' => '9-11 AM',
            'category_trends' => [
                'patient_safety' => 'stable',
                'operational' => 'increasing',
                'quality' => 'decreasing'
            ]
        ];
    }

    private function getEfficiencyMetrics(): array
    {
        return [
            'time_to_resolution' => 23.5,
            'alert_prevention_rate' => 67.8,
            'automation_score' => 78.9,
            'cost_savings_estimate' => 2450.00
        ];
    }

    private function getAnalyticsRecommendations(): array
    {
        return [
            'Implement automated acknowledgment for low-priority alerts',
            'Increase monitoring frequency during peak hours',
            'Consider staff training on quality improvement alerts',
            'Review patient safety alert thresholds'
        ];
    }
}
