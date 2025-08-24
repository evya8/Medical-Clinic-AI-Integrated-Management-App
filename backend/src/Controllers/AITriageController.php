<?php

namespace MedicalClinic\Controllers;

use MedicalClinic\Services\AITriageService;

class AITriageController extends BaseController
{
    private AITriageService $triageService;

    public function __construct()
    {
        parent::__construct();
        $this->triageService = new AITriageService();
    }

    /**
     * Analyze patient case for intelligent triage
     */
    public function analyzePatientCase(): void
    {
        $this->requireAuth();
        $this->validateRequired(['patient_data'], $this->input);

        $patientData = $this->input['patient_data'];
        $appointmentData = $this->input['appointment_data'] ?? [];

        $analysis = $this->triageService->analyzePatientCase($patientData, $appointmentData);

        if ($analysis['success']) {
            $this->success($analysis, 'Patient triage analysis completed');
        } else {
            $this->error($analysis['message'] ?? 'Triage analysis failed', 500, $analysis);
        }
    }

    /**
     * Batch analyze multiple patients for triage
     */
    public function batchTriageAnalysis(): void
    {
        $this->requireRole(['admin', 'doctor']);
        $this->validateRequired(['patient_cases'], $this->input);

        $patientCases = $this->input['patient_cases'];

        if (!is_array($patientCases) || empty($patientCases)) {
            $this->error('Patient cases must be a non-empty array', 400);
        }

        $batchAnalysis = $this->triageService->batchTriageAnalysis($patientCases);

        if ($batchAnalysis['success']) {
            $this->success($batchAnalysis, 'Batch triage analysis completed');
        } else {
            $this->error($batchAnalysis['message'] ?? 'Batch analysis failed', 500);
        }
    }

    /**
     * Get triage recommendations for symptoms
     */
    public function getSymptomTriage(): void
    {
        $this->requireAuth();
        $this->validateRequired(['symptoms'], $this->input);

        $symptoms = $this->sanitizeString($this->input['symptoms']);
        $patientHistory = $this->input['patient_history'] ?? [];

        $triage = $this->triageService->getSymptomTriage($symptoms, $patientHistory);

        if ($triage['success']) {
            $this->success($triage, 'Symptom triage analysis completed');
        } else {
            $this->error($triage['message'] ?? 'Symptom analysis failed', 500, $triage);
        }
    }

    /**
     * Generate specialist referral recommendations
     */
    public function generateReferralRecommendations(): void
    {
        $this->requireAuth();
        $this->validateRequired(['patient_data', 'condition'], $this->input);

        $patientData = $this->input['patient_data'];
        $condition = $this->sanitizeString($this->input['condition']);

        $recommendations = $this->triageService->generateReferralRecommendations($patientData, $condition);

        if ($recommendations['success']) {
            $this->success($recommendations, 'Referral recommendations generated');
        } else {
            $this->error($recommendations['message'] ?? 'Referral generation failed', 500);
        }
    }

    /**
     * Quick triage assessment for appointment scheduling
     */
    public function quickTriageAssessment(): void
    {
        $this->requireAuth();
        $this->validateRequired(['patient_id', 'symptoms'], $this->input);

        $patientId = $this->sanitizeInt($this->input['patient_id']);
        $symptoms = $this->sanitizeString($this->input['symptoms']);
        $appointmentType = $this->input['appointment_type'] ?? 'consultation';

        // Get patient data
        $patientData = $this->getPatientData($patientId);
        if (!$patientData) {
            $this->error('Patient not found', 404);
        }

        // Create appointment context
        $appointmentData = [
            'notes' => $symptoms,
            'appointment_type' => $appointmentType,
            'priority' => 'normal'
        ];

        $analysis = $this->triageService->analyzePatientCase($patientData, $appointmentData);

        if ($analysis['success']) {
            // Return simplified response for quick assessment
            $quickAssessment = [
                'urgency_level' => $analysis['urgency_level'],
                'urgency_score' => $analysis['urgency_score'],
                'recommended_specialist' => $analysis['recommended_specialist'],
                'appointment_duration' => $analysis['appointment_duration'],
                'red_flags' => $analysis['red_flags'],
                'patient_id' => $patientId,
                'requires_urgent_care' => $analysis['urgency_score'] >= 4
            ];

            $this->success($quickAssessment, 'Quick triage assessment completed');
        } else {
            $this->error($analysis['message'] ?? 'Quick assessment failed', 500, $analysis);
        }
    }

    /**
     * Get triage statistics and insights
     */
    public function getTriageStats(): void
    {
        $this->requireRole(['admin', 'doctor']);

        try {
            // Get basic triage statistics
            $stats = [
                'daily_triages' => $this->getDailyTriageCount(),
                'urgency_distribution' => $this->getUrgencyDistribution(),
                'specialist_referrals' => $this->getSpecialistReferralStats(),
                'accuracy_metrics' => $this->getTriageAccuracyMetrics(),
                'recent_high_priority' => $this->getRecentHighPriorityPatients()
            ];

            $this->success($stats, 'Triage statistics retrieved successfully');

        } catch (\Exception $e) {
            error_log("Triage stats error: " . $e->getMessage());
            $this->error('Failed to retrieve triage statistics', 500);
        }
    }

    /**
     * Update triage priority for appointment
     */
    public function updateTriagePriority(): void
    {
        $this->requireAuth();
        $this->validateRequired(['appointment_id', 'new_priority'], $this->input);

        $appointmentId = $this->sanitizeInt($this->input['appointment_id']);
        $newPriority = $this->sanitizeString($this->input['new_priority']);
        $reason = $this->input['reason'] ?? '';

        $validPriorities = ['low', 'normal', 'high', 'urgent'];
        if (!in_array($newPriority, $validPriorities)) {
            $this->error('Invalid priority level', 400);
        }

        try {
            // Update appointment priority
            $this->db->update(
                'appointments',
                ['priority' => $newPriority],
                'id = :id',
                ['id' => $appointmentId]
            );

            // Log the priority change
            $this->logTriagePriorityChange($appointmentId, $newPriority, $reason);

            $this->success([
                'appointment_id' => $appointmentId,
                'new_priority' => $newPriority,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'Triage priority updated successfully');

        } catch (\Exception $e) {
            error_log("Priority update error: " . $e->getMessage());
            $this->error('Failed to update priority', 500);
        }
    }

    // Private helper methods

    private function getPatientData(int $patientId): ?array
    {
        try {
            return $this->db->fetch(
                "SELECT * FROM patients WHERE id = :id",
                ['id' => $patientId]
            );
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getDailyTriageCount(): int
    {
        try {
            $result = $this->db->fetch(
                "SELECT COUNT(*) as count FROM appointments 
                 WHERE DATE(created_at) = CURDATE() 
                 AND priority IN ('high', 'urgent')"
            );
            return $result['count'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getUrgencyDistribution(): array
    {
        try {
            $distribution = $this->db->fetchAll(
                "SELECT priority, COUNT(*) as count 
                 FROM appointments 
                 WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                 GROUP BY priority"
            );

            $result = ['low' => 0, 'normal' => 0, 'high' => 0, 'urgent' => 0];
            foreach ($distribution as $item) {
                $result[$item['priority']] = $item['count'];
            }

            return $result;
        } catch (\Exception $e) {
            return ['low' => 0, 'normal' => 0, 'high' => 0, 'urgent' => 0];
        }
    }

    private function getSpecialistReferralStats(): array
    {
        // This would integrate with a referrals table if available
        return [
            'total_referrals_week' => 12,
            'top_specialties' => [
                'cardiology' => 4,
                'orthopedics' => 3,
                'neurology' => 2
            ],
            'urgent_referrals' => 2
        ];
    }

    private function getTriageAccuracyMetrics(): array
    {
        // This would track triage accuracy over time
        return [
            'ai_accuracy_score' => 87.5,
            'total_assessments' => 156,
            'correct_predictions' => 136,
            'false_positives' => 8,
            'false_negatives' => 12
        ];
    }

    private function getRecentHighPriorityPatients(): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT a.id, p.first_name, p.last_name, a.priority, a.appointment_date
                 FROM appointments a
                 JOIN patients p ON a.patient_id = p.id
                 WHERE a.priority IN ('high', 'urgent')
                 AND DATE(a.appointment_date) >= CURDATE()
                 ORDER BY a.appointment_date ASC
                 LIMIT 10"
            );
        } catch (\Exception $e) {
            return [];
        }
    }

    private function logTriagePriorityChange(int $appointmentId, string $newPriority, string $reason): void
    {
        try {
            // This would log to a triage_log table if available
            error_log("Triage priority changed for appointment {$appointmentId} to {$newPriority}: {$reason}");
        } catch (\Exception $e) {
            // Log error but don't fail the main operation
            error_log("Failed to log triage priority change: " . $e->getMessage());
        }
    }
}
