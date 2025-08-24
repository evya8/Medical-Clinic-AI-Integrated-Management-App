<?php

namespace MedicalClinic\Controllers;

use MedicalClinic\Utils\Database;
use MedicalClinic\Utils\JWTAuth;

abstract class BaseController
{
    protected $db;
    protected $input;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->input = $this->getInput();
    }

    protected function getInput(): array
    {
        $input = json_decode(file_get_contents('php://input'), true);
        return $input ?? [];
    }

    protected function respond(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    protected function success(mixed $data = null, string $message = 'Success'): void
    {
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        $this->respond($response);
    }

    protected function error(string $message, int $statusCode = 400, mixed $details = null): void
    {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        if ($details !== null) {
            $response['details'] = $details;
        }

        $this->respond($response, $statusCode);
    }

    protected function validateRequired(array $fields, array $data): void
    {
        $missing = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            $this->error(
                'Missing required fields: ' . implode(', ', $missing),
                400,
                ['missing_fields' => $missing]
            );
        }
    }

    protected function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    protected function validatePhone(string $phone): bool
    {
        // Basic phone validation - adjust regex as needed
        return preg_match('/^[\+]?[1-9][\d]{0,15}$/', preg_replace('/[^\d\+]/', '', $phone));
    }

    protected function sanitizeString(string $str): string
    {
        return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
    }

    protected function sanitizeInt(mixed $value): int
    {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    protected function getCurrentUser(): ?array
    {
        $token = JWTAuth::getTokenFromHeader();
        
        if (!$token) {
            return null;
        }

        return JWTAuth::validateToken($token);
    }

    protected function requireAuth(): array
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            $this->error('Authentication required', 401);
        }
        return $user;
    }

    protected function requireRole(array $allowedRoles): array
    {
        $user = $this->requireAuth();
        if (!in_array($user['role'], $allowedRoles)) {
            $this->error('Insufficient permissions', 403);
        }
        return $user;
    }
}
