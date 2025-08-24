<?php

/**
 * AI Features Demo Script
 * Run this script to test all AI-powered features
 */

require_once __DIR__ . '/../vendor/autoload.php';

use MedicalClinic\Services\GroqAIService;
use MedicalClinic\Services\AIStaffDashboardService;
use MedicalClinic\Services\AITriageService;
use MedicalClinic\Services\AIAppointmentSummaryService;
use MedicalClinic\Services\AIAlertService;

class AIFeaturesDemo
{
    public function runDemo()
    {
        echo "🚀 Medical Clinic AI Features Demo\n";
        echo "==================================\n\n";

        // Test Groq AI Connection
        $this->testGroqConnection();
        
        // Demo Staff Dashboard
        $this->demoStaffDashboard();
        
        // Demo Patient Triage
        $this->demoPatientTriage();
        
        // Demo Appointment Summaries
        $this->demoAppointmentSummaries();
        
        // Demo Alert System
        $this->demoAlertSystem();
        
        echo "\n✨ Demo completed successfully!\n";
        echo "Your medical clinic is now powered by advanced AI! 🏥🤖\n";
    }

    private function testGroqConnection()
    {
        echo "🔌 Testing Groq AI Connection...\n";
        
        try {
            $groqService = new GroqAIService();
            $result = $groqService->testConnection();
            
            if ($result['success']) {
                echo "✅ Groq AI Connection: SUCCESSFUL\n";
                echo "   Model: {$result['model_tested']}\n";
                echo "   Response Time: {$result['response_time']}ms\n";
            } else {
                echo "❌ Groq AI Connection: FAILED\n";
                echo "   Error: {$result['message']}\n";
            }
        } catch (Exception $e) {
            echo "❌ Connection Error: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }

    private function demoStaffDashboard()
    {
        echo "📊 AI-Powered Staff Dashboard Demo\n";
        echo "---------------------------------\n";
        
        try {
            $dashboardService = new AIStaffDashboardService();
            
            // Generate daily briefing
            echo "Generating daily briefing...\n";
            $briefing = $dashboardService->generateDailyBriefing();
            
            if ($briefing['success']) {
                echo "✅ Daily Briefing Generated Successfully!\n";
                echo "   Model Used: {$briefing['ai_model']}\n";
                echo "   Response Time: {$briefing['response_time_ms']}ms\n";
                echo "   Preview: " . substr($briefing['briefing'], 0, 100) . "...\n";
            }
            
            // Get clinic status
            echo "\nRetrieving clinic status...\n";
            $status = $dashboardService->getClinicStatus();
            echo "✅ Clinic Status Retrieved\n";
            echo "   Today's Appointments: " . count($status['todays_appointments']) . "\n";
            echo "   Urgent Cases: " . count($status['urgent_cases']) . "\n";
            echo "   Low Inventory Items: " . count($status['low_inventory']) . "\n";
            
        } catch (Exception $e) {
            echo "❌ Dashboard Error: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }

    private function demoPatientTriage()
    {
        echo "🩺 Intelligent Patient Triage Demo\n";
        echo "---------------------------------\n";
        
        try {
            $triageService = new AITriageService();
            
            // Sample patient data
            $samplePatient = [
                'id' => 123,
                'date_of_birth' => '1980-05-15',
                'medical_notes' => 'History of hypertension and diabetes',
                'allergies' => 'Penicillin, shellfish'
            ];
            
            $sampleAppointment = [
                'notes' => 'Chest pain, shortness of breath, sweating for 2 hours',
                'appointment_type' => 'urgent_care',
                'priority' => 'high'
            ];
            
            echo "Analyzing patient case...\n";
            $analysis = $triageService->analyzePatientCase($samplePatient, $sampleAppointment);
            
            if ($analysis['success']) {
                echo "✅ Triage Analysis Completed!\n";
                echo "   Urgency Level: {$analysis['urgency_level']} (Score: {$analysis['urgency_score']}/5)\n";
                echo "   Recommended Specialist: {$analysis['recommended_specialist']}\n";
                echo "   Appointment Duration: {$analysis['appointment_duration']} minutes\n";
                echo "   Red Flags: " . implode(', ', array_slice($analysis['red_flags'], 0, 2)) . "\n";
                echo "   AI Confidence: {$analysis['ai_confidence']}/5\n";
            }
            
            // Demo symptom triage
            echo "\nTesting symptom-based triage...\n";
            $symptomTriage = $triageService->getSymptomTriage(
                "Severe headache with nausea and light sensitivity"
            );
            
            if ($symptomTriage['success']) {
                echo "✅ Symptom Triage Completed!\n";
                echo "   Assessment: " . substr($symptomTriage['triage_assessment'], 0, 100) . "...\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Triage Error: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }

    private function demoAppointmentSummaries()
    {
        echo "📝 AI-Generated Appointment Summaries Demo\n";
        echo "-----------------------------------------\n";
        
        try {
            $summaryService = new AIAppointmentSummaryService();
            
            // Sample appointment data
            $sampleAppointmentData = [
                'id' => 456,
                'appointment_date' => '2024-01-15',
                'start_time' => '10:00:00',
                'end_time' => '10:30:00',
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'date_of_birth' => '1975-03-20',
                'medical_notes' => 'History of hypertension',
                'allergies' => 'None known',
                'doctor_first_name' => 'Dr. Sarah',
                'doctor_last_name' => 'Johnson',
                'specialty' => 'Family Medicine',
                'appointment_type' => 'follow-up',
                'priority' => 'normal',
                'notes' => 'Follow-up for hypertension management',
                'diagnosis' => 'Essential hypertension, well controlled',
                'treatment_notes' => 'Continue current medication regimen, lifestyle modifications discussed',
                'follow_up_required' => true,
                'follow_up_date' => '2024-04-15'
            ];
            
            echo "Generating comprehensive appointment summary...\n";
            $summary = $summaryService->generateAppointmentSummary(456, $sampleAppointmentData);
            
            if ($summary['success']) {
                echo "✅ Appointment Summary Generated!\n";
                echo "   Model Used: " . ($summary['model_used'] ?? 'N/A') . "\n";
                echo "   Word Count: " . ($summary['word_count'] ?? 'N/A') . "\n";
                echo "   Tokens Used: " . ($summary['tokens_used'] ?? 'N/A') . "\n";
                echo "   Response Time: " . ($summary['response_time_ms'] ?? 'N/A') . "ms\n";
                
                // Show summary sections
                $summaryData = $summary['summary'];
                echo "\n📋 Summary Sections:\n";
                foreach ($summaryData as $section => $content) {
                    if (!empty($content)) {
                        echo "   • " . ucwords(str_replace('_', ' ', $section)) . ": " . substr($content, 0, 80) . "...\n";
                    }
                }
            }
            
            // Demo SOAP note generation
            echo "\nGenerating SOAP note...\n";
            $soapData = [
                'subjective' => 'Patient reports feeling well, no new symptoms. Taking medications as prescribed.',
                'objective' => 'BP 128/82, HR 72, normal physical examination',
                'assessment' => 'Essential hypertension, well controlled with current therapy',
                'plan' => 'Continue lisinopril 10mg daily, follow-up in 3 months, home BP monitoring'
            ];
            
            $soapNote = $summaryService->generateSOAPNote(456, $soapData);
            
            if ($soapNote['success']) {
                echo "✅ SOAP Note Generated!\n";
                echo "   Format: {$soapNote['format']}\n";
                echo "   Preview: " . substr($soapNote['soap_note'], 0, 100) . "...\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Summary Error: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }

    private function demoAlertSystem()
    {
        echo "🚨 Intelligent Alert System Demo\n";
        echo "-------------------------------\n";
        
        try {
            $alertService = new AIAlertService();
            
            echo "Generating intelligent alerts...\n";
            $alerts = $alertService->generateIntelligentAlerts();
            
            if ($alerts['success']) {
                echo "✅ Alerts Generated Successfully!\n";
                echo "   Total Alerts: {$alerts['alert_count']}\n";
                echo "   High Priority: {$alerts['high_priority_count']}\n";
                echo "   AI Generated: {$alerts['ai_generated_count']}\n";
                echo "   System Generated: {$alerts['system_generated_count']}\n";
                
                if (!empty($alerts['alerts'])) {
                    echo "\n🔥 Sample High-Priority Alerts:\n";
                    $highPriorityAlerts = array_filter($alerts['alerts'], fn($alert) => $alert['priority'] >= 4);
                    $sampleAlerts = array_slice($highPriorityAlerts, 0, 3);
                    
                    foreach ($sampleAlerts as $index => $alert) {
                        echo "   " . ($index + 1) . ". [{$alert['priority']}/5] {$alert['title']}\n";
                        echo "      Type: {$alert['type']}\n";
                        echo "      Action: {$alert['action_required']}\n";
                        echo "      Timeline: {$alert['timeline']}\n\n";
                    }
                }
            }
            
            // Demo patient safety alerts
            echo "Generating patient safety alerts...\n";
            $safetyAlerts = $alertService->generatePatientSafetyAlerts();
            
            if ($safetyAlerts['success']) {
                echo "✅ Patient Safety Alerts Generated!\n";
                echo "   Critical Alerts: {$safetyAlerts['critical_count']}\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Alert System Error: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
}

// Run the demo
echo "🏥 Welcome to the Medical Clinic AI Features Demo!\n";
echo "This will test all four AI-powered features...\n\n";

$demo = new AIFeaturesDemo();
$demo->runDemo();
