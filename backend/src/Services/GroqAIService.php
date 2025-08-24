<?php

namespace MedicalClinic\Services;

class GroqAIService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.groq.com/openai/v1/chat/completions';
    private array $modelConfig;

    public function __construct()
    {
        // Load environment variables if not already loaded
        if (empty($_ENV['GROQ_API_KEY']) && file_exists(__DIR__ . '/../../.env')) {
            $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
            $dotenv->load();
        }
        
        $config = require __DIR__ . '/../../config/app.php';
        $this->apiKey = $_ENV['GROQ_API_KEY'] ?? '';
        
        // Model configuration for different tasks
        $this->modelConfig = [
            'dashboard' => [
                'model' => 'llama3-8b-8192',
                'temperature' => 0.3,
                'max_tokens' => 1000
            ],
            'triage' => [
                'model' => 'llama3-70b-8192', 
                'temperature' => 0.1, // Lower for medical accuracy
                'max_tokens' => 1500
            ],
            'summary' => [
                'model' => 'mixtral-8x7b-32768',
                'temperature' => 0.2,
                'max_tokens' => 1200
            ],
            'alerts' => [
                'model' => 'llama3-8b-8192',
                'temperature' => 0.2,
                'max_tokens' => 800
            ]
        ];
    }

    public function generateResponse(string $prompt, string $taskType = 'dashboard', array $options = []): array
    {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'message' => 'Groq API key not configured',
                'content' => null
            ];
        }

        $config = $this->modelConfig[$taskType] ?? $this->modelConfig['dashboard'];
        
        // Merge with custom options
        $config = array_merge($config, $options);

        $messages = [
            [
                'role' => 'system',
                'content' => $this->getSystemPrompt($taskType)
            ],
            [
                'role' => 'user', 
                'content' => $prompt
            ]
        ];

        $payload = [
            'model' => $config['model'],
            'messages' => $messages,
            'temperature' => $config['temperature'],
            'max_tokens' => $config['max_tokens'],
            'top_p' => 0.9,
            'stream' => false
        ];

        try {
            $response = $this->makeApiCall($payload);
            
            if ($response['success']) {
                return [
                    'success' => true,
                    'content' => $response['data']['choices'][0]['message']['content'],
                    'model_used' => $config['model'],
                    'tokens_used' => $response['data']['usage']['total_tokens'] ?? 0,
                    'response_time' => $response['response_time']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $response['error'],
                    'content' => null
                ];
            }

        } catch (\Exception $e) {
            error_log("Groq AI Service Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'AI service temporarily unavailable',
                'content' => null
            ];
        }
    }

    private function getSystemPrompt(string $taskType): string
    {
        $prompts = [
            'dashboard' => "You are an AI assistant for medical clinic staff. Your role is to analyze daily clinic operations and provide clear, actionable briefings that help medical professionals prioritize their work and manage resources effectively. Focus on patient safety, operational efficiency, and clear communication. Always maintain HIPAA compliance by using only aggregate data and never mentioning specific patient names in summaries.",
            
            'triage' => "You are a clinical decision support AI assistant for medical professionals. Your role is to help qualified medical staff analyze patient cases and make informed triage decisions. Provide evidence-based suggestions while emphasizing that all clinical decisions must be made by licensed medical professionals. Focus on patient safety, clinical accuracy, and supporting existing medical protocols. Never provide definitive diagnoses - only supportive analysis.",
            
            'summary' => "You are a medical documentation AI assistant that helps healthcare providers create comprehensive, accurate appointment summaries. Focus on clinical accuracy, proper medical terminology, and structured documentation that supports continuity of care and billing compliance. Maintain professional medical standards in all documentation.",
            
            'alerts' => "You are an AI assistant that helps medical staff identify and prioritize important follow-up actions and care opportunities. Focus on patient safety, preventive care, and operational efficiency. Provide clear, actionable alerts that help prevent care gaps and improve patient outcomes."
        ];

        return $prompts[$taskType] ?? $prompts['dashboard'];
    }

    private function makeApiCall(array $payload): array
    {
        $startTime = microtime(true);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $responseTime = round((microtime(true) - $startTime) * 1000, 2); // milliseconds
        
        if (curl_error($ch)) {
            curl_close($ch);
            throw new \Exception('Curl error: ' . curl_error($ch));
        }
        
        curl_close($ch);

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            return [
                'success' => false,
                'error' => $errorData['error']['message'] ?? 'Unknown API error',
                'response_time' => $responseTime
            ];
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response from API');
        }

        return [
            'success' => true,
            'data' => $data,
            'response_time' => $responseTime
        ];
    }

    public function testConnection(): array
    {
        $testPrompt = "Respond with 'Groq AI service is working correctly for medical clinic management.'";
        
        $result = $this->generateResponse($testPrompt, 'dashboard', ['max_tokens' => 50]);
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Groq AI service connection successful',
                'model_tested' => 'llama3-8b-8192',
                'response_time' => $result['response_time'] ?? 0
            ];
        } else {
            return [
                'success' => false,
                'message' => $result['message']
            ];
        }
    }

    public function getModelInfo(): array
    {
        return [
            'available_models' => [
                'llama3-8b-8192' => 'Llama 3 8B - Fast, efficient for general tasks',
                'llama3-70b-8192' => 'Llama 3 70B - High accuracy for critical decisions', 
                'mixtral-8x7b-32768' => 'Mixtral 8x7B - Excellent for structured text generation'
            ],
            'task_assignments' => $this->modelConfig
        ];
    }
}
