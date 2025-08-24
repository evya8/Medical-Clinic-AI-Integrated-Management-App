<?php

namespace MedicalClinic\Services;

use MedicalClinic\Utils\Database;

class AIAppointmentSummaryService
{
    private Database $db;
    private GroqAIService $aiService;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->aiService = new GroqAIService();
    }

    /**
     * Generate comprehensive appointment summary
     */
    public function generateAppointmentSummary(int $appointmentId, array $appointmentData = []): array
    {
        try {
            // Get appointment details if not provided
            if (empty($appointmentData)) {
                $appointmentData = $this->getAppointmentDetails($appointmentId);
                if (!$appointmentData) {
                    throw new \Exception("Appointment not found");
                }
            }

            // Build comprehensive appointment context
            $summaryContext = $this->buildSummaryContext($appointmentData);
            
            // Generate summary prompt
            $summaryPrompt = $this->buildSummaryPrompt($summaryContext);
            
            // Generate AI summary using Mixtral for structured text generation
            $aiResponse = $this->aiService->generateResponse($summaryPrompt, 'summary');
            
            if (!$aiResponse['success']) {
                return $this->getFallbackSummary($summaryContext);
            }

            // Parse and structure the summary
            $structuredSummary = $this->parseAppointmentSummary($aiResponse['content']);

            // Store summary in database
            $summaryId = $this->storeSummary($appointmentId, $structuredSummary, $aiResponse);

            return [
                'success' => true,
                'appointment_id' => $appointmentId,
                'summary_id' => $summaryId,
                'summary' => $structuredSummary,
                'word_count' => str_word_count($aiResponse['content']),
                'model_used' => $aiResponse['model_used'],
                'tokens_used' => $aiResponse['tokens_used'],
                'generated_at' => date('Y-m-d H:i:s'),
                'response_time_ms' => $aiResponse['response_time']
            ];

        } catch (\Exception $e) {
            error_log("Appointment summary error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to generate appointment summary',
                'fallback' => $this->getFallbackSummary($appointmentData ?? [])
            ];
        }
    }

    /**
     * Generate SOAP note format summary
     */
    public function generateSOAPNote(int $appointmentId, array $soapData): array
    {
        $prompt = "Create a professional SOAP note for this medical appointment:

SUBJECTIVE:
{$soapData['subjective']}

OBJECTIVE:
{$soapData['objective']}

ASSESSMENT:
{$soapData['assessment']}

PLAN:
{$soapData['plan']}

Format as a comprehensive SOAP note with:
- Clear section headers
- Professional medical terminology
- Structured presentation
- ICD-10/CPT code suggestions where appropriate
- Follow-up recommendations

Maintain clinical accuracy and documentation standards.";

        $aiResponse = $this->aiService->generateResponse($prompt, 'summary');

        if ($aiResponse['success']) {
            return [
                'success' => true,
                'appointment_id' => $appointmentId,
                'soap_note' => $aiResponse['content'],
                'format' => 'SOAP',
                'generated_at' => date('Y-m-d H:i:s')
            ];
        }

        return [
            'success' => false,
            'message' => 'SOAP note generation failed',
            'fallback' => $this->generateFallbackSOAP($soapData)
        ];
    }

    /**
     * Generate billing-focused summary
     */
    public function generateBillingSummary(int $appointmentId, array $appointmentData = []): array
    {
        if (empty($appointmentData)) {
            $appointmentData = $this->getAppointmentDetails($appointmentId);
        }

        $prompt = "Generate a billing-focused summary for this medical appointment:

APPOINTMENT TYPE: {$appointmentData['appointment_type']}
DURATION: " . $this->calculateDuration($appointmentData) . " minutes
CHIEF COMPLAINT: {$appointmentData['notes']}
DIAGNOSIS: {$appointmentData['diagnosis']}
TREATMENT: {$appointmentData['treatment_notes']}
PROCEDURES: " . ($appointmentData['procedures'] ?? 'None documented') . "

Provide billing summary with:
1. Suggested CPT codes with justification
2. Appropriate ICD-10 diagnosis codes
3. Level of service (99201-99215 for office visits)
4. Documentation requirements met
5. Billing compliance notes

Focus on accurate coding and documentation compliance.";

        $aiResponse = $this->aiService->generateResponse($prompt, 'summary');

        if ($aiResponse['success']) {
            $billingSummary = $this->parseBillingCodes($aiResponse['content']);
            
            return [
                'success' => true,
                'appointment_id' => $appointmentId,
                'billing_summary' => $aiResponse['content'],
                'suggested_codes' => $billingSummary,
                'generated_at' => date('Y-m-d H:i:s')
            ];
        }

        return [
            'success' => false,
            'message' => 'Billing summary generation failed'
        ];
    }

    /**
     * Generate patient-friendly summary
     */
    public function generatePatientSummary(int $appointmentId, array $appointmentData = []): array
    {
        if (empty($appointmentData)) {
            $appointmentData = $this->getAppointmentDetails($appointmentId);
        }

        $prompt = "Create a patient-friendly summary of this medical appointment:

APPOINTMENT DATE: {$appointmentData['appointment_date']}
MAIN CONCERN: {$appointmentData['notes']}
FINDINGS: {$appointmentData['diagnosis']}
TREATMENT PROVIDED: {$appointmentData['treatment_notes']}
FOLLOW-UP: " . (($appointmentData['follow_up_required'] ?? false) ? 'Required on ' . ($appointmentData['follow_up_date'] ?? 'TBD') : 'Not required') . "

Create a clear, easy-to-understand summary for the patient including:
- What was discussed during the visit
- Any findings or test results
- Treatment provided or prescribed
- Next steps and follow-up care
- When to contact the office

Use simple, non-medical language while being accurate and reassuring.";

        $aiResponse = $this->aiService->generateResponse($prompt, 'summary');

        if ($aiResponse['success']) {
            return [
                'success' => true,
                'appointment_id' => $appointmentId,
                'patient_summary' => $aiResponse['content'],
                'reading_level' => 'patient-friendly',
                'generated_at' => date('Y-m-d H:i:s')
            ];
        }

        return [
            'success' => false,
            'message' => 'Patient summary generation failed'
        ];
    }

    /**
     * Batch generate summaries for multiple appointments
     */
    public function batchGenerateSummaries(array $appointmentIds, string $summaryType = 'standard'): array
    {
        $results = [];
        $successful = 0;
        $failed = 0;

        foreach ($appointmentIds as $appointmentId) {
            switch ($summaryType) {
                case 'soap':
                    // Would need SOAP data - skip for batch
                    $results[$appointmentId] = ['success' => false, 'message' => 'SOAP notes require manual data input'];
                    $failed++;
                    break;
                case 'billing':
                    $result = $this->generateBillingSummary($appointmentId);
                    break;
                case 'patient':
                    $result = $this->generatePatientSummary($appointmentId);
                    break;
                default:
                    $result = $this->generateAppointmentSummary($appointmentId);
                    break;
            }

            $results[$appointmentId] = $result;
            if ($result['success']) {
                $successful++;
            } else {
                $failed++;
            }

            // Add small delay to avoid rate limiting
            usleep(100000); // 100ms delay
        }

        return [
            'success' => true,
            'batch_results' => $results,
            'summary_type' => $summaryType,
            'total_processed' => count($appointmentIds),
            'successful' => $successful,
            'failed' => $failed,
            'completion_time' => date('Y-m-d H:i:s')
        ];
    }

    // Private helper methods

    private function getAppointmentDetails(int $appointmentId): ?array
    {
        try {
            $appointment = $this->db->fetch(
                "SELECT a.*, 
                        p.first_name, p.last_name, p.date_of_birth, p.medical_notes, p.allergies,
                        u.first_name as doctor_first_name, u.last_name as doctor_last_name,
                        d.specialty
                 FROM appointments a
                 JOIN patients p ON a.patient_id = p.id
                 JOIN doctors doc ON a.doctor_id = doc.id
                 JOIN users u ON doc.user_id = u.id
                 LEFT JOIN doctors d ON doc.id = d.id
                 WHERE a.id = :appointment_id",
                ['appointment_id' => $appointmentId]
            );

            return $appointment ?: null;

        } catch (\Exception $e) {
            error_log("Error fetching appointment details: " . $e->getMessage());
            return null;
        }
    }

    private function buildSummaryContext(array $appointmentData): array
    {
        $patientAge = $this->calculateAge($appointmentData['date_of_birth'] ?? null);
        $duration = $this->calculateDuration($appointmentData);

        return [
            'appointment_id' => $appointmentData['id'],
            'date' => $appointmentData['appointment_date'],
            'time' => $appointmentData['start_time'] . ' - ' . $appointmentData['end_time'],
            'duration_minutes' => $duration,
            'patient' => [
                'name' => ($appointmentData['first_name'] ?? 'Unknown') . ' ' . ($appointmentData['last_name'] ?? 'Patient'),
                'age' => $patientAge,
                'medical_history' => $appointmentData['medical_notes'] ?? '',
                'allergies' => $appointmentData['allergies'] ?? 'None known'
            ],
            'provider' => [
                'name' => ($appointmentData['doctor_first_name'] ?? 'Dr.') . ' ' . ($appointmentData['doctor_last_name'] ?? 'Unknown'),
                'specialty' => $appointmentData['specialty'] ?? 'General Practice'
            ],
            'visit' => [
                'type' => $appointmentData['appointment_type'] ?? 'consultation',
                'priority' => $appointmentData['priority'] ?? 'normal',
                'chief_complaint' => $appointmentData['notes'] ?? '',
                'diagnosis' => $appointmentData['diagnosis'] ?? '',
                'treatment' => $appointmentData['treatment_notes'] ?? '',
                'follow_up_required' => $appointmentData['follow_up_required'] ?? false,
                'follow_up_date' => $appointmentData['follow_up_date'] ?? null
            ]
        ];
    }

    private function buildSummaryPrompt(array $context): string
    {
        return "Generate a comprehensive medical appointment summary:

APPOINTMENT DETAILS:
Date: {$context['date']} at {$context['time']}
Duration: {$context['duration_minutes']} minutes
Type: {$context['visit']['type']}
Priority: {$context['visit']['priority']}

PATIENT INFORMATION:
Name: {$context['patient']['name']}
Age: {$context['patient']['age']} years
Medical History: {$context['patient']['medical_history']}
Known Allergies: {$context['patient']['allergies']}

PROVIDER:
{$context['provider']['name']}, {$context['provider']['specialty']}

VISIT SUMMARY:
Chief Complaint: {$context['visit']['chief_complaint']}
Diagnosis: {$context['visit']['diagnosis']}
Treatment Provided: {$context['visit']['treatment']}
Follow-up Required: " . ($context['visit']['follow_up_required'] ? 'Yes, scheduled for ' . ($context['visit']['follow_up_date'] ?? 'TBD') : 'No') . "

Create a structured appointment summary with these sections:
1. VISIT_OVERVIEW: Brief summary of the appointment purpose and outcome
2. CHIEF_COMPLAINT: Patient's main concern or reason for visit
3. CLINICAL_FINDINGS: Assessment and diagnostic findings
4. TREATMENT_PROVIDED: Interventions, medications, or procedures
5. FOLLOW_UP_PLAN: Next steps and follow-up care
6. PROVIDER_NOTES: Additional clinical observations
7. BILLING_NOTES: Documentation level and complexity
8. PATIENT_INSTRUCTIONS: Key instructions for patient care

Format each section clearly with the labels above.
Use professional medical terminology while ensuring clarity.";
    }

    private function parseAppointmentSummary(string $response): array
    {
        $sections = [
            'visit_overview' => '',
            'chief_complaint' => '',
            'clinical_findings' => '',
            'treatment_provided' => '',
            'follow_up_plan' => '',
            'provider_notes' => '',
            'billing_notes' => '',
            'patient_instructions' => ''
        ];

        try {
            foreach ($sections as $section => $default) {
                $sectionLabel = strtoupper($section);
                $pattern = "/{$sectionLabel}:\s*(.*?)(?=\n[A-Z_]+:|$)/s";
                
                if (preg_match($pattern, $response, $matches)) {
                    $sections[$section] = trim($matches[1]);
                }
            }

            // If no structured sections found, use the full response as overview
            if (empty(array_filter($sections))) {
                $sections['visit_overview'] = $response;
            }

        } catch (\Exception $e) {
            error_log("Error parsing appointment summary: " . $e->getMessage());
            $sections['visit_overview'] = $response;
        }

        return $sections;
    }

    private function parseBillingCodes(string $content): array
    {
        $codes = [
            'cpt_codes' => [],
            'icd10_codes' => [],
            'service_level' => '',
            'modifier_codes' => []
        ];

        // Extract CPT codes (5 digits)
        if (preg_match_all('/(?:CPT|cpt)\s*:?\s*(\d{5})/i', $content, $cptMatches)) {
            $codes['cpt_codes'] = array_unique($cptMatches[1]);
        }

        // Extract ICD-10 codes (letter followed by digits and dots)
        if (preg_match_all('/(?:ICD|icd)[-\s]*10\s*:?\s*([A-Z]\d{2}(?:\.\d{1,3})?)/i', $content, $icdMatches)) {
            $codes['icd10_codes'] = array_unique($icdMatches[1]);
        }

        // Extract service level (99xxx codes)
        if (preg_match('/99(\d{3})/i', $content, $serviceMatches)) {
            $codes['service_level'] = '99' . $serviceMatches[1];
        }

        return $codes;
    }

    private function calculateAge(?string $dateOfBirth): int
    {
        if (!$dateOfBirth) return 0;
        
        $birthDate = new \DateTime($dateOfBirth);
        $today = new \DateTime();
        return $birthDate->diff($today)->y;
    }

    private function calculateDuration(array $appointmentData): int
    {
        if (!isset($appointmentData['start_time'], $appointmentData['end_time'])) {
            return 30; // Default duration
        }

        $start = new \DateTime($appointmentData['start_time']);
        $end = new \DateTime($appointmentData['end_time']);
        return $start->diff($end)->i; // minutes
    }

    private function storeSummary(int $appointmentId, array $summary, array $aiResponse): ?int
    {
        try {
            // Create appointment_summaries table if needed
            $this->createSummaryTableIfNotExists();

            $summaryData = [
                'appointment_id' => $appointmentId,
                'summary_type' => 'standard',
                'structured_summary' => json_encode($summary),
                'ai_model_used' => $aiResponse['model_used'],
                'tokens_used' => $aiResponse['tokens_used'] ?? 0,
                'response_time_ms' => $aiResponse['response_time'] ?? 0,
                'created_at' => date('Y-m-d H:i:s')
            ];

            return $this->db->insert('appointment_summaries', $summaryData);

        } catch (\Exception $e) {
            error_log("Error storing summary: " . $e->getMessage());
            return null;
        }
    }

    private function createSummaryTableIfNotExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS appointment_summaries (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            appointment_id INT UNSIGNED NOT NULL,
            summary_type ENUM('standard', 'soap', 'billing', 'patient') DEFAULT 'standard',
            structured_summary JSON,
            ai_model_used VARCHAR(50),
            tokens_used INT UNSIGNED DEFAULT 0,
            response_time_ms DECIMAL(10,2) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
            INDEX idx_appointment (appointment_id),
            INDEX idx_type (summary_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->query($sql);
    }

    private function getFallbackSummary(array $appointmentData): array
    {
        return [
            'success' => true,
            'summary' => [
                'visit_overview' => 'Appointment completed on ' . ($appointmentData['appointment_date'] ?? date('Y-m-d')),
                'chief_complaint' => $appointmentData['notes'] ?? 'See appointment notes',
                'clinical_findings' => $appointmentData['diagnosis'] ?? 'Clinical assessment completed',
                'treatment_provided' => $appointmentData['treatment_notes'] ?? 'Treatment provided as indicated',
                'follow_up_plan' => ($appointmentData['follow_up_required'] ?? false) ? 'Follow-up scheduled' : 'No follow-up required',
                'provider_notes' => 'Standard care provided',
                'billing_notes' => 'Documentation completed',
                'patient_instructions' => 'Follow provider recommendations'
            ],
            'source' => 'fallback',
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    private function generateFallbackSOAP(array $soapData): string
    {
        return "SOAP NOTE\n\n" .
               "SUBJECTIVE:\n" . ($soapData['subjective'] ?? 'Patient presented with chief complaint.') . "\n\n" .
               "OBJECTIVE:\n" . ($soapData['objective'] ?? 'Physical examination completed.') . "\n\n" .
               "ASSESSMENT:\n" . ($soapData['assessment'] ?? 'Clinical assessment documented.') . "\n\n" .
               "PLAN:\n" . ($soapData['plan'] ?? 'Treatment plan established.');
    }
}
