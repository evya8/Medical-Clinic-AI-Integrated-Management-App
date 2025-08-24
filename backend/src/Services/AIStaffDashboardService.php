<?php

namespace MedicalClinic\Services;

use MedicalClinic\Utils\Database;

class AIStaffDashboardService
{
    private Database $db;
    private GroqAIService $aiService;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->aiService = new GroqAIService();
    }

    /**
     * Generate comprehensive daily briefing for medical staff
     */
    public function generateDailyBriefing(?int $userId = null): array
    {
        try {
            // Gather all necessary data
            $dashboardData = $this->collectDashboardData();
            
            // Generate AI-powered briefing
            $briefingPrompt = $this->buildBriefingPrompt($dashboardData);
            
            $aiResponse = $this->aiService->generateResponse($briefingPrompt, 'dashboard');
            
            if (!$aiResponse['success']) {
                return $this->getFallbackBriefing($dashboardData);
            }

            return [
                'success' => true,
                'briefing' => $aiResponse['content'],
                'data_summary' => $dashboardData['summary'],
                'generated_at' => date('Y-m-d H:i:s'),
                'ai_model' => $aiResponse['model_used'],
                'response_time_ms' => $aiResponse['response_time']
            ];

        } catch (\Exception $e) {
            error_log("Dashboard briefing error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Unable to generate briefing at this time',
                'fallback' => $this->getFallbackBriefing($this->collectDashboardData())
            ];
        }
    }

    /**
     * Get real-time clinic status for dashboard
     */
    public function getClinicStatus(): array
    {
        $today = date('Y-m-d');
        
        // Today's appointments
        $todaysAppointments = $this->db->fetchAll(
            "SELECT a.*, p.first_name, p.last_name, u.first_name as doctor_first_name, 
                    u.last_name as doctor_last_name, d.specialty
             FROM appointments a
             JOIN patients p ON a.patient_id = p.id
             JOIN doctors doc ON a.doctor_id = doc.id
             JOIN users u ON doc.user_id = u.id
             LEFT JOIN doctors d ON doc.id = d.id
             WHERE DATE(a.appointment_date) = :today
             AND a.status NOT IN ('cancelled')
             ORDER BY a.start_time",
            ['today' => $today]
        );

        // Urgent/priority cases
        $urgentCases = array_filter($todaysAppointments, function($apt) {
            return in_array($apt['priority'], ['high', 'urgent']);
        });

        // Upcoming appointments (next 2 hours)
        $upcomingAppointments = $this->db->fetchAll(
            "SELECT a.*, p.first_name, p.last_name
             FROM appointments a
             JOIN patients p ON a.patient_id = p.id
             WHERE DATE(a.appointment_date) = :today
             AND a.start_time BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 2 HOUR)
             AND a.status = 'scheduled'
             ORDER BY a.start_time",
            ['today' => $today]
        );

        // Resource alerts
        $lowInventory = $this->db->fetchAll(
            "SELECT item_name, quantity, minimum_stock_level
             FROM inventory 
             WHERE quantity <= minimum_stock_level
             ORDER BY (quantity/minimum_stock_level) ASC
             LIMIT 5"
        );

        // Pending reminders
        $pendingReminders = $this->db->fetch(
            "SELECT COUNT(*) as count
             FROM reminders 
             WHERE status = 'pending' 
             AND scheduled_time <= NOW()"
        );

        // Overdue follow-ups (simplified)
        $overdueFollowups = $this->db->fetchAll(
            "SELECT a.*, p.first_name, p.last_name
             FROM appointments a
             JOIN patients p ON a.patient_id = p.id
             WHERE a.follow_up_required = 1
             AND a.follow_up_date < CURDATE()
             AND a.status = 'completed'
             LIMIT 10"
        );

        return [
            'todays_appointments' => $todaysAppointments,
            'urgent_cases' => $urgentCases,
            'upcoming_appointments' => $upcomingAppointments,
            'low_inventory' => $lowInventory,
            'pending_reminders' => $pendingReminders['count'],
            'overdue_followups' => $overdueFollowups,
            'last_updated' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Generate priority task list for staff
     */
    public function generatePriorityTasks(): array
    {
        $statusData = $this->getClinicStatus();
        
        $tasksPrompt = "Based on this clinic status, generate a prioritized task list for medical staff:

URGENT CASES TODAY: " . count($statusData['urgent_cases']) . "
UPCOMING APPOINTMENTS (2hrs): " . count($statusData['upcoming_appointments']) . "
LOW INVENTORY ITEMS: " . count($statusData['low_inventory']) . "
PENDING REMINDERS: " . $statusData['pending_reminders'] . "
OVERDUE FOLLOW-UPS: " . count($statusData['overdue_followups']) . "

Create a numbered priority task list (1-10) focusing on:
- Patient safety and urgent care
- Time-sensitive administrative tasks
- Resource management
- Follow-up care

Format as a simple numbered list with brief action items.";

        $aiResponse = $this->aiService->generateResponse($tasksPrompt, 'dashboard');
        
        if ($aiResponse['success']) {
            return [
                'success' => true,
                'priority_tasks' => $aiResponse['content'],
                'task_count' => $this->extractTaskCount($aiResponse['content'])
            ];
        }

        return [
            'success' => false,
            'fallback_tasks' => $this->generateFallbackTasks($statusData)
        ];
    }

    /**
     * Get performance metrics summary
     */
    public function getPerformanceMetrics(): array
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $thisWeek = date('Y-m-d', strtotime('-7 days'));

        // Today's metrics
        $todayMetrics = $this->db->fetch(
            "SELECT 
                COUNT(*) as total_appointments,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) as no_shows,
                SUM(CASE WHEN priority IN ('high', 'urgent') THEN 1 ELSE 0 END) as urgent_cases
             FROM appointments 
             WHERE DATE(appointment_date) = :today",
            ['today' => $today]
        );

        // Compare with yesterday
        $yesterdayMetrics = $this->db->fetch(
            "SELECT 
                COUNT(*) as total_appointments,
                SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) as no_shows
             FROM appointments 
             WHERE DATE(appointment_date) = :yesterday",
            ['yesterday' => $yesterday]
        );

        // Weekly reminder success rate
        $reminderStats = $this->db->fetch(
            "SELECT 
                COUNT(*) as total_sent,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
             FROM reminders 
             WHERE DATE(created_at) >= :week_start",
            ['week_start' => $thisWeek]
        );

        return [
            'today' => $todayMetrics,
            'yesterday' => $yesterdayMetrics,
            'reminder_stats' => $reminderStats,
            'trends' => $this->calculateTrends($todayMetrics, $yesterdayMetrics)
        ];
    }

    // Private helper methods

    private function collectDashboardData(): array
    {
        $status = $this->getClinicStatus();
        $metrics = $this->getPerformanceMetrics();
        
        return [
            'status' => $status,
            'metrics' => $metrics,
            'summary' => [
                'total_appointments_today' => count($status['todays_appointments']),
                'urgent_cases' => count($status['urgent_cases']),
                'upcoming_next_2h' => count($status['upcoming_appointments']),
                'low_inventory_items' => count($status['low_inventory']),
                'pending_reminders' => $status['pending_reminders'],
                'overdue_followups' => count($status['overdue_followups'])
            ]
        ];
    }

    private function buildBriefingPrompt(array $data): string
    {
        $summary = $data['summary'];
        
        return "Generate a comprehensive daily briefing for medical clinic staff with this information:

📅 TODAY'S OVERVIEW:
- Total appointments scheduled: {$summary['total_appointments_today']}
- Urgent/priority cases: {$summary['urgent_cases']}
- Appointments in next 2 hours: {$summary['upcoming_next_2h']}

🚨 ALERTS & PRIORITIES:
- Low inventory items requiring attention: {$summary['low_inventory_items']}
- Pending reminders to be sent: {$summary['pending_reminders']}
- Overdue follow-ups: {$summary['overdue_followups']}

Provide a clear, professional briefing with:
1. **Key Priorities** - Top 3 things staff should focus on today
2. **Time Management** - Suggested workflow for the day
3. **Resource Alerts** - Any inventory or system issues to address
4. **Patient Care Reminders** - Important follow-ups or urgent cases

Keep it concise, actionable, and focused on improving patient care and operational efficiency.";
    }

    private function getFallbackBriefing(array $data): array
    {
        $summary = $data['summary'];
        
        $fallbackBriefing = "📋 **Daily Clinic Briefing - " . date('M j, Y') . "**\n\n";
        $fallbackBriefing .= "**Today's Schedule:**\n";
        $fallbackBriefing .= "• {$summary['total_appointments_today']} appointments scheduled\n";
        $fallbackBriefing .= "• {$summary['urgent_cases']} urgent/priority cases\n";
        $fallbackBriefing .= "• {$summary['upcoming_next_2h']} appointments in next 2 hours\n\n";
        
        $fallbackBriefing .= "**Action Items:**\n";
        if ($summary['urgent_cases'] > 0) {
            $fallbackBriefing .= "• Review {$summary['urgent_cases']} urgent cases immediately\n";
        }
        if ($summary['low_inventory_items'] > 0) {
            $fallbackBriefing .= "• Check {$summary['low_inventory_items']} low inventory items\n";
        }
        if ($summary['overdue_followups'] > 0) {
            $fallbackBriefing .= "• Contact {$summary['overdue_followups']} patients for overdue follow-ups\n";
        }
        
        return [
            'success' => true,
            'briefing' => $fallbackBriefing,
            'data_summary' => $summary,
            'generated_at' => date('Y-m-d H:i:s'),
            'source' => 'fallback'
        ];
    }

    private function generateFallbackTasks(array $statusData): array
    {
        $tasks = [];
        
        if (count($statusData['urgent_cases']) > 0) {
            $tasks[] = "1. Review " . count($statusData['urgent_cases']) . " urgent patient cases";
        }
        
        if (count($statusData['upcoming_appointments']) > 0) {
            $tasks[] = "2. Prepare for " . count($statusData['upcoming_appointments']) . " upcoming appointments";
        }
        
        if (count($statusData['low_inventory']) > 0) {
            $tasks[] = "3. Address " . count($statusData['low_inventory']) . " low inventory items";
        }
        
        if ($statusData['pending_reminders'] > 0) {
            $tasks[] = "4. Send " . $statusData['pending_reminders'] . " pending reminders";
        }
        
        if (count($statusData['overdue_followups']) > 0) {
            $tasks[] = "5. Schedule " . count($statusData['overdue_followups']) . " overdue follow-ups";
        }
        
        return $tasks;
    }

    private function extractTaskCount(string $content): int
    {
        preg_match_all('/^\d+\./m', $content, $matches);
        return count($matches[0]);
    }

    private function calculateTrends(array $today, array $yesterday): array
    {
        $appointmentTrend = $today['total_appointments'] - $yesterday['total_appointments'];
        $noShowTrend = $today['no_shows'] - $yesterday['no_shows'];
        
        return [
            'appointments' => [
                'change' => $appointmentTrend,
                'direction' => $appointmentTrend > 0 ? 'up' : ($appointmentTrend < 0 ? 'down' : 'stable')
            ],
            'no_shows' => [
                'change' => $noShowTrend,
                'direction' => $noShowTrend > 0 ? 'up' : ($noShowTrend < 0 ? 'down' : 'stable')
            ]
        ];
    }
}
