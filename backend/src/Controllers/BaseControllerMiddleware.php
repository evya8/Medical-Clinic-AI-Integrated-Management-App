<?php

namespace MedicalClinic\Controllers;

use MedicalClinic\Utils\Database;
use MedicalClinic\Models\User;

abstract class BaseControllerMiddleware
{
    protected Database $db;
    protected array $request;

    public function __construct(array $request = [])
    {
        $this->db = Database::getInstance();
        $this->request = $request;
    }

    /**
     * Get authenticated user from middleware
     */
    protected function getUser(): ?User
    {
        return $this->request['auth_user'] ?? null;
    }

    /**
     * Get validated input from middleware
     */
    protected function getInput(): array
    {
        return $this->request['validated_input'] ?? 
               json_decode(file_get_contents('php://input'), true) ?? 
               array_merge($_GET, $_POST);
    }

    /**
     * Get route parameters
     */
    protected function getParams(): array
    {
        return $this->request['params'] ?? [];
    }

    /**
     * Get user ID from authenticated user
     */
    protected function getUserId(): int
    {
        $user = $this->getUser();
        return $user ? $user->id : 0;
    }

    /**
     * Get user role from authenticated user
     */
    protected function getUserRole(): string
    {
        return $this->request['user_role'] ?? $this->getUser()?->role ?? '';
    }

    /**
     * Check if current user has specific role
     */
    protected function hasRole(string $role): bool
    {
        return $this->getUserRole() === $role;
    }

    /**
     * Check if current user is admin
     */
    protected function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if current user is doctor
     */
    protected function isDoctor(): bool
    {
        return $this->hasRole('doctor');
    }

    /**
     * Send success response
     */
    protected function success(mixed $data = null, string $message = 'Success'): never
    {
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    /**
     * Send error response
     */
    protected function error(string $message, int $statusCode = 400, mixed $details = null): never
    {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        if ($details !== null) {
            $response['details'] = $details;
        }

        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    /**
     * Send paginated response
     */
    protected function paginated(array $data, int $total, int $page = 1, int $perPage = 10): never
    {
        $totalPages = ceil($total / $perPage);
        
        $this->success([
            'items' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ]
        ]);
    }

    /**
     * Legacy methods for backward compatibility
     * These can be removed after all controllers are updated
     */
    protected function requireAuth(): array
    {
        $user = $this->getUser();
        if (!$user) {
            $this->error('Authentication required', 401);
        }
        return $user->toArray();
    }

    protected function requireRole(array $allowedRoles): array
    {
        $user = $this->getUser();
        if (!$user || !$user->hasAnyRole($allowedRoles)) {
            $this->error('Insufficient permissions', 403);
        }
        return $user->toArray();
    }

    protected function validateRequired(array $required, array $input): void
    {
        $missing = [];
        
        foreach ($required as $field) {
            if (!isset($input[$field]) || trim($input[$field]) === '') {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            $this->error('Missing required fields: ' . implode(', ', $missing), 422);
        }
    }

    protected function sanitizeString(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}
