<?php

namespace MedicalClinic\Controllers;

use MedicalClinic\Services\AIStaffDashboardService;
use MedicalClinic\Services\GroqAIService;

class AIDashboardController extends BaseController
{
    private AIStaffDashboardService $dashboardService;
    private GroqAIService $aiService;

    public function __construct()
    {
        parent::__construct();
        $this->dashboardService = new AIStaffDashboardService();
        $this->aiService = new GroqAIService();
    }

    /**
     * Get AI-powered daily briefing for staff
     */
    public function getDailyBriefing(): void
    {
        $user = $this->requireAuth();

        $briefing = $this->dashboardService->generateDailyBriefing($user['id']);

        if ($briefing['success']) {
            $this->success($briefing, 'Daily briefing generated successfully');
        } else {
            $this->error($briefing['message'] ?? 'Failed to generate briefing', 500, $briefing);
        }
    }

    /**
     * Get real-time clinic status overview
     */
    public function getClinicStatus(): void
    {
        $this->requireAuth();

        $status = $this->dashboardService->getClinicStatus();

        $this->success($status, 'Clinic status retrieved successfully');
    }

    /**
     * Get AI-generated priority task list
     */
    public function getPriorityTasks(): void
    {
        $this->requireAuth();

        $tasks = $this->dashboardService->generatePriorityTasks();

        if ($tasks['success']) {
            $this->success($tasks, 'Priority tasks generated successfully');
        } else {
            $this->success($tasks['fallback_tasks'] ?? [], 'Priority tasks retrieved (fallback mode)');
        }
    }

    /**
     * Get performance metrics and trends
     */
    public function getPerformanceMetrics(): void
    {
        $this->requireRole(['admin', 'doctor']);

        $metrics = $this->dashboardService->getPerformanceMetrics();

        $this->success($metrics, 'Performance metrics retrieved successfully');
    }

    /**
     * Test AI service connection
     */
    public function testAIConnection(): void
    {
        $this->requireRole(['admin']);

        $connectionTest = $this->aiService->testConnection();

        if ($connectionTest['success']) {
            $this->success($connectionTest, 'AI service connection successful');
        } else {
            $this->error($connectionTest['message'], 500);
        }
    }

    /**
     * Get AI model information and configuration
     */
    public function getAIModelInfo(): void
    {
        $this->requireRole(['admin']);

        $modelInfo = $this->aiService->getModelInfo();

        $this->success($modelInfo, 'AI model information retrieved successfully');
    }

    /**
     * Generate custom AI analysis based on user query
     */
    public function generateCustomAnalysis(): void
    {
        $user = $this->requireAuth();

        $this->validateRequired(['query', 'analysis_type'], $this->input);

        $query = $this->sanitizeString($this->input['query']);
        $analysisType = $this->sanitizeString($this->input['analysis_type']);

        // Validate analysis type
        $allowedTypes = ['dashboard', 'triage', 'summary', 'alerts'];
        if (!in_array($analysisType, $allowedTypes)) {
            $this->error('Invalid analysis type. Must be one of: ' . implode(', ', $allowedTypes), 400);
        }

        // Prepare context-aware prompt
        $contextPrompt = $this->buildContextPrompt($query, $analysisType);

        $aiResponse = $this->aiService->generateResponse($contextPrompt, $analysisType);

        if ($aiResponse['success']) {
            $this->success([
                'analysis' => $aiResponse['content'],
                'query' => $query,
                'analysis_type' => $analysisType,
                'model_used' => $aiResponse['model_used'],
                'tokens_used' => $aiResponse['tokens_used'],
                'response_time_ms' => $aiResponse['response_time'],
                'generated_at' => date('Y-m-d H:i:s')
            ], 'Custom analysis generated successfully');
        } else {
            $this->error($aiResponse['message'], 500);
        }
    }

    /**
     * Get comprehensive dashboard summary
     */
    public function getDashboardSummary(): void
    {
        $this->requireAuth();

        try {
            // Get all dashboard data in parallel
            $briefing = $this->dashboardService->generateDailyBriefing();
            $status = $this->dashboardService->getClinicStatus();
            $tasks = $this->dashboardService->generatePriorityTasks();
            $metrics = $this->dashboardService->getPerformanceMetrics();

            $summary = [
                'briefing' => $briefing,
                'clinic_status' => $status,
                'priority_tasks' => $tasks,
                'performance_metrics' => $metrics,
                'last_updated' => date('Y-m-d H:i:s'),
                'ai_powered' => true
            ];

            $this->success($summary, 'Dashboard summary retrieved successfully');

        } catch (\Exception $e) {
            error_log("Dashboard summary error: " . $e->getMessage());
            $this->error('Failed to generate dashboard summary', 500);
        }
    }

    /**
     * Refresh AI analysis for specific dashboard component
     */
    public function refreshDashboardComponent(): void
    {
        $this->requireAuth();

        $this->validateRequired(['component'], $this->input);

        $component = $this->sanitizeString($this->input['component']);

        switch ($component) {
            case 'briefing':
                $result = $this->dashboardService->generateDailyBriefing();
                break;
            case 'tasks':
                $result = $this->dashboardService->generatePriorityTasks();
                break;
            case 'status':
                $result = $this->dashboardService->getClinicStatus();
                break;
            case 'metrics':
                $result = $this->dashboardService->getPerformanceMetrics();
                break;
            default:
                $this->error('Invalid component. Must be: briefing, tasks, status, or metrics', 400);
                return;
        }

        $this->success([
            'component' => $component,
            'data' => $result,
            'refreshed_at' => date('Y-m-d H:i:s')
        ], ucfirst($component) . ' refreshed successfully');
    }

    // Private helper methods

    private function buildContextPrompt(string $query, string $analysisType): string
    {
        $today = date('Y-m-d');
        
        $basePrompt = "Current date: {$today}\n";
        $basePrompt .= "Medical clinic context: You are assisting medical staff with operational decisions.\n";
        $basePrompt .= "Analysis type: {$analysisType}\n\n";
        $basePrompt .= "Staff query: {$query}\n\n";
        
        switch ($analysisType) {
            case 'dashboard':
                $basePrompt .= "Provide operational insights and recommendations for clinic management.";
                break;
            case 'triage':
                $basePrompt .= "Provide clinical decision support while emphasizing that all medical decisions must be made by licensed professionals.";
                break;
            case 'summary':
                $basePrompt .= "Create a structured summary suitable for medical documentation.";
                break;
            case 'alerts':
                $basePrompt .= "Identify priorities and actionable alerts for patient care and operations.";
                break;
        }

        return $basePrompt;
    }
}
