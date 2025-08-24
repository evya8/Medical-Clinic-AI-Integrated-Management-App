<?php

namespace MedicalClinic\Controllers;

use MedicalClinic\Services\AIAppointmentSummaryService;

class AIAppointmentSummaryController extends BaseController
{
    private AIAppointmentSummaryService $summaryService;

    public function __construct()
    {
        parent::__construct();
        $this->summaryService = new AIAppointmentSummaryService();
    }

    /**
     * Generate comprehensive appointment summary
     */
    public function generateAppointmentSummary(): void
    {
        $this->requireAuth();
        $this->validateRequired(['appointment_id'], $this->input);

        $appointmentId = $this->sanitizeInt($this->input['appointment_id']);
        $appointmentData = $this->input['appointment_data'] ?? [];

        $summary = $this->summaryService->generateAppointmentSummary($appointmentId, $appointmentData);

        if ($summary['success']) {
            $this->success($summary, 'Appointment summary generated successfully');
        } else {
            $this->error($summary['message'] ?? 'Summary generation failed', 500, $summary);
        }
    }

    /**
     * Generate SOAP note format summary
     */
    public function generateSOAPNote(): void
    {
        $this->requireAuth();
        $this->validateRequired(['appointment_id', 'soap_data'], $this->input);

        $appointmentId = $this->sanitizeInt($this->input['appointment_id']);
        $soapData = $this->input['soap_data'];

        // Validate SOAP data structure
        $requiredSoapFields = ['subjective', 'objective', 'assessment', 'plan'];
        foreach ($requiredSoapFields as $field) {
            if (!isset($soapData[$field])) {
                $this->error("Missing required SOAP field: {$field}", 400);
            }
        }

        $soapNote = $this->summaryService->generateSOAPNote($appointmentId, $soapData);

        if ($soapNote['success']) {
            $this->success($soapNote, 'SOAP note generated successfully');
        } else {
            $this->error($soapNote['message'] ?? 'SOAP note generation failed', 500, $soapNote);
        }
    }

    /**
     * Generate billing-focused summary
     */
    public function generateBillingSummary(): void
    {
        $this->requireAuth();
        $this->validateRequired(['appointment_id'], $this->input);

        $appointmentId = $this->sanitizeInt($this->input['appointment_id']);
        $appointmentData = $this->input['appointment_data'] ?? [];

        $billingSummary = $this->summaryService->generateBillingSummary($appointmentId, $appointmentData);

        if ($billingSummary['success']) {
            $this->success($billingSummary, 'Billing summary generated successfully');
        } else {
            $this->error($billingSummary['message'] ?? 'Billing summary generation failed', 500);
        }
    }

    /**
     * Generate patient-friendly summary
     */
    public function generatePatientSummary(): void
    {
        $this->requireAuth();
        $this->validateRequired(['appointment_id'], $this->input);

        $appointmentId = $this->sanitizeInt($this->input['appointment_id']);
        $appointmentData = $this->input['appointment_data'] ?? [];

        $patientSummary = $this->summaryService->generatePatientSummary($appointmentId, $appointmentData);

        if ($patientSummary['success']) {
            $this->success($patientSummary, 'Patient summary generated successfully');
        } else {
            $this->error($patientSummary['message'] ?? 'Patient summary generation failed', 500);
        }
    }

    /**
     * Batch generate summaries for multiple appointments
     */
    public function batchGenerateSummaries(): void
    {
        $this->requireRole(['admin', 'doctor']);
        $this->validateRequired(['appointment_ids'], $this->input);

        $appointmentIds = $this->input['appointment_ids'];
        $summaryType = $this->input['summary_type'] ?? 'standard';

        if (!is_array($appointmentIds) || empty($appointmentIds)) {
            $this->error('Appointment IDs must be a non-empty array', 400);
        }

        // Validate appointment IDs
        $appointmentIds = array_map([$this, 'sanitizeInt'], $appointmentIds);
        $appointmentIds = array_filter($appointmentIds, fn($id) => $id > 0);

        if (empty($appointmentIds)) {
            $this->error('No valid appointment IDs provided', 400);
        }

        $validSummaryTypes = ['standard', 'soap', 'billing', 'patient'];
        if (!in_array($summaryType, $validSummaryTypes)) {
            $this->error('Invalid summary type. Must be one of: ' . implode(', ', $validSummaryTypes), 400);
        }

        $batchResults = $this->summaryService->batchGenerateSummaries($appointmentIds, $summaryType);

        if ($batchResults['success']) {
            $this->success($batchResults, 'Batch summary generation completed');
        } else {
            $this->error($batchResults['message'] ?? 'Batch generation failed', 500);
        }
    }

    /**
     * Get existing appointment summary
     */
    public function getAppointmentSummary(?int $appointmentId = null): void
    {
        $this->requireAuth();
        
        // Get appointment ID from URL parameter or request body
        if ($appointmentId === null) {
            $this->validateRequired(['appointment_id'], $this->input);
            $appointmentId = $this->sanitizeInt($this->input['appointment_id']);
        }

        try {
            $summary = $this->db->fetch(
                "SELECT * FROM appointment_summaries 
                 WHERE appointment_id = :appointment_id 
                 ORDER BY created_at DESC 
                 LIMIT 1",
                ['appointment_id' => $appointmentId]
            );

            if ($summary) {
                // Decode the JSON summary
                $summary['structured_summary'] = json_decode($summary['structured_summary'], true);
                
                $this->success([
                    'appointment_id' => $appointmentId,
                    'summary' => $summary,
                    'has_summary' => true
                ], 'Appointment summary retrieved successfully');
            } else {
                $this->success([
                    'appointment_id' => $appointmentId,
                    'summary' => null,
                    'has_summary' => false
                ], 'No summary found for this appointment');
            }

        } catch (\Exception $e) {
            error_log("Error fetching appointment summary: " . $e->getMessage());
            $this->error('Failed to retrieve appointment summary', 500);
        }
    }

    /**
     * Get summary statistics and analytics
     */
    public function getSummaryStats(): void
    {
        $this->requireRole(['admin', 'doctor']);

        try {
            $stats = [
                'total_summaries' => $this->getTotalSummariesCount(),
                'summaries_by_type' => $this->getSummariesByType(),
                'recent_activity' => $this->getRecentSummaryActivity(),
                'ai_performance' => $this->getAIPerformanceMetrics(),
                'cost_analysis' => $this->getCostAnalysis()
            ];

            $this->success($stats, 'Summary statistics retrieved successfully');

        } catch (\Exception $e) {
            error_log("Summary stats error: " . $e->getMessage());
            $this->error('Failed to retrieve summary statistics', 500);
        }
    }

    /**
     * Update or regenerate existing summary
     */
    public function updateSummary(): void
    {
        $this->requireAuth();
        $this->validateRequired(['appointment_id'], $this->input);

        $appointmentId = $this->sanitizeInt($this->input['appointment_id']);
        $summaryType = $this->input['summary_type'] ?? 'standard';
        $forceRegenerate = $this->input['force_regenerate'] ?? false;

        try {
            // Check if summary exists
            $existingSummary = $this->db->fetch(
                "SELECT id FROM appointment_summaries WHERE appointment_id = :appointment_id",
                ['appointment_id' => $appointmentId]
            );

            if ($existingSummary && !$forceRegenerate) {
                $this->error('Summary already exists. Use force_regenerate=true to overwrite.', 409);
            }

            // Generate new summary
            switch ($summaryType) {
                case 'soap':
                    if (!isset($this->input['soap_data'])) {
                        $this->error('SOAP data required for SOAP note generation', 400);
                    }
                    $result = $this->summaryService->generateSOAPNote($appointmentId, $this->input['soap_data']);
                    break;
                case 'billing':
                    $result = $this->summaryService->generateBillingSummary($appointmentId);
                    break;
                case 'patient':
                    $result = $this->summaryService->generatePatientSummary($appointmentId);
                    break;
                default:
                    $result = $this->summaryService->generateAppointmentSummary($appointmentId);
                    break;
            }

            if ($result['success']) {
                // If updating existing, mark old as inactive
                if ($existingSummary) {
                    $this->db->update(
                        'appointment_summaries',
                        ['is_active' => 0],
                        'appointment_id = :appointment_id',
                        ['appointment_id' => $appointmentId]
                    );
                }

                $this->success($result, 'Summary updated successfully');
            } else {
                $this->error($result['message'] ?? 'Summary update failed', 500);
            }

        } catch (\Exception $e) {
            error_log("Summary update error: " . $e->getMessage());
            $this->error('Failed to update summary', 500);
        }
    }

    /**
     * Export summary in different formats
     */
    public function exportSummary(): void
    {
        $this->requireAuth();
        $this->validateRequired(['appointment_id'], $this->input);

        $appointmentId = $this->sanitizeInt($this->input['appointment_id']);
        $format = $this->input['format'] ?? 'json';
        $includePatientInfo = $this->input['include_patient_info'] ?? true;

        $validFormats = ['json', 'pdf', 'html', 'text'];
        if (!in_array($format, $validFormats)) {
            $this->error('Invalid format. Must be one of: ' . implode(', ', $validFormats), 400);
        }

        try {
            // Get summary data
            $summary = $this->db->fetch(
                "SELECT s.*, a.appointment_date, a.start_time, a.end_time
                 FROM appointment_summaries s
                 JOIN appointments a ON s.appointment_id = a.id
                 WHERE s.appointment_id = :appointment_id
                 ORDER BY s.created_at DESC 
                 LIMIT 1",
                ['appointment_id' => $appointmentId]
            );

            if (!$summary) {
                $this->error('No summary found for this appointment', 404);
            }

            // Get patient info if requested
            $patientInfo = null;
            if ($includePatientInfo) {
                $patientInfo = $this->db->fetch(
                    "SELECT p.first_name, p.last_name, p.date_of_birth
                     FROM patients p
                     JOIN appointments a ON p.id = a.patient_id
                     WHERE a.id = :appointment_id",
                    ['appointment_id' => $appointmentId]
                );
            }

            $exportData = [
                'appointment_id' => $appointmentId,
                'summary' => json_decode($summary['structured_summary'], true),
                'summary_type' => $summary['summary_type'],
                'appointment_date' => $summary['appointment_date'],
                'appointment_time' => $summary['start_time'] . ' - ' . $summary['end_time'],
                'patient' => $patientInfo,
                'generated_at' => $summary['created_at'],
                'export_format' => $format,
                'exported_at' => date('Y-m-d H:i:s')
            ];

            // Format based on requested format
            switch ($format) {
                case 'json':
                    $this->success($exportData, 'Summary exported as JSON');
                    break;
                case 'text':
                    $textSummary = $this->formatSummaryAsText($exportData);
                    $this->success(['text_summary' => $textSummary], 'Summary exported as text');
                    break;
                case 'html':
                    $htmlSummary = $this->formatSummaryAsHTML($exportData);
                    $this->success(['html_summary' => $htmlSummary], 'Summary exported as HTML');
                    break;
                case 'pdf':
                    // PDF generation would require additional library
                    $this->success(['pdf_url' => '/api/summaries/pdf/' . $appointmentId], 'PDF export initiated');
                    break;
            }

        } catch (\Exception $e) {
            error_log("Export summary error: " . $e->getMessage());
            $this->error('Failed to export summary', 500);
        }
    }

    // Private helper methods

    private function getTotalSummariesCount(): int
    {
        try {
            $result = $this->db->fetch("SELECT COUNT(*) as count FROM appointment_summaries");
            return $result['count'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getSummariesByType(): array
    {
        try {
            $results = $this->db->fetchAll(
                "SELECT summary_type, COUNT(*) as count 
                 FROM appointment_summaries 
                 GROUP BY summary_type"
            );

            $byType = ['standard' => 0, 'soap' => 0, 'billing' => 0, 'patient' => 0];
            foreach ($results as $result) {
                $byType[$result['summary_type']] = $result['count'];
            }

            return $byType;
        } catch (\Exception $e) {
            return ['standard' => 0, 'soap' => 0, 'billing' => 0, 'patient' => 0];
        }
    }

    private function getRecentSummaryActivity(): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT DATE(created_at) as date, COUNT(*) as count
                 FROM appointment_summaries 
                 WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                 GROUP BY DATE(created_at)
                 ORDER BY date DESC"
            );
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getAIPerformanceMetrics(): array
    {
        try {
            $metrics = $this->db->fetch(
                "SELECT 
                    AVG(tokens_used) as avg_tokens,
                    AVG(response_time_ms) as avg_response_time,
                    COUNT(*) as total_generations,
                    ai_model_used
                 FROM appointment_summaries 
                 WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                 GROUP BY ai_model_used
                 LIMIT 1"
            );

            return [
                'average_tokens_used' => round($metrics['avg_tokens'] ?? 0),
                'average_response_time_ms' => round($metrics['avg_response_time'] ?? 0, 2),
                'total_generations_month' => $metrics['total_generations'] ?? 0,
                'primary_model' => $metrics['ai_model_used'] ?? 'N/A'
            ];
        } catch (\Exception $e) {
            return [
                'average_tokens_used' => 0,
                'average_response_time_ms' => 0,
                'total_generations_month' => 0,
                'primary_model' => 'N/A'
            ];
        }
    }

    private function getCostAnalysis(): array
    {
        // This would calculate costs based on token usage and model pricing
        return [
            'estimated_monthly_cost' => 45.50,
            'cost_per_summary' => 0.23,
            'token_efficiency' => 'high',
            'cost_trend' => 'stable'
        ];
    }

    private function formatSummaryAsText(array $exportData): string
    {
        $text = "APPOINTMENT SUMMARY\n";
        $text .= "===================\n\n";
        
        if ($exportData['patient']) {
            $text .= "Patient: " . $exportData['patient']['first_name'] . " " . $exportData['patient']['last_name'] . "\n";
        }
        
        $text .= "Date: " . $exportData['appointment_date'] . "\n";
        $text .= "Time: " . $exportData['appointment_time'] . "\n\n";
        
        $summary = $exportData['summary'];
        foreach ($summary as $section => $content) {
            $text .= strtoupper(str_replace('_', ' ', $section)) . ":\n";
            $text .= $content . "\n\n";
        }
        
        $text .= "Generated: " . $exportData['generated_at'] . "\n";
        
        return $text;
    }

    private function formatSummaryAsHTML(array $exportData): string
    {
        $html = "<html><body>";
        $html .= "<h1>Appointment Summary</h1>";
        
        if ($exportData['patient']) {
            $html .= "<p><strong>Patient:</strong> " . htmlspecialchars($exportData['patient']['first_name'] . " " . $exportData['patient']['last_name']) . "</p>";
        }
        
        $html .= "<p><strong>Date:</strong> " . htmlspecialchars($exportData['appointment_date']) . "</p>";
        $html .= "<p><strong>Time:</strong> " . htmlspecialchars($exportData['appointment_time']) . "</p>";
        
        $summary = $exportData['summary'];
        foreach ($summary as $section => $content) {
            $html .= "<h3>" . ucwords(str_replace('_', ' ', $section)) . "</h3>";
            $html .= "<p>" . nl2br(htmlspecialchars($content)) . "</p>";
        }
        
        $html .= "<p><em>Generated: " . htmlspecialchars($exportData['generated_at']) . "</em></p>";
        $html .= "</body></html>";
        
        return $html;
    }
}
