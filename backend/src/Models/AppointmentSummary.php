<?php

namespace MedicalClinic\Models;

class AppointmentSummary extends BaseModel
{
    protected static string $table = 'appointment_summaries';

    // Relationships
    public function appointment(): ?Appointment
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    // Static methods
    public static function findByAppointmentId(int $appointmentId): ?static
    {
        $results = static::where('appointment_id', $appointmentId);
        return $results[0] ?? null;
    }

    public static function getRecentSummaries(int $limit = 20): array
    {
        static::initializeDatabase();
        $results = static::$db->fetchAll(
            "SELECT s.*, 
                    a.appointment_date, a.appointment_type,
                    p.first_name as patient_first_name, p.last_name as patient_last_name,
                    u.first_name as doctor_first_name, u.last_name as doctor_last_name
             FROM appointment_summaries s
             JOIN appointments a ON s.appointment_id = a.id
             JOIN patients p ON a.patient_id = p.id
             JOIN doctors d ON a.doctor_id = d.id
             JOIN users u ON d.user_id = u.id
             ORDER BY s.created_at DESC
             LIMIT :limit",
            ['limit' => $limit]
        );
        return array_map(fn($row) => new static($row, true), $results);
    }

    public static function getBySummaryType(string $summaryType): array
    {
        return static::where('summary_type', $summaryType);
    }

    public static function getHighConfidenceSummaries(int $minWordCount = 100): array
    {
        return static::where('word_count', $minWordCount, '>=');
    }

    // Summary type helpers
    public function isSOAPNote(): bool
    {
        return $this->summary_type === 'soap';
    }

    public function isBillingSummary(): bool
    {
        return $this->summary_type === 'billing';
    }

    public function isPatientSummary(): bool
    {
        return $this->summary_type === 'patient';
    }

    // Content methods
    public function getWordCount(): int
    {
        return $this->word_count ?? 0;
    }

    public function getCharacterCount(): int
    {
        return strlen($this->raw_ai_response ?? '');
    }

    public function getReadingTime(): int
    {
        // Assuming 200 words per minute average reading speed
        return max(1, ceil($this->getWordCount() / 200));
    }

    public function hasSOAPNotes(): bool
    {
        $structured = $this->getStructuredSummary();
        return !empty($structured['soap_notes'] ?? '');
    }

    public function hasBillingCodes(): bool
    {
        $structured = $this->getStructuredSummary();
        return !empty($structured['billing_codes'] ?? '');
    }

    public function hasRecommendations(): bool
    {
        $structured = $this->getStructuredSummary();
        return !empty($structured['recommendations'] ?? '');
    }

    // Helper method to get structured summary data
    public function getStructuredSummary(): array
    {
        if (!$this->structured_summary) {
            return [];
        }
        
        $decoded = json_decode($this->structured_summary, true);
        return is_array($decoded) ? $decoded : [];
    }

    // Quality assessment based on word count and response time
    public function isHighQuality(): bool
    {
        return $this->word_count >= 100 && $this->response_time_ms < 5000;
    }

    public function isMediumQuality(): bool
    {
        return $this->word_count >= 50 && $this->word_count < 100;
    }

    public function isLowQuality(): bool
    {
        return $this->word_count < 50;
    }

    public function getQualityLevel(): string
    {
        if ($this->isHighQuality()) return 'high';
        if ($this->isMediumQuality()) return 'medium';
        return 'low';
    }

    public function getQualityColor(): string
    {
        return match($this->getQualityLevel()) {
            'high' => 'green',
            'medium' => 'yellow',
            'low' => 'red',
            default => 'gray'
        };
    }

    // Content parsing and extraction
    public function getBillingCodesArray(): array
    {
        $structured = $this->getStructuredSummary();
        return $structured['billing_codes'] ?? [];
    }

    public function getRecommendationsArray(): array
    {
        $structured = $this->getStructuredSummary();
        return $structured['recommendations'] ?? [];
    }

    public function getSOAPComponents(): array
    {
        $structured = $this->getStructuredSummary();
        if (!isset($structured['soap_notes'])) {
            return [];
        }
        
        $soapNotes = $structured['soap_notes'];
        
        // Default SOAP structure
        $components = [
            'subjective' => '',
            'objective' => '',
            'assessment' => '',
            'plan' => ''
        ];
        
        // If soap_notes is already structured as an array
        if (is_array($soapNotes)) {
            return array_merge($components, $soapNotes);
        }
        
        // If soap_notes is a string, try to parse it
        if (is_string($soapNotes)) {
            $lines = explode("\n", $soapNotes);
            $currentSection = '';
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                if (stripos($line, 'subjective') === 0 || stripos($line, 's:') === 0) {
                    $currentSection = 'subjective';
                    continue;
                } elseif (stripos($line, 'objective') === 0 || stripos($line, 'o:') === 0) {
                    $currentSection = 'objective';
                    continue;
                } elseif (stripos($line, 'assessment') === 0 || stripos($line, 'a:') === 0) {
                    $currentSection = 'assessment';
                    continue;
                } elseif (stripos($line, 'plan') === 0 || stripos($line, 'p:') === 0) {
                    $currentSection = 'plan';
                    continue;
                }
                
                if ($currentSection && isset($components[$currentSection])) {
                    $components[$currentSection] .= ($components[$currentSection] ? "\n" : "") . $line;
                }
            }
        }
        
        return $components;
    }

    // Display methods
    public function getDisplayInfo(): array
    {
        $appointment = $this->appointment();
        $patient = $appointment?->patient();
        $doctor = $appointment?->doctor();
        
        return [
            'id' => $this->id,
            'appointment_id' => $this->appointment_id,
            'summary_type' => $this->summary_type,
            'patient_name' => $patient?->getFullName() ?? 'Unknown Patient',
            'doctor_name' => $doctor?->getDisplayName() ?? 'Unknown Doctor',
            'appointment_date' => $appointment?->appointment_date,
            'quality_level' => $this->getQualityLevel(),
            'quality_color' => $this->getQualityColor(),
            'word_count' => $this->getWordCount(),
            'reading_time' => $this->getReadingTime(),
            'response_time_ms' => $this->response_time_ms,
            'ai_model_used' => $this->ai_model_used,
            'tokens_used' => $this->tokens_used,
            'has_soap_notes' => $this->hasSOAPNotes(),
            'has_billing_codes' => $this->hasBillingCodes(),
            'has_recommendations' => $this->hasRecommendations(),
            'created_at' => $this->created_at
        ];
    }

    public function getFormattedSummary(): string
    {
        // Return formatted summary with proper line breaks and structure
        return nl2br(htmlspecialchars($this->raw_ai_response ?? ''));
    }

    public function getExcerpt(int $wordLimit = 50): string
    {
        $text = $this->raw_ai_response ?? '';
        $words = explode(' ', strip_tags($text));
        if (count($words) <= $wordLimit) {
            return strip_tags($text);
        }
        
        return implode(' ', array_slice($words, 0, $wordLimit)) . '...';
    }

    // Export methods
    public function exportAsPDF(): array
    {
        // Return data structure for PDF generation
        $appointment = $this->appointment();
        $patient = $appointment?->patient();
        $doctor = $appointment?->doctor();
        
        return [
            'title' => ucfirst($this->summary_type) . ' Summary',
            'patient' => $patient?->getDisplayInfo(),
            'doctor' => $doctor?->getDisplayInfo(),
            'appointment' => $appointment?->getDisplayInfo(),
            'summary_content' => $this->raw_ai_response,
            'soap_notes' => $this->getSOAPComponents(),
            'billing_codes' => $this->getBillingCodesArray(),
            'recommendations' => $this->getRecommendationsArray(),
            'ai_model_used' => $this->ai_model_used,
            'tokens_used' => $this->tokens_used,
            'response_time_ms' => $this->response_time_ms,
            'generated_at' => $this->created_at
        ];
    }

    public function exportAsText(): string
    {
        $data = $this->exportAsPDF();
        $text = "=== " . strtoupper($data['title']) . " ===\n\n";
        
        if ($data['patient']) {
            $text .= "Patient: " . $data['patient']['full_name'] . "\n";
        }
        if ($data['doctor']) {
            $text .= "Doctor: " . $data['doctor']['name'] . "\n";
        }
        if ($data['appointment']) {
            $text .= "Date: " . $data['appointment']['date'] . " " . $data['appointment']['time'] . "\n";
        }
        
        $text .= "\nSummary:\n" . $this->raw_ai_response . "\n";
        
        if ($this->hasSOAPNotes()) {
            $soap = $this->getSOAPComponents();
            $text .= "\n=== SOAP NOTES ===\n";
            foreach ($soap as $section => $content) {
                if ($content) {
                    $text .= strtoupper($section) . ":\n" . $content . "\n\n";
                }
            }
        }
        
        $text .= "\nGenerated at: " . $this->created_at . "\n";
        $text .= "Confidence Score: " . ($this->confidence_score * 100) . "%\n";
        
        return $text;
    }

    // Statistics
    public static function getSummaryStats(): array
    {
        static::initializeDatabase();
        $stats = static::$db->fetch(
            "SELECT 
                COUNT(*) as total,
                AVG(word_count) as avg_word_count,
                AVG(response_time_ms) as avg_response_time,
                SUM(CASE WHEN summary_type = 'soap' THEN 1 ELSE 0 END) as soap_count,
                SUM(CASE WHEN summary_type = 'billing' THEN 1 ELSE 0 END) as billing_count,
                SUM(CASE WHEN summary_type = 'patient' THEN 1 ELSE 0 END) as patient_count,
                SUM(CASE WHEN word_count >= 100 THEN 1 ELSE 0 END) as high_quality_count
             FROM appointment_summaries 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            []
        );

        return [
            'total_summaries' => (int) $stats['total'],
            'average_word_count' => round((float) $stats['avg_word_count'], 1),
            'average_response_time_ms' => round((float) $stats['avg_response_time'], 1),
            'soap_summaries' => (int) $stats['soap_count'],
            'billing_summaries' => (int) $stats['billing_count'],
            'patient_summaries' => (int) $stats['patient_count'],
            'high_quality_summaries' => (int) $stats['high_quality_count'],
            'high_quality_rate' => $stats['total'] > 0 ? 
                round(($stats['high_quality_count'] / $stats['total']) * 100, 1) : 0
        ];
    }
}
