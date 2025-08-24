<?php

namespace MedicalClinic\Services;

use MedicalClinic\Utils\Database;

class AITriageService
{
    private Database $db;
    private GroqAIService $aiService;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->aiService = new GroqAIService();
    }

    /**
     * Analyze patient case for intelligent triage
     */
    public function analyzePatientCase(array $patientData, array $appointmentData = []): array
    {
        try {
            // Build comprehensive patient profile
            $patientProfile = $this->buildPatientProfile($patientData, $appointmentData);
            
            // Generate triage prompt
            $triagePrompt = $this->buildTriagePrompt($patientProfile);
            
            // Get AI analysis using Llama 3 70B for high accuracy
            $aiResponse = $this->aiService->generateResponse($triagePrompt, 'triage');
            
            if (!$aiResponse['success']) {
                return $this->getFallbackTriage($patientProfile);
            }

            // Parse and structure the AI response
            $triageAnalysis = $this->parseTriageResponse($aiResponse['content']);

            return [
                'success' => true,
                'patient_id' => $patientData['id'] ?? null,
                'urgency_score' => $triageAnalysis['urgency_score'],
                'urgency_level' => $this->getUrgencyLevel($triageAnalysis['urgency_score']),
                'recommended_specialist' => $triageAnalysis['specialist'],
                'appointment_duration' => $triageAnalysis['duration'],
                'red_flags' => $triageAnalysis['red_flags'],
                'differential_diagnoses' => $triageAnalysis['diagnoses'],
                'recommended_tests' => $triageAnalysis['tests'],
                'triage_notes' => $triageAnalysis['notes'],
                'ai_confidence' => $triageAnalysis['confidence'],
                'model_used' => $aiResponse['model_used'],
                'analysis_timestamp' => date('Y-m-d H:i:s')
            ];

        } catch (\Exception $e) {
            error_log("Triage analysis error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Triage analysis unavailable',
                'fallback' => $this->getFallbackTriage($patientData)
            ];
        }
    }

    /**
     * Batch analyze multiple patients for triage prioritization
     */
    public function batchTriageAnalysis(array $patientCases): array
    {
        $results = [];
        $priorityQueue = [];

        foreach ($patientCases as $case) {
            $analysis = $this->analyzePatientCase($case['patient'], $case['appointment'] ?? []);
            
            if ($analysis['success']) {
                $results[] = $analysis;
                $priorityQueue[] = [
                    'patient_id' => $analysis['patient_id'],
                    'urgency_score' => $analysis['urgency_score'],
                    'urgency_level' => $analysis['urgency_level'],
                    'appointment_time' => $case['appointment']['start_time'] ?? null
                ];
            }
        }

        // Sort by urgency score (descending)
        usort($priorityQueue, function($a, $b) {
            return $b['urgency_score'] <=> $a['urgency_score'];
        });

        return [
            'success' => true,
            'batch_analysis' => $results,
            'priority_queue' => $priorityQueue,
            'total_analyzed' => count($results),
            'high_priority_count' => count(array_filter($priorityQueue, fn($p) => $p['urgency_score'] >= 4)),
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Get triage recommendations for symptoms
     */
    public function getSymptomTriage(string $symptoms, array $patientHistory = []): array
    {
        $prompt = "Analyze these symptoms for medical triage:

SYMPTOMS: {$symptoms}
PATIENT HISTORY: " . json_encode($patientHistory) . "

Provide triage assessment with:
1. Urgency level (1-5 scale)
2. Recommended time frame for care
3. Key warning signs to monitor
4. Appropriate care setting (urgent care, ED, primary care)
5. Initial care recommendations

Focus on patient safety and appropriate care escalation.";

        $aiResponse = $this->aiService->generateResponse($prompt, 'triage');

        if ($aiResponse['success']) {
            return [
                'success' => true,
                'symptoms_analyzed' => $symptoms,
                'triage_assessment' => $aiResponse['content'],
                'model_used' => $aiResponse['model_used'],
                'response_time_ms' => $aiResponse['response_time']
            ];
        }

        return [
            'success' => false,
            'message' => 'Symptom analysis unavailable',
            'fallback_advice' => 'Please consult with medical professional for symptom evaluation.'
        ];
    }

    /**
     * Generate specialist referral recommendations
     */
    public function generateReferralRecommendations(array $patientData, string $condition): array
    {
        $prompt = "Recommend appropriate specialist referrals for this patient:

PATIENT AGE: {$patientData['age']}
CONDITION: {$condition}
MEDICAL HISTORY: " . ($patientData['medical_notes'] ?? 'None provided') . "
ALLERGIES: " . ($patientData['allergies'] ?? 'None known') . "

Provide:
1. Primary specialist recommendation
2. Alternative specialists if appropriate
3. Urgency of referral (routine, urgent, emergent)
4. Key information to include in referral
5. Patient preparation instructions

Focus on optimal patient care coordination.";

        $aiResponse = $this->aiService->generateResponse($prompt, 'triage');

        if ($aiResponse['success']) {
            return [
                'success' => true,
                'patient_id' => $patientData['id'],
                'condition' => $condition,
                'referral_recommendations' => $aiResponse['content'],
                'generated_at' => date('Y-m-d H:i:s')
            ];
        }

        return [
            'success' => false,
            'message' => 'Referral recommendations unavailable'
        ];
    }

    // Private helper methods

    private function buildPatientProfile(array $patientData, array $appointmentData): array
    {
        $age = $this->calculateAge($patientData['date_of_birth'] ?? null);
        
        return [
            'id' => $patientData['id'] ?? null,
            'age' => $age,
            'gender' => $patientData['gender'] ?? 'not specified',
            'chief_complaint' => $appointmentData['notes'] ?? '',
            'appointment_type' => $appointmentData['appointment_type'] ?? '',
            'symptoms' => $this->extractSymptoms($appointmentData['notes'] ?? ''),
            'medical_history' => $patientData['medical_notes'] ?? '',
            'allergies' => $patientData['allergies'] ?? 'None known',
            'medications' => $this->getPatientMedications($patientData['id'] ?? null),
            'blood_type' => $patientData['blood_type'] ?? 'Unknown',
            'previous_appointments' => $this->getRecentAppointments($patientData['id'] ?? null),
            'priority' => $appointmentData['priority'] ?? 'normal'
        ];
    }

    private function buildTriagePrompt(array $profile): string
    {
        return "Analyze this patient case for medical triage assessment:

PATIENT PROFILE:
- Age: {$profile['age']} years
- Gender: {$profile['gender']}
- Chief Complaint: {$profile['chief_complaint']}
- Appointment Type: {$profile['appointment_type']}
- Current Priority: {$profile['priority']}

CLINICAL INFORMATION:
- Symptoms: {$profile['symptoms']}
- Medical History: {$profile['medical_history']}
- Known Allergies: {$profile['allergies']}
- Blood Type: {$profile['blood_type']}
- Current Medications: " . json_encode($profile['medications']) . "

RECENT CARE:
- Previous Appointments: " . json_encode($profile['previous_appointments']) . "

TRIAGE ANALYSIS REQUIRED:
Provide structured assessment with:

1. URGENCY_SCORE: (1-5 scale where 1=routine, 5=immediate)
2. SPECIALIST: (recommended specialist type or 'primary care')
3. DURATION: (suggested appointment length in minutes)
4. RED_FLAGS: (warning signs to monitor - list format)
5. DIAGNOSES: (preliminary differential diagnoses - list format)
6. TESTS: (recommended diagnostic tests - list format)
7. NOTES: (clinical reasoning and recommendations)
8. CONFIDENCE: (AI confidence level 1-5)

Use evidence-based medical guidelines and emphasize patient safety.
Format each section clearly with the labels above.";
    }

    private function parseTriageResponse(string $response): array
    {
        $defaultResponse = [
            'urgency_score' => 2,
            'specialist' => 'primary care',
            'duration' => 30,
            'red_flags' => ['Monitor vital signs'],
            'diagnoses' => ['Requires clinical evaluation'],
            'tests' => ['Clinical assessment'],
            'notes' => 'Standard clinical evaluation recommended.',
            'confidence' => 3
        ];

        try {
            // Extract structured data from AI response
            $urgencyMatch = preg_match('/URGENCY_SCORE:\s*(\d+)/i', $response, $urgencyMatches);
            $specialistMatch = preg_match('/SPECIALIST:\s*([^\n]+)/i', $response, $specialistMatches);
            $durationMatch = preg_match('/DURATION:\s*(\d+)/i', $response, $durationMatches);
            $confidenceMatch = preg_match('/CONFIDENCE:\s*(\d+)/i', $response, $confidenceMatches);

            // Extract lists
            $redFlags = $this->extractListItems($response, 'RED_FLAGS');
            $diagnoses = $this->extractListItems($response, 'DIAGNOSES');
            $tests = $this->extractListItems($response, 'TESTS');
            
            // Extract notes
            $notesMatch = preg_match('/NOTES:\s*([^0-9\n].*?)(?=\n[A-Z_]+:|$)/s', $response, $notesMatches);

            return [
                'urgency_score' => $urgencyMatch ? (int)$urgencyMatches[1] : $defaultResponse['urgency_score'],
                'specialist' => $specialistMatch ? trim($specialistMatches[1]) : $defaultResponse['specialist'],
                'duration' => $durationMatch ? (int)$durationMatches[1] : $defaultResponse['duration'],
                'red_flags' => !empty($redFlags) ? $redFlags : $defaultResponse['red_flags'],
                'diagnoses' => !empty($diagnoses) ? $diagnoses : $defaultResponse['diagnoses'],
                'tests' => !empty($tests) ? $tests : $defaultResponse['tests'],
                'notes' => $notesMatch ? trim($notesMatches[1]) : $defaultResponse['notes'],
                'confidence' => $confidenceMatch ? (int)$confidenceMatches[1] : $defaultResponse['confidence']
            ];

        } catch (\Exception $e) {
            error_log("Error parsing triage response: " . $e->getMessage());
            return $defaultResponse;
        }
    }

    private function extractListItems(string $text, string $section): array
    {
        $pattern = "/{$section}:\s*(.*?)(?=\n[A-Z_]+:|$)/s";
        if (preg_match($pattern, $text, $matches)) {
            $content = trim($matches[1]);
            // Split by common list delimiters
            $items = preg_split('/\n|•|\*|-|\d+\./', $content);
            $items = array_filter(array_map('trim', $items));
            return array_values($items);
        }
        return [];
    }

    private function getUrgencyLevel(int $score): string
    {
        switch ($score) {
            case 5: return 'immediate';
            case 4: return 'urgent';
            case 3: return 'semi-urgent';
            case 2: return 'standard';
            case 1: return 'routine';
            default: return 'standard';
        }
    }

    private function calculateAge(?string $dateOfBirth): int
    {
        if (!$dateOfBirth) return 0;
        
        $birthDate = new \DateTime($dateOfBirth);
        $today = new \DateTime();
        return $birthDate->diff($today)->y;
    }

    private function extractSymptoms(string $notes): string
    {
        // Simple symptom extraction - can be enhanced with NLP
        $keywords = ['pain', 'fever', 'headache', 'nausea', 'fatigue', 'cough', 'shortness of breath'];
        $foundSymptoms = [];
        
        foreach ($keywords as $keyword) {
            if (stripos($notes, $keyword) !== false) {
                $foundSymptoms[] = $keyword;
            }
        }
        
        return implode(', ', $foundSymptoms) ?: 'See appointment notes';
    }

    private function getPatientMedications(?int $patientId): array
    {
        if (!$patientId) return [];
        
        // This would integrate with a medications table if available
        // For now, return empty array
        return [];
    }

    private function getRecentAppointments(?int $patientId, int $limit = 3): array
    {
        if (!$patientId) return [];
        
        try {
            return $this->db->fetchAll(
                "SELECT appointment_date, diagnosis, treatment_notes 
                 FROM appointments 
                 WHERE patient_id = :patient_id 
                 AND status = 'completed'
                 ORDER BY appointment_date DESC 
                 LIMIT :limit",
                ['patient_id' => $patientId, 'limit' => $limit]
            );
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getFallbackTriage(array $patientData): array
    {
        return [
            'success' => true,
            'patient_id' => $patientData['id'] ?? null,
            'urgency_score' => 2,
            'urgency_level' => 'standard',
            'recommended_specialist' => 'primary care',
            'appointment_duration' => 30,
            'red_flags' => ['Monitor patient condition'],
            'differential_diagnoses' => ['Requires clinical evaluation'],
            'recommended_tests' => ['Physical examination', 'Vital signs'],
            'triage_notes' => 'Standard clinical assessment recommended. AI triage unavailable.',
            'ai_confidence' => 1,
            'model_used' => 'fallback',
            'analysis_timestamp' => date('Y-m-d H:i:s')
        ];
    }
}
