<?php

namespace MedicalClinic\Middleware;

use MedicalClinic\Utils\Database;

abstract class BaseMiddleware implements MiddlewareInterface
{
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Send a JSON response and exit
     */
    protected function jsonResponse(array $data, int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Send an error response and exit
     */
    protected function errorResponse(string $message, int $statusCode = 400, array $details = []): never
    {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        if (!empty($details)) {
            $response['details'] = $details;
        }

        $this->jsonResponse($response, $statusCode);
    }

    /**
     * Send a success response and exit
     */
    protected function successResponse(mixed $data = null, string $message = 'Success'): never
    {
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        $this->jsonResponse($response, 200);
    }

    /**
     * Get input data from request body, GET, and POST
     */
    protected function getInput(): array
    {
        $input = json_decode(file_get_contents('php://input'), true);
        return array_merge($_GET, $_POST, $input ?? []);
    }

    /**
     * Get request headers (CLI-compatible)
     */
    protected function getHeaders(): array
    {
        // getallheaders() doesn't exist in CLI, so provide fallback
        if (function_exists('getallheaders')) {
            return getallheaders() ?: [];
        }
        
        // Fallback for CLI and environments without getallheaders()
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$header] = $value;
            }
        }
        return $headers;
    }

    /**
     * Get specific header value
     */
    protected function getHeader(string $name, string $default = ''): string
    {
        $headers = $this->getHeaders();
        
        // Try case-insensitive header lookup
        foreach ($headers as $key => $value) {
            if (strtolower($key) === strtolower($name)) {
                return $value;
            }
        }
        
        return $default;
    }

    /**
     * Validate required fields in input
     */
    protected function validateRequired(array $required, array $input): void
    {
        $missing = [];
        
        foreach ($required as $field) {
            if (!isset($input[$field]) || trim($input[$field]) === '') {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            $this->errorResponse(
                'Missing required fields: ' . implode(', ', $missing),
                422,
                ['missing_fields' => $missing]
            );
        }
    }

    /**
     * Sanitize string input
     */
    protected function sanitizeString(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate email format
     */
    protected function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Log middleware activity (for debugging)
     */
    protected function log(string $message, array $context = []): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'middleware' => static::class,
            'message' => $message,
            'context' => $context
        ];
        
        // For now, just log to error_log. In production, use proper logging
        error_log('MIDDLEWARE: ' . json_encode($logEntry));
    }
}
